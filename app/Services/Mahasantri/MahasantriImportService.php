<?php

namespace App\Services\Mahasantri;

use App\Jobs\DownloadGoogleDriveFile;
use App\Models\Berkas;
use App\Models\Gelombang;
use App\Models\Orangtua;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Menangani import batch mahasantri dari file Excel/CSV hingga data turunannya
 * siap diproses oleh workflow unduh berkas.
 */
class MahasantriImportService
{
    /**
     * Memproses seluruh file import dalam satu transaksi agar data mahasantri,
     * orangtua, dan berkas tetap konsisten per batch.
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (count($rows) < 2) {
            throw new \RuntimeException('File Excel kosong atau tidak memiliki data.');
        }

        $headers = array_map('trim', $rows[0]);
        $dataRows = array_slice($rows, 1);

        $imported = 0;
        $skipped = 0;
        $validationErrors = [];
        $skippedDuplicates = [];
        $allPendingBerkas = [];

        DB::beginTransaction();

        try {
            foreach ($dataRows as $index => $row) {
                $lineNumber = $index + 2;
                $data = $this->mapRowToHeaders($headers, $row);

                try {
                    // Bangun payload domain lebih dulu supaya validasi dan mapping
                    // terpusat sebelum ada operasi insert.
                    $rowPayload = $this->buildRowPayload($data, $lineNumber, $validationErrors);
                    if ($rowPayload['skip']) {
                        $skippedDuplicates[] = $rowPayload['skip_message'];
                        $skipped++;
                        continue;
                    }

                    User::create($rowPayload['mahasantri']);

                    // Insert entity turunan dilakukan setelah mahasantri berhasil dibuat
                    // agar foreign key dan urutan ID tetap sinkron.
                    foreach ($rowPayload['orangtua'] as $ort) {
                        Orangtua::create([
                            'id_mahasantri' => $rowPayload['mahasantri']['id_mahasantri'],
                            'tipe_hubungan' => $ort['tipe_hubungan'],
                            'nama_lengkap' => $ort['nama_lengkap'],
                            'pekerjaan' => $ort['pekerjaan'],
                            'alamat' => $ort['alamat'],
                            'no_wa' => $ort['no_wa'],
                        ]);
                    }

                    foreach ($rowPayload['berkas'] as $berkasDefinition) {
                        $berkas = Berkas::create([
                            'id_mahasantri' => $rowPayload['mahasantri']['id_mahasantri'],
                            'tipe_berkas' => $berkasDefinition['tipe_berkas'],
                            'link_sumber' => $berkasDefinition['link_sumber'],
                            'status_verifikasi' => Berkas::STATUS_MENUNGGU,
                            'tanggal_upload' => now(),
                        ]);

                        $berkas->riwayatUnduhan()->create([
                            'download_status' => 'pending',
                        ]);

                        $allPendingBerkas[] = $berkas;
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $validationErrors[] = "Baris {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Job unduh dijalankan setelah commit agar worker tidak membaca row
        // yang masih berada di dalam transaksi database.
        foreach ($allPendingBerkas as $berkas) {
            DownloadGoogleDriveFile::dispatch($berkas);
        }

        $message = "Berhasil mengupload {$imported} data baru.";
        if (count($validationErrors) > 0) {
            $message .= ' ' . count($validationErrors) . ' baris gagal diimpor - lihat detail di bawah.';
        }
        if ($skipped > 0) {
            $message .= " ({$skipped} baris sudah terdaftar, di-skip.)";
        }

        return [
            'message' => $message,
            'imported' => $imported,
            'skipped' => $skipped,
            'skipped_duplicates' => $skippedDuplicates,
            'errors' => $validationErrors,
        ];
    }

    private function mapRowToHeaders(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $colIdx => $header) {
            $data[$header] = $row[$colIdx] ?? '';
        }

        return $data;
    }

    /**
     * Mengubah satu baris import menjadi payload siap insert.
     * Method ini juga menjadi titik utama business rule validasi import.
     */
    private function buildRowPayload(array $data, int $lineNumber, array &$validationErrors): array
    {
        $namaLengkap = $this->validateNamaLengkap($data['Nama Lengkap'] ?? null, '', 35);

        $nikValue = $this->validateNik($data['NIK (Nomor Induk Keluarga)'] ?? null);
        if ($nikValue && User::where('nik', $nikValue)->exists()) {
            return [
                'skip' => true,
                'skip_message' => "Baris {$lineNumber}: NIK {$nikValue} sudah terdaftar - di-skip",
            ];
        }

        $nisn = $this->validateNisn($data['NISN (Nomor Induk Siswa Nasional)'] ?? null);
        $tanggalDaftar = $this->parseTanggal($data['Timestamp'] ?? $data['Cap waktu'] ?? null) ?? now();
        $gelombangInfo = $this->detectGelombang($tanggalDaftar);

        if (!$gelombangInfo) {
            throw new \RuntimeException(
                "Tidak ada gelombang yang aktif untuk tanggal {$tanggalDaftar->format('Y-m-d')}. Periksa konfigurasi gelombang."
            );
        }

        $idMahasantri = User::generateId($tanggalDaftar->format('Y'), $gelombangInfo['id']);
        $email = $this->validateEmail($data['Email'] ?? null, $idMahasantri . '@example.com');
        $tempatLahir = $this->validateTempatLahir($data['Tempat Lahir'] ?? null);

        $alamat = trim($data['Alamat tempat tinggal'] ?? '');
        if ($alamat !== '') {
            $this->validateStringLength($alamat, 255, 'Alamat tempat tinggal');
        }

        $tanggalLahir = $this->parseExcelSerialNumber($data['Tanggal Lahir'] ?? null);

        return [
            'skip' => false,
            'mahasantri' => [
                'id_mahasantri' => $idMahasantri,
                'nama_lengkap' => $namaLengkap,
                'email' => $email,
                'nik' => $nikValue,
                'nisn' => $nisn,
                'tempat_lahir' => $tempatLahir,
                'alamat' => $alamat ?: null,
                'tanggal_lahir' => $tanggalLahir ? $tanggalLahir->format('Y-m-d') : null,
                'status' => 'Pendaftar Baru',
                'tanggal_daftar' => $tanggalDaftar,
            ],
            'orangtua' => $this->buildOrangtuaDefinitions($data, $lineNumber, $validationErrors),
            'berkas' => $this->buildBerkasDefinitions($data),
        ];
    }

    /**
     * Mapping kolom upload ke tipe dokumen internal agar format Excel tetap bisa
     * berubah labelnya tanpa memengaruhi struktur tabel berkas.
     */
    private function buildBerkasDefinitions(array $data): array
    {
        $berkasMapping = [
            'Scan KTP asli' => 'KTP',
            'Scan Kartu Keluarga asli' => 'KK',
            'Scan Ijazah terakhir' => 'Ijazah',
            'Surat izin Orang tua' => 'Surat Izin Orangtua',
            'Pas Foto' => 'Pas Foto',
        ];

        $definitions = [];

        foreach ($berkasMapping as $excelColumn => $tipeDokumen) {
            $urlValue = trim($data[$excelColumn] ?? '');

            if ($urlValue !== '') {
                $definitions[] = [
                    'tipe_berkas' => $tipeDokumen,
                    'link_sumber' => $urlValue,
                ];
            }
        }

        return $definitions;
    }

    /**
     * Mengumpulkan relasi orangtua/wali dari satu baris import sekaligus
     * menjaga aturan duplikasi nomor telepon dalam satu keluarga.
     */
    private function buildOrangtuaDefinitions(array $data, int $lineNumber, array &$validationErrors): array
    {
        $seenPhones = [];
        $definitions = [];

        $definitions = array_merge($definitions, $this->buildOrangtuaDefinition(
            trim($data['Nama Ayah Kandung'] ?? ''),
            'Ayah',
            25,
            trim($data['Pekerjaan Ayah'] ?? ''),
            'Pekerjaan Ayah',
            $data['No HP/Whatsap Ayah Yang Aktif'] ?? null,
            trim($data['Alamat Ayah'] ?? ''),
            'Alamat Ayah',
            $seenPhones,
            $lineNumber,
            $validationErrors
        ));

        $definitions = array_merge($definitions, $this->buildOrangtuaDefinition(
            trim($data['Nama Ibu Kandung'] ?? ''),
            'Ibu',
            25,
            trim($data['Pekerjaan Ibu'] ?? ''),
            'Pekerjaan Ibu',
            $data['No HP/Whatsap Ibu Yang Aktif'] ?? null,
            trim($data['Alamat Ibu'] ?? ''),
            'Alamat Ibu',
            $seenPhones,
            $lineNumber,
            $validationErrors
        ));

        $definitions = array_merge($definitions, $this->buildOrangtuaDefinition(
            trim($data['Nama Wali (jika peserta di tanggung oleh selain orang tua kandung)'] ?? ''),
            'Wali',
            25,
            trim($data['Pekerjaan Wali'] ?? ''),
            'Pekerjaan Wali',
            $data['Nomer HP Wali'] ?? null,
            trim($data['Alamat Wali'] ?? ''),
            'Alamat Wali',
            $seenPhones,
            $lineNumber,
            $validationErrors
        ));

        return $definitions;
    }

    /**
     * Membangun satu row orangtua/wali yang telah dinormalisasi.
     */
    private function buildOrangtuaDefinition(
        string $nama,
        string $tipeHubungan,
        int $namaMax,
        string $pekerjaan,
        string $pekerjaanLabel,
        ?string $phone,
        string $alamat,
        string $alamatLabel,
        array &$seenPhones,
        int $lineNumber,
        array &$validationErrors
    ): array {
        if ($nama === '') {
            return [];
        }

        $nama = $this->validateNamaLengkap($nama, $tipeHubungan, $namaMax);

        if ($pekerjaan !== '') {
            $this->validateStringLength($pekerjaan, 20, $pekerjaanLabel);
        }

        if ($alamat !== '') {
            $this->validateStringLength($alamat, 255, $alamatLabel);
        }

        $phone = $this->parseAndValidatePhone($phone);
        $phone = $this->handleDuplicatePhoneForParents(
            $seenPhones,
            $phone,
            $tipeHubungan,
            $lineNumber,
            $validationErrors
        );

        return [[
            'tipe_hubungan' => $tipeHubungan,
            'nama_lengkap' => $nama,
            'pekerjaan' => $pekerjaan ?: null,
            'alamat' => $alamat ?: null,
            'no_wa' => $phone,
        ]];
    }

    /**
     * Menentukan gelombang dari tanggal pendaftaran yang ada di file import.
     */
    private function detectGelombang(Carbon $tanggal): ?array
    {
        $gelombangSettings = Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']);

        foreach ($gelombangSettings as $setting) {
            $start = Carbon::parse($setting->start_date)->startOfDay();
            $end = Carbon::parse($setting->end_date)->endOfDay();

            if ($tanggal->between($start, $end)) {
                return [
                    'nama' => $setting->nama,
                    'id' => $setting->id,
                ];
            }
        }

        return null;
    }

    /**
     * Import menerima beberapa variasi format tanggal dari spreadsheet lama
     * maupun ekspor form online.
     */
    private function parseTanggal(?string $str): ?Carbon
    {
        if (empty($str)) {
            return null;
        }

        $formats = [
            'Y/m/d h:i:s A T',
            'Y/m/d H:i:s',
            'Y-m-d H:i:s',
            'Y-m-d',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($str));
            } catch (\Exception) {
            }
        }

        try {
            return Carbon::parse(trim($str));
        } catch (\Exception) {
            return now();
        }
    }

    /**
     * Excel kadang menyimpan tanggal sebagai serial number, kadang sebagai string.
     * Helper ini menyatukan keduanya ke objek Carbon.
     */
    private function parseExcelSerialNumber(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays((int) $value);
        }

        $value = trim((string) $value);

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            try {
                return Carbon::createFromFormat('d/m/Y', $value);
            } catch (\Exception) {
                return null;
            }
        }
    }

    private function validateStringLength(string $value, int $max, string $label): void
    {
        if (mb_strlen(trim($value)) > $max) {
            throw new \RuntimeException("{$label} melebihi {$max} karakter.");
        }
    }

    private function validateNik(?string $nik): ?string
    {
        $nik = trim($nik ?? '');
        if ($nik === '') {
            return null;
        }

        if (preg_match('/[^0-9]/', $nik)) {
            throw new \RuntimeException('NIK hanya boleh berisi angka.');
        }

        if (strlen($nik) !== 16) {
            throw new \RuntimeException('NIK harus 16 digit angka.');
        }

        return $nik;
    }

    private function validateNisn(?string $nisn): ?string
    {
        $nisn = trim($nisn ?? '');
        if ($nisn === '') {
            return null;
        }

        if (preg_match('/[^0-9]/', $nisn)) {
            throw new \RuntimeException('NISN hanya boleh berisi angka.');
        }

        if (strlen($nisn) !== 10) {
            throw new \RuntimeException('NISN harus 10 digit angka.');
        }

        return $nisn;
    }

    private function validateEmail(?string $email, string $fallback): string
    {
        $email = trim($email ?? '');
        if ($email === '') {
            return $fallback;
        }

        if (mb_strlen($email) > 100) {
            throw new \RuntimeException('Email maksimal 100 karakter.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Format email tidak valid.');
        }

        return $email;
    }

    private function validateNamaLengkap(?string $nama, string $context, int $max = 35): string
    {
        $nama = trim($nama ?? '');
        if ($nama === '') {
            throw new \RuntimeException("Nama lengkap {$context} wajib diisi.");
        }

        if (mb_strlen($nama) > $max) {
            throw new \RuntimeException("Nama lengkap {$context} maksimal {$max} karakter.");
        }

        return $nama;
    }

    private function validateTempatLahir(?string $value): ?string
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > 50) {
            throw new \RuntimeException('Tempat lahir maksimal 50 karakter.');
        }

        return $value;
    }

    private function parseAndValidatePhone(?string $phone): ?string
    {
        $phone = trim($phone ?? '');
        if ($phone === '') {
            return null;
        }

        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        if (!preg_match('/^(\+?62|0)8[1-9][0-9]{7,10}$/', $cleaned)) {
            throw new \RuntimeException(
                'Nomor WA tidak valid. Gunakan format Indonesia (08xx atau +62xx), 10-13 digit angka.'
            );
        }

        $normalized = preg_replace('/^(\+?62)/', '0', $cleaned);

        if (strlen($normalized) > 13) {
            throw new \RuntimeException('Nomor WA terlalu panjang (maksimal 13 karakter setelah normalisasi).');
        }

        return $normalized;
    }

    private function handleDuplicatePhoneForParents(
        array &$seenPhones,
        ?string $normalizedPhone,
        string $tipeHubungan,
        int $lineNumber,
        array &$validationErrors
    ): ?string {
        if ($normalizedPhone === null) {
            return null;
        }

        if (isset($seenPhones[$normalizedPhone])) {
            // Perilaku import lama dipertahankan: nomor kedua dikosongkan,
            // tetapi satu baris tetap boleh lanjut diimpor.
            $validationErrors[] = "Baris {$lineNumber}: Nomor WA {$tipeHubungan} ({$normalizedPhone}) sama dengan {$seenPhones[$normalizedPhone]} - dikosongkan";
            return null;
        }

        $seenPhones[$normalizedPhone] = $tipeHubungan;

        return $normalizedPhone;
    }
}
