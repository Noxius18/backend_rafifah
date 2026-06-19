<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HasilTes as Hasil;
use App\Models\User as Mahasantri;

class JadwalTes extends Model
{
    protected $table = "jadwal_tes";
    protected $primaryKey = "id_jadwal";
    protected $keyType = "string";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'id_jadwal',
        'id_mahasantri',
        'tanggal',
        'jam',
        'interval_minutes',
        'link_zoom',
        'penanggung_jawab',
        'penguji_bacaan_al_quran',
        'penguji_tajwid_tahsin',
        'penguji_hafalan',
        'penguji_wawancara',
        'zoom_reminder_sent',
        'status_konfirmasi',
        'catatan_ketua',
        'dikonfirmasi_oleh',
        'dikonfirmasi_pada',
        'status',
        'alasan_pembatalan',
        'dibatalkan_oleh',
        'dibatalkan_pada',
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

    // Relasi ke panitia sebagai penguji per aspek
    public function pengujiBacaanAlQuran() {
        return $this->belongsTo(Panitia::class, 'penguji_bacaan_al_quran', 'id_panitia');
    }

    public function pengujiTajwidTahsin() {
        return $this->belongsTo(Panitia::class, 'penguji_tajwid_tahsin', 'id_panitia');
    }

    public function pengujiHafalan() {
        return $this->belongsTo(Panitia::class, 'penguji_hafalan', 'id_panitia');
    }

    public function pengujiWawancara() {
        return $this->belongsTo(Panitia::class, 'penguji_wawancara', 'id_panitia');
    }
}