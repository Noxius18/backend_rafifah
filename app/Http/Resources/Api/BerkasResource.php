<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BerkasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_berkas' => $this->id_berkas,
            'tipe_berkas' => $this->tipe_berkas,
            'status_verifikasi' => $this->status_verifikasi,
            'catatan_revisi' => $this->catatan_revisi,
            'tanggal_upload' => $this->tanggal_upload,
            'file_available' => $this->file_exists,
            'download_status' => $this->riwayatUnduhan?->download_status,
            'error_message' => $this->riwayatUnduhan?->error_message,
        ];
    }
}
