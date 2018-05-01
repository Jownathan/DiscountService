<?php

require __DIR__ . '/../src/Classes/DiscountCalculator.php';

use Slim\Http\Request;
use Slim\Http\Response;
use Discount\Classes\DiscountCalculator;


// Routes

$app->get('/api/discounts', function (Request $request, Response $response, array $args){


});

$app->post('/api/order', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    $discountcalculator = new DiscountCalculator();

    $body = $request->getParsedBody();
    $id = $body['id'];
    $customerid = $body['customer-id'];
    $items = $body['items'];
    $total = $body['total'];

    try{
        $data = $discountcalculator->calculateDiscount($body);
        return $response->withJson($data);

    }catch(Exception $exception){
        return $response->body = $exception->getMessage();
    }


    //$data = array('id' => $id, 'customer-id' => $customerid,'items' => $items,'total' => $total);
});

/*
$app->put('/api/data/customers', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    $body = $request->getParsedBody();

    $data =null;

    return $response->withJson($body);
});

$app->put('/api/data/products', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    $body = $request->getParsedBody();

    $data =null;

    return $response->withJson($body);
});
*/