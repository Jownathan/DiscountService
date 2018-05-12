<?php

require __DIR__ . '/../src/Classes/DiscountCalculator.php';

use Slim\Http\Request;
use Slim\Http\Response;
use Discount\Classes\DiscountCalculator;


// Routes
//If an order is sent as json to '/api/order', a json with additional discounts will be returned
$app->post('/api/order', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    $discountcalculator = new DiscountCalculator();

    $body = $request->getParsedBody();

    try{
        $data = $discountcalculator->calculateDiscount($body);
        return $response->withJson($data);

    }catch(Exception $exception){
        return $response->withJson(array("Error: "=>$exception->getMessage()));
    }
});