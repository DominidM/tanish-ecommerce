<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('woo_id')->unique();
            $table->string('sku')->nullable()->index();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('type', 32)->nullable();
            $table->string('status', 32)->nullable()->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('regular_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->boolean('manage_stock')->default(false);
            $table->decimal('stock_quantity', 12, 2)->nullable();
            $table->string('stock_status', 32)->nullable()->index();
            $table->json('category_ids')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('woo_created_at')->nullable();
            $table->timestamp('woo_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_snapshots');
    }
};
