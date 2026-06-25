<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MahasantriApiToken extends Model
{
    protected $fillable = [
        'id_mahasantri',
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function mahasantri()
    {
        return $this->belongsTo(User::class, 'id_mahasantri', 'id_mahasantri');
    }
}
