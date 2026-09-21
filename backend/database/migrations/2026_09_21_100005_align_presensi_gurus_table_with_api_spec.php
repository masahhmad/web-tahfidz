<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi_gurus', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->change();
            // Nullable: baris lama tidak punya sesi. Backend tetap mewajibkannya untuk data baru.
            $table->date('tanggal')->nullable()->after('user_id');
            $table->enum('sesi', ['pagi', 'siang', 'sore'])->nullable()->after('tanggal');
        });

        // Tanggal baris lama diambil dari waktu pembuatannya.
        DB::table('presensi_gurus')->select('id', 'created_at')->orderBy('id')->each(function ($row) {
            DB::table('presensi_gurus')->where('id', $row->id)
                ->update(['tanggal' => substr((string) $row->created_at, 0, 10) ?: null]);
        });

        Schema::table('presensi_gurus', function (Blueprint $table) {
            $table->unique(['user_id', 'tanggal', 'sesi']);
        });
    }

    public function down(): void
    {
        Schema::table('presensi_gurus', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'tanggal', 'sesi']);
            $table->dropColumn(['tanggal', 'sesi']);
        });
        Schema::table('presensi_gurus', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'izin', 'sakit'])->change();
        });
    }
};
