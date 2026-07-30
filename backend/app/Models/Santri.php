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
}
