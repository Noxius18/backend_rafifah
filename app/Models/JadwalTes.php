<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\{Table, Fillable};
use App\Models\JadwalTes as Jadwal;

#[Table("jadwal", key: 'id_jadwal', keyType: 'char')]
#[Fillable('nama_tes', 'keterangan', 'tanggal', 'link_zoom')]

class JadwalTes extends Model
{
    public function hasilTes() {
        return $this->hasMany(Jadwal::class,'id_jadwal');
    }

    /*
    TODO: Tambah relasi ke Panitia untuk penguji
    */
}
