<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\{User as Mhasantri, JadwalTes as Jadwal};

class Panitia extends Authenticatable
{
    protected $table = 'panitia';
    protected $primaryKey = 'id_panitia';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_panitia',
        'nama_lengkap',
        'username',
        'no_hp',
        'password',
        'jabatan'
    ];

    protected $hidden = [
        'password'
    ];

    public function jadwalPenguji() {
        return $this->hasMany(Jadwal::class, 'penguji');
    }
    
    /* TODO: Mungkin nanti tambah relasi ke tabel Mahasantri untuk siapa yang mengelola salah satu pendaftar
             dan relasi juga ke tabel Jadwal tes untuk pengujinya
    */

    
}
