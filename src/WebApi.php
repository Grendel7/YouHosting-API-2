<?php

namespace YouHosting;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Class WebApi
 *
 * Get and set data by crawling the YouHosting website
 *
 * @package YouHosting
 */
class WebApi
{
    protected $options = array(
        'base_url' => 'http://www.youhosting.com',
        'cookie_type' => 'array',
    );

    protected $username;
    protected $password;
    private $cookiejar;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $webclient;

    /**
     * Create a new instance of the YouHosting API
     *
     * @param string $username The username of your YouHosting reseller account
     * @param string $password The password of your YouHosting reseller account
     * @param array $options An array of options
     * @throws YouHostingException
     */
    public function __construct($username, $password, $options = array())
    {
        $this->options = array_replace_recursive($this->options, $options);
        $this->setCookieJar();
        $this->webclient = new \GuzzleHttp\Client(array(
            'base_url' => $this->options['base_url'],
        ));
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get the relevant cookie jar
     *
     * @return CookieJar|FileCookieJar|SessionCookieJar
     * @throws YouHostingException
     */
    private function setCookieJar()
    {
        switch($this->options['cookie_type']){
            case 'array':
                $this->cookiejar = new CookieJar();
                break;
            case 'file':
                $this->cookiejar = new FileCookieJar($this->options['cookie_file_name']);
                break;
            case 'session':
                $this->cookiejar = new SessionCookieJar($this->options['cookie_session_key']);
                break;
            default:
                throw new YouHostingException("You didn't specify a valid cookie type!");
        }
    }

    /**
     * Get the substring of $content between the first occurrence of $start until $end
     *
     * @param string $content
     * @param string $start
     * @param string $end
     * @return string
     */
    protected function getBetween($content, $start, $end)
    {
        $startIndex = strpos($content, $start) + strlen($start);
        $length = strpos($content, $end, $startIndex) - $startIndex;
        return substr($content, $startIndex, $length);
    }

    /**
     * Get the substring of $content between the last occurrence of $end until $start
     *
     * @param $content
     * @param $start
     * @param $end
     * @return string
     */
    protected function getBetweenReverse($content, $start, $end)
    {
        $endIndex = strrpos($content, $end);
        $startIndex = strrpos($content, $start, -1 * (strlen($content) - $endIndex)) + strlen($start);
        return substr($content, $startIndex, $endIndex - $startIndex);
    }

    /**
     * Login to YouHosting's website
     *
     * @throws YouHostingException
     */
    protected function webLogin()
    {
        $response = $this->webclient->get("/en/auth", array(
            'cookies' => $this->cookiejar,
        ));

        $csrf = $this->getBetween((string)$response->getBody(), "document.write('", "');");
        $csrf = str_replace("' + '", "", $csrf);
        $name = $this->getBetween($csrf, 'name="', '"');
        $value = $this->getBetween($csrf, 'value="', '"');

        $response = $this->webclient->post("/en/auth", array(
            'body' => array(
                'submit' => 'Login',
                'email' => $this->username,
                'password' => $this->password,
                $name => $value,
            ),
            'cookies' => $this->cookiejar,
            'allow_redirects' => false,
        ));

        if($response->getStatusCode() == 200){
            throw new YouHostingException($this->getBetween((string) $response->getBody(), '<div class="notification info">', '</div>'));
        }
    }

    /**
     * Send a POST request to youhosting.com
     *
     * @param string $url the relative url
     * @param array $data an array of post data
     * @return Response
     * @throws YouHostingException
     */
    protected function post($url, $data)
    {
        $request = $this->webclient->createRequest('POST', $url, array(
            'body' => $data,
            'cookies' => $this->cookiejar,
            'allow_redirects' => false,
        ));

        return $this->sendRequest($request);
    }

    /**
     * Send the request away
     *
     * @param Request $request
     * @return Response
     * @throws YouHostingException
     */
    private function sendRequest($request)
    {
        $response = $this->webclient->send($request);
        if(!$this->isLoggedIn($response)) {
            $this->webLogin();
            $response = $this->webclient->send($request);
        }

        if($response->getStatusCode() == 500){
            throw new YouHostingException("Unknown YouHosting Error");
        }

        return $response;
    }

    /**
     * Send a GET request to youhosting.com
     *
     * @param string $url the relative url
     * @param array $data any data which will be passed as query string
     * @return Response
     * @throws YouHostingException
     */
    protected function get($url, $data = array())
    {
        if(!empty($data)){
            $url .= http_build_query($data);
        }

        $request = $this->webclient->createRequest('GET', $url, array(
            'cookies' => $this->cookiejar,
            'allow_redirects' => false,
        ));

        return $this->sendRequest($request);
    }

    /**
     * Check if the request failed because the user was not logged in
     *
     * @param ResponseInterface $response
     * @return bool
     */
    private function isLoggedIn(ResponseInterface $response)
    {
        if($response->getStatusCode() != 302){
            return true;
        }

        $location = $response->getHeader('Location');
        if(strpos($location, "/en/auth") !== false){
            return false;
        }

        return true;
    }

    /**
     * Get a client from YouHosting
     *
     * @param $id
     * @return Client
     */
    public function getClient($id)
    {
        $response = $this->get('/en/client/edit/id/'.$id);

        $content = trim($this->getBetween((string) $response->getBody(), '<table class="wide">', "</table>"));

        $client = new Client(array(
            'id' => $id,
            'email' => $this->getBetween($content, '<input type="text" name="email" id="email" value="', '">'),
            'first_name' => $this->getBetween($content, '<input type="text" name="first_name" id="first_name" value="', '">'),
            'last_name' => $this->getBetween($content, '<input type="text" name="last_name" id="last_name" value="', '">'),
            'company' => $this->getBetween($content, '<input type="text" name="company" id="company" value="', '">'),
            'address_1' => $this->getBetween($content, '<input type="text" name="address_1" id="address_1" value="', '">'),
            'address_2' => $this->getBetween($content, '<input type="text" name="address_2" id="address_2" value="', '">'),
            'country' => $this->getBetweenReverse($content, '<option value="', '" selected="selected">'),
            'city' => $this->getBetween($content, '<input type="text" name="city" id="city" value="', '">'),
            'state' => $this->getBetween($content, '<input type="text" name="state" id="state" value="', '">'),
            'zip' => $this->getBetween($content, '<input type="text" name="zip" id="zip" value="', '">'),
            'phone_cc' => $this->getBetween($content, '<input type="text" name="phone_cc" id="phone_cc" value="', '">'),
            'phone' => $this->getBetween($content, '<input type="text" name="phone" id="phone" value="', '">'),
        ));

        return $client;
    }
}