<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\{Table, Fillable, Hidden};
use Illuminate\Database\Eloquent\Model;

#[Table("panitia", key: 'id_panitia', keyType: 'char')]
#[Fillable(['nama_lengkap', 'username', 'password', 'jabatan'])]
#[Hidden('password')]

class Panitia extends Model
{
    /* TODO: Mungkin nanti tambah relasi ke tabel Mahasantri untuk siapa yang mengelola salah satu pendaftar
             dan relasi juga ke tabel Jadwal tes untuk pengujinya
    */
}
