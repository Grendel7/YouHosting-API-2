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
     */
    public function getClient($client)
    {
        if($client instanceof Client){
            $id = $client->id;
        } else {
            $id = $client;
        }

        return $this->api->getClient($id);
    }


}