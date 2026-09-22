<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Setoran */
class SetoranResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'santri'  => $this->santri ? ['id' => $this->santri->id, 'nama' => $this->santri->nama] : null,
            'kelas'   => $this->santri?->kelas?->kelas,
            'juz'     => $this->juz,
            'baris'   => $this->baris,
            'tanggal' => $this->tanggal?->toDateString(),
        ];
    }
}
