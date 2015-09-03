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
     * Get the client ID from a Client object, e-mail or id
     *
     * @param Client|string|int $client
     * @return int
     * @throws YouHostingException
     */
    protected function getClientId($client)
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

        return (int)$id;
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
        return $this->api->getClient($this->getClientId($client));
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

    public function getAccount($account)
    {
        return $this->api->getAccount($this->getAccountId($account));
    }

    protected function getAccountId($account)
    {
        if($account instanceof Account){
            if(!empty($account->id)){
                $id = $account->id;
            } elseif(!empty($account->domain)){
                $id = $this->api->searchAccountId($account->domain);
            } else {
                throw new YouHostingException("You need to provide either an account ID (recommended) or account domain to search for");
            }
        } elseif(is_numeric($account)){
            $id = $account;
        } else {
            $id = $this->api->searchAccountId($account);
        }

        return $id;
    }

    /**
     * Get a new captcha (SVIP API only)
     *
     * @return array containing a numeric id and a url to the captcha image
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

    /**
     * Get a list of clients
     *
     * Returns array with the parameter list (containing an array of Client objects) and per_page (telling you the number of clients).
     * When using the SVIP API, you also get pages (the total number of pages), and total (the total number of clients for the reseller)
     *
     * @param int $page optional the page number
     * @return array
     */
    public function listClients($page = 1)
    {
        return $this->api->listClients($page);
    }

    /**
     * Get the login URL for a client
     *
     * @param Client|string|int a client object, client e-mail (not recommended) or client ID
     * @return string
     */
    public function getLoginUrl($client)
    {
        return $this->api->getClientLoginUrl($this->getClientId($client));
    }

    /**
     * Get the login URL for an account
     *
     * @param Account|string|int $account an account object, domain name (not recommended) or account ID
     * @return string
     */
    public function getLoginUrlAccount($account)
    {
        return $this->api->getAccountLoginUrl($this->getAccountId($account));
    }

    /**
     * Check if a domain name is available
     *
     * @param string $type 'domain' or 'subdomain' to separate whether it's an owned domain or a subdomain
     * @param string $domain If the type is 'domain', then this contains the full domain. If the type is 'subdomain', then this is the master domain.
     * @param string $subdomain If the type is 'subdomain', then this is the client's custom part of the subdomain
     * @return bool
     */
    public function checkDomain($type, $domain, $subdomain = "")
    {
        return $this->api->checkDomain($type, $domain, $subdomain);
    }

    /**
     * Get a list of accounts
     *
     * Returns array with the parameter list (containing an array of Account objects) and per_page (telling you the number of accounts).
     * When using the SVIP API, you also get pages (the total number of pages), and total (the total number of accounts for the reseller)
     *
     * @param int $page optional the page number
     * @return array
     */
    public function listAccounts($page = 1)
    {
        return $this->api->listAccounts($page);
    }

    public function suspendAccount($account, $reason = "", $info = "")
    {
        return $this->api->suspendAccount($this->getAccountId($account), $reason, $info);
    }

    public function suspendClient($client, $allAccounts = false, $allVps = false, $reason = "", $info = "")
    {
        return $this->api->suspendClient($this->getClientId($client), $allAccounts, $allVps, $reason, $info);
    }

    public function unsuspendAccount($account, $reason = "", $info = "")
    {
        return $this->api->unsuspendAccount($this->getAccountId($account), $reason, $info);
    }

    public function unsuspendClient($client, $allAccounts = false, $allVps = false, $reason = "", $info = "")
    {
        return $this->api->unsuspendClient($this->getClientId($client), $allAccounts, $allVps, $reason, $info);
    }

    public function changeAccountStatus($account, $status, $reason = "", $info = "")
    {
        return $this->api->changeAccountStatus($this->getAccountId($account), $status, $reason, $info);
    }

    public function changeClientStatus($client, $status, $allAccounts = false, $allVps = false, $reason = "", $info = "")
    {
        return $this->api->changeClientStatus($this->getClientId($client), $status, $allAccounts, $allVps, $reason, $info);
    }

    public function deleteAccount($account)
    {
        return $this->api->deleteAccount($this->getAccountId($account));
    }

    public function deleteClient($client)
    {
        return $this->api->deleteClient($this->getClientId($client));
    }

    public function getSubdomains()
    {
        return $this->api->getSubdomains();
    }

    public function getPlans()
    {
        return $this->api->getPlans();
    }

    public function getNameservers()
    {
        return $this->api->getNameservers();
    }

}