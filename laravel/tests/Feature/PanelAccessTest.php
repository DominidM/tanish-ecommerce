<?php

namespace Tests\Feature;

use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_panel_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_the_login_page_uses_a_username_field(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Usuario');
    }

    public function test_a_user_can_authenticate_with_a_username(): void
    {
        $user = User::factory()->create([
            'username' => 'tanish_admin',
            'password' => 'secret-password',
        ]);

        $this->assertTrue(Auth::attempt([
            'username' => 'tanish_admin',
            'password' => 'secret-password',
        ]));

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_administrator_can_access_the_panel(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_all_resource_pages_render_for_an_administrator(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([
            '/admin',
            '/admin/product-snapshots',
            '/admin/order-snapshots',
            '/admin/customer-snapshots',
            '/admin/inventory-movements',
            '/admin/inventory-movements/create',
            '/admin/webhook-events',
            '/admin/analytics-events',
            '/admin/notification-logs',
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_a_sales_user_cannot_open_the_inventory_create_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VENTAS]);

        $this->actingAs($user)->get('/admin/inventory-movements/create')->assertForbidden();
    }

    public function test_an_unknown_role_cannot_access_the_panel(): void
    {
        $user = User::factory()->create(['role' => 'invitado']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_only_admin_and_warehouse_roles_can_create_inventory_movements(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ALMACEN]));
        $this->assertTrue(InventoryMovementResource::canCreate());

        $this->actingAs(User::factory()->create(['role' => User::ROLE_VENTAS]));
        $this->assertFalse(InventoryMovementResource::canCreate());

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->assertTrue(InventoryMovementResource::canCreate());
    }
}
