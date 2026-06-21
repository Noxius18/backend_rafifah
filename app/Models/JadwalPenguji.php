<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{JadwalTes as Jadwal, Panitia};

class JadwalPenguji extends Model
{
    protected $table = "jadwal_penguji";
    protected $primaryKey = "id_jadwal_penguji";
    protected $keyType = "string";
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'id_jadwal_penguji',
        'id_jadwal',
        'id_panitia',
        'aspek_penguji',
        'catatan_penguji',
        'nilai',
    ];

    public function jadwalTes() {
        return $this->belongsTo(Jadwal::class, 'id_jadwal', 'id_jadwal');
    }

    public function panitia() {
        return $this->belongsTo(Panitia::class, 'id_panitia', 'id_panitia');
    }
}