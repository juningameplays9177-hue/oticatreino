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
                if (!Schema::hasColumn('payables', 'is_recurring')) {
                    $table->boolean('is_recurring')->default(false)->after('status');
                }
                if (!Schema::hasColumn('payables', 'recurring_type')) {
                    $table->string('recurring_type', 20)->nullable()->after('is_recurring'); // monthly, quarterly, yearly, etc
                }
                if (!Schema::hasColumn('payables', 'recurring_end_date')) {
                    $table->date('recurring_end_date')->nullable()->after('recurring_type');
                }
                if (!Schema::hasColumn('payables', 'installments')) {
                    $table->integer('installments')->default(1)->after('recurring_end_date');
                }
                if (!Schema::hasColumn('payables', 'installment_number')) {
                    $table->integer('installment_number')->default(1)->after('installments');
                }
                if (!Schema::hasColumn('payables', 'parent_payable_id')) {
                    $table->foreignId('parent_payable_id')->nullable()->after('installment_number')->constrained('payables')->onDelete('cascade');
                }
                if (!Schema::hasColumn('payables', 'recurring_group_id')) {
                    $table->string('recurring_group_id', 50)->nullable()->after('parent_payable_id')->index();
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
                if (Schema::hasColumn('payables', 'recurring_group_id')) {
                    $table->dropColumn('recurring_group_id');
                }
                if (Schema::hasColumn('payables', 'parent_payable_id')) {
                    $table->dropForeign(['parent_payable_id']);
                    $table->dropColumn('parent_payable_id');
                }
                if (Schema::hasColumn('payables', 'installment_number')) {
                    $table->dropColumn('installment_number');
                }
                if (Schema::hasColumn('payables', 'installments')) {
                    $table->dropColumn('installments');
                }
                if (Schema::hasColumn('payables', 'recurring_end_date')) {
                    $table->dropColumn('recurring_end_date');
                }
                if (Schema::hasColumn('payables', 'recurring_type')) {
                    $table->dropColumn('recurring_type');
                }
                if (Schema::hasColumn('payables', 'is_recurring')) {
                    $table->dropColumn('is_recurring');
                }
            });
        }
    }
};

