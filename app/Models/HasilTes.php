<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\{Table, Fillable};
use App\Models\{JadwalTes as Jadwal, User as Mahasantri};

#[Table('hasil_tes', key: 'id_hasil', keyType: 'char')]
#[Fillable('status', 'catatan_penguji', 'id_mahasantri', 'id_jadwal')]

class HasilTes extends Model
{
    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class,'id_mahasantri', 'id_mahasantri');
    }

    public function jadwalTes() {
        return $this->belongsTo(Jadwal::class, 'id_jadwal', 'id_jadwal');
    }
}
