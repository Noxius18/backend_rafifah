<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrangtuaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_orangtua' => $this->id_orangtua,
            'tipe_hubungan' => $this->tipe_hubungan,
            'nama_lengkap' => $this->nama_lengkap,
            'pekerjaan' => $this->pekerjaan,
            'alamat' => $this->alamat,
            'no_wa' => $this->no_wa,
        ];
    }
}
