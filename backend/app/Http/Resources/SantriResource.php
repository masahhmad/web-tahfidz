<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Santri */
class SantriResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nama'           => $this->nama,
            'nisn'           => $this->nisn,
            'kategori'       => $this->kategori,
            'kelas'          => $this->kelas ? ['id' => $this->kelas->id, 'nama' => $this->kelas->kelas] : null,
            'jumlah_hafalan' => $this->jumlah_hafalan,
            'pengampu'       => $this->pengampu ? ['id' => $this->pengampu->id, 'nama' => $this->pengampu->username] : null,
            'halaqah'        => $this->halaqah ? ['id' => $this->halaqah->id, 'nama' => $this->halaqah->nama] : null,
        ];
    }
}
