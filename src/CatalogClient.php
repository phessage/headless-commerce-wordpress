<?php declare(strict_types=1);

namespace Phessage\OneComm;

final class CatalogClient
{
    public static function forStore(string $storeId, string $bootstrapUrl = 'https://api.1ecomm.com'): self
    {
        $cache = 'onecomm_store_' . hash('sha256', $storeId . $bootstrapUrl);
        $runtime = get_transient($cache);
        if (!is_array($runtime)) {
            $response = wp_safe_remote_request(rtrim($bootstrapUrl, '/') . '/v1/headless/stores/' . rawurlencode($storeId) . '/config', ['method' => 'GET', 'timeout' => 8, 'redirection' => 0, 'headers' => ['Accept' => 'application/json']]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) throw new \RuntimeException('Headless store bootstrap failed');
            $runtime = json_decode(wp_remote_retrieve_body($response), true)['data'] ?? [];
            if (($runtime['storeId'] ?? null) !== $storeId || !str_starts_with((string) ($runtime['publishableKey'] ?? ''), 'pk_')) throw new \RuntimeException('Invalid headless store bootstrap response');
            set_transient($cache, $runtime, 5 * MINUTE_IN_SECONDS);
        }
        return new self((string) $runtime['apiUrl'], (string) $runtime['publishableKey']);
    }

    public function __construct(private string $baseUrl, private string $key) {}

    public function products(int $limit = 12): array
    {
        $limit = max(1, min(100, $limit));
        $cache = 'onecomm_products_' . hash('sha256', $this->baseUrl . $this->key . $limit);
        $cached = get_transient($cache);
        if (is_array($cached)) return $cached;
        $result = $this->request('GET', '/v1/headless/products?limit=' . $limit);
        $products = is_array($result['data'] ?? null) ? $result['data'] : [];
        if ($products !== []) set_transient($cache, $products, MINUTE_IN_SECONDS);
        return $products;
    }

    public function createCart(): array { return $this->request('POST', '/v1/headless/carts'); }
    public function cart(string $token): array { return $this->request('GET', '/v1/headless/carts/current', null, $token); }
    public function checkout(string $token): array { return $this->request('GET', '/v1/headless/carts/current/checkout', null, $token); }

    public function addItem(string $token, string $productId, int $quantity = 1): array
    {
        return $this->request('POST', '/v1/headless/carts/current/items', ['productId' => $productId, 'quantity' => max(1, $quantity)], $token);
    }

    public function updateItem(string $token, string $itemId, int $quantity): array
    {
        return $this->request('PATCH', '/v1/headless/carts/current/items/' . rawurlencode($itemId), ['quantity' => max(1, $quantity)], $token);
    }

    public function removeItem(string $token, string $itemId): array
    {
        return $this->request('DELETE', '/v1/headless/carts/current/items/' . rawurlencode($itemId), null, $token);
    }

    public function updateCheckout(string $token, array $details): array
    {
        return $this->request('PATCH', '/v1/headless/carts/current/checkout', $details, $token);
    }

    public function selectShippingMethod(string $token, string $id): array
    {
        return $this->request('PUT', '/v1/headless/carts/current/checkout/shipping-method', ['id' => $id], $token);
    }

    public function selectPaymentMethod(string $token, string $id): array
    {
        return $this->request('PUT', '/v1/headless/carts/current/checkout/payment-method', ['id' => $id], $token);
    }
    public function placeOrder(string $token, string $idempotencyKey): array
    {
        $key = trim($idempotencyKey); if ($key === '' || strlen($key) > 120) return [];
        return $this->request('POST', '/v1/headless/carts/current/checkout/order', null, $token, ['Idempotency-Key' => $key]);
    }

    public function lookupOrder(string $orderNumber, string $email): array
    {
        $number = trim($orderNumber); $address = sanitize_email($email);
        if ($number === '' || $address === '' || strlen($number) > 64 || strlen($address) > 254) return [];
        return $this->request('POST', '/v1/headless/orders/lookup', ['orderNumber' => $number, 'email' => $address]);
    }

    private function request(string $method, string $path, ?array $body = null, ?string $token = null, array $extraHeaders = []): array
    {
        if (!str_starts_with($this->key, 'pk_') || !str_starts_with($this->baseUrl, 'https://') || ($token !== null && !$this->validToken($token))) return [];
        $args = ['method' => $method, 'timeout' => 8, 'redirection' => 0, 'headers' => ['Accept' => 'application/json', 'x-publishable-key' => $this->key] + $extraHeaders];
        if ($token !== null) $args['headers']['x-cart-token'] = $token;
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        // WordPress validates the destination; redirects are disabled. Mutations run once only.
        $response = wp_safe_remote_request(rtrim($this->baseUrl, '/') . $path, $args);
        $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) return [];
        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function validToken(string $token): bool
    {
        return preg_match('/^hc_[A-Za-z0-9_-]{43}$/D', $token) === 1;
    }
}
