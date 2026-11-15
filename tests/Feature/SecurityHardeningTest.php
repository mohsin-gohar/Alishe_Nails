<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the temporary deployment endpoints and for the
 * privacy of the public order-tracking endpoint.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_deployment_endpoints_no_longer_exist(): void
    {
        $uris = [
            '/_deploy',
            '/_deploy?token=anything',
            '/check-db-products',
            '/check-db-products?token=anything',
            '/fix-images',
            '/fix-images?token=anything',
        ];

        foreach ($uris as $uri) {
            $this->get($uri)->assertNotFound();
        }
    }

    public function test_order_tracking_json_never_exposes_private_order_data(): void
    {
        $order = Order::create([
            'user_id' => null,
            'order_number' => 'ALN-PRIVACY-1',
            'access_token' => 'super-secret-token',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'email' => 'aisha@example.com',
            'phone' => '+923001234567',
            'address' => 'House 12, Street 4',
            'city' => 'Karachi',
            'area' => 'DHA',
            'postal_code' => '75500',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TXN-99887766',
            'subtotal' => 2500,
            'shipping' => 200,
            'discount_amount' => 0,
            'total' => 2700,
            'status' => 'processing',
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Rosy Quartz Ombre',
            'shape' => 'Almond',
            'size' => 'Medium',
            'quantity' => 1,
            'price' => 2500,
            'line_total' => 2500,
        ]);

        $response = $this->postJson(route('track-order.search'), [
            'order_number' => 'ALN-PRIVACY-1',
            'identifier' => '+923001234567',
        ]);

        $response->assertOk();
        $response->assertJsonPath('order.order_number', 'ALN-PRIVACY-1');
        $response->assertJsonPath('order.items.0.name', 'Rosy Quartz Ombre');

        $payload = $response->json();

        $this->assertSame(
            ['order_number', 'status', 'payment_status', 'created_at', 'total', 'items'],
            array_keys($payload['order']),
        );
        $this->assertSame(
            ['name', 'quantity', 'price', 'line_total'],
            array_keys($payload['order']['items'][0]),
        );

        $json = $response->getContent();
        $this->assertStringNotContainsString('super-secret-token', $json);
        $this->assertStringNotContainsString('TXN-99887766', $json);
        $this->assertStringNotContainsString('aisha@example.com', $json);
        $this->assertStringNotContainsString('House 12', $json);
        $this->assertStringNotContainsString('+923001234567', $json);
    }

    public function test_order_model_hides_the_access_token_when_serialised(): void
    {
        $order = new Order(['order_number' => 'ALN-HIDDEN-1', 'access_token' => 'secret']);

        $this->assertArrayNotHasKey('access_token', $order->toArray());
        $this->assertArrayNotHasKey('transaction_reference', $order->toArray());
    }

    public function test_sitemap_returns_well_formed_xml(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $body = $response->getContent();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', ltrim($body));
        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringContainsString(route('shop.index'), $body);

        $xml = @simplexml_load_string($body);
        $this->assertNotFalse($xml, 'The sitemap must be valid, parseable XML.');
    }
}
