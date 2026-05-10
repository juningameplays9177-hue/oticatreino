<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('doctor_name', 190)->nullable();
            $table->date('valid_until')->nullable();
            $table->string('attachment_path', 255)->nullable();
            
            // LONGE - OD
            $table->decimal('longe_esferico_od', 5, 2)->nullable();
            $table->decimal('longe_cilindrico_od', 5, 2)->nullable();
            $table->smallInteger('longe_eixo_od')->nullable();
            $table->decimal('longe_altura_od', 5, 2)->nullable();
            $table->decimal('longe_dnp_od', 5, 2)->nullable();
            
            // LONGE - OE
            $table->decimal('longe_esferico_oe', 5, 2)->nullable();
            $table->decimal('longe_cilindrico_oe', 5, 2)->nullable();
            $table->smallInteger('longe_eixo_oe')->nullable();
            $table->decimal('longe_altura_oe', 5, 2)->nullable();
            $table->decimal('longe_dnp_oe', 5, 2)->nullable();
            
            // PERTO - OD
            $table->decimal('perto_esferico_od', 5, 2)->nullable();
            $table->decimal('perto_cilindrico_od', 5, 2)->nullable();
            $table->smallInteger('perto_eixo_od')->nullable();
            $table->decimal('perto_altura_od', 5, 2)->nullable();
            $table->decimal('perto_dnp_od', 5, 2)->nullable();
            
            // PERTO - OE
            $table->decimal('perto_esferico_oe', 5, 2)->nullable();
            $table->decimal('perto_cilindrico_oe', 5, 2)->nullable();
            $table->smallInteger('perto_eixo_oe')->nullable();
            $table->decimal('perto_altura_oe', 5, 2)->nullable();
            $table->decimal('perto_dnp_oe', 5, 2)->nullable();
            
            $table->decimal('adicao', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index('client_id');
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};

