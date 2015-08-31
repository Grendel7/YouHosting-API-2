<?php

namespace YouHosting;

/**
 * Class Client
 *
 * Data container for a client on YouHosting
 *
 * @package YouHosting
 */
class Client extends AbstractDataContainer
{
    public $id;
    public $email;
    public $first_name;
    public $last_name;
    public $company;
    public $address_1;
    public $address_2;
    public $city;
    public $country;
    public $state;
    public $zip;
    public $phone;
    public $phone_cc;
    public $created_at;
}