<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'service_order_id')) {
                $table->unsignedBigInteger('service_order_id')->nullable()->after('customer_id');
                $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('set null');
                $table->index('service_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'service_order_id')) {
                $table->dropForeign(['service_order_id']);
                $table->dropIndex(['service_order_id']);
                $table->dropColumn('service_order_id');
            }
        });
    }
};


