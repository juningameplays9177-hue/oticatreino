<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('os_number', 30)->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('store_id');
            $table->enum('os_type', ['OTICA'])->default('OTICA');
            $table->datetime('registered_at');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('source', 80)->nullable();
            $table->date('delivery_date')->nullable();
            $table->time('delivery_time')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['REGISTRADA', 'EM_PRODUCAO', 'PRONTA', 'ENTREGUE', 'CANCELADA', 'PERDA', 'VENDIDA', 'NAO_VENDIDA'])->default('REGISTRADA');
            $table->enum('advance_type', ['SEM', 'TOTAL', 'PARCIAL'])->default('SEM');
            $table->decimal('advance_value', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_value', 12, 2)->default(0.00);
            $table->decimal('total_value', 12, 2)->default(0.00);
            $table->decimal('min_value_filter', 12, 2)->virtualAs('COALESCE(total_value, 0)');
            $table->string('cancel_reason', 190)->nullable();
            $table->string('loss_reason', 190)->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('restrict');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            
            $table->index(['store_id', 'registered_at']);
            $table->index('status');
            $table->index('client_id');
            $table->index('os_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};

