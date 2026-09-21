<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KenaikanJuz extends Model
{
    // `nilai_setoran` = "Nilai Hafalan" di kontrak API.
    protected $fillable = [
        'santri_id',
        'penguji_id',
        'juz',
        'nilai_setoran',
        'nilai_soal',
        'nilai_tambahan',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    public function penguji()
    {
        return $this->belongsTo(User::class, 'penguji_id');
    }
}
