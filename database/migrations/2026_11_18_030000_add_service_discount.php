<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', fn (Blueprint $table) => $table->unsignedBigInteger('discount')->default(0));
    }

    public function down(): void
    {
        Schema::table('service_orders', fn (Blueprint $table) => $table->dropColumn('discount'));
    }
};
