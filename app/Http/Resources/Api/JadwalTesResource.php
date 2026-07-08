<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalTesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_jadwal' => $this->kode_jadwal,
            'id_jadwal' => $this->id_jadwal,
            'tanggal' => $this->tanggal,
            'jam' => $this->jam,
            'link_zoom' => $this->link_zoom,
            'status_jadwal' => $this->status_jadwal,
            'catatan_ketua' => $this->catatan_ketua,
            'catatan_perubahan' => $this->catatan_perubahan,
            'penanggung_jawab' => $this->whenLoaded('penanggungJawab', fn () => [
                'id_panitia' => $this->penanggungJawab?->id_panitia,
                'nama_lengkap' => $this->penanggungJawab?->nama_lengkap,
            ]),
            'penguji' => JadwalPengujiResource::collection($this->whenLoaded('jadwalPenguji')),
        ];
    }
}
