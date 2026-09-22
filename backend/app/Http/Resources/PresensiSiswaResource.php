<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PresensiSiswa */
class PresensiSiswaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'santri_id'  => $this->santri_id,
            'nama_siswa' => $this->santri?->nama,
            'halaqah_id' => $this->santri?->halaqah_id,
            'halaqah'    => $this->santri?->halaqah?->nama,
            'tanggal'    => $this->tanggal?->toDateString(),
            'sesi'       => $this->sesi,
            'status'     => $this->status,
            'keterangan' => $this->keterangan,
        ];
    }
}
