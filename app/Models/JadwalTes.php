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
    ];

    public function hasilTes() {
        return $this->hasMany(Hasil::class, 'id_jadwal', 'id_jadwal');
    }

    public function pengujiList() {
        return $this->hasMany(Penguji::class, 'id_jadwal', 'id_jadwal');
    }

    public function penanggungJawab() {
        return $this->belongsTo(Panitia::class, 'penanggung_jawab', 'id_panitia');
    }

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }
}
