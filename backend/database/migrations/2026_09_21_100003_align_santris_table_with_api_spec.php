<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // nim (integer) -> nisn (string): NISN bisa berawalan nol, integer membuangnya.
        Schema::table('santris', function (Blueprint $table) {
            $table->renameColumn('nim', 'nisn');
        });
        Schema::table('santris', function (Blueprint $table) {
            $table->string('nisn', 10)->change();
            $table->unsignedTinyInteger('jumlah_hafalan')->default(0)->change();
        });

        // Data lama yang tersimpan sebagai integer dikembalikan ke 10 digit.
        DB::table('santris')->select('id', 'nisn')->orderBy('id')->each(function ($row) {
            DB::table('santris')->where('id', $row->id)
                ->update(['nisn' => str_pad((string) $row->nisn, 10, '0', STR_PAD_LEFT)]);
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->unique('nisn');
            $table->foreignId('halaqah_id')->nullable()->after('guru_id')
                ->constrained('halaqahs')->nullOnDelete();
            $table->index('guru_id');
            $table->index('kelas_id');
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropIndex(['guru_id']);
            $table->dropIndex(['kelas_id']);
            $table->dropConstrainedForeignId('halaqah_id');
            $table->dropUnique(['nisn']);
        });
        Schema::table('santris', function (Blueprint $table) {
            $table->integer('jumlah_hafalan')->change();
            $table->integer('nisn')->change();
        });
        Schema::table('santris', function (Blueprint $table) {
            $table->renameColumn('nisn', 'nim');
        });
    }
};
