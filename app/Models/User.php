<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\{Orangtua, Berkas, HasilTes as Hasil};

class User extends Authenticatable
{
    protected $table = 'mahasantri';
    protected $primaryKey = 'id_mahasantri';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id_mahasantri',
        'nama_lengkap',
        'email',
        'no_hp',
        'password',
        'alamat_lengkap',
        'nik',
        'jenis_kelamin',
        'tanggal_lahir',
        'tempat_lahir',
    ];
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orangtua() {
        return $this->hasMany(Orangtua::class, 'id_orangtua');
    }

    public function berkas() {
        return $this->hasMany(Berkas::class, 'id_berkas');
    }

    public function hasilTes() {
        return $this->hasMany(Hasil::class, 'id_hasil');
    }

    // TODO: Mungkin tambah relasi ke Panitia buat siapa panitia yang kelola salah satu data santri
}
