<?php declare(strict_types=1);

namespace Phessage\OneComm;

final class Plugin
{
    private const COOKIE = 'onecomm_cart_token';
    private const NONCE = 'onecomm_cart_mutation';

    public static function boot(): void
    {
        add_action('admin_init', [self::class, 'settings']);
        add_action('init', [self::class, 'blocks']);
        add_action('admin_post_onecomm_cart', [self::class, 'handleCartAction']);
        add_action('admin_post_nopriv_onecomm_cart', [self::class, 'handleCartAction']);
        add_shortcode('onecomm_products', [self::class, 'shortcode']);
        add_shortcode('onecomm_storefront', [self::class, 'storefrontShortcode']);
    }

    public static function settings(): void
    {
        register_setting('onecomm', 'onecomm_store_id', ['type' => 'string', 'sanitize_callback' => static fn ($v) => preg_match('/^[0-9a-f-]{36}$/Di', (string) $v) === 1 ? sanitize_text_field($v) : '']);
        register_setting('onecomm', 'onecomm_api_url', ['type' => 'string', 'sanitize_callback' => static fn ($v) => str_starts_with((string) $v, 'https://') ? esc_url_raw($v) : '']);
        register_setting('onecomm', 'onecomm_publishable_key', ['type' => 'string', 'sanitize_callback' => static fn ($v) => str_starts_with((string) $v, 'pk_') ? sanitize_text_field($v) : '']);
    }

    public static function blocks(): void
    {
        register_block_type(__DIR__ . '/../block', ['render_callback' => static fn ($a) => self::render((int) ($a['limit'] ?? 12))]);
    }

    public static function shortcode(array|string $attributes = []): string
    {
        $a = shortcode_atts(['limit' => 12], is_array($attributes) ? $attributes : []);
        return self::render((int) $a['limit']);
    }

    public static function storefrontShortcode(array|string $attributes = []): string
    {
        $a = shortcode_atts(['limit' => 12], is_array($attributes) ? $attributes : []);
        return self::renderStorefront((int) $a['limit']);
    }

    public static function render(int $limit): string
    {
        $items = self::client()->products($limit);
        if (!$items) return '<p class="onecomm-empty">' . esc_html__('No products available.', 'onecomm') . '</p>';
        $html = '<div class="onecomm-grid">';
        foreach ($items as $item) $html .= self::productCard($item, false);
        return $html . '</div>';
    }

    public static function renderStorefront(int $limit): string
    {
        $client = self::client();
        $token = self::cartToken();
        $cart = $token === null ? [] : ($client->cart($token)['data'] ?? []);
        $checkout = $token === null ? [] : ($client->checkout($token)['data'] ?? []);
        $confirmation = $token === null ? null : get_transient(self::confirmationKey($token));
        $html = '<section class="onecomm-storefront"><h2>' . esc_html__('Products', 'onecomm') . '</h2><div class="onecomm-grid">';
        foreach ($client->products($limit) as $item) $html .= self::productCard($item, true);
        $html .= '</div>' . self::cartMarkup(is_array($cart) ? $cart : []);
        if (is_array($confirmation)) $html .= '<section class="onecomm-order-confirmation" aria-label="Order confirmation"><h2>' . esc_html(sprintf(__('Order %s placed', 'onecomm'), (string) ($confirmation['orderNumber'] ?? ''))) . '</h2><p>' . esc_html(sprintf(__('Status: %s', 'onecomm'), (string) ($confirmation['status'] ?? ''))) . '</p><p>' . esc_html(sprintf(__('Payment: %s', 'onecomm'), (string) ($confirmation['paymentStatus'] ?? ''))) . '</p></section>';
        elseif (($cart['items'] ?? []) !== []) $html .= self::checkoutMarkup(is_array($checkout) ? $checkout : []);
        return $html . '</section>';
    }

    public static function handleCartAction(): void
    {
        check_admin_referer(self::NONCE);
        $operation = sanitize_key((string) ($_POST['operation'] ?? ''));
        $client = self::client();
        $token = self::cartToken();
        if ($operation === 'add') {
            if ($token === null) {
                $created = $client->createCart();
                $candidate = (string) ($created['cartToken'] ?? '');
                if (!self::isCartToken($candidate)) self::redirect('error');
                self::setCartToken($candidate);
                $token = $candidate;
            }
            $client->addItem($token, self::uuidPost('product_id'), self::positivePost('quantity'));
        } elseif ($token !== null && $operation === 'update') {
            $client->updateItem($token, self::uuidPost('item_id'), self::positivePost('quantity'));
        } elseif ($token !== null && $operation === 'remove') {
            $client->removeItem($token, self::uuidPost('item_id'));
        } elseif ($token !== null && $operation === 'details') {
            $client->updateCheckout($token, self::checkoutInput());
        } elseif ($token !== null && $operation === 'shipping') {
            $client->selectShippingMethod($token, self::selectionPost('selection_id'));
        } elseif ($token !== null && $operation === 'payment') {
            $client->selectPaymentMethod($token, self::uuidPost('selection_id'));
        } elseif ($token !== null && $operation === 'order') {
            $intentKey = self::intentKey($token); $intent = (string) get_transient($intentKey); if ($intent === '') { $intent = 'wordpress-' . wp_generate_uuid4(); set_transient($intentKey, $intent, 10 * MINUTE_IN_SECONDS); }
            $placed = $client->placeOrder($token, $intent); $confirmation = $placed['data'] ?? null;
            if (!is_array($confirmation) || ($confirmation['requiresPayment'] ?? true) !== false) self::redirect('error');
            set_transient(self::confirmationKey($token), $confirmation, 10 * MINUTE_IN_SECONDS); delete_transient($intentKey);
        } else self::redirect('error');
        self::redirect('updated');
    }

    private static function client(): CatalogClient
    {
        $storeId = (string) get_option('onecomm_store_id', '');
        if ($storeId !== '') return CatalogClient::forStore($storeId);
        return new CatalogClient((string) get_option('onecomm_api_url', ''), (string) get_option('onecomm_publishable_key', ''));
    }

    private static function productCard(array $item, bool $withForm): string
    {
        $html = '<article class="onecomm-product"><h3>' . esc_html((string) ($item['name'] ?? '')) . '</h3><p>' . esc_html((string) ($item['description'] ?? '')) . '</p><strong>' . esc_html((string) ($item['price']['amount'] ?? '')) . ' ' . esc_html((string) ($item['price']['currency'] ?? '')) . '</strong>';
        if ($withForm && ($item['available'] ?? false) === true) $html .= self::form('add', '<input type="hidden" name="product_id" value="' . esc_attr((string) ($item['id'] ?? '')) . '"><label>' . esc_html__('Quantity', 'onecomm') . ' <input type="number" name="quantity" min="1" value="1"></label><button type="submit">' . esc_html__('Add to cart', 'onecomm') . '</button>');
        return $html . '</article>';
    }

    private static function cartMarkup(array $cart): string
    {
        $html = '<section class="onecomm-cart"><h2>' . esc_html__('Cart', 'onecomm') . '</h2>';
        if (($cart['items'] ?? []) === []) return $html . '<p>' . esc_html__('Your cart is empty.', 'onecomm') . '</p></section>';
        foreach ($cart['items'] as $item) {
            $id = esc_attr((string) ($item['id'] ?? ''));
            $html .= '<div class="onecomm-line"><span>' . esc_html((string) ($item['name'] ?? '')) . '</span>';
            $html .= self::form('update', '<input type="hidden" name="item_id" value="' . $id . '"><input type="number" name="quantity" min="1" value="' . esc_attr((string) ($item['quantity'] ?? 1)) . '"><button type="submit">' . esc_html__('Update', 'onecomm') . '</button>');
            $html .= self::form('remove', '<input type="hidden" name="item_id" value="' . $id . '"><button type="submit">' . esc_html__('Remove', 'onecomm') . '</button>') . '</div>';
        }
        return $html . '<strong>' . esc_html__('Total', 'onecomm') . ': ' . esc_html((string) ($cart['totals']['total'] ?? '')) . ' ' . esc_html((string) ($cart['currency'] ?? '')) . '</strong></section>';
    }

    private static function checkoutMarkup(array $checkout): string
    {
        $fields = '';
        foreach (['first_name', 'last_name', 'email', 'phone', 'address1', 'address2', 'city', 'state', 'postal_code', 'country'] as $name) {
            $fields .= '<label>' . esc_html(ucwords(str_replace('_', ' ', $name))) . ' <input type="' . ($name === 'email' ? 'email' : 'text') . '" name="' . $name . '"' . ($name === 'country' ? ' maxlength="2"' : '') . '></label>';
        }
        $html = '<section class="onecomm-checkout"><h2>' . esc_html__('Checkout preparation', 'onecomm') . '</h2>' . self::form('details', $fields . '<button type="submit">' . esc_html__('Save checkout details', 'onecomm') . '</button>');
        $html .= '<h3>' . esc_html__('Shipping', 'onecomm') . '</h3>';
        foreach (($checkout['shippingOptions'] ?? []) as $o) $html .= self::form('shipping', '<input type="hidden" name="selection_id" value="' . esc_attr((string) ($o['id'] ?? '')) . '"><button type="submit">' . esc_html((string) ($o['name'] ?? '')) . ' — ' . esc_html((string) ($o['amount'] ?? '')) . ' ' . esc_html((string) ($o['currency'] ?? '')) . '</button>');
        $html .= '<h3>' . esc_html__('Payment method', 'onecomm') . '</h3>';
        foreach (($checkout['paymentMethods'] ?? []) as $m) $html .= self::form('payment', '<input type="hidden" name="selection_id" value="' . esc_attr((string) ($m['id'] ?? '')) . '"><button type="submit">' . esc_html((string) ($m['name'] ?? '')) . '</button>');
        $selected = null; foreach (($checkout['paymentMethods'] ?? []) as $method) if (($method['id'] ?? null) === ($checkout['selectedPaymentMethodId'] ?? null)) $selected = $method;
        $canPlace = ($checkout['ready'] ?? false) && (($selected['capabilities']['requiresHostedCheckout'] ?? null) === false) && (($selected['capabilities']['canPlaceOrder'] ?? null) === true);
        $status = ($checkout['ready'] ?? false) ? __('Checkout is prepared. Payment has not been collected.', 'onecomm') : __('More checkout details or selections are required.', 'onecomm');
        if ($canPlace) $html .= self::form('order', '<button type="submit">' . esc_html__('Place pending order', 'onecomm') . '</button>');
        return $html . '<p class="onecomm-readiness">' . esc_html($status) . '</p></section>';
    }

    private static function form(string $operation, string $contents): string
    {
        return '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="onecomm_cart"><input type="hidden" name="operation" value="' . esc_attr($operation) . '">' . wp_nonce_field(self::NONCE, '_wpnonce', true, false) . $contents . '</form>';
    }

    private static function checkoutInput(): array
    {
        $value = static fn (string $key, int $max): string => mb_substr(sanitize_text_field((string) ($_POST[$key] ?? '')), 0, $max);
        $contact = ['firstName' => $value('first_name', 100), 'lastName' => $value('last_name', 100), 'email' => sanitize_email((string) ($_POST['email'] ?? '')), 'phone' => $value('phone', 40)];
        $address = $contact + ['address1' => $value('address1', 200), 'address2' => $value('address2', 200), 'city' => $value('city', 100), 'state' => $value('state', 100), 'postalCode' => $value('postal_code', 30), 'country' => strtoupper($value('country', 2))];
        return ['customerInfo' => $contact, 'billingAddress' => $address, 'shippingAddress' => $address + ['sameAsBilling' => true]];
    }

    private static function positivePost(string $key): int { return max(1, absint($_POST[$key] ?? 1)); }
    private static function uuidPost(string $key): string
    {
        $v = sanitize_text_field((string) ($_POST[$key] ?? ''));
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di', $v) === 1 ? $v : '';
    }
    private static function selectionPost(string $key): string
    {
        return mb_substr(sanitize_text_field((string) ($_POST[$key] ?? '')), 0, 200);
    }
    private static function cartToken(): ?string { $v = (string) ($_COOKIE[self::COOKIE] ?? ''); return self::isCartToken($v) ? $v : null; }
    private static function isCartToken(string $v): bool { return preg_match('/^hc_[A-Za-z0-9_-]{43}$/D', $v) === 1; }
    private static function intentKey(string $token): string { return 'onecomm_order_intent_' . hash('sha256', $token); }
    private static function confirmationKey(string $token): string { return 'onecomm_order_confirmation_' . hash('sha256', $token); }
    private static function setCartToken(string $token): void
    {
        setcookie(self::COOKIE, $token, ['expires' => time() + DAY_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax']);
        $_COOKIE[self::COOKIE] = $token;
    }
    private static function redirect(string $state): never
    {
        wp_safe_redirect(add_query_arg('onecomm', $state, wp_get_referer() ?: home_url('/')), 303);
        exit;
    }
}
