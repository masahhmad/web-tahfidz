<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiSiswa extends Model
{
    protected $fillable = [
        'status',
        'keterangan',
        'santri_id'
    ];
}
