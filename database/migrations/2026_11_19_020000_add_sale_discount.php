<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->unsignedBigInteger('discount')->default(0));
    }

    public function down(): void
    {
        if (DB::table('sales')->where('discount', '>', 0)->exists()) {
            throw new LogicException('Rollback ditolak: masih ada penjualan dengan diskon.');
        }
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('discount'));
    }
};
