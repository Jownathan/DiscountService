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
        $this->checkCustomer($customerid);
        //$total = $body['total'];

        $this->checkItems($body['items']);

        $body['items'] = $this->calculate($body['items']);
        $body['total'] = $this->calculateTotal($body['items']);
        $body = $this->calculateExtraDiscounts($body);
        return $body;
    }

    private function updateData(){
        $this->products = self::getProducts();
        $this->customers = self::getCustomers();
    }

    private function checkCustomer($customerid){
        if(!array_key_exists($customerid,$this->customers)){
            throw new \Exception("Customer not found with id: ".$customerid.".");
        }
    }

    private function checkProductID($productid){
        if(!array_key_exists($productid,$this->products)){
            throw new \Exception("Product-id: ".$productid." not found.");
        }
    }

    private function checkQuantity($productid,$quantity){
        if($quantity<=0){
            throw new \Exception("Quantity is too low for product with id: ".$productid.".");
        }
    }

    private function checkUnitprice($productid,$unitprice){
        if(!((float)$this->products[$productid]['price'])==$unitprice){
            throw new \Exception("Unit-price mismatch for product with id: ".$productid.".");
        }
    }

    private function checkTotal($productid,$unitprice,$quantity,$total){
        if(! (abs($total-($unitprice*$quantity)) < 0.00001)){
            throw new \Exception("Incorrect total price for product with id: ".$productid.".");
        }
    }

    private function checkItems($items){
        foreach ( $items as $item){

            $productid = $item['product-id'];
            $this->checkProductID($productid);

            $quantity = (int)$item['quantity'];
            $this->checkQuantity($productid, $quantity);

            $unitprice = (float)$item['unit-price'];
            $this->checkUnitprice($productid,$unitprice);

            $itemtotal = (float)$item['total'];
            $this->checkTotal($productid,$unitprice,$quantity,$itemtotal);


        }
    }

    private function calculateExtraDiscounts($body){
        $body = $this->calculate_extra_1($body);
        return $body;
    }

    private function calculateTotal($items){
        $total =0.0;
        foreach($items as $item){
            $total+=((float) $item['total']);
        }
        return (string) round($total,2);
    }

    private function calculate($items){
        $array_categ = array();
        foreach($items as $item){
            if(!is_array($array_categ[$this->products[$item['product-id']]['category']])){
                $array_categ[$this->products[$item['product-id']]['category']] = array();
            }
            array_push($array_categ[$this->products[$item['product-id']]['category']],$item);
        }
        $array_res = array();
        foreach ($array_categ as $cat => $array_items){
            $function = "calculate_".$cat;
            $array_res = array_merge($array_res,$this->$function($array_items));
        }
        return $array_res;
    }

    private function calculate_extra_1($body){
        $discount = 9/10;
        $minimum = 1000.0;
        if(((float)$this->customers[$body['customer-id']]['revenue'])>$minimum){
            $body['total'] = (string) round($discount*$body['total'],2);
            $body['discount'] = (string) round((1-$discount)*100,2)."%";
            $body['discount-description'] =  "Revenue of client was above ".$minimum."€.";
        }
        return $body;
    }

    private function calculate_1($array_items){
        $discount = 4/5;
        $minimum = 2;
        if(count($array_items)>=$minimum){
            $index=0;
            $cheapest_index = 0;
            $cheapest_total = (float) $array_items[0]['total'];
            foreach ($array_items as $item) {
                if(((float)$item['total']) < $cheapest_total){
                    $cheapest_index  = $index;
                    $cheapest_total = (float)$item['total'];
                }
                $index++;
            }
            $array_items[$cheapest_index]['total']= (string) round($discount* (float)$array_items[$cheapest_index]['total'],2);
            $array_items[$cheapest_index]['discount'] = (string) round((1-$discount)*100,2)."%";
            $array_items[$cheapest_index]['discount-description'] = $minimum." or more products of category \"Tools\" (id1) were bought, giving a discount on the cheapest product.";

        }

        return $array_items;
        /*
         * $amount = 0;
        $cheapest = array();
        $cheapest[$array_items[0]['product-id']] = (float) $array_items[0]['unit-price'];
        foreach ($array_items as $item) {
            $amount+= (int) $item['quantity'];
            if(((float)$item['unit-price']) < $cheapest[0]){
                reset($cheapest);
                $cheapest[$item['product-id']] = (float)$item['unit-price'];
            }
        }
        if($amount>=2){
            foreach ($array_items as $item) {
                if(array_key_exists($item['product-id'],$cheapest)){
                    $item['discount'] = $discount;

                }
            }
        }
        */
    }

    private function calculate_2($array_items){
        $amount_discount = 5;
        foreach($array_items as $index=>$item){
            $quantity = ((int)$item['quantity']);
            if($quantity>=$amount_discount){
                $amount_free = floor($quantity/$amount_discount);
                $array_items[$index]['quantity'] = (string) ($quantity+$amount_free);
                $array_items[$index]['amount-free-items'] = (string) $amount_free;
                $array_items[$index]['discount-description'] = "For every product of category  \"Switches\" (id 2), when you buy ".$amount_discount." you get one for free.";
            }
        }
        return $array_items;
    }

}