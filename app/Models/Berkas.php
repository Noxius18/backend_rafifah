<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User as Mahasantri;
use Illuminate\Support\Facades\Storage;

class Berkas extends Model
{
    protected $table = "berkas";
    protected $primaryKey = "id_berkas";
    protected $keyType = "string";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'id_berkas',
        'id_mahasantri',
        'tipe_berkas',
        'link_sumber',
        'file_path',
        'status_verifikasi',
        'tanggal_upload',
    ];

    protected $casts = [
        'status_verifikasi' => 'boolean',
    ];

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }

    /**
     * Get the latest download history record.
     */
    public function riwayatUnduhan()
    {
        return $this->hasOne(RiwayatUnduhan::class, 'id_berkas', 'id_berkas')
            ->latestOfMany('attempted_at');
    }

    /**
     * Get the full storage path for the downloaded file.
     * Now uses private_berkas disk (storage/app/private/berkas/).
     */
    public function getStoragePathAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return Storage::disk('private_berkas')->path($this->file_path);
    }

    /**
     * Check if the file exists on disk.
     */
    public function getFileExistsAttribute(): bool
    {
        if (!$this->file_path) {
            return false;
        }
        return Storage::disk('private_berkas')->exists($this->file_path);
    }

    /**
     * Generate the filename for download.
     */
    public function getDownloadFilenameAttribute(): string
    {
        // Untuk Pas Foto, deteksi extension dari file_path jika file sudah diunduh
        if ($this->tipe_berkas === 'Pas Foto' && $this->file_path) {
            $extension = pathinfo($this->file_path, PATHINFO_EXTENSION);
            if ($extension) {
                return $this->id_berkas . '_' . $this->tipe_berkas . '.' . $extension;
            }
        }
        return $this->id_berkas . '_' . $this->tipe_berkas . '.pdf';
    }
}