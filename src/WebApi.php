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
        'web_url' => 'http://www.youhosting.com',
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
            'base_url' => $this->options['web_url'],
            'defaults' => array(
                'cookies' => $this->cookiejar,
                'allow_redirects' => false,
                'connect_timeout' => 20,
                'timeout' => 30,
            ),
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
        ));
        return $this->sendRequest($request);
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
            $url = $url . "?" . http_build_query($data);
        }
        $request = $this->webclient->createRequest('GET', $url);
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

    public function getAccount($id)
    {
        $response = $this->get('/en/client-account/edit/id/'.$id);

        $content = trim($this->getBetween((string) $response, '<h2 class="head icon-4"><strong>Account Information</strong></h2>', '<div id="foot">'));

        $domain = $this->getBetween($content, "http://redirect.main-hosting.com/", '</td>');
        $domain = $this->getBetween($domain, '">', '</a>');

        $type = $this->getBetween($content, '<td>Hosting type</td>', '</tr>');
        $type = trim($this->getBetween($type, '<td>', '</td>'));

        if($type == "Free"){
            $period = "none";
        } else {
            $period = $this->getBetween($content, '<select name="billing_cycle" id="billing_cycle">', '</select>');
            $period = $this->getBetweenReverse($period, 'value="', '" selected=');
        }

        return new Account(array(
            'id' => (int)$this->getBetween($content, "<td>#", "</td>"),
            'client_id' => (int)$this->getBetween($content, '/en/client/view/id/', '"'),
            'plan_id' => (int)$this->getBetween($content, '/en/client-account/change-hosting-plan/id/', '#upgrade'),
            'domain' => $domain,
            'username' => 'u'.$this->getBetween($content, '<td>u', '</td>'),
            'status' => $this->getBetweenReverse(
                $this->getBetween($content, '<select name="status" id="status">', '</select>'), 'value="', '" selected="selected"'
            ),
            'period' => $period,
            'created_at' => $this->getBetween(
                $this->getBetween($content, '<td>Created At</td', '</tr>'), '<td>', '</td>'
            ),
        ));
    }

    /**
     * Get a client ID from an e-mail address
     *
     * @param $email string
     * @return string
     */
    public function searchClientId($email)
    {
        $response = $this->get('/en/client/manage', array(
            'email' => $email,
            'submit' => "Search",
        ));

        return trim($this->getBetween((string) $response->getBody(), '/en/client/view/id/', '"'));
    }

    public function searchAccountId($domain)
    {
        $response = $this->get('/en/client-account/manage', array(
            'domain' => $domain,
            'submit' => "Search",
        ));

        return trim($this->getBetween((string) $response->getBody(), '/en/client-account/edit/id/', '"'));
    }

    /**
     * Create a new client on YouHosting
     *
     * @param Client $client a container for client details
     * @param string $password
     * @return Client
     * @throws YouHostingException
     */
    public function createClient(Client $client, $password)
    {
        $data = $client->toArray();
        $data['password_confirm'] = $data['password'] = $password;
        $data['send_email'] = 0;
        $data['submit'] = 'Save';

        $response = $this->post('/en/client/add', $data);

        if($response->getStatusCode() == 200){
            $error = $this->getBetween((string) $response->getBody(), '<ul class="errors">', '</ul>');
            $error = $this->getBetween($error, '<li>', '</li>');
            throw new YouHostingException("Unable to create profile: ".$error);
        }

        $response = $this->get($response->getHeader('Location'));

        $client->id = $this->getBetweenReverse((string) $response->getBody(), '/en/client/view/id/', '">'.$client->email);
        return $client;
    }

    /**
     * Get a new captcha (SVIP API only)
     * @return array containing a numeric id and a url to the captcha iamge
     * @throws YouHostingException
     */
    public function getCaptcha()
    {
        throw new YouHostingException("Captcha verification is only supported by the REST API");
    }

    /**
     * Verify the captcha result (SVIP API only)
     *
     * @param int $id the captcha id
     * @param string $solution the solution of the captcha submitted by the user
     * @return bool
     * @throws YouHostingException
     */
    public function checkCaptcha($id, $solution)
    {
        throw new YouHostingException("Captcha verification is only supported by the REST API");
    }

    public function listClients($page)
    {
        $response = $this->get('/en/client/index/page/'.$page);
        $table = $this->getBetween((string) $response->getBody(), "<tbody>", '</tbody>');
        $rows = explode("</tr>", $table);

        $clients = array();

        foreach($rows as $row){
            $id = $this->getBetween($row, '/en/client/view/id/', '">');
            $clients[] = $this->getClient($id);
        }

        return array(
            'pages' => null,
            'page' => $page,
            'per_page' => count($clients),
            'total' => null,
            'list' => $clients
        );
    }

    public function getClientLoginUrl($id)
    {
        $response = $this->get('/en/jump-to/client-area/id/'.$id);
        if($response->getStatusCode() != 302){
            throw new YouHostingException("Wrong response status code, expected 302 but received ".$response->getStatusCode());
        }

        return $response->getHeader('Location');
    }

    public function getAccountLoginUrl($id)
    {
        $response = $this->get('/en/jump-to/client-account/id/'.$id);
        if($response->getStatusCode() != 302){
            throw new YouHostingException("Wrong response status code, expected 302 but received ".$response->getStatusCode());
        }

        return $response->getHeader('Location');
    }

    public function checkDomain($type, $domain, $subdomain)
    {
        if($type == 'subdomain'){
            $domain = $subdomain . "." . $domain;
        }

        $response = $this->get('/en/client-account/manage', array(
            'domain' => $domain,
            'submit' => 'Search',
        ));

        if(strpos($response->getBody(), "cPanel") !== false){
            return true;
        } else {
            throw new YouHostingException("Domain ".$domain." is already registered");
        }
    }
}