<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_app_id')->nullable()->constrained('client_apps')->onDelete('set null');
            $table->foreignId('gateway_id')->nullable()->constrained('sms_gateways')->onDelete('set null');
            $table->string('request_id', 50)->unique();
            $table->string('phone', 20);
            $table->text('message');
            $table->enum('type', ['otp', 'notification', 'custom'])->default('otp');
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('phone');
            $table->index(['status', 'locked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
