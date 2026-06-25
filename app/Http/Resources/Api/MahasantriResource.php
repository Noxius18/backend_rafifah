<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MahasantriResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_mahasantri' => $this->id_mahasantri,
            'nama_lengkap' => $this->nama_lengkap,
            'email' => $this->email,
            'nik' => $this->nik,
            'nisn' => $this->nisn,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tempat_lahir' => $this->tempat_lahir,
            'alamat' => $this->alamat,
            'tanggal_lahir' => $this->tanggal_lahir,
            'status' => $this->status,
            'tanggal_daftar' => $this->tanggal_daftar,
            'orangtua' => OrangtuaResource::collection($this->whenLoaded('orangtuas')),
            'berkas' => BerkasResource::collection($this->whenLoaded('berkas')),
        ];
    }
}
