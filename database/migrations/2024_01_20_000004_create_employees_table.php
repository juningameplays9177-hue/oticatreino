<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('name', 190);
            $table->unsignedBigInteger('role_func_id')->nullable();
            $table->string('rg', 40)->nullable();
            $table->char('cpf_clean', 11)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->char('zip_code', 8)->nullable();
            $table->string('address', 190)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->char('state', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->foreign('role_func_id')->references('id')->on('job_functions')->onDelete('set null');
            
            $table->index(['company_id', 'store_id', 'is_active'], 'idx_employees_company_store_active');
            $table->index('cpf_clean', 'idx_employees_cpf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

