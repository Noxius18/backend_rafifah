<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HasilTes as Hasil;

class JadwalTes extends Model
{
    protected $table = "jadwal_tes";
    protected $primaryKey = "id_jadwal";
    protected $keyType = "string";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'id_jadwal',
        'periode',
        'keterangan',
        'tanggal',
        'link_zoom',
        'pic',
    ];

    public function hasilTes() {
        return $this->hasMany(Hasil::class, 'id_jadwal', 'id_jadwal');
    }

    public function pengujiList() {
        return $this->hasMany(Penguji::class, 'id_jadwal', 'id_jadwal');
    }

    public function picPanitia() {
        return $this->belongsTo(Panitia::class, 'pic', 'id_panitia');
    }
}
