<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{JadwalTes as Jadwal, User as Mahasantri};

class HasilTes extends Model
{
    protected $table = "hasil_tes";
    protected $primaryKey = "id_hasil";
    protected $keyType = "string";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'status',
        'catatan_penguji',
        'id_mahasantri',
        'id_jadwal',
        # TODO: Mungkin tambah field nilai kali ya
    ];

    public function jadwalTes() {
        return $this->belongsTo(Jadwal::class, 'id_jadwal', 'id_jadwal');
    }

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }
}
