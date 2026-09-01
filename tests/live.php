<?php

use Phessage\OneComm\CatalogClient;

$url = getenv('HEADLESS_API_URL') ?: '';
$key = getenv('HEADLESS_PUBLISHABLE_KEY') ?: '';
if ($url === '' || $key === '') {
    fwrite(STDERR, "Live test requires HEADLESS_API_URL and HEADLESS_PUBLISHABLE_KEY\n");
    exit(2);
}
$assert = static function (bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
};
$client = new CatalogClient($url, $key);
$productId = '1f7884bd-759d-4f47-9fdb-c7ea3dd3a9ef';
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
echo "WordPress deployed checkout-preparation journey passed\n";
