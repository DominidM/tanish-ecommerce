<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->index();
            $table->string('event_id', 191)->nullable()->unique();
            $table->unsignedBigInteger('woo_order_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->json('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
