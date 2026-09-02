<?php

use Phessage\OneComm\CatalogClient;

$storeId = getenv('HEADLESS_STORE_ID') ?: '01f5b02f-d7c0-42cd-b880-59f78ea70aa3';
$assert = static function (bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
};
$key = getenv('HEADLESS_PUBLISHABLE_KEY') ?: '';
$client = $key !== '' ? new CatalogClient(getenv('HEADLESS_API_URL') ?: 'https://api.1ecomm.com', $key) : CatalogClient::forStore($storeId);
$productId = getenv('HEADLESS_PRODUCT_ID') ?: '1f7884bd-759d-4f47-9fdb-c7ea3dd3a9ef';
$catalog = $client->products(100);
$assert(in_array($productId, array_column($catalog, 'id'), true), 'sellable fixture missing');
$created = $client->createCart();
$token = (string) ($created['cartToken'] ?? '');
$added = $client->addItem($token, $productId, 1);
$assert(count($added['data']['items'] ?? []) === 1, 'item was not added');
$prepared = $client->updateCheckout($token, [
    'customerInfo' => ['firstName' => 'Headless', 'lastName' => 'Fixture', 'email' => 'wordpress-live@example.test'],
    'billingAddress' => ['firstName' => 'Headless', 'lastName' => 'Fixture', 'email' => 'wordpress-live@example.test', 'address1' => '1 Test Way', 'city' => 'Vancouver', 'state' => 'BC', 'postalCode' => 'V6B1A1', 'country' => 'CA'],
    'shippingAddress' => ['sameAsBilling' => true],
]);
$data = $prepared['data'] ?? [];
$assert(($data['shippingOptions'] ?? []) !== [] && ($data['paymentMethods'] ?? []) !== [], 'checkout choices missing');
$shipping = $client->selectShippingMethod($token, (string) $data['shippingOptions'][0]['id']);
$payment = $client->selectPaymentMethod($token, (string) $data['paymentMethods'][0]['id']);
$assert(($shipping['data']['selectedShippingMethodId'] ?? null) !== null, 'shipping selection missing');
$assert(($payment['data']['selectedPaymentMethodId'] ?? null) !== null, 'payment selection missing');
$placed = $client->placeOrder($token, 'wordpress-live-' . bin2hex(random_bytes(16)));
$assert(($placed['data']['requiresPayment'] ?? true) === false, 'fixture order unexpectedly requires hosted payment');
$assert(($placed['data']['status'] ?? null) === 'pending', 'pending order was not created');
$orderNumber = (string) ($placed['data']['orderNumber'] ?? '');
$lookup = $client->lookupOrder($orderNumber, 'wordpress-live@example.test');
$assert(($lookup['data']['orderNumber'] ?? null) === $orderNumber, 'created order could not be reopened');
echo 'WordPress deployed create/reopen journey passed: ' . $orderNumber . "\n";
