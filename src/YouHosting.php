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
     * @param Client|string|int $client
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

    /**
     * Get an account from YouHosting
     *
     * @param Account|string|int $account
     * @return Account
     * @throws YouHostingException
     */
    public function getAccount($account)
    {
        return $this->api->getAccount($this->getAccountId($account));
    }

    /**
     * Get an account id from either an id, email or account object
     *
     * @param $account
     * @return int
     * @throws YouHostingException
     */
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

        return (int)$id;
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

    /**
     * Suspend a hosting account
     *
     * @param Account|string|int $account
     * @param string $reason Can be 'none', 'abuse', 'non_payment' or 'fraud'
     * @param string $info
     * @return bool|mixed
     * @throws YouHostingException
     */
    public function suspendAccount($account, $reason = "", $info = "")
    {
        return $this->api->suspendAccount($this->getAccountId($account), $reason, $info);
    }

    /**
     * Suspend a client profile
     *
     * @param Client|string|int $client
     * @param bool|false $allAccounts Set to true to suspend all accounts as well
     * @param bool|false $allVps Set to true to suspend all VPS as well
     * @param string $reason Can be 'none', 'abuse', 'non_payment' or 'fraud'
     * @param string $info
     * @return bool
     * @throws YouHostingException
     */
    public function suspendClient($client, $allAccounts = false, $allVps = false, $reason = "", $info = "")
    {
        return $this->api->suspendClient($this->getClientId($client), $allAccounts, $allVps, $reason, $info);
    }

    /**
     * Reactivate a hosting account
     *
     * @param Account|string|int $account
     * @param string $reason Can be 'none', 'abuse', 'non_payment' or 'fraud'
     * @param string $info
     * @return bool|mixed
     * @throws YouHostingException
     */
    public function unsuspendAccount($account, $reason = "", $info = "")
    {
        return $this->api->unsuspendAccount($this->getAccountId($account), $reason, $info);
    }

    /**
     * Reactivate a client profile
     *
     * @param Client|string|int $client
     * @param bool|false $allAccounts Set to true to suspend all accounts as well
     * @param bool|false $allVps Set to true to suspend all VPS as well
     * @param string $reason Can be 'none', 'abuse', 'non_payment' or 'fraud'
     * @param string $info
     * @return bool
     * @throws YouHostingException
     */
    public function unsuspendClient($client, $allAccounts = false, $allVps = false, $reason = "none", $info = "")
    {
        return $this->api->unsuspendClient($this->getClientId($client), $allAccounts, $allVps, $reason, $info);
    }

    /**
     * Change the status of a hosting account
     *
     * @param Account|string|int $account
     * @param string $status Can be 'pending_payment', 'active', 'suspended', 'canceled' or 'failed'
     * @param string $reason Can be 'none', 'abuse', 'non_payment' or 'fraud'
     * @param string $info
     * @return bool|mixed
     * @throws YouHostingException
     */
    public function changeAccountStatus($account, $status, $reason = "", $info = "")
    {
        return $this->api->changeAccountStatus($this->getAccountId($account), $status, $reason, $info);
    }

    /**
     * Change the status of a client profile
     *
     * @param Client|string|int $client
     * @param string $status Can be 'pending_phone_confirmation', 'pending_confirmation', 'active', 'suspended' or 'canceled'
     * @param bool|false $allAccounts Set to true to suspend all accounts as well
     * @param bool|false $allVps Set to true to suspend all VPS as well
     * @param string $reason Can be 'none', 'abuse', 'non_payment' or 'fraud'
     * @param string $info
     * @return bool
     * @throws YouHostingException
     */
    public function changeClientStatus($client, $status, $allAccounts = false, $allVps = false, $reason = "none", $info = "")
    {
        return $this->api->changeClientStatus($this->getClientId($client), $status, $allAccounts, $allVps, $reason, $info);
    }

    /**
     * Delete a hosting account (must be cancelled first!)
     *
     * @param Account|string|int $account
     * @return bool
     * @throws YouHostingException
     */
    public function deleteAccount($account)
    {
        return $this->api->deleteAccount($this->getAccountId($account));
    }

    /**
     * Delete a client profile (the client may not have any active accounts or vps)
     *
     * @param Client|string|int $client
     * @return bool
     * @throws YouHostingException
     */
    public function deleteClient($client)
    {
        return $this->api->deleteClient($this->getClientId($client));
    }

    /**
     * Get the list of domains for which subdomains can be created
     *
     * @return array
     */
    public function getSubdomains()
    {
        return $this->api->getSubdomains();
    }

    /**
     * Get a list of HostingPlans detailing which plans are available
     *
     * @return HostingPlan[]
     */
    public function getPlans()
    {
        return $this->api->getPlans();
    }

    /**
     * Get a list of nameservers and nameserver IPs
     *
     * @return array
     */
    public function getNameservers()
    {
        return $this->api->getNameservers();
    }

    /**
     * Change the password of a hosting account
     *
     * @param Account|int|string $account An ID or domain to identify the account
     * @param string $password The new password
     * @return bool
     * @throws YouHostingException
     */
    public function changeAccountPassword($account, $password)
    {
        return $this->api->changeAccountPassword($this->getAccountId($account), $password);
    }

    /**
     * Change the password of a client profile
     *
     * @param Client|int|string $client An ID or email to identify the user
     * @param string $password The new password
     * @return bool
     * @throws YouHostingException
     */
    public function changeClientPassword($client, $password)
    {
        return $this->api->changeClientPassword($this->getClientId($client), $password);
    }

    /**
     * Get the current account balance of a client
     *
     * @param Client|int|string $client An ID or email to identify the user
     * @return string
     */
    public function getClientBalance($client)
    {
        return $this->api->getClientBalance($this->getClientId($client));
    }

    /**
     * Apply a transaction to the balance of a client
     *
     * @param Client|int|string $client An ID or email to identify the user
     * @param string $amount The amount to apply to the balance. This can be a positive or negative number.
     * @param string $description A description text of the payment.
     * @param string $gateway The name of the payment gateway (can be any text)
     * @param string $invoiceId An optional invoice to cover with this payment
     * @return bool
     * @throws YouHostingException
     */
    public function updateBalance($client, $amount, $description, $gateway, $invoiceId = null)
    {
        return $this->api->updateBalance($this->getClientId($client), $amount, $description, $gateway, $invoiceId);
    }

    /**
     * Cover invoices of the client using the account balance
     *
     * @param Client|int|string $client An ID or email to identify the user
     * @param string $invoiceId Optional. If this is not provided, all invoices will be covered.
     * @return bool
     * @throws YouHostingException
     */
    public function coverInvoice($client, $invoiceId = null)
    {
        return $this->api->coverInvoice($this->getClientId($client), $invoiceId);
    }

}