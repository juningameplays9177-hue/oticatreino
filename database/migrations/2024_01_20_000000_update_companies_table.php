<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('tax_id_type', ['CPF', 'CNPJ'])->nullable()->after('code');
            $table->char('cpf_clean', 11)->nullable()->after('tax_id_type');
            $table->char('cnpj_clean', 14)->nullable()->after('cpf_clean');
            $table->string('legal_name', 190)->nullable()->after('cnpj_clean');
            $table->string('trade_name', 190)->nullable()->after('legal_name');
            $table->string('slug', 80)->nullable()->unique()->after('trade_name');
            $table->string('phone', 30)->nullable()->after('slug');
            $table->string('mobile', 30)->nullable()->after('phone');
            $table->string('contact_name', 120)->nullable()->after('mobile');
            $table->string('email', 190)->nullable()->after('contact_name');
            $table->char('zip_code', 8)->nullable()->after('email');
            $table->string('address', 190)->nullable()->after('zip_code');
            $table->string('number', 30)->nullable()->after('address');
            $table->string('complement', 120)->nullable()->after('number');
            $table->string('district', 120)->nullable()->after('complement');
            $table->string('city', 120)->nullable()->after('district');
            $table->char('state', 2)->nullable()->after('city');
            $table->string('logo_path', 255)->nullable()->after('state');
            $table->boolean('is_active')->default(true)->after('logo_path');
            
            // Remover colunas antigas se existirem
            if (Schema::hasColumn('companies', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('companies', 'active')) {
                $table->dropColumn('active');
            }
            
            $table->index(['slug', 'is_active'], 'idx_companies_slug_active');
            $table->index('cnpj_clean', 'idx_companies_cnpj');
            $table->index('cpf_clean', 'idx_companies_cpf');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('idx_companies_slug_active');
            $table->dropIndex('idx_companies_cnpj');
            $table->dropIndex('idx_companies_cpf');
            
            $table->dropColumn([
                'tax_id_type', 'cpf_clean', 'cnpj_clean', 'legal_name', 'trade_name',
                'slug', 'phone', 'mobile', 'contact_name', 'email', 'zip_code',
                'address', 'number', 'complement', 'district', 'city', 'state',
                'logo_path', 'is_active'
            ]);
            
            $table->string('name', 120);
            $table->boolean('active')->default(true);
        });
    }
};

