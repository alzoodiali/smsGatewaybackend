<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained('sms_gateways')->onDelete('cascade');
            $table->string('event', 50);
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_logs');
    }
};
