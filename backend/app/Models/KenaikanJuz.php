<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KenaikanJuz extends Model
{
    protected $fillable = [
        'juz',
        'nilai_setoran',
        'nilai_soal',
        'nilai_tambahan',
        'santri_id'
    ];
}
