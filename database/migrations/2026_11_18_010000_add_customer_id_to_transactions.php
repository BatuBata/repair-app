<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['service_orders', 'sales'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['service_orders', 'sales'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        }
    }
};
