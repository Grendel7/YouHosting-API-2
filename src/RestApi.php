<?php

namespace YouHosting;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Class RestApi
 *
 * Improve many methods using the official Rest API where available
 *
 * @package YouHosting
 */
class RestApi extends WebApi
{
    protected $apiKey;
    protected $apiClient;
    protected $apiOptions = array(
        'api_url' => 'https://rest.main-hosting.com',
        'verify_ssl' => false,
    );

    /**
     * Setup a new RestApi
     *
     * @param string $username
     * @param string $password
     * @param array $options
     * @param string $apiKey
     */
    public function __construct($username, $password, $options, $apiKey)
    {
        parent::__construct($username, $password, array_merge($this->apiOptions, $options));
        $this->apiKey = $apiKey;

        $this->apiClient = new \GuzzleHttp\Client(array(
            'base_url' => $this->options['api_url'],
            'defaults' => array(
                'verify' => $this->options['verify_ssl'],
                'connect_timeout' => 20,
                'timeout' => 30,
                'auth' => array('reseller', $this->apiKey),
            ),
        ));
    }

    /**
     * Perform a GET request
     *
     * @param string $url
     * @param array $data
     * @return mixed
     */
    protected function apiGet($url, $data = array())
    {
        if(!empty($data)){
            $url .= http_build_query($data);
        }

        $response = $this->apiClient->get($url);

        return $this->processResponse($response);
    }

    /**
     * Perform a POST request
     *
     * @param string $url
     * @param array $data
     * @return mixed
     */
    protected function apiPost($url, $data = array())
    {
        $response = $this->apiClient->post($url, array(
            'body' => $data,
        ));

        return $this->processResponse($response);
    }

    /**
     * Preprocess the result to check whether the request was successful
     *
     * @param ResponseInterface $response
     * @return mixed
     * @throws YouHostingException
     */
    private function processResponse(ResponseInterface $response)
    {
        if($response->getStatusCode() != 200){
            throw new YouHostingException("The API returned with a non-successful error code: ".$response->getStatusCode());
        }

        $json = $response->json();

        if(!empty($json['error'])){
            throw new YouHostingException($json['error']['message'], $json['error']['code']);
        }

        return $json['result'];
    }

    /**
     * Get a client from YouHosting
     *
     * @param $id
     * @return Client
     */
    public function getClient($id)
    {
        return new Client($this->apiGet("/v1/client/".$id));
    }
}