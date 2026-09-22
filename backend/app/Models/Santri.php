<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Santri extends Model
{
    // Riwayat presensi/setoran/ujian tidak ikut terhapus saat siswa dihapus.
    use SoftDeletes;

    protected $fillable = [
        'nama',
        'nisn',
        'kategori',
        'jumlah_hafalan',
        'kelas_id',
        'guru_id',
        'halaqah_id',
    ];

    protected function casts(): array
    {
        return ['jumlah_hafalan' => 'integer'];
    }

    public function pengampu()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function halaqah()
    {
        return $this->belongsTo(Halaqah::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function presensi()
    {
        return $this->hasMany(PresensiSiswa::class);
    }

    public function setoran()
    {
        return $this->hasMany(Setoran::class);
    }

    public function kenaikanJuz()
    {
        return $this->hasMany(KenaikanJuz::class);
    }

    /**
     * Row-level scope: guru_halaqah hanya melihat siswa yang ia ampu, role lain melihat semua.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role === 'guru_halaqah'
            ? $query->where('santris.guru_id', $user->id)
            : $query;
    }

    public function isTaughtBy(User $user): bool
    {
        return $this->guru_id !== null && (int) $this->guru_id === (int) $user->id;
    }
}
