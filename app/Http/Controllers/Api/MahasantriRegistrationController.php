<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MahasantriResource;
use App\Models\Berkas;
use App\Models\Gelombang;
use App\Models\Orangtua;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MahasantriRegistrationController extends Controller
{
    private const DOCUMENT_FIELDS = [
        'dokumen_ktp' => 'KTP',
        'dokumen_kk' => 'KK',
        'dokumen_ijazah' => 'Ijazah',
        'dokumen_surat_izin_orangtua' => 'Surat Izin Orangtua',
        'dokumen_pas_foto' => 'Pas Foto',
    ];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:35',
            'email' => 'required|email|max:100|unique:mahasantri,email',
            'password' => 'required|string|min:8|confirmed',
            'nik' => 'nullable|digits:16|unique:mahasantri,nik',
            'nisn' => 'nullable|digits:10|unique:mahasantri,nisn',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'orangtua' => 'required|array|min:1|max:3',
            'orangtua.*.tipe_hubungan' => 'required|in:Ayah,Ibu,Wali',
            'orangtua.*.nama_lengkap' => 'required|string|max:25',
            'orangtua.*.pekerjaan' => 'nullable|string|max:20',
            'orangtua.*.alamat' => 'nullable|string|max:255',
            'orangtua.*.no_wa' => 'nullable|string|max:20',
            'dokumen_ktp' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_kk' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_ijazah' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_surat_izin_orangtua' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_pas_foto' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $tanggalDaftar = now();
        $gelombang = $this->activeGelombang($tanggalDaftar);

        if (!$gelombang) {
            throw ValidationException::withMessages([
                'gelombang' => ['Tidak ada gelombang aktif untuk tanggal pendaftaran saat ini.'],
            ]);
        }

        $orangtua = $this->normalizeOrangtua($validated['orangtua']);

        $storedPaths = [];

        try {
            $mahasantri = DB::transaction(function () use ($request, $validated, $tanggalDaftar, $gelombang, $orangtua, &$storedPaths) {
                $idMahasantri = User::generateId($tanggalDaftar->format('Y'), $gelombang->id);

                $mahasantri = User::create([
                    'id_mahasantri' => $idMahasantri,
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'nik' => $validated['nik'] ?? null,
                    'nisn' => $validated['nisn'] ?? null,
                    'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
                    'tempat_lahir' => $validated['tempat_lahir'] ?? null,
                    'alamat' => $validated['alamat'] ?? null,
                    'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
                    'status' => 'Pendaftar Baru',
                    'tanggal_daftar' => $tanggalDaftar,
                ]);

                $nextOrangtuaNumber = $this->nextNumericId(Orangtua::query()->pluck('id_orangtua')->all());
                foreach ($orangtua as $item) {
                    Orangtua::create([
                        'id_orangtua' => 'ORT' . str_pad($nextOrangtuaNumber++, 2, '0', STR_PAD_LEFT),
                        'id_mahasantri' => $idMahasantri,
                        ...$item,
                    ]);
                }

                $nextBerkasNumber = $this->nextNumericId(Berkas::query()->pluck('id_berkas')->all());
                foreach (self::DOCUMENT_FIELDS as $field => $tipeBerkas) {
                    $file = $request->file($field);
                    $idBerkas = 'BR' . str_pad($nextBerkasNumber++, 3, '0', STR_PAD_LEFT);
                    $extension = $file->getClientOriginalExtension();
                    $path = $file->storeAs($idMahasantri, "{$idBerkas}.{$extension}", 'private_berkas');
                    $storedPaths[] = $path;

                    $berkas = Berkas::create([
                        'id_berkas' => $idBerkas,
                        'id_mahasantri' => $idMahasantri,
                        'tipe_berkas' => $tipeBerkas,
                        'link_sumber' => null,
                        'file_path' => $path,
                        'status_verifikasi' => false,
                        'tanggal_upload' => now(),
                    ]);

                    $berkas->riwayatUnduhan()->create([
                        'download_status' => 'success',
                        'error_message' => null,
                    ]);
                }

                return $mahasantri;
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('private_berkas')->delete($path);
            }

            throw $e;
        }

        return (new MahasantriResource($mahasantri->load(['orangtuas', 'berkas.riwayatUnduhan'])))
            ->additional(['message' => 'Pendaftaran berhasil.'])
            ->response()
            ->setStatusCode(201);
    }

    private function activeGelombang(Carbon $date): ?Gelombang
    {
        return Gelombang::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderBy('start_date')
            ->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOrangtua(array $items): array
    {
        $seenPhoneInRequest = [];

        return collect($items)->map(function (array $item, int $index) use (&$seenPhoneInRequest) {
            $phone = $this->normalizeIndonesianPhone($item['no_wa'] ?? null);

            if ($phone) {
                if (isset($seenPhoneInRequest[$phone])) {
                    throw ValidationException::withMessages([
                        "orangtua.{$index}.no_wa" => ['Nomor WA orang tua/wali tidak boleh duplikat dalam satu pendaftaran.'],
                    ]);
                }

                if (Orangtua::where('no_wa', $phone)->exists()) {
                    throw ValidationException::withMessages([
                        "orangtua.{$index}.no_wa" => ['Nomor WA sudah terdaftar.'],
                    ]);
                }

                $seenPhoneInRequest[$phone] = true;
            }

            return [
                'tipe_hubungan' => $item['tipe_hubungan'],
                'nama_lengkap' => $item['nama_lengkap'],
                'pekerjaan' => $item['pekerjaan'] ?? null,
                'alamat' => $item['alamat'] ?? null,
                'no_wa' => $phone,
            ];
        })->all();
    }

    private function normalizeIndonesianPhone(?string $phone): ?string
    {
        $phone = trim($phone ?? '');

        if ($phone === '') {
            return null;
        }

        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        if (!preg_match('/^(\+?62|0)8[1-9][0-9]{7,10}$/', $cleaned)) {
            throw ValidationException::withMessages([
                'orangtua.*.no_wa' => ['Nomor WA tidak valid. Gunakan format Indonesia seperti 08xx atau +62xx.'],
            ]);
        }

        return preg_replace('/^(\+?62)/', '0', $cleaned);
    }

    /**
     * @param  array<int, string>  $ids
     */
    private function nextNumericId(array $ids): int
    {
        $max = collect($ids)
            ->map(fn (string $id) => (int) preg_replace('/^\D+/', '', $id))
            ->max();

        return ($max ?? 0) + 1;
    }
}
