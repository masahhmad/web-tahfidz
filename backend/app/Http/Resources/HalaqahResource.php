<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Halaqah */
class HalaqahResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'nama'         => $this->nama,
            'jumlah_siswa' => $this->santri_count ?? $this->santri()->count(),
        ];
    }
}
