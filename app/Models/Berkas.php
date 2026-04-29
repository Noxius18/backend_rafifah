<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User as Mahasantri;

class Berkas extends Model
{
    protected $table = "berkas";
    protected $primaryKey = "id_berkas";
    protected $keyType = "string";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'jenis_berkas',
        'path_berkas',
        'tanggal_upload',
        'id_mahasantri',
    ];

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }
}
