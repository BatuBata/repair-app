<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('category', 50)->change();
        });
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('name', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        $defaults = ['bensin', 'konsumsi', 'gaji', 'lainnya'];
        if (DB::table('expenses')->whereNotIn('category', $defaults)->exists()) {
            throw new LogicException('Rollback ditolak: kategori pengeluaran tambahan masih tersimpan pada transaksi.');
        }
        Schema::table('expenses', function (Blueprint $table) use ($defaults) {
            $table->enum('category', $defaults)->change();
        });
        Schema::dropIfExists('expense_categories');
    }
};
