<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Halaqah extends Model
{
    protected $fillable = ['nama'];

    public function santri()
    {
        return $this->hasMany(Santri::class);
    }
}
