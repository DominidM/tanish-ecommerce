<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('woo_product_id')->index();
            $table->string('movement_type', 16)->index();
            $table->decimal('quantity_change', 12, 2);
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->text('reason');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 32)->default('manual')->index();
            $table->string('reference', 191)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Idempotencia para movimientos originados por pedidos y conciliación.
            $table->unique(
                ['source', 'reference', 'woo_product_id', 'movement_type'],
                'inventory_movements_idempotency'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
