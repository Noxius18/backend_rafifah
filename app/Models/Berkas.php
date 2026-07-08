<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User as Mahasantri;
use Illuminate\Support\Facades\Storage;

class Berkas extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'berkas';
    public $timestamps = false;

    protected $fillable = [
        'id_mahasantri',
        'tipe_berkas',
        'file_path',
        'link_sumber',
        'catatan_revisi',
        'status_verifikasi',
        'tanggal_upload'
    ];

    public function getIdBerkasAttribute(): int
    {
        return $this->id;
    }

    protected function casts(): array
    {
        return [
            'tanggal_upload' => 'datetime',
        ];
    }

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }

    public static function verificationStatuses(): array
    {
        return [
            self::STATUS_MENUNGGU,
            self::STATUS_DISETUJUI,
            self::STATUS_DITOLAK,
        ];
    }

    public static function reviewableVerificationStatuses(): array
    {
        return [
            self::STATUS_DISETUJUI,
            self::STATUS_DITOLAK,
        ];
    }

    /**
     * Get the latest download history record.
     */
    public function riwayatUnduhan()
    {
        return $this->hasOne(RiwayatUnduhan::class, 'berkas_id', 'id')
            ->latestOfMany('attempted_at');
    }

    /**
     * Get the full storage path for the downloaded file.
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

    public function getFileExtensionAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        $extension = pathinfo($this->file_path, PATHINFO_EXTENSION);

        return $extension !== '' ? strtolower($extension) : null;
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    /**
     * Generate the filename for download.
     */
    public function getDownloadFilenameAttribute(): string
    {
        if ($this->file_extension) {
            return $this->id_berkas . '_' . $this->tipe_berkas . '.' . $this->file_extension;
        }

        return $this->id_berkas . '_' . $this->tipe_berkas;
    }
}
