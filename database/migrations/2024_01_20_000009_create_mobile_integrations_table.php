<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('device_name', 120)->nullable();
            $table->string('api_key', 64)->unique();
            $table->string('token_label', 60)->nullable();
            $table->json('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->datetime('last_used_at')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_integrations');
    }
};

