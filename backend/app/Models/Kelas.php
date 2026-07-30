<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $fillable = [
        'kelas',
        'kategori',
        'target_hafalan'
    ];

    public function santri()
    {
        return $this->hasMany(Santri::class, 'kelas_id');
    }
}
