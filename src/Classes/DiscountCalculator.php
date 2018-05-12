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
    //Save data of customers and products for use in methods
    private $customers;
    private $products;

    public function __construct()
    {
        $this->updateData();

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

    //Get latest data from external API
    private function updateData(){
        $this->products = self::getProducts();
        $this->customers = self::getCustomers();
    }

    /*
     * This is the main method for calculating the discount on an order.
     * It will first update the customer an product data.
     * Then it will check if the customers exists and if the order doesn't contain any anomalies.
     * Once every check has been passed, it will start calculating the discounts.
     */
    public function calculateDiscount($body)
    {
        $this->updateData();

        $customerid = $body['customer-id'];
        $this->checkCustomer($customerid);
        //$total = $body['total'];

        $this->checkItems($body['items']);

        //There are two ways of calculating discounts
        //This method is the first way of calculating discounts and is based on the category of the items
        $body['items'] = $this->calculateCategories($body['items']);

        //This method is just to change the total after the discounts on the items have been made
        $body['total'] = $this->calculateTotal($body['items']);

        //This method is the second way of calculating discounts and will calculate additional discounts that are not linked with any category
        $body = $this->calculateExtraDiscounts($body);

        return $body;
    }

    //Check if customer-id exists in the data
    private function checkCustomer($customerid){
        if(!array_key_exists($customerid,$this->customers)){
            throw new \Exception("Customer not found with id: ".$customerid.".");
        }
    }

    //Check if product-id exists in the data
    private function checkProductID($productid){
        if(!array_key_exists($productid,$this->products)){
            throw new \Exception("Product-id: ".$productid." not found.");
        }
    }

    //Check if quantity is a usable amount
    private function checkQuantity($productid,$quantity){
        if($quantity<=0){
            throw new \Exception("Quantity is too low for product with id: ".$productid.".");
        }
    }

    //Check if there isn't a mismatch between given unit-price and the unit-price of the external API
    private function checkUnitprice($productid,$unitprice){
        if(!((float)$this->products[$productid]['price'])==$unitprice){
            throw new \Exception("Unit-price mismatch for product with id: ".$productid.".");
        }
    }

    //Check if total is correct with given quantity and unit-price
    private function checkTotal($productid,$unitprice,$quantity,$total){
        if(! (abs($total-($unitprice*$quantity)) < 0.00001)){
            throw new \Exception("Incorrect total price for product with id: ".$productid.".");
        }
    }

    //Performs every necessary check for the items
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

    /*
     * Every item will be put in an array for each category
     * If the method 'calculate_category_numberofcategory' exist, it will be executed on the correct array
     * If additional discounts for new categories need to be added, a new function with the correct number can be added
     */
    private function calculateCategories($items){
        $array_categ = array();
        foreach($items as $item){
            if(!array_key_exists($this->products[$item['product-id']]['category'],$array_categ)){
                $array_categ[$this->products[$item['product-id']]['category']] = array();
            }
            array_push($array_categ[$this->products[$item['product-id']]['category']],$item);
        }
        $array_res = array();
        foreach ($array_categ as $cat => $array_items){
            $function = "calculate_category_".$cat;
            if(method_exists($this,$function)){
                $array_res = array_merge($array_res,$this->$function($array_items));
            }else{
                $array_res = array_merge($array_res,$array_items);
            }
        }
        return $array_res;
    }

    //This function is to calculate the discounts for every item of category 1
    private function calculate_category_1($array_items){
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

    //This function is to calculate the discounts for every item of category 2
    private function calculate_category_2($array_items){
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

    //Simple function for calculating the total of the order
    private function calculateTotal($items){
        $total =0.0;
        foreach($items as $item){
            $total+=((float) $item['total']);
        }
        return (string) round($total,2);
    }

    /*
     * For discounts that are independent of any category
     * They will be executed here
     * Additional extra discounts can be added by making function with the name 'calculate_extra_number'
     * Where the number starts with 1 and has to increase sequentially for every new method
     */
    private function calculateExtraDiscounts($body){
        $i = 1;
        while(method_exists($this,"calculate_extra_".$i)){
            $body = $this->calculate_extra_1($body);
            $i++;
        }
        return $body;
    }

    //First function for an extra discount
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

}