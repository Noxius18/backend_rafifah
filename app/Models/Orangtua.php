<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User as Mahasantri;

class Orangtua extends Model
{
    protected $table = "orangtua";
    protected $primaryKey = "id_orangtua";
    protected $keyType = "string";
    public $incrementing = false;
    protected $fillable = [
        'nama_lengkap',
        'pekerjaan',
        'no_hp',
        'tipe_hubungan',
        'id_mahasantri',
    ];

    public function mahasantri() {
        return $this->belongsTo( Mahasantri::class,'id_mahasantri', 'id_mahasantri' );
    }
}
