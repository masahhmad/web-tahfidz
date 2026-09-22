<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setorans', function (Blueprint $table) {
            // Nullable: baris lama tidak punya juz. Backend tetap mewajibkannya untuk data baru.
            $table->unsignedTinyInteger('juz')->nullable()->after('baris');
            $table->date('tanggal')->nullable()->after('juz');
            $table->foreignId('guru_id')->nullable()->after('santri_id')->constrained('users')->nullOnDelete();
        });

        DB::table('setorans')->select('id', 'created_at')->orderBy('id')->each(function ($row) {
            DB::table('setorans')->where('id', $row->id)
                ->update(['tanggal' => substr((string) $row->created_at, 0, 10) ?: null]);
        });

        Schema::table('setorans', function (Blueprint $table) {
            $table->index(['santri_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::table('setorans', function (Blueprint $table) {
            $table->dropIndex(['santri_id', 'tanggal']);
            $table->dropConstrainedForeignId('guru_id');
            $table->dropColumn(['juz', 'tanggal']);
        });
    }
};
