<?php

namespace Tests\Feature;

use App\Models\ProductSnapshot;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_inserts_and_purges_demo_data(): void
    {
        ProductSnapshot::create([
            'woo_id' => 5001,
            'sku' => 'DEMO-1',
            'name' => 'Producto demo',
            'slug' => 'producto-demo',
            'type' => 'simple',
            'status' => 'publish',
            'price' => 25,
            'regular_price' => 25,
            'manage_stock' => true,
            'stock_quantity' => 30,
            'stock_status' => 'instock',
            'category_ids' => [1],
            'payload' => ['categories' => [['id' => 1, 'name' => 'Demo']]],
        ]);

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('order_snapshots', 5);
        $this->assertDatabaseCount('inventory_movements', 8);
        $this->assertDatabaseCount('analytics_events', 5);
        $this->assertDatabaseCount('notification_logs', 5);
        $this->assertDatabaseCount('webhook_events', 3);

        DemoDataSeeder::purge();

        $this->assertDatabaseCount('order_snapshots', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('analytics_events', 0);
        $this->assertDatabaseCount('notification_logs', 0);
        $this->assertDatabaseCount('webhook_events', 0);
    }
}
