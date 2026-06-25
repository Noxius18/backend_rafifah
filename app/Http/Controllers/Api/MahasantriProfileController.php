<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MahasantriResource;
use App\Models\Berkas;
use App\Models\Orangtua;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class MahasantriProfileController extends Controller
{
    private const DOCUMENT_FIELDS = [
        'dokumen_ktp' => 'KTP',
        'dokumen_kk' => 'KK',
        'dokumen_ijazah' => 'Ijazah',
        'dokumen_surat_izin_orangtua' => 'Surat Izin Orangtua',
        'dokumen_pas_foto' => 'Pas Foto',
    ];

    public function updateProfile(Request $request)
    {
        $mahasantri = $request->user();

        $validated = $request->validate([
            'nik' => [
                'nullable',
                'digits:16',
                Rule::unique('mahasantri', 'nik')->ignore($mahasantri->id_mahasantri, 'id_mahasantri'),
            ],
            'nisn' => [
                'nullable',
                'digits:10',
                Rule::unique('mahasantri', 'nisn')->ignore($mahasantri->id_mahasantri, 'id_mahasantri'),
            ],
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
        ]);

        $mahasantri->update($validated);

        return (new MahasantriResource($mahasantri->fresh()->load(['orangtuas', 'berkas.riwayatUnduhan'])))
            ->additional(['message' => 'Profil berhasil diperbarui.']);
    }

    public function replaceOrangtua(Request $request)
    {
        $validated = $request->validate([
            'orangtua' => 'required|array|min:1|max:3',
            'orangtua.*.tipe_hubungan' => 'required|in:Ayah,Ibu,Wali',
            'orangtua.*.nama_lengkap' => 'required|string|max:25',
            'orangtua.*.pekerjaan' => 'nullable|string|max:20',
            'orangtua.*.alamat' => 'nullable|string|max:255',
            'orangtua.*.no_wa' => 'nullable|string|max:20',
        ]);

        $mahasantri = $request->user();
        $items = $this->normalizeOrangtua($validated['orangtua'], $mahasantri->id_mahasantri);

        DB::transaction(function () use ($mahasantri, $items) {
            $mahasantri->orangtuas()->delete();

            $nextNumber = $this->nextNumericId(Orangtua::query()->pluck('id_orangtua')->all());
            foreach ($items as $item) {
                Orangtua::create([
                    'id_orangtua' => 'ORT' . str_pad($nextNumber++, 2, '0', STR_PAD_LEFT),
                    'id_mahasantri' => $mahasantri->id_mahasantri,
                    ...$item,
                ]);
            }
        });

        return (new MahasantriResource($mahasantri->fresh()->load(['orangtuas', 'berkas.riwayatUnduhan'])))
            ->additional(['message' => 'Data orang tua/wali berhasil diperbarui.']);
    }

    public function uploadDocuments(Request $request)
    {
        $validated = $request->validate([
            'dokumen_ktp' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_kk' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_ijazah' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_surat_izin_orangtua' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen_pas_foto' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $mahasantri = $request->user();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $mahasantri, &$storedPaths) {
                $nextNumber = $this->nextNumericId(Berkas::query()->pluck('id_berkas')->all());

                foreach (self::DOCUMENT_FIELDS as $field => $tipeBerkas) {
                    $file = $request->file($field);
                    $berkas = $mahasantri->berkas()->where('tipe_berkas', $tipeBerkas)->first();
                    $idBerkas = $berkas?->id_berkas ?? 'BR' . str_pad($nextNumber++, 3, '0', STR_PAD_LEFT);
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
                            'status_verifikasi' => false,
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
            ->additional(['message' => 'Dokumen berhasil diunggah.']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
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
