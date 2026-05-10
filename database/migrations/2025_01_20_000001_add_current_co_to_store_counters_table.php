<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_counters', function (Blueprint $table) {
            $table->unsignedInteger('current_co')->default(0)->after('current_os');
        });
    }

    public function down(): void
    {
        Schema::table('store_counters', function (Blueprint $table) {
            $table->dropColumn('current_co');
        });
    }
};


