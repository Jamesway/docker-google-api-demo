<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 3/19/18
 * Time: 5:20 PM
 */

namespace App;


interface ISecretStore
{

    public function get($property);

    public function set($property, $value);
}