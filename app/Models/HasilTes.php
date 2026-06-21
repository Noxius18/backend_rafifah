<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{JadwalTes as Jadwal, User as Mahasantri, JadwalPenguji as Penguji};

class HasilTes extends Model
{
    protected $table = "hasil_seleksi";
    protected $primaryKey = "id_hasil";
    protected $keyType = "string";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'id_hasil',
        'status',
        'total_nilai',
        'id_jadwal',
    ];

    public function jadwalTes() {
        return $this->belongsTo(Jadwal::class, 'id_jadwal', 'id_jadwal');
    }

    public function jadwalPenguji() {
        return $this->hasMany(Penguji::class, 'id_jadwal', 'id_jadwal');
    }

    public function mahasantri() {
        return $this->hasOneThrough(
            Mahasantri::class,
            Jadwal::class,
            'id_jadwal',   // Foreign key on jadwal_tes
            'id_mahasantri', // Foreign key on users/mahasantri
            'id_jadwal',    // Local key on hasil_tes
            'id_mahasantri' // Local key on jadwal_tes
        );
    }
}
