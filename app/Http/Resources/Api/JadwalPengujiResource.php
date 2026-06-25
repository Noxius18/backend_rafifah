<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalPengujiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'aspek_penguji' => $this->aspek_penguji,
            'nilai' => $this->nilai,
            'catatan_penguji' => $this->catatan_penguji,
            'panitia' => $this->whenLoaded('panitia', fn () => [
                'id_panitia' => $this->panitia?->id_panitia,
                'nama_lengkap' => $this->panitia?->nama_lengkap,
            ]),
        ];
    }
}
