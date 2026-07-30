<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Santri extends Model
{
    protected $fillable = [
        'nama',
        'nim',
        'kategori',
        'jumlah_hafalan',
        'kelas_id',
        'guru_id'
    ];

    public function guruTahfidz()
    {
        return $this->belongsTo(User::class);
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
}
