<?php

namespace Tests\Feature;

use App\Exceptions\InventoryException;
use App\Models\InventoryMovement;
use App\Models\ProductSnapshot;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entry_increases_stock_and_records_a_movement(): void
    {
        Http::fake([
            'woo.test/*' => Http::sequence()
                ->push($this->product(stock: 10))
                ->push($this->product(stock: 15)),
        ]);

        $movement = app(InventoryService::class)->apply(21, InventoryMovement::TYPE_ENTRY, 5, 'Compra a proveedor');

        $this->assertSame('5.00', $movement->quantity_change);
        $this->assertSame('10.00', $movement->stock_before);
        $this->assertSame('15.00', $movement->stock_after);
        $this->assertSame('15.00', ProductSnapshot::firstOrFail()->stock_quantity);
    }

    public function test_an_exit_cannot_exceed_available_stock(): void
    {
        Http::fake(['woo.test/*' => Http::response($this->product(stock: 3))]);

        $this->expectException(InventoryException::class);

        try {
            app(InventoryService::class)->apply(21, InventoryMovement::TYPE_EXIT, 10, 'Venta');
        } finally {
            $this->assertDatabaseCount('inventory_movements', 0);
        }
    }

    public function test_an_adjustment_sets_the_final_stock(): void
    {
        Http::fake([
            'woo.test/*' => Http::sequence()
                ->push($this->product(stock: 10))
                ->push($this->product(stock: 4)),
        ]);

        $movement = app(InventoryService::class)->apply(21, InventoryMovement::TYPE_ADJUSTMENT, 4, 'Conteo físico');

        $this->assertSame('-6.00', $movement->quantity_change);
        $this->assertSame('4.00', $movement->stock_after);
    }

    public function test_the_movement_records_the_authenticated_user(): void
    {
        Http::fake([
            'woo.test/*' => Http::sequence()
                ->push($this->product(stock: 10))
                ->push($this->product(stock: 12)),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ALMACEN]);

        $movement = app(InventoryService::class)->apply(21, InventoryMovement::TYPE_ENTRY, 2, 'Reposición', $user->id);

        $this->assertSame($user->id, $movement->user_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function product(float $stock): array
    {
        return [
            'id' => 21,
            'name' => 'Polo TANISH',
            'sku' => 'POLO-001',
            'type' => 'simple',
            'status' => 'publish',
            'manage_stock' => true,
            'stock_quantity' => $stock,
            'stock_status' => $stock > 0 ? 'instock' : 'outofstock',
        ];
    }
}
