<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 2/27/18
 * Time: 2:24 PM
 */

namespace App\Controllers;


interface IController
{
    public function execute(array $params);
}