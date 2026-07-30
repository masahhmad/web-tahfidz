<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setoran extends Model
{
    protected $fillable = [
        'baris',
        'santri_id'
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }
}
