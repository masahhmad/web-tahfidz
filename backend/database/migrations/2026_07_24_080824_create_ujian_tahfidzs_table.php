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
        Schema::create('ujian_tahfidzs', function (Blueprint $table) {
            $table->id();
            $table->integer('juz');
            $table->integer('nilai');
            $table->integer('nilai_tambahan')->nullable();
            $table->boolean('status_diterima')->default(false);
            $table->foreignId('santri_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('penguji_id');
            $table->foreign('penguji_id')->nullable()->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ujian_tahfidzs');
    }
};
