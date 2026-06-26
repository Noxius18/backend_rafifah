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
            'tempat_lahir' => $this->tempat_lahir,
            'alamat' => $this->alamat,
            'tanggal_lahir' => $this->tanggal_lahir,
            'status' => $this->status,
            'tanggal_daftar' => $this->tanggal_daftar,
            'profile_completed' => $this->profileCompleted(),
            'orangtua_completed' => $this->relationLoaded('orangtuas') ? $this->orangtuas->isNotEmpty() : null,
            'documents_completed' => $this->documentsCompleted(),
            'orangtua' => OrangtuaResource::collection($this->whenLoaded('orangtuas')),
            'berkas' => BerkasResource::collection($this->whenLoaded('berkas')),
        ];
    }

    private function profileCompleted(): bool
    {
        return !empty($this->nik)
            && !empty($this->nisn)
            && !empty($this->tempat_lahir)
            && !empty($this->alamat)
            && !empty($this->tanggal_lahir);
    }

    private function documentsCompleted(): ?bool
    {
        if (!$this->relationLoaded('berkas')) {
            return null;
        }

        $required = collect(['KTP', 'KK', 'Ijazah', 'Surat Izin Orangtua', 'Pas Foto']);
        $available = $this->berkas
            ->filter(fn ($berkas) => $berkas->file_exists)
            ->pluck('tipe_berkas');

        return $required->diff($available)->isEmpty();
    }
}
