# DiscountService
*Current Version:* ***0.0.1***

A PHP Microservice for calculating discounts on orders.

## Introduction
DiscountService is written in PHP and was created as a test for [Teamleader](https://www.teamleader.eu/).
It uses the [Slim](https://www.slimframework.com/) framework and an external (for this test fictional) API for fetching customer and product data.

## Usage
To calculate a discount you can use the following request where you send a json-file with the order:

```
POST /api/order
```
The order you send should look something like this:

```
{
  "id": "1",
  "customer-id": "1",
  "items": [
    {
      "product-id": "B102",
      "quantity": "10",
      "unit-price": "4.99",
      "total": "49.90"
    }
  ],
  "total": "49.90"
}
```
And a json file will be returned looking like this:

```
{
    "id": "1",
    "customer-id": "1",
    "items": [
        {
            "product-id": "B102",
            "quantity": "12",
            "unit-price": "4.99",
            "total": "49.90",
            "amount-free-items": "2",
            "discount-description": "For every product of category  \"Switches\" (id 2), when you buy 5 you get one for free."
        }
    ],
    "total": "49.9"
}
```

## Install the Service

Docker can be used to run the service.
Go to the DiscountService-folder and executing the following command:

```
cd [directory-of-service]/DiscountService
docker-compose up
```
POST requests can then be sent to: 
```
http://192.168.99.100:8080/api/order
```

The service can also be run locally without docker by doing the same steps, except using a different command

```
cd [directory-of-service]/DiscountService
php -S localhost:8080 -t public public/index.php
```
Here POST requests can be sent to:
```
http://localhost:8080/api/order
```
