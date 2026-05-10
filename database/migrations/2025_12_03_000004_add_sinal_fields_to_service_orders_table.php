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
        if (Schema::hasTable('service_orders')) {
            Schema::table('service_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('service_orders', 'sinal_amount')) {
                    $table->decimal('sinal_amount', 10, 2)->nullable()->after('advance_value')->default(0);
                }
                if (!Schema::hasColumn('service_orders', 'sinal_method')) {
                    $table->string('sinal_method', 50)->nullable()->after('sinal_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('service_orders')) {
            Schema::table('service_orders', function (Blueprint $table) {
                if (Schema::hasColumn('service_orders', 'sinal_method')) {
                    $table->dropColumn('sinal_method');
                }
                if (Schema::hasColumn('service_orders', 'sinal_amount')) {
                    $table->dropColumn('sinal_amount');
                }
            });
        }
    }
};

