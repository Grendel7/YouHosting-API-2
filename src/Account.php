<?php
/**
 * Created by PhpStorm.
 * User: hans
 * Date: 3-9-15
 * Time: 14:45
 */

namespace YouHosting;


class Account extends AbstractDataContainer
{
    public $id;
    public $client_id;
    public $plan_id;
    public $domain;
    public $username;
    public $status;
    public $period;
    public $created_at;
}