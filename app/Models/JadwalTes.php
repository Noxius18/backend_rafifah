<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\JadwalTes as Jadwal;

class JadwalTes extends Model
{
    protected $table = "jadwal_tes";
    protected $primaryKey = "id_jadwal";
    protected $keyType = "string";
    public $incrementing = false;
    protected $fillable = [
        'nama_tes',
        'keterangan',
        'tanggal',
        'link_zoom',
        'penguji',
    ];

    public function hasilTes() {
        return $this->hasMany(Jadwal::class,'id_jadwal');
    }

    /*
    TODO: Tambah relasi ke Panitia untuk penguji
    */
    public function penguji() {
        return $this->belongsTo(Panitia::class, 'penguji', 'id_panitia');
    }
}
