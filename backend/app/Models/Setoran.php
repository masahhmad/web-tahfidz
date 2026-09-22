<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setoran extends Model
{
    protected $fillable = [
        'santri_id',
        'guru_id',
        'juz',
        'baris',
        'tanggal',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d'];
    }

    public function santri()
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
