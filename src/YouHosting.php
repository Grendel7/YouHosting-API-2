<?php
/**
 * Created by PhpStorm.
 * User: hans
 * Date: 31-8-15
 * Time: 21:43
 */

namespace YouHosting;


class YouHosting
{
    protected $api;

    /**
     * Create a new instance of YouHosting
     *
     * @param $username string Your YouHosting administrator e-mail
     * @param $password string Your YouHosting password
     * @param array $options an array of options
     * @param string $apiKey (optional) Your SVIP API key (if you have the SVIP plan, using this is highly recommended)
     */
    public function __construct($username, $password, $options = array(), $apiKey = null)
    {
        if(empty($apiKey)){
            $this->api = new WebApi($username, $password, $options);
        } else {
            $this->api = new RestApi($username, $password, $options, $apiKey);
        }
    }

    /**
     * Get a client from YouHosting
     *
     * @param mixed $client An instance of a Client or a client ID
     * @return Client
     * @throws YouHostingException
     */
    public function getClient($client)
    {
        if($client instanceof Client){
            if(!empty($client->id)) {
                $id = $client->id;
            } elseif(!empty($client->email)){
                $id = $this->api->searchClientId($client->email);
            } else {
                throw new YouHostingException("You need to provide either a client ID (recommended) or client e-mail to search for");
            }
        } elseif (is_numeric($client)) {
            $id = $client;
        } else {
            $id = $this->api->searchClientId($client);
        }

        return $this->api->getClient($id);
    }

    /**
     * Create a new client on YouHosting
     *
     * @param Client $client a container for client details
     * @param string $password
     * @param int $captchaId
     * @return Client
     * @throws YouHostingException
     */
    public function createClient(Client $client, $password, $captchaId = null)
    {
        return $this->api->createClient($client, $password, $captchaId);
    }

    /**
     * Get a new captcha (SVIP API only)
     *
     * @return array containing a numeric id and a url to the captcha iamge
     */
    public function getCaptcha()
    {
        return $this->api->getCaptcha();
    }

    /**
     * Verify the captcha result (SVIP API only)
     *
     * @param int $id the captcha id
     * @param string $solution the solution of the captcha submitted by the user
     * @return boolean
     */
    public function checkCaptcha($id, $solution)
    {
        return $this->api->checkCaptcha($id, $solution);
    }


}