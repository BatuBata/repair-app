<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique();
            $table->enum('role', ['admin', 'teknisi'])->default('teknisi');
            $table->boolean('is_active')->default(true);
            $table->string('email')->nullable()->change();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->string('prefix', 3);
            $table->char('period', 4);
            $table->unsignedInteger('last_seq')->default(0);
            $table->primary(['prefix', 'period']);
        });
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 15)->index();
            $table->text('customer_address')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->text('item_description')->nullable();
            $table->string('complaint', 500);
            $table->enum('status', ['diterima', 'dikerjakan', 'selesai', 'diambil', 'batal'])->default('diterima')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->date('invoiced_on')->nullable()->index();
            $table->unsignedInteger('warranty_days')->nullable();
            $table->date('warranty_until')->nullable()->index();
            $table->boolean('unrepairable')->default(false);
            $table->date('picked_up_on')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->dateTime('warranty_contacted_at')->nullable();
            $table->string('tracking_token', 64)->unique();
            $table->timestamps();
        });
        Schema::create('service_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['part', 'jasa']);
            $table->string('description');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('price');
            $table->unsignedInteger('cost');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('cost_price');
            $table->unsignedInteger('sell_price');
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('stock_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('cost_price');
            $table->date('entry_date');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('sold_on')->index();
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 15)->nullable();
            $table->unsignedInteger('warranty_days')->nullable();
            $table->date('warranty_until')->nullable()->index();
            $table->dateTime('warranty_contacted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('price');
            $table->unsignedInteger('cost');
            $table->timestamps();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on')->index();
            $table->enum('category', ['bensin', 'konsumsi', 'gaji', 'lainnya']);
            $table->text('description')->nullable();
            $table->unsignedInteger('amount');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('store_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary()->default(1);
            $table->string('name')->default('');
            $table->text('address');
            $table->string('phone', 15)->default('');
            $table->string('logo_path')->nullable();
            $table->text('warranty_terms')->nullable();
            $table->unsignedInteger('default_warranty_days')->default(30);
        });
    }

    public function down(): void
    {
        foreach (['store_settings', 'expenses', 'sale_items', 'sales', 'stock_entries', 'products', 'service_order_items', 'service_orders', 'number_sequences', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
