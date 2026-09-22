<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Kelas */
class KelasResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'kelas'          => $this->kelas,
            'kategori'       => $this->kategori,
            'target_hafalan' => $this->target_hafalan,
            'jumlah_siswa'   => $this->santri_count ?? $this->santri()->count(),
        ];
    }
}
