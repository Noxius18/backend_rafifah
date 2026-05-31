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
    public $timestamps = false;
    protected $fillable = [
        'id_mahasantri',
        'nama_lengkap',
        'nik',
        'nisn',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'status',
        'gelombang',
        'tanggal_daftar',
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

    public function orangtuas() {
        return $this->hasMany(Orangtua::class, 'id_mahasantri', 'id_mahasantri');
    }

    public function berkas() {
        return $this->hasMany(Berkas::class, 'id_mahasantri', 'id_mahasantri');
    }

    public function hasilTes() {
        return $this->hasMany(Hasil::class, 'id_mahasantri', 'id_mahasantri');
    }

    // TODO: Mungkin tambah relasi ke Panitia buat siapa panitia yang kelola salah satu data santri

    /**
     * Generate ID mahasantri dengan format: <2-digit-tahun><2-digit-gelombang><2-digit-nomor>
     * Contoh: Gelombang 1 tahun 2026 → 260101, 260102, ...
     *         Gelombang 2 tahun 2026 → 260201, 260202, ...
     *
     * Nomor urut reset per tahun (per prefix tahun+gelombang).
     */
    public static function generateId(string $tahun, int $nomorGelombang): string
    {
        $prefix = substr($tahun, -2) . str_pad($nomorGelombang, 2, '0', STR_PAD_LEFT);

        $last = static::where('id_mahasantri', 'LIKE', $prefix . '%')
            ->orderBy('id_mahasantri', 'desc')
            ->first();

        $urut = 1;
        if ($last) {
            $urut = (int) substr($last->id_mahasantri, 4) + 1;
        }

        return $prefix . str_pad($urut, 2, '0', STR_PAD_LEFT);
    }
}