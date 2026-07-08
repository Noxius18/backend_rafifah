<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HasilTesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_hasil' => $this->id_hasil,
            'total_nilai' => $this->total_nilai,
            'status' => $this->status,
        ];
    }
}
