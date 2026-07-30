<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiGuru extends Model
{
    protected $fillable = [
        'status',
        'keterangan',
        'lokasi',
        'user_id'
    ];

    public function presensi()
    {
        return $this->belongsTo(User::class);
    }
}
