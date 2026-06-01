<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gelombang extends Model
{
    protected $table = 'gelombang';

    protected $fillable = [
        'nama',
        'start_date',
        'end_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date'   => 'date:Y-m-d',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(Panitia::class, 'updated_by', 'id_panitia');
    }
}
