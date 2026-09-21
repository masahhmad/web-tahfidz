<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedTinyInteger('target_hafalan')->nullable()->change();
            $table->unique(['kelas', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique(['kelas', 'kategori']);
            $table->integer('target_hafalan')->nullable()->change();
        });
    }
};
