<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HasilTes as Hasil;
use App\Models\User as Mahasantri;

class JadwalTes extends Model
{
    protected $table = "jadwal_seleksi";
    protected $primaryKey = "id_jadwal";
    protected $keyType = "string";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'id_jadwal',
        'id_mahasantri',
        'tanggal',
        'jam',
        'interval',
        'link_zoom',
        'penanggung_jawab',
        'catatan_ketua',
        'catatan_perubahan',
        'status_jadwal',
        'diproses_oleh',
    ];

    public function hasilTes() {
        return $this->hasOne(Hasil::class, 'id_jadwal', 'id_jadwal');
    }

    public function penanggungJawab() {
        return $this->belongsTo(Panitia::class, 'penanggung_jawab', 'id_panitia');
    }

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }

    // Relasi ke jadwal_penguji (hasil normalisasi)
    public function jadwalPenguji() {
        return $this->hasMany(JadwalPenguji::class, 'id_jadwal', 'id_jadwal');
    }

    public function scheduleStatus() {
        return $this->hasOne(ScheduleStatus::class, 'id_jadwal', 'id_jadwal');
    }

    // Helper: ambil panitia penguji berdasarkan aspek tertentu
    public function getPengujiByAspek(string $aspek): ?Panitia
    {
        return $this->jadwalPenguji()
            ->where('aspek_penguji', $aspek)
            ->first()?->panitia;
    }

    // Helper: ambil nilai berdasarkan aspek tertentu
    public function getNilaiByAspek(string $aspek): ?int
    {
        return $this->jadwalPenguji()
            ->where('aspek_penguji', $aspek)
            ->first()?->nilai;
    }

    // Helper: cek apakah seorang panitia ditugaskan sebagai penguji untuk aspek tertentu
    public function isPenguji(string $idPanitia, string $aspek): bool
    {
        return $this->jadwalPenguji()
            ->where('id_panitia', $idPanitia)
            ->where('aspek_penguji', $aspek)
            ->exists();
    }

    // Helper: cek apakah seorang panitia adalah pembuat jadwal ini
    public function isCreator(string $idPanitia): bool
    {
        return $this->penanggung_jawab === $idPanitia;
    }
}
