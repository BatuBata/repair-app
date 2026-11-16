<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TENANT_TABLES = [
        'users',
        'categories',
        'service_orders',
        'products',
        'sales',
        'expenses',
    ];

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('tenants')->insert([
            'id' => 1,
            'name' => DB::table('store_settings')->value('name') ?: 'Anugerah',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable();
            });
            DB::table($tableName)->update(['tenant_id' => 1]);
        }

        $this->replaceNumberSequences();
        $this->replaceStoreSettings();

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_name_unique');
            $table->unique(['tenant_id', 'name'], 'categories_tenant_name_unique');
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropUnique('service_orders_number_unique');
            $table->unique(['tenant_id', 'number'], 'service_orders_tenant_number_unique');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_number_unique');
            $table->unique(['tenant_id', 'number'], 'sales_tenant_number_unique');
        });

        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
                $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        $this->guardSingleTenantRollback();

        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_tenant_name_unique');
            $table->unique('name');
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropUnique('service_orders_tenant_number_unique');
            $table->unique('number');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_tenant_number_unique');
            $table->unique('number');
        });

        $this->restoreNumberSequences();
        $this->restoreStoreSettings();

        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        Schema::drop('tenants');
    }

    private function replaceNumberSequences(): void
    {
        Schema::create('number_sequences_tenant', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('prefix', 3);
            $table->char('period', 4);
            $table->unsignedInteger('last_seq')->default(0);
            $table->primary(['tenant_id', 'prefix', 'period']);
        });

        DB::table('number_sequences_tenant')->insertUsing(
            ['tenant_id', 'prefix', 'period', 'last_seq'],
            DB::table('number_sequences')->selectRaw('1, prefix, period, last_seq'),
        );
        Schema::drop('number_sequences');
        Schema::rename('number_sequences_tenant', 'number_sequences');
    }

    private function replaceStoreSettings(): void
    {
        Schema::create('store_settings_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->restrictOnDelete();
            $table->string('name')->default('');
            $table->text('address');
            $table->string('phone', 15)->default('');
            $table->string('logo_path')->nullable();
            $table->text('warranty_terms')->nullable();
            $table->unsignedInteger('default_warranty_days')->default(30);
        });

        DB::table('store_settings_tenant')->insertUsing(
            ['id', 'tenant_id', 'name', 'address', 'phone', 'logo_path', 'warranty_terms', 'default_warranty_days'],
            DB::table('store_settings')->selectRaw('id, 1, name, address, phone, logo_path, warranty_terms, default_warranty_days'),
        );
        Schema::drop('store_settings');
        Schema::rename('store_settings_tenant', 'store_settings');
    }

    private function guardSingleTenantRollback(): void
    {
        if (DB::table('tenants')->where('id', '!=', 1)->exists()) {
            throw new LogicException('Rollback fondasi tenant ditolak karena data tenant lain sudah ada.');
        }

        foreach ([...self::TENANT_TABLES, 'number_sequences', 'store_settings'] as $tableName) {
            if (DB::table($tableName)->where('tenant_id', '!=', 1)->exists()) {
                throw new LogicException("Rollback fondasi tenant ditolak karena {$tableName} berisi data tenant lain.");
            }
        }
    }

    private function restoreNumberSequences(): void
    {
        Schema::create('number_sequences_single', function (Blueprint $table) {
            $table->string('prefix', 3);
            $table->char('period', 4);
            $table->unsignedInteger('last_seq')->default(0);
            $table->primary(['prefix', 'period']);
        });
        DB::table('number_sequences_single')->insertUsing(
            ['prefix', 'period', 'last_seq'],
            DB::table('number_sequences')->select(['prefix', 'period', 'last_seq']),
        );
        Schema::drop('number_sequences');
        Schema::rename('number_sequences_single', 'number_sequences');
    }

    private function restoreStoreSettings(): void
    {
        Schema::create('store_settings_single', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary()->default(1);
            $table->string('name')->default('');
            $table->text('address');
            $table->string('phone', 15)->default('');
            $table->string('logo_path')->nullable();
            $table->text('warranty_terms')->nullable();
            $table->unsignedInteger('default_warranty_days')->default(30);
        });
        DB::table('store_settings_single')->insertUsing(
            ['id', 'name', 'address', 'phone', 'logo_path', 'warranty_terms', 'default_warranty_days'],
            DB::table('store_settings')->select(['id', 'name', 'address', 'phone', 'logo_path', 'warranty_terms', 'default_warranty_days']),
        );
        Schema::drop('store_settings');
        Schema::rename('store_settings_single', 'store_settings');
    }
};
