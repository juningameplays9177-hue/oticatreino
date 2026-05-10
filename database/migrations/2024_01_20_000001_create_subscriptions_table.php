<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('plan_code', ['GESTAO', 'FISCAL', 'FISCAL_SAT', 'VENDA_MAIS', 'VENDA_MAIS_SAT'])->default('GESTAO');
            $table->enum('contract_type', ['ATIVACAO_1x400', 'ATIVACAO_2x300', 'ATIVACAO_3x200', 'ATIVACAO_4x150', 'ATIVACAO_6x100', 'SEM_ATIVACAO'])->default('SEM_ATIVACAO');
            $table->decimal('activation_fee_total', 12, 2)->default(0.00);
            $table->decimal('monthly_fee', 12, 2)->default(0.00);
            $table->date('started_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->enum('status', ['ATIVA', 'PENDENTE', 'SUSPENSA', 'CANCELADA'])->default('PENDENTE');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'status']);
            $table->index('plan_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

