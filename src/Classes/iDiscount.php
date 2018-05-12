<?php
/**
 * Created by PhpStorm.
 * User: jonat
 * Date: 1/05/2018
 * Time: 10:49
 */

namespace Discount\Classes;


interface iDiscount
{
    public function calculateDiscount($body);
}