<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id')->nullable()->index();
            $table->string('idempotency_key')->unique();
            $table->string('recipient');
            $table->enum('channel', ['sms', 'email', 'push'])->index();
            $table->text('content');
            $table->enum('priority', ['high', 'normal', 'low'])->default('normal')->index();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'cancelled'])->default('pending')->index();
            $table->string('external_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
