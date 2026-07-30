<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kenaikan_juzs', function (Blueprint $table) {
            $table->id();
            $table->integer('juz');
            $table->integer('nilai_setoran');
            $table->integer('nilai_soal');
            $table->integer('nilai_tambahan')->nullable();
            $table->foreignId('santri_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kenaikan_juzs');
    }
};
