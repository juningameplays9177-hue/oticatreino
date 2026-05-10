<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->enum('type', ['PF', 'PJ'])->default('PF');
            $table->string('external_code', 60)->unique()->nullable();
            $table->string('origin', 80)->nullable();
            $table->string('name', 190);
            $table->string('nickname', 120)->nullable();
            $table->string('cpf_cnpj', 20)->unique()->nullable();
            $table->string('rg_ie', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('address', 190)->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement', 120)->nullable();
            $table->string('father_name', 190)->nullable();
            $table->string('mother_name', 190)->nullable();
            $table->string('guardian_name', 190)->nullable();
            $table->string('guardian_relation', 60)->nullable();
            $table->string('profession', 120)->nullable();
            $table->decimal('default_adjust_percent', 6, 2)->default(0.00);
            $table->string('income_family', 60)->nullable();
            $table->string('education_level', 60)->nullable();
            $table->enum('sex', ['M', 'F', 'NI'])->default('NI');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['name', 'nickname', 'city', 'district'], 'idx_clients_search1');
            $table->index('created_at', 'idx_clients_created');
            $table->index('origin', 'idx_clients_origin');
            $table->index('type', 'idx_clients_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
