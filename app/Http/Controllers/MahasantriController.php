<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Orangtua;
use App\Models\Berkas;
use App\Models\JadwalTes;
use App\Models\Penguji;
use Carbon\Carbon;
use App\Jobs\DownloadGoogleDriveFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use League\Csv\Reader;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\HasilTes;

class MahasantriController extends Controller
{
    /**
     * Display a listing of all mahasantri
     */
    public function index()
    {
        $query = User::with(['orangtuas', 'berkas']);

        // Filter gelombang berdasarkan prefix id_mahasantri
        if ($gelombangFilter = request('gelombang')) {
            $prefix = match ($gelombangFilter) {
                '1' => date('y') . '01%', // 2601%
                '2' => date('y') . '02%', // 2602%
                default => null,
            };
            if ($prefix) {
                $query->where('id_mahasantri', 'LIKE', $prefix);
            }
        }

        $mahasantris = $query->paginate(10);

        return view('menu.mahasantri.index', [
            'mahasantris'     => $mahasantris,
            'filterGelombang' => request('gelombang', ''),
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
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa menambah data mahasantri');
        }

        $validated = $request->validate([
            'nama_lengkap'  => 'required|string|max:35',
            'nik'           => 'nullable|size:16|unique:mahasantri,nik',
            'nisn'          => 'nullable|size:10|unique:mahasantri,nisn',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir'  => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
            'gelombang'     => 'nullable|string|max:20',
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

        // Auto-detect gelombang jika tidak dipilih
        $tanggalDaftar = now();
        if (empty($validated['gelombang'])) {
            $gelombang = $this->detectGelombang($tanggalDaftar);
            if (!$gelombang) {
                return redirect()->back()
                    ->with('error', 'Tidak ada gelombang yang aktif untuk tanggal ini. Periksa konfigurasi gelombang.')
                    ->withInput();
            }
            $validated['gelombang'] = $gelombang['nama'];
            $nomorGelombang = $gelombang['nomor'];
        } else {
            // User pilih gelombang — cari nomor gelombang dari config
            $nomorGelombang = $this->getNomorGelombangByNama($validated['gelombang']);
            if (!$nomorGelombang) {
                // Fallback: parse dari config atau default 1
                $nomorGelombang = 1;
            }
        }

        $tahun = $tanggalDaftar->format('Y');
        $validated['id_mahasantri'] = User::generateId($tahun, $nomorGelombang);
        $validated['status'] = 'Pendaftar Baru';
        $validated['tanggal_daftar'] = $tanggalDaftar;

        User::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil ditambahkan'], 201);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil ditambahkan');
    }

    /**
     * Cetak PDF — nilai per mahasantri
     */
    public function cetakPdf(User $mahasantri)
    {
        $hasilTes = HasilTes::where('id_mahasantri', $mahasantri->id_mahasantri)
            ->with('jadwalTes')
            ->get();

        $html = view('menu.laporan.pdf-nilai-single', [
            'mahasantri' => $mahasantri,
            'hasilTes'   => $hasilTes,
            'date'       => now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'nilai-' . str_replace(' ', '-', $mahasantri->nama_lengkap) . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

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
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa mengubah data mahasantri');
        }

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
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa menghapus data mahasantri');
        }

        $mahasantri->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil dihapus']);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil dihapus');
    }

    /**
     * Verifikasi mahasantri (ubah status dari Pendaftar Baru ke Terverifikasi)
     */
    public function verifikasi($id)
    {
        $mahasantri = \App\Models\User::findOrFail($id);
        
        // 1. Update status jadi Terverifikasi
        $mahasantri->update(['status' => 'Terverifikasi']);

        // 2. LOGIKA AUTO-JADWAL
        if ($mahasantri->gelombang) {
            $lastJadwal = \App\Models\JadwalTes::whereHas('mahasantri', function($q) use ($mahasantri) {
                $q->where('gelombang', $mahasantri->gelombang);
            })->orderBy('tanggal', 'desc')->orderBy('jam', 'desc')->first();

            if ($lastJadwal) {
                $newJam = \Carbon\Carbon::parse($lastJadwal->jam)->addMinutes(30)->format('H:i:s');
                
                // Generate ID Jadwal Baru (Contoh: JDT02)
                $lastJadwalDb = \App\Models\JadwalTes::orderBy('id_jadwal', 'desc')->first();
                $nextJadwalNum = $lastJadwalDb ? intval(substr($lastJadwalDb->id_jadwal, 3)) + 1 : 1;
                $newId = 'JDT' . str_pad($nextJadwalNum, 2, '0', STR_PAD_LEFT);

                $newJadwal = \App\Models\JadwalTes::create([
                    'id_jadwal'        => $newId,
                    'id_mahasantri'    => $mahasantri->id_mahasantri,
                    'tanggal'          => $lastJadwal->tanggal,
                    'jam'              => $newJam,
                    'link_zoom'        => $lastJadwal->link_zoom,
                    'penanggung_jawab' => $lastJadwal->penanggung_jawab,
                ]);

                // Generate Penguji & ID Penguji (Contoh: PGJ05)
                $lastPengujiDb = \App\Models\Penguji::orderBy('id_penguji', 'desc')->first();
                $nextPengujiNum = $lastPengujiDb ? intval(substr($lastPengujiDb->id_penguji, 3)) + 1 : 1;

                foreach ($lastJadwal->pengujiList as $penguji) {
                    $newPengujiId = 'PGJ' . str_pad($nextPengujiNum, 2, '0', STR_PAD_LEFT);
                    \App\Models\Penguji::create([
                        'id_penguji' => $newPengujiId,
                        'id_jadwal'  => $newJadwal->id_jadwal,
                        'id_panitia' => $penguji->id_panitia,
                        'aspek'      => $penguji->aspek,
                    ]);
                    $nextPengujiNum++;
                }
            }
        }

        return redirect()->back()->with('success', 'Mahasantri berhasil diverifikasi dan ditambahkan ke jadwal (jika ada).');
    }

    /**
     * Update gelombang mahasantri
     */
    public function updateGelombang(Request $request, User $mahasantri)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa mengubah gelombang mahasantri');
        }

        $validated = $request->validate([
            'gelombang' => 'required|string|max:20',
        ]);

        $mahasantri->update(['gelombang' => $validated['gelombang']]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Gelombang berhasil diperbarui']);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Gelombang berhasil diperbarui');
    }

    /**
     * Download a specific berkas file (force download).
     */
    public function downloadBerkas(Berkas $berkas)
    {
        if (!$berkas->file_path || !$berkas->file_exists) {
            return redirect()->back()->with('error', 'File belum tersedia atau belum diunduh.');
        }

        $fullPath = $berkas->storage_path;

        if (!file_exists($fullPath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di penyimpanan.');
        }

        return response()->download($fullPath, $berkas->download_filename);
    }

    /**
     * Preview a specific berkas file inline (display in browser).
     */
    public function previewBerkas(Berkas $berkas)
    {
        if (!$berkas->file_path || !$berkas->file_exists) {
            abort(404, 'File belum tersedia atau belum diunduh.');
        }

        $fullPath = $berkas->storage_path;

        if (!file_exists($fullPath)) {
            abort(404, 'File tidak ditemukan di penyimpanan.');
        }

        // Deteksi mime type asli file agar PDF tampil sebagai PDF dan image sebagai image
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $berkas->download_filename . '"',
        ]);
    }

    /**
     * Retry download for a failed berkas.
     */
    public function retryDownload(Request $request, Berkas $berkas)
    {
        if ($berkas->download_status === 'success') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Berkas ini sudah berhasil diunduh.'], 400);
            }
            return redirect()->back()->with('info', 'Berkas ini sudah berhasil diunduh.');
        }

        $berkas->update([
            'download_status' => 'pending',
            'error_message' => null,
        ]);

        DownloadGoogleDriveFile::dispatch($berkas);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Proses unduh ulang telah dimulai.']);
        }

        return redirect()->back()->with('success', 'Proses unduh ulang telah dimulai.');
    }

    /**
     * Update the specified berkas (is_valid, NIK, NISN, dll)
     */
    public function updateBerkas(Request $request, Berkas $berkas)
    {
        $validated = $request->validate([
            'is_valid'      => 'required|boolean',
            'nik'           => 'nullable|size:16|unique:mahasantri,nik,' . $berkas->id_mahasantri . ',id_mahasantri',
            'nisn'          => 'nullable|size:10|unique:mahasantri,nisn,' . $berkas->id_mahasantri . ',id_mahasantri',
            'tempat_lahir'  => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
        ], [
            'nik.size'              => 'NIK harus 16 karakter.',
            'nik.unique'            => 'NIK sudah terdaftar.',
            'nisn.size'             => 'NISN harus 10 karakter.',
            'nisn.unique'           => 'NISN sudah terdaftar.',
            'tempat_lahir.max'      => 'Tempat lahir maksimal 50 karakter.',
            'tanggal_lahir.date'    => 'Format tanggal lahir tidak valid.',
        ]);

        $berkas->update([
            'is_valid' => $validated['is_valid'],
        ]);

        // Update data mahasantri terkait jika ada field yg diisi
        $mahasantriData = [];
        if ($request->has('nik'))           $mahasantriData['nik']           = $validated['nik'];
        if ($request->has('nisn'))          $mahasantriData['nisn']          = $validated['nisn'];
        if ($request->has('tempat_lahir'))  $mahasantriData['tempat_lahir']  = $validated['tempat_lahir'];
        if ($request->has('tanggal_lahir')) $mahasantriData['tanggal_lahir'] = $validated['tanggal_lahir'];

        if (!empty($mahasantriData)) {
            $berkas->mahasantri()->update($mahasantriData);
        }

        // ── Auto-verifikasi: jika semua dokumen terverifikasi & data pribadi lengkap ──
        $mahasantri = $berkas->mahasantri;
        if ($mahasantri && $mahasantri->status === 'Pendaftar Baru') {
            $allBerkasValid = $mahasantri->berkas()
                ->where('download_status', 'success')
                ->where('is_valid', false)
                ->doesntExist(); // tidak ada berkas success yang belum terverifikasi

            $dataLengkap = !empty($mahasantri->nik)
                && !empty($mahasantri->nisn)
                && !empty($mahasantri->tempat_lahir)
                && !empty($mahasantri->tanggal_lahir);

            if ($allBerkasValid && $dataLengkap) {
                $mahasantri->update(['status' => 'Terverifikasi']);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Data dokumen dan mahasantri berhasil diperbarui',
                'is_valid' => (bool) $validated['is_valid'],
            ]);
        }

        return redirect()->back()->with('success', 'Data dokumen dan mahasantri berhasil diperbarui');
    }

    /**
     * Show the form for importing Excel
     */
    public function import()
    {
        return view('menu.mahasantri.import');
    }

    /**
     * ── Helper: detect gelombang dari tanggal ────────────────────────────
     * Cari gelombang yang aktif berdasarkan tanggal daftar.
     * Returns array{gelombang: string, nomor: int} atau null jika tidak cocok.
     */
    private function detectGelombang(Carbon $tanggal): ?array
    {
        $daftarGelombang = config('gelombang.gelombang', []);

        foreach ($daftarGelombang as $nomor => $config) {
            $start = Carbon::parse($config['start'])->startOfDay();
            $end   = Carbon::parse($config['end'])->endOfDay();

            if ($tanggal->between($start, $end)) {
                return [
                    'nama'  => $config['nama'],
                    'nomor' => (int) $nomor,
                ];
            }
        }

        return null;
    }

    /**
     * ── Helper: cari nomor gelombang dari nama ───────────────────────────
     */
    private function getNomorGelombangByNama(string $nama): ?int
    {
        $daftarGelombang = config('gelombang.gelombang', []);

        foreach ($daftarGelombang as $nomor => $config) {
            if ($config['nama'] === $nama) {
                return (int) $nomor;
            }
        }

        return null;
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
     * ── Helper: parse serial number Excel ke date ──────────────────────────
     */
    private function parseExcelSerialNumber(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        // Jika numeric (serial number Excel)
        if (is_numeric($value)) {
            // Excel serial number: day 1 = 1900-01-01 (dengan bug leap year 1900)
            return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays((int) $value);
        }

        // Jika string tanggal biasa
        try {
            return Carbon::parse(trim($value));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ── Helper: konversi jenis kelamin ─────────────────────────────────────
     */
    private function parseJenisKelamin(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim(strtolower($value));

        return match ($value) {
            'l', 'laki-laki', 'laki laki', 'lakilaki' => 'L',
            'p', 'perempuan'                          => 'P',
            default                                   => null,
        };
    }

    /**
     * Process Excel import
     */
    public function processImport(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa import data mahasantri');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes'    => 'File harus berformat Excel (xlsx, xls) atau CSV.',
            'file.max'      => 'Ukuran file maksimal 10MB.',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (count($rows) < 2) {
            return redirect()->route('mahasantri.import.form')
                ->with('error', 'File Excel kosong atau tidak memiliki data.');
        }

        // ── Ambil header dari baris pertama ──────────────────────────────
        $headers = array_map('trim', $rows[0]);
        $dataRows = array_slice($rows, 1);

        $imported = 0;
        $errors = [];
        $allPendingBerkas = [];

        // ── Ambil counter awal sekali di luar transaksi ──────────────────
        $counterOrt  = DB::table('orangtua')->orderBy('id_orangtua', 'desc')->first();
        $counterDkm  = DB::table('berkas')->orderBy('id_berkas', 'desc')->first();
        $counterOrtVal  = $counterOrt ? (int) substr($counterOrt->id_orangtua, 3) + 1 : 1;
        $counterDkmVal  = $counterDkm ? (int) substr($counterDkm->id_berkas, 3) + 1 : 1;

        DB::beginTransaction();
        try {
            foreach ($dataRows as $index => $row) {
                $lineNumber = $index + 2;

                // ── Buat associative array dari header ───────────────────
                $data = [];
                foreach ($headers as $colIdx => $header) {
                    $data[$header] = $row[$colIdx] ?? '';
                }

                try {
                    // ── Parsing tanggal ──────────────────────────────────
                    $tanggalDaftar = $this->parseTanggal($data['Timestamp'] ?? $data['Cap waktu'] ?? null);
                    if (!$tanggalDaftar) {
                        $tanggalDaftar = now();
                    }

                    // ── Auto-detect gelombang ───────────────────────────
                    $gelombangInfo = $this->detectGelombang($tanggalDaftar);
                    if (!$gelombangInfo) {
                        throw new \Exception("Tidak ada gelombang yang aktif untuk tanggal {$tanggalDaftar->format('Y-m-d')}. Periksa konfigurasi gelombang.");
                    }

                    $tahun = $tanggalDaftar->format('Y');
                    $idMahasantri = User::generateId($tahun, $gelombangInfo['nomor']);

                    // ── Parse jenis kelamin ─────────────────────────────
                    $jenisKelamin = $this->parseJenisKelamin($data['Jenis Kelamin'] ?? null);

                    // ── Parse tanggal lahir ─────────────────────────────
                    $tanggalLahir = $this->parseExcelSerialNumber($data['Tanggal Lahir'] ?? null);

                    // ── 1. Insert Mahasantri ──────────────────────────────
                    User::create([
                        'id_mahasantri'  => $idMahasantri,
                        'nama_lengkap'   => $data['Nama Lengkap'] ?? 'Tidak Diketahui',
                        'nik'            => trim($data['NIK (Nomor Induk Keluarga)'] ?? '') ?: null,
                        'nisn'           => trim($data['NISN (Nomor Induk Siswa Nasional)'] ?? '') ?: null,
                        'jenis_kelamin'  => $jenisKelamin,
                        'tempat_lahir'   => trim($data['Tempat Lahir'] ?? '') ?: null,
                        'tanggal_lahir'  => $tanggalLahir ? $tanggalLahir->format('Y-m-d') : null,
                        'status'         => 'Pendaftar Baru',
                        'gelombang'      => $gelombangInfo['nama'],
                        'tanggal_daftar' => $tanggalDaftar,
                    ]);

                    // ── 2. Insert Orangtua (Ayah, Ibu, [Wali]) ───────────
                    $orangtuaDefinitions = [];

                    if (!empty(trim($data['Nama Ayah Kandung'] ?? ''))) {
                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Ayah',
                            'nama_lengkap'  => trim($data['Nama Ayah Kandung']),
                            'pekerjaan'     => trim($data['Pekerjaan Ayah'] ?? ''),
                            'no_wa'         => trim($data['No HP/Whatsap Ayah Yang Aktif'] ?? ''),
                        ];
                    }

                    if (!empty(trim($data['Nama Ibu Kandung'] ?? ''))) {
                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Ibu',
                            'nama_lengkap'  => trim($data['Nama Ibu Kandung']),
                            'pekerjaan'     => trim($data['Pekerjaan Ibu'] ?? ''),
                            'no_wa'         => trim($data['No HP/Whatsap Ibu Yang Aktif'] ?? ''),
                        ];
                    }

                    if (!empty(trim($data['Nama Wali (jika peserta di tanggung oleh selain orang tua kandung)'] ?? ''))) {
                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Wali',
                            'nama_lengkap'  => trim($data['Nama Wali (jika peserta di tanggung oleh selain orang tua kandung)']),
                            'pekerjaan'     => trim($data['Pekerjaan Wali'] ?? ''),
                            'no_wa'         => trim($data['Nomer HP Wali'] ?? ''),
                        ];
                    }

                    foreach ($orangtuaDefinitions as $ort) {
                        $idOrt = 'ORT' . str_pad($counterOrtVal, 2, '0', STR_PAD_LEFT);
                        $counterOrtVal++;

                        Orangtua::create([
                            'id_orangtua'   => $idOrt,
                            'id_mahasantri' => $idMahasantri,
                            'tipe_hubungan' => $ort['tipe_hubungan'],
                            'nama_lengkap'  => $ort['nama_lengkap'],
                            'pekerjaan'     => $ort['pekerjaan'] ?: null,
                            'no_wa'         => $ort['no_wa'] ?: null,
                        ]);
                    }

                    // ── 3. Insert Berkas (KTP, KK, Ijazah, Surat Izin, Pas Foto) ──
                    $berkasMapping = [
                        'Scan KTP asli'                        => 'KTP',
                        'Scan Kartu Keluarga asli'             => 'KK',
                        'Scan Ijazah terakhir'                 => 'Ijazah',
                        'Surat izin Orang tua'                 => 'Surat Izin Orangtua',
                        'Pas Foto'                             => 'Pas Foto',
                    ];

                    foreach ($berkasMapping as $excelColumn => $tipeDokumen) {
                        $urlValue = trim($data[$excelColumn] ?? '');

                        if (!empty($urlValue)) {
                            $idDkm = 'DKM' . str_pad($counterDkmVal, 2, '0', STR_PAD_LEFT);
                            $counterDkmVal++;

                            $berkas = Berkas::create([
                                'id_berkas'      => $idDkm,
                                'id_mahasantri'  => $idMahasantri,
                                'tipe_dokumen'   => $tipeDokumen,
                                'original_url'   => $urlValue,
                                'download_status'=> 'pending',
                                'is_valid'       => false,
                                'tanggal_upload' => now(),
                            ]);

                            $allPendingBerkas[] = $berkas;
                        }
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();

            // ── Dispatch download jobs for all pending berkas ────────────
            foreach ($allPendingBerkas as $berkas) {
                DownloadGoogleDriveFile::dispatch($berkas);
            }

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