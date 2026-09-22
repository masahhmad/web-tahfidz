<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'alpha' -> 'alpa'. 'tidur' dibiarkan di enum agar baris lama tidak hilang, tapi API tidak menerimanya lagi.
        Schema::table('presensi_siswas', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'sakit', 'izin', 'tidur', 'alpha', 'alpa'])->change();
        });
        DB::table('presensi_siswas')->where('status', 'alpha')->update(['status' => 'alpa']);
        Schema::table('presensi_siswas', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'sakit', 'izin', 'tidur', 'alpa'])->change();
            $table->date('tanggal')->nullable()->after('santri_id');
            $table->enum('sesi', ['pagi', 'siang', 'sore'])->nullable()->after('tanggal');
            $table->foreignId('guru_id')->nullable()->after('sesi')->constrained('users')->nullOnDelete();
        });

        DB::table('presensi_siswas')->select('id', 'created_at')->orderBy('id')->each(function ($row) {
            DB::table('presensi_siswas')->where('id', $row->id)
                ->update(['tanggal' => substr((string) $row->created_at, 0, 10) ?: null]);
        });

        Schema::table('presensi_siswas', function (Blueprint $table) {
            $table->unique(['santri_id', 'tanggal', 'sesi']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('presensi_siswas', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
            $table->dropUnique(['santri_id', 'tanggal', 'sesi']);
            $table->dropConstrainedForeignId('guru_id');
            $table->dropColumn(['tanggal', 'sesi']);
        });
        Schema::table('presensi_siswas', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'sakit', 'izin', 'tidur', 'alpha', 'alpa'])->change();
        });
        DB::table('presensi_siswas')->where('status', 'alpa')->update(['status' => 'alpha']);
        Schema::table('presensi_siswas', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'sakit', 'izin', 'tidur', 'alpha'])->change();
        });
    }
};
