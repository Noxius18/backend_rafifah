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
        'link_zoom',
        'penanggung_jawab',
        'penguji_tajwid',
        'penguji_tahsin',
        'penguji_kelancaran',
        'penguji_wawancara',
    ];

    public function hasilTes() {
        return $this->hasMany(Hasil::class, 'id_jadwal', 'id_jadwal');
    }

    public function penanggungJawab() {
        return $this->belongsTo(Panitia::class, 'penanggung_jawab', 'id_panitia');
    }

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }

    // Relasi ke panitia sebagai penguji per aspek
    public function pengujiTajwid() {
        return $this->belongsTo(Panitia::class, 'penguji_tajwid', 'id_panitia');
    }

    public function pengujiTahsin() {
        return $this->belongsTo(Panitia::class, 'penguji_tahsin', 'id_panitia');
    }

    public function pengujiKelancaran() {
        return $this->belongsTo(Panitia::class, 'penguji_kelancaran', 'id_panitia');
    }

    public function pengujiWawancara() {
        return $this->belongsTo(Panitia::class, 'penguji_wawancara', 'id_panitia');
    }
}