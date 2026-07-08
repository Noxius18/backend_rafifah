<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{JadwalTes as Jadwal, Panitia};

class JadwalPenguji extends Model
{
    protected $table = "jadwal_penguji";
    protected $primaryKey = "id_jadwal_penguji";
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'id_jadwal_penguji',
        'jadwal_id',
        'id_panitia',
        'aspek_penguji',
        'catatan_penguji',
        'nilai',
    ];

    public function getIdJadwalAttribute(): ?string
    {
        return $this->jadwalTes?->kode_jadwal;
    }

    public function jadwalTes() {
        return $this->belongsTo(Jadwal::class, 'jadwal_id', 'id');
    }

    public function panitia() {
        return $this->belongsTo(Panitia::class, 'id_panitia', 'id_panitia');
    }
}
