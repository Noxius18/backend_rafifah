<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{JadwalTes as Jadwal, Panitia};

class Penguji extends Model
{
    protected $table = 'penguji';
    protected $primaryKey = 'id_penguji';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_penguji',
        'id_jadwal',
        'id_panitia',
        'aspek',
    ];

    public function jadwal() {
        return $this->belongsTo(Jadwal::class, 'id_jadwal', 'id_jadwal');
    }

    public function panitia() {
        return $this->belongsTo(Panitia::class, 'id_panitia', 'id_panitia');
    }
}