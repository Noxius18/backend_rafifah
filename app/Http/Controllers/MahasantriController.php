<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Orangtua;
use App\Models\Berkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class MahasantriController extends Controller
{
    /**
     * Display a listing of all mahasantri
     */
    public function index()
    {
        $mahasantris = User::with(['orangtuas', 'berkas'])->get();

        return view('menu.mahasantri.index', [
            'mahasantris' => $mahasantris
        ]);
    }

    /**
     * Show the form for creating a new mahasantri
     */
    public function create()
    {
        return view('menu.mahasantri.create');
    }

    /**
     * Store a newly created mahasantri in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap'  => 'required|string|max:35',
            'nik'           => 'nullable|size:16|unique:mahasantri,nik',
            'nisn'          => 'nullable|size:10|unique:mahasantri,nisn',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir'  => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 35 karakter.',
            'nik.size'              => 'NIK harus 16 karakter.',
            'nik.unique'            => 'NIK sudah terdaftar.',
            'nisn.size'             => 'NISN harus 10 karakter.',
            'nisn.unique'           => 'NISN sudah terdaftar.',
            'jenis_kelamin.in'      => 'Jenis kelamin harus L atau P.',
            'tempat_lahir.max'      => 'Tempat lahir maksimal 50 karakter.',
            'tanggal_lahir.date'    => 'Format tanggal lahir tidak valid.',
        ]);

        $last = User::where('id_mahasantri', 'LIKE', 'MHS%')
            ->orderBy('id_mahasantri', 'desc')
            ->first();

        $urut = 1;
        if ($last) {
            $urut = (int) substr($last->id_mahasantri, 3) + 1;
        }

        $validated['id_mahasantri'] = 'MHS' . str_pad($urut, 2, '0', STR_PAD_LEFT);
        $validated['status'] = 'Pendaftar Baru';
        $validated['tanggal_daftar'] = now();

        User::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil ditambahkan'], 201);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil ditambahkan');
    }

    /**
     * Display the specified mahasantri with relations
     */
    public function show(User $mahasantri)
    {
        $mahasantri->load(['orangtuas', 'berkas']);

        if (request()->wantsJson()) {
            return response()->json($mahasantri);
        }

        return view('menu.mahasantri.show', [
            'mahasantri' => $mahasantri
        ]);
    }

    /**
     * Show the form for editing the specified mahasantri
     */
    public function edit(User $mahasantri)
    {
        if (request()->wantsJson()) {
            return response()->json($mahasantri);
        }

        return view('menu.mahasantri.edit', [
            'mahasantri' => $mahasantri
        ]);
    }

    /**
     * Update the specified mahasantri in storage
     */
    public function update(Request $request, User $mahasantri)
    {
        $validated = $request->validate([
            'nama_lengkap'  => 'required|string|max:35',
            'nik'           => 'nullable|size:16|unique:mahasantri,nik,' . $mahasantri->id_mahasantri . ',id_mahasantri',
            'nisn'          => 'nullable|size:10|unique:mahasantri,nisn,' . $mahasantri->id_mahasantri . ',id_mahasantri',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir'  => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
            'status'        => 'nullable|in:Pendaftar Baru,Terverifikasi,Lulus,Tidak Lulus',
        ]);

        $mahasantri->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil diperbarui']);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil diperbarui');
    }

    /**
     * Remove the specified mahasantri from storage
     */
    public function destroy(Request $request, User $mahasantri)
    {
        $mahasantri->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil dihapus']);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil dihapus');
    }

    /**
     * Show the form for importing CSV
     */
    public function import()
    {
        return view('menu.mahasantri.import');
    }

    /**
     * ── Helper: ambil nomor urut terakhir dari tabel ─────────────────────
     * Dipanggil sekali sebelum loop import agar counter berjalan benar
     * di dalam transaksi.
     */
    private function getLastCounter(string $table, string $prefix, string $column): int
    {
        $last = DB::table($table)
            ->where($column, 'LIKE', $prefix . '%')
            ->orderBy($column, 'desc')
            ->first();

        if ($last) {
            return (int) substr($last->$column, strlen($prefix)) + 1;
        }
        return 1;
    }

    /**
     * ── Helper: parse tanggal ────────────────────────────────────────────
     */
    private function parseTanggal(?string $str): ?\Carbon\Carbon
    {
        if (empty($str)) {
            return null;
        }

        $formats = [
            'Y/m/d h:i:s A T',   // "2026/05/08 10:46:49 AM GMT+7"
            'Y/m/d H:i:s',       // "2026/05/08 10:46:49"
            'Y-m-d H:i:s',
            'Y-m-d',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, trim($str));
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback ke parse otomatis
        try {
            return \Carbon\Carbon::parse(trim($str));
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Process CSV import
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ], [
            'file.required' => 'File CSV wajib diupload.',
            'file.mimes'    => 'File harus berformat CSV.',
            'file.max'      => 'Ukuran file maksimal 10MB.',
        ]);

        $file = $request->file('file');
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $imported = 0;
        $errors = [];

        // ── Ambil counter awal sekali di luar transaksi ──────────────────
        $counterMhs  = $this->getLastCounter('mahasantri', 'MHS', 'id_mahasantri');
        $counterOrt  = $this->getLastCounter('orangtua',   'ORT', 'id_orangtua');
        $counterDkm  = $this->getLastCounter('berkas',     'DKM', 'id_berkas');

        DB::beginTransaction();
        try {
            foreach ($records as $index => $row) {
                $lineNumber = $index + 2; // +2 karena header baris 1, data mulai baris 2

                try {
                    // ── 1. Insert Mahasantri ──────────────────────────────
                    $idMahasantri = 'MHS' . str_pad($counterMhs, 2, '0', STR_PAD_LEFT);

                    $tanggalDaftar = $this->parseTanggal($row['Cap waktu'] ?? null);

                    User::create([
                        'id_mahasantri'  => $idMahasantri,
                        'nama_lengkap'   => $row['Nama Lengkap'] ?? 'Tidak Diketahui',
                        'nik'            => null,
                        'nisn'           => null,
                        'jenis_kelamin'  => null,
                        'tempat_lahir'   => null,
                        'tanggal_lahir'  => null,
                        'status'         => 'Pendaftar Baru',
                        'tanggal_daftar' => $tanggalDaftar,
                    ]);

                    $counterMhs++; // increment untuk row berikutnya

                    // ── 2. Insert Orangtua (Ayah, Ibu, [Wali]) ───────────
                    $orangtuaDefinitions = [];

                    if (!empty(trim($row['Nama Ayah Kandung'] ?? ''))) {
                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Ayah',
                            'nama_lengkap'  => trim($row['Nama Ayah Kandung']),
                            'pekerjaan'     => trim($row['Pekerjaan Ayah'] ?? ''),
                            'no_wa'         => trim($row['No HP/Whatsap Ayah Yang Aktif'] ?? ''),
                        ];
                    }

                    if (!empty(trim($row['Nama Ibu Kandung'] ?? ''))) {
                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Ibu',
                            'nama_lengkap'  => trim($row['Nama Ibu Kandung']),
                            'pekerjaan'     => trim($row['Pekerjaan Ibu'] ?? ''),
                            'no_wa'         => trim($row['No HP/Whatsap Ibu Yang Aktif'] ?? ''),
                        ];
                    }

                    if (!empty(trim($row['Nama Wali (jika peserta di tanggung oleh selain orang tua kandung)'] ?? ''))) {
                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Wali',
                            'nama_lengkap'  => trim($row['Nama Wali (jika peserta di tanggung oleh selain orang tua kandung)']),
                            'pekerjaan'     => trim($row['Pekerjaan Wali'] ?? ''),
                            'no_wa'         => trim($row['Nomer HP Wali'] ?? ''),
                        ];
                    }

                    foreach ($orangtuaDefinitions as $ort) {
                        $idOrt = 'ORT' . str_pad($counterOrt, 2, '0', STR_PAD_LEFT);
                        $counterOrt++;

                        Orangtua::create([
                            'id_orangtua'   => $idOrt,
                            'id_mahasantri' => $idMahasantri,
                            'tipe_hubungan' => $ort['tipe_hubungan'],
                            'nama_lengkap'  => $ort['nama_lengkap'],
                            'pekerjaan'     => $ort['pekerjaan'] ?: null,
                            'no_wa'         => $ort['no_wa'] ?: null,
                        ]);
                    }

                    // ── 3. Insert Berkas (KTP, KK, Ijazah, Surat Izin) ──
                    $berkasMapping = [
                        'Scan KTP asli'                        => 'KTP',
                        'Scan Kartu Keluarga asli'             => 'KK',
                        'Scan Ijazah terakhir'                 => 'Ijazah',
                        'Surat izin Orang tua'                 => 'Surat Izin Orangtua',
                    ];

                    foreach ($berkasMapping as $csvColumn => $tipeDokumen) {
                        $urlValue = trim($row[$csvColumn] ?? '');

                        if (!empty($urlValue)) {
                            $idDkm = 'DKM' . str_pad($counterDkm, 2, '0', STR_PAD_LEFT);
                            $counterDkm++;

                            Berkas::create([
                                'id_berkas'      => $idDkm,
                                'id_mahasantri'  => $idMahasantri,
                                'tipe_dokumen'   => $tipeDokumen,
                                'url'            => $urlValue,
                                'is_valid'       => false,
                                'tanggal_upload' => now(),
                            ]);
                        }
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();

            $message = "Berhasil mengimpor {$imported} data mahasantri.";
            if (count($errors) > 0) {
                $message .= " Gagal: " . count($errors) . " baris.";
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'imported' => $imported,
                    'errors' => $errors,
                ]);
            }

            return redirect()->route('mahasantri.index')
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal mengimpor data: ' . $e->getMessage()], 500);
            }

            return redirect()->route('mahasantri.import.form')
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }
}