<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MahasantriResource;
use App\Models\Berkas;
use App\Models\Orangtua;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MahasantriPendaftaranController extends Controller
{
    private const DOCUMENT_FIELDS = [
        'ktp' => 'KTP',
        'kk' => 'KK',
        'ijazah' => 'Ijazah',
        'surat_izin_orangtua' => 'Surat Izin Orangtua',
        'pas_foto' => 'Pas Foto',
    ];

    public function submit(Request $request)
    {
        $mahasantri = $request->user();

        $validated = $request->validate([
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('mahasantri', 'nik')->ignore($mahasantri->id_mahasantri, 'id_mahasantri'),
            ],
            'nisn' => [
                'required',
                'digits:10',
                Rule::unique('mahasantri', 'nisn')->ignore($mahasantri->id_mahasantri, 'id_mahasantri'),
            ],
            'jenis_kelamin' => 'required|in:L,P',
            'tempat_lahir' => 'required|string|max:50',
            'alamat' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'orangtua' => 'required|array|min:2|max:3',
            'orangtua.*.tipe_hubungan' => 'required|in:Ayah,Ibu,Wali',
            'orangtua.*.nama_lengkap' => 'required|string|max:25',
            'orangtua.*.pekerjaan' => 'nullable|string|max:20',
            'orangtua.*.alamat' => 'nullable|string|max:255',
            'orangtua.*.no_wa' => 'nullable|string|max:13',
            'berkas' => 'required|array',
            // VALIDASI DINAMIS: Hanya required jika data berkas belum ada di DB
            'berkas.ktp' => [Rule::requiredIf(fn() => !$mahasantri->berkas()->where('tipe_berkas', 'KTP')->exists()), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'berkas.kk' => [Rule::requiredIf(fn() => !$mahasantri->berkas()->where('tipe_berkas', 'KK')->exists()), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'berkas.ijazah' => [Rule::requiredIf(fn() => !$mahasantri->berkas()->where('tipe_berkas', 'Ijazah')->exists()), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'berkas.surat_izin_orangtua' => [Rule::requiredIf(fn() => !$mahasantri->berkas()->where('tipe_berkas', 'Surat Izin Orangtua')->exists()), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'berkas.pas_foto' => [Rule::requiredIf(fn() => !$mahasantri->berkas()->where('tipe_berkas', 'Pas Foto')->exists()), 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $items = $this->normalizeOrangtua($validated['orangtua'], $mahasantri->id_mahasantri);
        $this->ensureRequiredParentsPresent($items);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $mahasantri, $validated, $items, &$storedPaths) {
                $mahasantri->update([
                    'nik' => $validated['nik'],
                    'nisn' => $validated['nisn'],
                    'jenis_kelamin' => $validated['jenis_kelamin'],
                    'tempat_lahir' => $validated['tempat_lahir'],
                    'alamat' => $validated['alamat'],
                    'tanggal_lahir' => $validated['tanggal_lahir'],
                ]);

                $mahasantri->orangtuas()->delete();

                $nextOrangtuaNumber = $this->nextNumericId(Orangtua::query()->pluck('id_orangtua')->all());
                foreach ($items as $item) {
                    Orangtua::create([
                        'id_orangtua' => 'ORT' . str_pad($nextOrangtuaNumber++, 2, '0', STR_PAD_LEFT),
                        'id_mahasantri' => $mahasantri->id_mahasantri,
                        ...$item,
                    ]);
                }

                $nextBerkasNumber = $this->nextNumericId(Berkas::query()->pluck('id_berkas')->all());
                foreach (self::DOCUMENT_FIELDS as $field => $tipeBerkas) {
                    // JIKA FILE TIDAK DIUPLOAD, LEWATI (Keep file lama)
                    if (!$request->hasFile("berkas.{$field}")) {
                        continue;
                    }

                    $file = $request->file("berkas.{$field}");
                    $berkas = $mahasantri->berkas()->where('tipe_berkas', $tipeBerkas)->first();
                    $idBerkas = $berkas?->id_berkas ?? 'BR' . str_pad($nextBerkasNumber++, 3, '0', STR_PAD_LEFT);
                    $extension = $file->getClientOriginalExtension();
                    $path = $file->storeAs($mahasantri->id_mahasantri, "{$idBerkas}.{$extension}", 'private_berkas');
                    $storedPaths[] = $path;
                    $oldPath = $berkas?->file_path;

                    $berkas = Berkas::updateOrCreate(
                        [
                            'id_mahasantri' => $mahasantri->id_mahasantri,
                            'tipe_berkas' => $tipeBerkas,
                        ],
                        [
                            'id_berkas' => $idBerkas,
                            'link_sumber' => null,
                            'file_path' => $path,
                            'status_verifikasi' => false, // Set false agar panitia review kembali berkas baru
                            'tanggal_upload' => now(),
                        ]
                    );

                    $berkas->riwayatUnduhan()->create([
                        'download_status' => 'success',
                        'error_message' => null,
                    ]);

                    if ($oldPath && $oldPath !== $path) {
                        Storage::disk('private_berkas')->delete($oldPath);
                    }
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('private_berkas')->delete($path);
            }
            throw $e;
        }

        return (new MahasantriResource($mahasantri->fresh()->load(['orangtuas', 'berkas.riwayatUnduhan'])))
            ->additional(['message' => 'Data pendaftaran berhasil diperbarui.']);
    }
    
    private function normalizeOrangtua(array $items, string $currentMahasantriId): array
    {
        $seenPhoneInRequest = [];

        return collect($items)->map(function (array $item, int $index) use (&$seenPhoneInRequest, $currentMahasantriId) {
            $phone = $this->normalizeIndonesianPhone($item['no_wa'] ?? null, $index);

            if ($phone) {
                if (isset($seenPhoneInRequest[$phone])) {
                    throw ValidationException::withMessages([
                        "orangtua.{$index}.no_wa" => ['Nomor WA orang tua/wali tidak boleh duplikat dalam satu pendaftaran.'],
                    ]);
                }

                if (Orangtua::where('no_wa', $phone)->where('id_mahasantri', '!=', $currentMahasantriId)->exists()) {
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

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function ensureRequiredParentsPresent(array $items): void
    {
        $types = collect($items)->pluck('tipe_hubungan');

        foreach (['Ayah', 'Ibu'] as $requiredType) {
            if (!$types->contains($requiredType)) {
                throw ValidationException::withMessages([
                    'orangtua' => ["Data {$requiredType} wajib diisi."],
                ]);
            }
        }
    }

    private function normalizeIndonesianPhone(?string $phone, int $index): ?string
    {
        $phone = trim($phone ?? '');

        if ($phone === '') {
            return null;
        }

        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        if (!preg_match('/^(\+?62|0)8[1-9][0-9]{7,10}$/', $cleaned)) {
            throw ValidationException::withMessages([
                "orangtua.{$index}.no_wa" => ['Nomor WA tidak valid. Gunakan format Indonesia seperti 08xx atau +62xx.'],
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
