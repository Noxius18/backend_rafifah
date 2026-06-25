<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GelombangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'start_date' => optional($this->start_date)->format('Y-m-d') ?: $this->start_date,
            'end_date' => optional($this->end_date)->format('Y-m-d') ?: $this->end_date,
        ];
    }
}
