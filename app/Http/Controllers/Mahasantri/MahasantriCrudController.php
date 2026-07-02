<?php

namespace App\Http\Controllers\Mahasantri;

use App\Http\Controllers\Controller;
use App\Models\Gelombang;
use App\Models\User;
use App\Services\Mahasantri\MahasantriWorkflowService;
use Illuminate\Http\Request;

/**
 * Controller tipis untuk operasi CRUD mahasantri.
 * Business rule lintas model didelegasikan ke workflow service.
 */
class MahasantriCrudController extends Controller
{
    public function __construct(private readonly MahasantriWorkflowService $workflowService)
    {
    }

    public function index()
    {
        $query = User::with(['orangtuas', 'berkas']);
        $activeGelombang = Gelombang::activeOrFirst();
        $gelombangList = Gelombang::orderBy('id')
            ->get(['id', 'nama'])
            ->map(fn($gelombang) => [
                'value' => (string) $gelombang->id,
                'label' => $gelombang->nama,
            ])
            ->values()
            ->all();

        if ($gelombangFilter = request('gelombang')) {
            $prefix = null;
            if ($activeGelombang) {
                $prefix = $activeGelombang->tahunPrefix() . str_pad((string) $gelombangFilter, 2, '0', STR_PAD_LEFT) . '%';
            }

            if ($prefix) {
                $query->where('id_mahasantri', 'LIKE', $prefix);
            }
        }

        return view('menu.mahasantri.index', [
            'mahasantris' => $query->paginate(5),
            'filterGelombang' => request('gelombang', ''),
            'gelombangList' => $gelombangList,
        ]);
    }

    public function create()
    {
        return view('menu.mahasantri.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403, 'Hanya panitia yang bisa menambah data mahasantri');

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:35',
            'nik' => 'nullable|size:16|unique:mahasantri,nik',
            'nisn' => 'nullable|size:10|unique:mahasantri,nisn',
            'tempat_lahir' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 35 karakter.',
            'nik.size' => 'NIK harus 16 karakter.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'nisn.size' => 'NISN harus 10 karakter.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'tempat_lahir.max' => 'Tempat lahir maksimal 50 karakter.',
            'alamat.max' => 'Alamat maksimal 255 karakter.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
        ]);

        try {
            // Pembuatan ID mahasantri dan penentuan gelombang tidak dilakukan di controller
            // agar rule yang sama bisa dipakai ulang oleh entrypoint lain.
            $this->workflowService->createMahasantri($validated);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil ditambahkan'], 201);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil ditambahkan');
    }

    public function show(User $mahasantri)
    {
        $mahasantri->load(['orangtuas', 'berkas.riwayatUnduhan']);

        if (request()->wantsJson()) {
            return response()->json($mahasantri);
        }

        return view('menu.mahasantri.show', ['mahasantri' => $mahasantri]);
    }

    public function edit(User $mahasantri)
    {
        if (request()->wantsJson()) {
            return response()->json($mahasantri);
        }

        return view('menu.mahasantri.edit', ['mahasantri' => $mahasantri]);
    }

    public function update(Request $request, User $mahasantri)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403, 'Hanya panitia yang bisa mengubah data mahasantri');

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:35',
            'nik' => 'nullable|size:16|unique:mahasantri,nik,' . $mahasantri->id_mahasantri . ',id_mahasantri',
            'nisn' => 'nullable|size:10|unique:mahasantri,nisn,' . $mahasantri->id_mahasantri . ',id_mahasantri',
            'tempat_lahir' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'status' => 'nullable|in:Pendaftar Baru,Terverifikasi,Lulus,Tidak Lulus',
        ]);

        $mahasantri->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil diperbarui']);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil diperbarui');
    }

    public function destroy(Request $request, User $mahasantri)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403, 'Hanya panitia yang bisa menghapus data mahasantri');

        $mahasantri->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data mahasantri berhasil dihapus']);
        }

        return redirect()->route('mahasantri.index')->with('success', 'Data mahasantri berhasil dihapus');
    }
}
