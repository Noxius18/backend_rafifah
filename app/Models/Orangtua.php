<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User as Mahasantri;

class Orangtua extends Model
{
    protected $table = "orangtua";
    public $timestamps = false;
    protected $fillable = [
        'id_mahasantri',
        'tipe_hubungan',
        'nama_lengkap',
        'pekerjaan',
        'alamat',
        'no_wa',
    ];

    public function getIdOrangtuaAttribute(): int
    {
        return $this->id;
    }

    public function mahasantri() {
        return $this->belongsTo(Mahasantri::class, 'id_mahasantri', 'id_mahasantri');
    }
}
