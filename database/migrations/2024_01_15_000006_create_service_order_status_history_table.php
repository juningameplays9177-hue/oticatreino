<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_order_id');
            $table->enum('from_status', ['REGISTRADA', 'EM_PRODUCAO', 'PRONTA', 'ENTREGUE', 'CANCELADA', 'PERDA', 'VENDIDA', 'NAO_VENDIDA'])->nullable();
            $table->enum('to_status', ['REGISTRADA', 'EM_PRODUCAO', 'PRONTA', 'ENTREGUE', 'CANCELADA', 'PERDA', 'VENDIDA', 'NAO_VENDIDA']);
            $table->unsignedBigInteger('changed_by');
            $table->datetime('changed_at');
            $table->string('note', 190)->nullable();
            $table->timestamps();
            
            $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('restrict');
            $table->index('service_order_id');
            $table->index('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_status_history');
    }
};

