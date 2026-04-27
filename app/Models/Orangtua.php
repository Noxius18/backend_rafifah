<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\{Table, Fillable};
use App\Models\User as Mahasantri;

#[Table("orangtua", key: 'id_orangtua', keyType: 'char')]
#[Fillable('nama_lengkap', 'pekerjaan', 'no_hp', 'tipe_hubungan', 'id_mahasantri')]

class Orangtua extends Model
{
    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class,'id_mahasantri','id_mahasantri');
    }
}
