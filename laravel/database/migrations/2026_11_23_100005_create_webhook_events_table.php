<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id', 191)->nullable()->index();
            $table->char('payload_hash', 64)->unique();
            $table->string('topic', 64)->index();
            $table->string('resource', 64)->nullable();
            $table->string('event_action', 64)->nullable();
            $table->string('source', 191)->nullable();
            $table->json('payload');
            $table->string('status', 16)->default('received')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
