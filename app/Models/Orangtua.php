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
    public $timestamps = false;
    protected $fillable = [
        'id_orangtua',
        'id_mahasantri',
        'tipe_hubungan',
        'nama_lengkap',
        'pekerjaan',
        'alamat',
        'no_wa',
    ];

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }
}