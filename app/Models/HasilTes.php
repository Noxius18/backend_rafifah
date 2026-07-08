<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{JadwalTes as Jadwal, User as Mahasantri, JadwalPenguji as Penguji};

class HasilTes extends Model
{
    protected $table = "hasil_seleksi";
    public $timestamps = false;
    protected $fillable = [
        'status',
        'total_nilai',
        'jadwal_id',
    ];

    public function getIdHasilAttribute(): int
    {
        return $this->id;
    }

    public function getIdJadwalAttribute(): ?string
    {
        return $this->jadwalTes?->kode_jadwal;
    }

    public function jadwalTes() {
        return $this->belongsTo(Jadwal::class, 'jadwal_id', 'id');
    }

    public function jadwalPenguji() {
        return $this->hasMany(Penguji::class, 'jadwal_id', 'jadwal_id');
    }

    public function mahasantri() {
        return $this->hasOneThrough(
            Mahasantri::class,
            Jadwal::class,
            'id',   // Foreign key on jadwal_tes
            'id_mahasantri', // Foreign key on users/mahasantri
            'jadwal_id',    // Local key on hasil_tes
            'id_mahasantri' // Local key on jadwal_tes
        );
    }
}
