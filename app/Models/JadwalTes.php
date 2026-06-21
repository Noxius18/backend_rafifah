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
}