<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Baris ujian dibuat dulu, dinilai kemudian -> nilai boleh kosong.
        Schema::table('kenaikan_juzs', function (Blueprint $table) {
            $table->integer('nilai_setoran')->nullable()->change();
            $table->integer('nilai_soal')->nullable()->change();
        });
        Schema::table('kenaikan_juzs', function (Blueprint $table) {
            $table->foreignId('penguji_id')->nullable()->after('santri_id')->constrained('users')->nullOnDelete();
            $table->unique(['santri_id', 'juz']);
        });
    }

    public function down(): void
    {
        Schema::table('kenaikan_juzs', function (Blueprint $table) {
            $table->dropUnique(['santri_id', 'juz']);
            $table->dropConstrainedForeignId('penguji_id');
        });
        Schema::table('kenaikan_juzs', function (Blueprint $table) {
            $table->integer('nilai_setoran')->nullable(false)->change();
            $table->integer('nilai_soal')->nullable(false)->change();
        });
    }
};
