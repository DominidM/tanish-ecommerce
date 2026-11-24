<?php

namespace Tests\Feature;

use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WooCommerceClientTest extends TestCase
{
    public function test_it_authenticates_with_basic_auth_and_decodes_products(): void
    {
        Http::fake([
            'woo.test/*' => Http::response([
                ['id' => 21, 'name' => 'Polo TANISH'],
            ]),
        ]);

        $products = WooCommerceClient::fromConfig()->products();

        $this->assertSame(21, $products[0]['id']);

        Http::assertSent(function ($request): bool {
            $expected = 'Basic '.base64_encode('test-key:test-secret');

            return str_starts_with($request->url(), 'http://woo.test/wp-json/wc/v3/products')
                && $request->hasHeader('Authorization', $expected);
        });
    }

    public function test_it_paginates_until_a_short_page_is_returned(): void
    {
        Http::fake([
            'woo.test/*' => Http::sequence()
                ->push(array_fill(0, 100, ['id' => 1]))
                ->push([['id' => 2]]),
        ]);

        $pages = iterator_to_array(WooCommerceClient::fromConfig()->paginate('products'));

        $this->assertCount(2, $pages);
        $this->assertCount(100, $pages[0]);
        $this->assertCount(1, $pages[1]);
    }

    public function test_it_fails_without_credentials(): void
    {
        config([
            'woocommerce.consumer_key' => null,
            'woocommerce.consumer_secret' => null,
        ]);

        $this->expectExceptionMessage('no está configurada');

        WooCommerceClient::fromConfig()->products();
    }
}
