<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UjianTahfidz extends Model
{
    protected $fillable = [
        'juz',
        'nilai',
        'nilai_tambahan',
        'status_diterima',
        'santri_id',
        'penguji_id'
    ];
}
