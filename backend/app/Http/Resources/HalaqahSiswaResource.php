<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris tabel halaman Halaqah: kelas berupa teks ("7A"), bukan objek.
 *
 * @mixin \App\Models\Santri
 */
class HalaqahSiswaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nama'           => $this->nama,
            'kelas'          => $this->kelas?->kelas,
            'nisn'           => $this->nisn,
            'jumlah_hafalan' => $this->jumlah_hafalan,
            'pengampu'       => $this->pengampu ? ['id' => $this->pengampu->id, 'nama' => $this->pengampu->username] : null,
            'halaqah'        => $this->halaqah ? ['id' => $this->halaqah->id, 'nama' => $this->halaqah->nama] : null,
        ];
    }
}
