<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 30)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

