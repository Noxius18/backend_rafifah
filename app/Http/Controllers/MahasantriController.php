<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Orangtua;
use App\Models\Berkas;
use App\Models\JadwalTes;
use App\Models\Penguji;
use App\Models\Gelombang;
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

        $mahasantris = $query->paginate(5);

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
            'tempat_lahir'  => 'nullable|string|max:50',
            'alamat'        => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 35 karakter.',
            'nik.size'              => 'NIK harus 16 karakter.',
            'nik.unique'            => 'NIK sudah terdaftar.',
            'nisn.size'             => 'NISN harus 10 karakter.',
            'nisn.unique'           => 'NISN sudah terdaftar.',
            'tempat_lahir.max'      => 'Tempat lahir maksimal 50 karakter.',
            'alamat.max'            => 'Alamat maksimal 255 karakter.',
            'tanggal_lahir.date'    => 'Format tanggal lahir tidak valid.',
        ]);

        // Auto-detect gelombang dari tanggal daftar
        $tanggalDaftar = now();
        $gelombang = $this->detectGelombang($tanggalDaftar);
        if (!$gelombang) {
            return redirect()->back()
                ->with('error', 'Tidak ada gelombang yang aktif untuk tanggal ini. Periksa konfigurasi gelombang.')
                ->withInput();
        }
        $nomorGelombang = $gelombang['id'];

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
        $jadwal = JadwalTes::where('id_mahasantri', $mahasantri->id_mahasantri)
            ->with('hasilTes', 'jadwalPenguji.panitia')
            ->first();

        $hasilTes = $jadwal && $jadwal->hasilTes
            ? collect([$jadwal->hasilTes])
            : collect();

        $html = view('menu.laporan.pdf-nilai-single', [
            'mahasantri' => $mahasantri,
            'hasilTes'   => $hasilTes,
            'jadwal'     => $jadwal,
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
        $mahasantri->load(['orangtuas', 'berkas.riwayatUnduhan']);

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
            'tempat_lahir'  => 'nullable|string|max:50',
            'alamat'        => 'nullable|string|max:255',
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
     * ── Helper: hapus berkas fisik di storage untuk sekumpulan id_mahasantri ──
     * Dipakai oleh destroyByGelombang & destroyByTahunAjaran saat checkbox "hapus file" aktif.
     */
    private function deleteBerkasFilesForPrefix(string $idPrefix, bool $alsoDeleteFiles): array
    {
        $deleted = ['mahasantri' => 0, 'orangtua' => 0, 'berkas' => 0, 'jadwal' => 0, 'hasil_tes' => 0, 'files' => 0];

        $mahasantriIds = User::where('id_mahasantri', 'LIKE', $idPrefix . '%')
            ->pluck('id_mahasantri')
            ->toArray();

        if (empty($mahasantriIds)) {
            return $deleted;
        }

        $deleted['mahasantri'] = count($mahasantriIds);
        $deleted['orangtua']   = Orangtua::whereIn('id_mahasantri', $mahasantriIds)->count();
        $deleted['berkas']     = Berkas::whereIn('id_mahasantri', $mahasantriIds)->count();
        $deleted['jadwal']     = JadwalTes::whereIn('id_mahasantri', $mahasantriIds)->count();
        $deleted['hasil_tes']  = HasilTes::whereHas('jadwalTes', function ($q) use ($mahasantriIds) {
            $q->whereIn('id_mahasantri', $mahasantriIds);
        })->count();

        if ($alsoDeleteFiles) {
            $berkasList = Berkas::whereIn('id_mahasantri', $mahasantriIds)
                ->whereNotNull('file_path')
                ->get();
            foreach ($berkasList as $b) {
                if ($b->file_exists) {
                    Storage::disk('private_berkas')->delete($b->file_path);
                    $deleted['files']++;
                }
            }
        }

        // Hapus langsung (CASCADE handle ortu/berkas/jadwal/hasil_tes/penguji)
        User::whereIn('id_mahasantri', $mahasantriIds)->delete();

        return $deleted;
    }

    /**
     * ── Hapus semua mahasantri di tahun ajaran aktif (gelombang 1 + 2) ─────
     * Hanya Ketua Panitia yang bisa menjalankan.
     */
    public function destroyByTahunAjaran(Request $request)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') {
            abort(403, 'Hanya Ketua Panitia yang bisa menghapus data mahasantri secara massal.');
        }

        $validated = $request->validate([
            'hapus_file_fisik' => 'nullable|boolean',
        ]);

        $tahun = date('y');
        $prefix = $tahun; // 26 → cocokkan semua 26xxxxx (gel 1 + gel 2)

        $alsoDeleteFiles = (bool) $request->boolean('hapus_file_fisik');

        DB::beginTransaction();
        try {
            $stats = $this->deleteBerkasFilesForPrefix($prefix, $alsoDeleteFiles);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('mahasantri.index')
                ->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }

        $msg = "Berhasil menghapus semua data tahun ajaran 20{$tahun}: "
             . "{$stats['mahasantri']} mahasantri, {$stats['orangtua']} data ortu, "
             . "{$stats['berkas']} berkas, {$stats['jadwal']} jadwal, {$stats['hasil_tes']} hasil tes.";
        if ($alsoDeleteFiles) {
            $msg .= " File fisik dihapus: {$stats['files']}.";
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $msg, 'stats' => $stats]);
        }

        return redirect()->route('mahasantri.index')->with('success', $msg);
    }

    /**
     * Verifikasi mahasantri (ubah status dari Pendaftar Baru ke Terverifikasi)
     */
    public function verifikasi($id)
    {
        $mahasantri = \App\Models\User::findOrFail($id);

        // 1. Update status jadi Terverifikasi
        $mahasantri->update(['status' => 'Terverifikasi']);

        // 2. LOGIKA AUTO-JADWAL — cari jadwal terakhir di gelombang yang sama
        $prefixGelombang = substr($mahasantri->id_mahasantri, 0, 4);
        $lastJadwal = \App\Models\JadwalTes::whereHas('mahasantri', function ($q) use ($prefixGelombang) {
            $q->where('id_mahasantri', 'LIKE', $prefixGelombang . '%');
        })->orderBy('tanggal', 'desc')->orderBy('jam', 'desc')->first();

        if ($lastJadwal) {
            // Ambil interval dari jadwal terakhir (sudah disimpan saat batch create)
            $interval = $lastJadwal->interval ?? 30;

            // Hitung jam baru berdasarkan interval yang tersimpan
            $newJam = \Carbon\Carbon::parse($lastJadwal->jam)->addMinutes($interval)->format('H:i:s');

            // Generate ID Jadwal Baru
            $lastJadwalNumber = \App\Models\JadwalTes::query()
                ->pluck('id_jadwal')
                ->map(fn($id) => (int) preg_replace('/^\D+/', '', $id))
                ->max();
            $nextJadwalNum = ($lastJadwalNumber ?? 0) + 1;
            $newId = 'JDS' . str_pad($nextJadwalNum, 2, '0', STR_PAD_LEFT);

            $newJadwal = \App\Models\JadwalTes::create([
                'id_jadwal'        => $newId,
                'id_mahasantri'    => $mahasantri->id_mahasantri,
                'tanggal'          => $lastJadwal->tanggal,
                'jam'              => $newJam,
                'interval'         => $interval,
                'link_zoom'        => $lastJadwal->link_zoom,
                'penanggung_jawab' => $lastJadwal->penanggung_jawab,
                'status_jadwal'    => 'Menunggu',
            ]);

            // Copy rows jadwal_penguji dari jadwal terakhir
            $lastPengujiRows = \App\Models\JadwalPenguji::where('id_jadwal', $lastJadwal->id_jadwal)->get();
            foreach ($lastPengujiRows as $pengujiRow) {
                \App\Models\JadwalPenguji::create([
                    'id_jadwal'     => $newId,
                    'id_panitia'    => $pengujiRow->id_panitia,
                    'aspek_penguji' => $pengujiRow->aspek_penguji,
                ]);
            }

            return redirect()->back()->with('success', 'Mahasantri berhasil diverifikasi dan ditambahkan ke jadwal.');
        }

        // Jika tidak ada jadwal yang cocok
        return redirect()->back()->with('success', 'Mahasantri berhasil diverifikasi.');
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
        $latest = $berkas->riwayatUnduhan;

        if ($latest && $latest->download_status === 'success') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Berkas ini sudah berhasil diunduh.'], 400);
            }
            return redirect()->back()->with('info', 'Berkas ini sudah berhasil diunduh.');
        }

        $berkas->riwayatUnduhan()->create([
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
     * Update the specified berkas (status_verifikasi, NIK, NISN, dll)
     */
    public function updateBerkas(Request $request, Berkas $berkas)
    {
        $validated = $request->validate([
            'status_verifikasi' => 'required|boolean',
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
            'status_verifikasi' => $validated['status_verifikasi'],
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
                ->where('status_verifikasi', false)
                ->whereHas('riwayatUnduhan', function ($q) {
                    $q->where('download_status', 'success');
                })
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
                'status_verifikasi' => (bool) $validated['status_verifikasi'],
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
     * Returns array{nama: string, id: int} atau null jika tidak cocok.
     */
    private function detectGelombang(Carbon $tanggal): ?array
    {
        $gelombangSettings = Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']);

        foreach ($gelombangSettings as $setting) {
            $start = Carbon::parse($setting->start_date)->startOfDay();
            $end   = Carbon::parse($setting->end_date)->endOfDay();

            if ($tanggal->between($start, $end)) {
                return [
                    'nama'  => $setting->nama,
                    'id' => $setting->id,
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
        $gelombang = Gelombang::where('nama', $nama)->first();

        return $gelombang?->id;
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
        $value = trim((string)$value);
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            // Coba format d/m/Y (format yang dipakai di Excel)
            try {
                return Carbon::createFromFormat('d/m/Y', $value);
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    /**
     * ── Helper: validasi panjang string (shared) ──────────────────────────
     * Throws jika panjang karakter melebihi batas DB.
     */
    private function validateStringLength(string $value, int $max, string $label): void
    {
        if (mb_strlen(trim($value)) > $max) {
            throw new \Exception("{$label} melebihi {$max} karakter.");
        }
    }

    /**
     * ── Helper: validasi NIK (16 digit angka) ─────────────────────────────
     * Returns NIK yang sudah dinormalisasi, atau null jika kosong.
     */
    private function validateNik(?string $nik): ?string
    {
        $nik = trim($nik ?? '');
        if ($nik === '') {
            return null;
        }

        // Deteksi karakter non-angka
        if (preg_match('/[^0-9]/', $nik)) {
            throw new \Exception('NIK hanya boleh berisi angka.');
        }

        if (strlen($nik) !== 16) {
            throw new \Exception('NIK harus 16 digit angka.');
        }

        return $nik;
    }

    /**
     * ── Helper: validasi NISN (10 digit angka) ────────────────────────────
     */
    private function validateNisn(?string $nisn): ?string
    {
        $nisn = trim($nisn ?? '');
        if ($nisn === '') {
            return null;
        }

        if (preg_match('/[^0-9]/', $nisn)) {
            throw new \Exception('NISN hanya boleh berisi angka.');
        }

        if (strlen($nisn) !== 10) {
            throw new \Exception('NISN harus 10 digit angka.');
        }

        return $nisn;
    }

    /**
     * ── Helper: validasi email ────────────────────────────────────────────
     * Jika kosong, gunakan fallback (default: id_mahasantri@example.com).
     */
    private function validateEmail(?string $email, string $fallback): string
    {
        $email = trim($email ?? '');
        if ($email === '') {
            return $fallback;
        }

        if (mb_strlen($email) > 100) {
            throw new \Exception('Email maksimal 100 karakter.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Format email tidak valid.');
        }

        return $email;
    }

    /**
     * ── Helper: validasi nama lengkap (required + max length) ─────────────
     * $context digunakan untuk pesan error yang lebih jelas.
     */
    private function validateNamaLengkap(?string $nama, string $context, int $max = 35): string
    {
        $nama = trim($nama ?? '');
        if ($nama === '') {
            throw new \Exception("Nama lengkap {$context} wajib diisi.");
        }

        if (mb_strlen($nama) > $max) {
            throw new \Exception("Nama lengkap {$context} maksimal {$max} karakter.");
        }

        return $nama;
    }

    /**
     * ── Helper: validasi tempat lahir (nullable, max 50) ─────────────────
     */
    private function validateTempatLahir(?string $value): ?string
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > 50) {
            throw new \Exception('Tempat lahir maksimal 50 karakter.');
        }

        return $value;
    }

    /**
     * ── Helper: validasi & normalisasi nomor HP/WA Indonesia ─────────────
     * Format input: "08xx", "+62xx", atau "62xx", dengan/tanpa spasi/dash.
     * Output: dinormalisasi ke "08..." (maks 13 karakter, sesuai kolom DB).
     *
     * Regex: ^(\+?62|0)8[1-9][0-9]{7,10}$
     *   - prefix: +62, 62, atau 0
     *   - digit pertama setelah prefix: 8 (mobile Indonesia)
     *   - digit kedua: 1-9 (bukan 0)
     *   - 7-10 digit sisanya
     */
    private function parseAndValidatePhone(?string $phone): ?string
    {
        $phone = trim($phone ?? '');
        if ($phone === '') {
            return null;
        }

        // Strip formatting: spasi, dash, parentesis, titik
        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        // Validasi format Indonesia
        if (!preg_match('/^(\+?62|0)8[1-9][0-9]{7,10}$/', $cleaned)) {
            throw new \Exception(
                'Nomor WA tidak valid. Gunakan format Indonesia (08xx atau +62xx), 10-13 digit angka.'
            );
        }

        // Normalisasi ke format "08..."
        $normalized = preg_replace('/^(\+?62)/', '0', $cleaned);

        if (strlen($normalized) > 13) {
            throw new \Exception('Nomor WA terlalu panjang (maksimal 13 karakter setelah normalisasi).');
        }

        return $normalized;
    }

    /**
     * ── Helper: handle duplikat no. HP antar-ortua di baris yang sama ─────
     * Karena kolom no_wa UNIQUE, jika Ayah & Ibu (atau Wali) punya nomor
     * yang sama dalam 1 baris, kita set null pada yg kedua + warning.
     *
     * @param array<string,string> $seenPhones Map: nomor => tipe_hubungan yg pertama
     * @param array<int,string>    $errors     Referensi ke $errors[] array
     */
    private function handleDuplicatePhoneForParents(
        array &$seenPhones,
        ?string $normalizedPhone,
        string $tipeHubungan,
        int $lineNumber,
        array &$errors
    ): ?string {
        if ($normalizedPhone === null) {
            return null;
        }

        if (isset($seenPhones[$normalizedPhone])) {
            $errors[] = "Baris {$lineNumber}: Nomor WA {$tipeHubungan} ({$normalizedPhone}) sama dengan {$seenPhones[$normalizedPhone]} - dikosongkan";
            return null;
        }

        $seenPhones[$normalizedPhone] = $tipeHubungan;
        return $normalizedPhone;
    }

    /**
     * Process Excel import
     */
    public function processImport(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa upload data mahasantri');
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
        $skipped = 0;
        $validationErrors = [];
        $skippedDuplicates = [];
        $allPendingBerkas = [];

        // ── Ambil counter awal sekali di luar transaksi ──────────────────
        $counterOrt  = DB::table('orangtua')->orderBy('id_orangtua', 'desc')->first();
        $counterOrtVal  = $counterOrt ? (int) substr($counterOrt->id_orangtua, 3) + 1 : 1;
        $counterBerkasVal = DB::table('berkas')
            ->pluck('id_berkas')
            ->map(fn($id) => (int) preg_replace('/^\D+/', '', $id))
            ->max();
        $counterBerkasVal = ($counterBerkasVal ?? 0) + 1;

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
                    // ═════════════════════════════════════════════════════
                    // FASE 1: VALIDASI SEMUA DATA SEBELUM INSERT
                    // ═════════════════════════════════════════════════════

                    // ── 1a. Validasi Nama Lengkap ──────────────────────────
                    $namaLengkap = $this->validateNamaLengkap(
                        $data['Nama Lengkap'] ?? null,
                        '',
                        35
                    );

                    // ── 1b. Validasi NIK (16 digit angka) + duplicate check ──
                    $nikValue = $this->validateNik($data['NIK (Nomor Induk Keluarga)'] ?? null);
                    if ($nikValue && User::where('nik', $nikValue)->exists()) {
                        $skippedDuplicates[] = "Baris {$lineNumber}: NIK {$nikValue} sudah terdaftar - di-skip";
                        $skipped++;
                        continue;
                    }

                    // ── 1c. Validasi NISN (10 digit angka) ──────────────────
                    $nisn = $this->validateNisn($data['NISN (Nomor Induk Siswa Nasional)'] ?? null);

                    // ── 1d. Parsing tanggal daftar ──────────────────────────
                    $tanggalDaftar = $this->parseTanggal($data['Timestamp'] ?? $data['Cap waktu'] ?? null);
                    if (!$tanggalDaftar) {
                        $tanggalDaftar = now();
                    }

                    // ── 1e. Auto-detect gelombang ──────────────────────────
                    $gelombangInfo = $this->detectGelombang($tanggalDaftar);
                    if (!$gelombangInfo) {
                        throw new \Exception("Tidak ada gelombang yang aktif untuk tanggal {$tanggalDaftar->format('Y-m-d')}. Periksa konfigurasi gelombang.");
                    }

                    $tahun = $tanggalDaftar->format('Y');
                    $idMahasantri = User::generateId($tahun, $gelombangInfo['id']);

                    // ── 1f. Validasi Email ──────────────────────────────────
                    $email = $this->validateEmail(
                        $data['Email'] ?? null,
                        $idMahasantri . '@example.com'
                    );

                    // ── 1g. Validasi Tempat Lahir ───────────────────────────
                    $tempatLahir = $this->validateTempatLahir($data['Tempat Lahir'] ?? null);

                    // ── 1h. Parse alamat tempat tinggal ──────────────────────
                    $alamat = trim($data['Alamat tempat tinggal'] ?? '');
                    if ($alamat !== '') {
                        $this->validateStringLength($alamat, 255, 'Alamat tempat tinggal');
                    }

                    // ── 1i. Parse tanggal lahir ─────────────────────────────
                    $tanggalLahir = $this->parseExcelSerialNumber($data['Tanggal Lahir'] ?? null);

                    // ── 1j. Validasi data Orangtua (koleksi + validasi) ─────
                    $seenPhones = [];
                    $orangtuaDefinitions = [];

                    // ── Ayah ──────────────────────────────────────────────────
                    $namaAyah = trim($data['Nama Ayah Kandung'] ?? '');
                    if ($namaAyah !== '') {
                        $namaAyah = $this->validateNamaLengkap(
                            $namaAyah,
                            'Ayah',
                            25
                        );
                        $pekerjaanAyah = trim($data['Pekerjaan Ayah'] ?? '');
                        if ($pekerjaanAyah !== '') {
                            $this->validateStringLength(
                                $pekerjaanAyah,
                                20,
                                'Pekerjaan Ayah'
                            );
                        }
                        $phoneAyah = $this->parseAndValidatePhone(
                            $data['No HP/Whatsap Ayah Yang Aktif'] ?? null
                        );
                        $phoneAyah = $this->handleDuplicatePhoneForParents(
                            $seenPhones,
                            $phoneAyah,
                            'Ayah',
                            $lineNumber,
                            $validationErrors
                        );

                        // ── Alamat Ayah ──────────────────────────────────────
                        $alamatAyah = trim($data['Alamat Ayah'] ?? '');
                        if ($alamatAyah !== '') {
                            $this->validateStringLength($alamatAyah, 255, 'Alamat Ayah');
                        }

                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Ayah',
                            'nama_lengkap'  => $namaAyah,
                            'pekerjaan'     => $pekerjaanAyah ?: null,
                            'alamat'        => $alamatAyah ?: null,
                            'no_wa'         => $phoneAyah,
                        ];
                    }

                    // ── Ibu ──────────────────────────────────────────────────
                    $namaIbu = trim($data['Nama Ibu Kandung'] ?? '');
                    if ($namaIbu !== '') {
                        $namaIbu = $this->validateNamaLengkap(
                            $namaIbu,
                            'Ibu',
                            25
                        );
                        $pekerjaanIbu = trim($data['Pekerjaan Ibu'] ?? '');
                        if ($pekerjaanIbu !== '') {
                            $this->validateStringLength(
                                $pekerjaanIbu,
                                20,
                                'Pekerjaan Ibu'
                            );
                        }
                        $phoneIbu = $this->parseAndValidatePhone(
                            $data['No HP/Whatsap Ibu Yang Aktif'] ?? null
                        );
                        $phoneIbu = $this->handleDuplicatePhoneForParents(
                            $seenPhones,
                            $phoneIbu,
                            'Ibu',
                            $lineNumber,
                            $validationErrors
                        );

                        // ── Alamat Ibu ───────────────────────────────────────
                        $alamatIbu = trim($data['Alamat Ibu'] ?? '');
                        if ($alamatIbu !== '') {
                            $this->validateStringLength($alamatIbu, 255, 'Alamat Ibu');
                        }

                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Ibu',
                            'nama_lengkap'  => $namaIbu,
                            'pekerjaan'     => $pekerjaanIbu ?: null,
                            'alamat'        => $alamatIbu ?: null,
                            'no_wa'         => $phoneIbu,
                        ];
                    }

                    // ── Wali ─────────────────────────────────────────────────
                    $namaWali = trim($data['Nama Wali (jika peserta di tanggung oleh selain orang tua kandung)'] ?? '');
                    if ($namaWali !== '') {
                        $namaWali = $this->validateNamaLengkap(
                            $namaWali,
                            'Wali',
                            25
                        );
                        $pekerjaanWali = trim($data['Pekerjaan Wali'] ?? '');
                        if ($pekerjaanWali !== '') {
                            $this->validateStringLength(
                                $pekerjaanWali,
                                20,
                                'Pekerjaan Wali'
                            );
                        }
                        $phoneWali = $this->parseAndValidatePhone(
                            $data['Nomer HP Wali'] ?? null
                        );
                        $phoneWali = $this->handleDuplicatePhoneForParents(
                            $seenPhones,
                            $phoneWali,
                            'Wali',
                            $lineNumber,
                            $validationErrors
                        );

                        // ── Alamat Wali ──────────────────────────────────────
                        $alamatWali = trim($data['Alamat Wali'] ?? '');
                        if ($alamatWali !== '') {
                            $this->validateStringLength($alamatWali, 255, 'Alamat Wali');
                        }

                        $orangtuaDefinitions[] = [
                            'tipe_hubungan' => 'Wali',
                            'nama_lengkap'  => $namaWali,
                            'pekerjaan'     => $pekerjaanWali ?: null,
                            'alamat'        => $alamatWali ?: null,
                            'no_wa'         => $phoneWali,
                        ];
                    }

                    // ═════════════════════════════════════════════════════
                    // FASE 2: EKSEKUSI INSERT
                    // ═════════════════════════════════════════════════════

                    // ── 2a. Insert Mahasantri ──────────────────────────────
                    User::create([
                        'id_mahasantri'  => $idMahasantri,
                        'nama_lengkap'   => $namaLengkap,
                        'email'          => $email,
                        'nik'            => $nikValue,
                        'nisn'           => $nisn,
                        'tempat_lahir'   => $tempatLahir,
                        'alamat'         => $alamat ?: null,
                        'tanggal_lahir'  => $tanggalLahir ? $tanggalLahir->format('Y-m-d') : null,
                        'status'         => 'Pendaftar Baru',
                        'tanggal_daftar' => $tanggalDaftar,
                    ]);

                    // ── 2b. Insert Orangtua ─────────────────────────────────
                    foreach ($orangtuaDefinitions as $ort) {
                        $idOrt = 'ORT' . str_pad($counterOrtVal, 2, '0', STR_PAD_LEFT);
                        $counterOrtVal++;

                        Orangtua::create([
                            'id_orangtua'   => $idOrt,
                            'id_mahasantri' => $idMahasantri,
                            'tipe_hubungan' => $ort['tipe_hubungan'],
                            'nama_lengkap'  => $ort['nama_lengkap'],
                            'pekerjaan'     => $ort['pekerjaan'] ?: null,
                            'alamat'        => $ort['alamat'] ?: null,
                            'no_wa'         => $ort['no_wa'] ?: null,
                        ]);
                    }

                    // ── 2c. Insert Berkas ───────────────────────────────────
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
                            $idBerkas = 'BR' . str_pad($counterBerkasVal, 3, '0', STR_PAD_LEFT);
                            $counterBerkasVal++;

                            $berkas = Berkas::create([
                                'id_berkas'      => $idBerkas,
                                'id_mahasantri'  => $idMahasantri,
                                'tipe_berkas'    => $tipeDokumen,
                                'link_sumber'    => $urlValue,
                                'status_verifikasi' => false,
                                'tanggal_upload' => now(),
                            ]);

                            $berkas->riwayatUnduhan()->create([
                                'download_status' => 'pending',
                            ]);

                            $allPendingBerkas[] = $berkas;
                        }
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $validationErrors[] = "Baris {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();

            // ── Dispatch download jobs for all pending berkas ────────────
            foreach ($allPendingBerkas as $berkas) {
                DownloadGoogleDriveFile::dispatch($berkas);
            }

            $message = "Berhasil mengupload {$imported} data baru.";
            if (count($validationErrors) > 0) {
                $message .= " " . count($validationErrors) . " baris gagal diimpor - lihat detail di bawah.";
            }
            if ($skipped > 0) {
                $message .= " ({$skipped} baris sudah terdaftar, di-skip.)";
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'skipped_duplicates' => $skippedDuplicates,
                    'errors' => $validationErrors,
                ]);
            }

            return redirect()->route('mahasantri.index')
                ->with('success', $message)
                ->with('import_errors', $validationErrors);

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
