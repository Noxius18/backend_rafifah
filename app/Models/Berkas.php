<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\{Table, Fillable};
use App\Models\User as Mahasantri;

#[Table("berkas", key: 'id_berkas', keyType: 'char')]
#[Fillable('jenis_berkas', 'path_file', 'tanggal_upload', 'id_mahasantri')]
#[WithoutTimestamps()]

class Berkas extends Model
{
    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class,'id_mahasantri','id_mahasantri');
    }
}
