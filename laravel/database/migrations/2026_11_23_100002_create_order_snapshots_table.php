<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('woo_id')->unique();
            $table->string('number', 64)->nullable()->index();
            $table->string('status', 32)->nullable()->index();
            $table->string('currency', 8)->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable()->index();
            $table->string('payment_method', 64)->nullable();
            $table->string('payment_method_title')->nullable();
            $table->timestamp('date_created')->nullable()->index();
            $table->timestamp('date_paid')->nullable();
            $table->json('line_items')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('stock_recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_snapshots');
    }
};
