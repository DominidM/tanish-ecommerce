<?php

namespace Tests\Feature;

use App\Models\ProductSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_lists_published_products(): void
    {
        $product = $this->product(['name' => 'Cuaderno A4']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Cuaderno A4')
            ->assertSee('TANISH');
    }

    public function test_the_catalog_can_be_searched(): void
    {
        $this->product(['name' => 'Lapicero gel', 'sku' => 'LAP-1']);
        $this->product(['name' => 'Mochila escolar', 'sku' => 'MOC-1', 'woo_id' => 12, 'slug' => 'mochila']);

        $this->get('/tienda?q=Lapicero')
            ->assertOk()
            ->assertSee('Lapicero gel')
            ->assertDontSee('Mochila escolar');
    }

    public function test_a_product_detail_page_is_rendered(): void
    {
        $product = $this->product(['name' => 'Grapadora metálica', 'slug' => 'grapadora']);

        $this->get('/producto/grapadora')
            ->assertOk()
            ->assertSee('Grapadora metálica');

        $this->get('/producto/no-existe')->assertNotFound();
    }

    public function test_products_can_be_added_and_removed_from_the_quote(): void
    {
        $product = $this->product(['woo_id' => 25, 'slug' => 'cuaderno', 'price' => 10]);

        $this->post(route('quote.add', $product), ['quantity' => 2])
            ->assertRedirect();

        $this->get('/cotizacion')
            ->assertOk()
            ->assertSee('Cuaderno de prueba')
            ->assertSee('20.00');

        $this->post(route('quote.remove', $product))->assertRedirect(route('quote.show'));

        $this->get('/cotizacion')->assertSee('Tu cotización está vacía');
    }

    public function test_the_quote_can_be_sent_to_whatsapp(): void
    {
        $product = $this->product(['woo_id' => 25, 'slug' => 'cuaderno', 'price' => 10]);

        $this->post(route('quote.add', $product), ['quantity' => 1]);

        $response = $this->get(route('quote.whatsapp'));

        $response->assertRedirect();

        $location = $response->headers->get('Location');

        $this->assertStringContainsString('https://wa.me/'.config('tanish.whatsapp_number'), $location);
        $this->assertStringContainsString(rawurlencode('Cuaderno de prueba'), $location);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function product(array $overrides = []): ProductSnapshot
    {
        return ProductSnapshot::create(array_merge([
            'woo_id' => 11,
            'sku' => 'SKU-1',
            'name' => 'Cuaderno de prueba',
            'slug' => 'cuaderno-de-prueba',
            'type' => 'simple',
            'status' => 'publish',
            'price' => 10,
            'regular_price' => 10,
            'manage_stock' => true,
            'stock_quantity' => 20,
            'stock_status' => 'instock',
            'category_ids' => [1],
            'payload' => [
                'description' => 'Descripción de prueba',
                'categories' => [['id' => 1, 'name' => 'Cuadernos']],
            ],
        ], $overrides));
    }
}
