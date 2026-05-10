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
        if (Schema::hasTable('payables')) {
            Schema::table('payables', function (Blueprint $table) {
                if (!Schema::hasColumn('payables', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('company_id')->constrained('users')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payables')) {
            Schema::table('payables', function (Blueprint $table) {
                if (Schema::hasColumn('payables', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
            });
        }
    }
};

