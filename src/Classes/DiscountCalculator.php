<?php
/**
 * Created by PhpStorm.
 * User: jonat
 * Date: 1/05/2018
 * Time: 10:46
 */

namespace Discount\Classes;
require __DIR__ . '/../Classes/iDiscount.php';

use Discount\Classes\iDiscount;

class DiscountCalculator implements iDiscount
{

    private $customers;
    private $products;

    public function __construct()
    {
        $this->updateData();

    }

    public function getDiscounts()
    {
        // TODO: Implement getDiscounts() method.
    }

    //get the Customers from the external API
    //Here a json-file is used as an example
    public function getCustomers(){
        $file = file_get_contents(__DIR__ .'/../data/customers.json', FILE_USE_INCLUDE_PATH);
        $data = json_decode($file,true);
        $res = array();
        foreach ($data as $customer){
            $id = $customer['id'];
            unset($customer['id']);
            $res[$id] = $customer;
        }
        return $res;
    }

    //get the Products from the external API
    //Here a json-file is used as an example
    public function getProducts(){
        $file = file_get_contents(__DIR__ .'/../data/products.json', FILE_USE_INCLUDE_PATH);
        $data = json_decode($file,true);
        $res = array();
        foreach ($data as $product){
            $id = $product['id'];
            unset($product['id']);
            $res[$id] = $product;
        }

        return  $res;
    }

    public function calculateDiscount($body)
    {
        $this->updateData();

        $customerid = $body['customer-id'];
        $total = $body['total'];

        foreach ( $body['items'] as $item){

            $productid = $item['product-id'];
            $this->checkProductID($productid);

            $quantity = (int)$item['quantity'];
            $this->checkQuantity($quantity);

            $unitprice = (float)$item['unit-price'];
            $this->checkUnitprice($productid,$unitprice);

            //$itemtotal = (float)$item['total'];

            $item = $this->calculate($item);
        }
        return $body;
    }

    private function updateData(){
        $this->products = self::getProducts();
        $this->customers = self::getCustomers();
    }

    private function checkProductID($productid){
        if(!array_key_exists($productid,$this->products)){
            throw new \Exception("Product-id not found.");
        }
    }

    private function checkQuantity($quantity){
        if($quantity<=0){
            throw new \Exception("Quantity is too low.");
        }
    }

    private function checkUnitprice($productid,$unitprice){
        if(!((float)$this->products[$productid]['price'])==$unitprice){
            throw new \Exception("Unit-price mismatch.");
        }
    }

    private function calculate($item){
        $productid = $item['product-id'];
        $quantity = (int)$item['quantity'];
        $unitprice = (float)$item['unit-price'];
        $itemtotal = (float)$item['total'];

    }




}