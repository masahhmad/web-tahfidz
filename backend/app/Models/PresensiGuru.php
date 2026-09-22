<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiGuru extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'sesi',
        'status',
        'keterangan',
        'lokasi',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
