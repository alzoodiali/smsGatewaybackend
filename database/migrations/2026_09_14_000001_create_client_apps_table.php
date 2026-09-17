<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('app_id', 50)->unique();
            $table->string('api_key_hash');
            $table->string('api_key_prefix', 10)->index();
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            $table->text('description')->nullable();
            $table->unsignedInteger('rate_limit_per_minute')->default(30);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_apps');
    }
};
