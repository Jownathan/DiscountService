<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    /*
    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args); */

    return json_encode($args);
});

$app->post('/api/order', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    $body = $request->getParsedBody();
    $id = $body['id'];
    $customerid = $body['customer-id'];
    $items = $body['items'];
    $total = $body['total'];

    $data = array('id' => $id, 'customer-id' => $customerid,'items' => $items,'total' => $total);

    return $response->withJson($data);
});

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
