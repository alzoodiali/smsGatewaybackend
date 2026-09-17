<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('device_id', 100)->unique();
            $table->string('token_hash');
            $table->enum('status', ['online', 'offline', 'busy', 'disabled'])->default('offline');
            $table->unsignedTinyInteger('sim_slot')->default(1);
            $table->string('phone_number', 20)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateways');
    }
};
