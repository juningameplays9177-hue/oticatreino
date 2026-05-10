<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('license_status', ['ATIVA', 'PENDENTE', 'CANCELADA', 'EXPIRADA'])->default('PENDENTE');
            $table->datetime('cert_valid_from')->nullable();
            $table->datetime('cert_valid_to')->nullable();
            $table->enum('cert_status', ['VALIDO', 'EXPIRADO', 'NAO_CONFIGURADO'])->default('NAO_CONFIGURADO');
            $table->datetime('last_check_at')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index('license_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_licenses');
    }
};

