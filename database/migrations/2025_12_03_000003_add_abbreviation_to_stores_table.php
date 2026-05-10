<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (!Schema::hasColumn('stores', 'abbreviation')) {
                    $table->string('abbreviation', 10)->nullable()->after('code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (Schema::hasColumn('stores', 'abbreviation')) {
                    $table->dropColumn('abbreviation');
                }
            });
        }
    }
};

