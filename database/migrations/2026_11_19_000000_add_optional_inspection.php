<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->enum('status', ['diterima', 'pengecekan', 'dikerjakan', 'selesai', 'diambil', 'batal'])->default('diterima')->change();
            $table->text('inspection_result')->nullable();
            $table->text('action_plan')->nullable();
            $table->dateTime('inspection_started_at')->nullable();
            $table->dateTime('work_started_at')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('service_orders')->where('status', 'pengecekan')->exists()) {
            throw new RuntimeException('Selesaikan atau batalkan TTS dalam Pengecekan sebelum rollback.');
        }
        Schema::table('service_orders', function (Blueprint $table) {
            $table->enum('status', ['diterima', 'dikerjakan', 'selesai', 'diambil', 'batal'])->default('diterima')->change();
            $table->dropColumn(['inspection_result', 'action_plan', 'inspection_started_at', 'work_started_at']);
        });
    }
};
