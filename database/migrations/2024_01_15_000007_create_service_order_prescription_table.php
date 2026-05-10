<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_prescription', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_order_id')->unique();
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->boolean('use_custom')->default(false);
            
            // Campos customizados (copiados de prescriptions quando use_custom=1)
            $table->string('custom_doctor_name', 190)->nullable();
            $table->date('custom_valid_until')->nullable();
            $table->string('custom_attachment_path', 255)->nullable();
            
            // LONGE - OD
            $table->decimal('custom_longe_esferico_od', 5, 2)->nullable();
            $table->decimal('custom_longe_cilindrico_od', 5, 2)->nullable();
            $table->smallInteger('custom_longe_eixo_od')->nullable();
            $table->decimal('custom_longe_altura_od', 5, 2)->nullable();
            $table->decimal('custom_longe_dnp_od', 5, 2)->nullable();
            
            // LONGE - OE
            $table->decimal('custom_longe_esferico_oe', 5, 2)->nullable();
            $table->decimal('custom_longe_cilindrico_oe', 5, 2)->nullable();
            $table->smallInteger('custom_longe_eixo_oe')->nullable();
            $table->decimal('custom_longe_altura_oe', 5, 2)->nullable();
            $table->decimal('custom_longe_dnp_oe', 5, 2)->nullable();
            
            // PERTO - OD
            $table->decimal('custom_perto_esferico_od', 5, 2)->nullable();
            $table->decimal('custom_perto_cilindrico_od', 5, 2)->nullable();
            $table->smallInteger('custom_perto_eixo_od')->nullable();
            $table->decimal('custom_perto_altura_od', 5, 2)->nullable();
            $table->decimal('custom_perto_dnp_od', 5, 2)->nullable();
            
            // PERTO - OE
            $table->decimal('custom_perto_esferico_oe', 5, 2)->nullable();
            $table->decimal('custom_perto_cilindrico_oe', 5, 2)->nullable();
            $table->smallInteger('custom_perto_eixo_oe')->nullable();
            $table->decimal('custom_perto_altura_oe', 5, 2)->nullable();
            $table->decimal('custom_perto_dnp_oe', 5, 2)->nullable();
            
            $table->decimal('custom_adicao', 5, 2)->nullable();
            $table->text('custom_notes')->nullable();
            
            $table->timestamps();
            
            $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_prescription');
    }
};

