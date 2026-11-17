<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['service_orders', 'sales'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('payment_method', 16)->nullable();
                $table->string('payment_bank', 50)->nullable();
                $table->timestamp('paid_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['service_orders', 'sales'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn(['payment_method', 'payment_bank', 'paid_at']));
        }
    }
};
