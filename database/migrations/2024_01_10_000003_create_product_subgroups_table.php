<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_subgroups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->string('name', 120);
            $table->timestamps();
            
            $table->foreign('group_id')->references('id')->on('product_groups')->onDelete('cascade');
            $table->unique(['group_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_subgroups');
    }
};

