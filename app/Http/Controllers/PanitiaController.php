<?php

namespace App\Http\Controllers;

use App\Models\Panitia;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class PanitiaController extends Controller
{
    /**
     * Get panitia ordered with Ketua Panitia first.
     */
    private function orderedPanitias()
    {
        $ketua = Panitia::where('jabatan', 'Ketua Panitia')
            ->orderBy('id_panitia')
            ->get();

        $lainnya = Panitia::where('jabatan', '!=', 'Ketua Panitia')
            ->orderBy('id_panitia')
            ->get();

        return $ketua->concat($lainnya)->values();
    }

    /**
     * Display a listing of all panitia
     */
    public function index()
    {
        $panitias = $this->orderedPanitias();
        
        return view('menu.panitia.index', [
            'panitias' => $panitias
        ]);
    }

    /**
     * Cetak daftar seluruh panitia dalam format PDF.
     */
    public function cetakPdf()
    {
        $panitias = $this->orderedPanitias();

        $html = view('menu.panitia.pdf', [
            'panitias' => $panitias,
            'date' => now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="daftar-panitia.pdf"');
    }

    /**
     * Show the form for creating a new panitia
     */
    public function create()
    {
        return view('menu.panitia.create');
    }

    /**
     * Store a newly created panitia in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:30',
            'username' => 'required|string|max:10|unique:panitia',
            'no_hp' => 'required|string|max:13|unique:panitia',
            'password' => 'required|string|min:8|confirmed',
            'jabatan' => 'required|in:Ketua Panitia,Panitia',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 30 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 10 karakter.',
            'no_hp.required' => 'No. HP wajib diisi.',
            'no_hp.max' => 'No. HP maksimal 13 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
        ]);

        $kodeJabatan = [
            'Ketua Panitia' => 'KPN',
            'Panitia' => 'PNT',
        ];

        $prefix = $kodeJabatan[$validated['jabatan']];
        $last = Panitia::where('id_panitia', 'LIKE', $prefix . '%')
            ->orderBy('id_panitia', 'desc')
            ->first();

        $urut = 1;
        if ($last) {
            $urut = (int) substr($last->id_panitia, 3) + 1;
        }

        $validated['id_panitia'] = $prefix . str_pad($urut, 2, '0', STR_PAD_LEFT);
        $validated['password'] = bcrypt($validated['password']);

        Panitia::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Panitia berhasil ditambahkan'], 201);
        }

        return redirect()->route('panitia.index')->with('success', 'Panitia berhasil ditambahkan');
    }

    /**
     * Display the specified panitia
     */
    public function show(Panitia $panitia)
    {
        return view('menu.panitia.show', [
            'panitia' => $panitia
        ]);
    }

    /**
     * Show the form for editing the specified panitia
     */
    public function edit(Panitia $panitia)
    {
        if (request()->wantsJson()) {
            return response()->json($panitia);
        }

        return view('menu.panitia.edit', [
            'panitia' => $panitia
        ]);
    }

    /**
     * Update the specified panitia in storage
     */
    public function update(Request $request, Panitia $panitia)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:30',
            'username' => 'required|string|max:10|unique:panitia,username,' . $panitia->id_panitia . ',id_panitia',
            'no_hp' => 'required|string|max:13|unique:panitia,no_hp,' . $panitia->id_panitia . ',id_panitia',
            'jabatan' => 'required|in:Ketua Panitia,Panitia',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Only hash password if provided
        if ($validated['password']) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        // LOGIKA BARU: Jika jabatan berubah, update Prefix ID dan urutannya
        if ($panitia->jabatan !== $validated['jabatan']) {
            $kodeJabatan = [
                'Ketua Panitia' => 'KPN',
                'Panitia'  => 'PNT',
            ];

            $prefix = $kodeJabatan[$validated['jabatan']];
            $last = Panitia::where('id_panitia', 'LIKE', $prefix . '%')
                ->orderBy('id_panitia', 'desc')
                ->first();

            $urut = 1;
            if ($last) {
                $urut = (int) substr($last->id_panitia, 3) + 1;
            }

            // Memasukkan ID baru ke dalam array validated untuk disimpan
            $validated['id_panitia'] = $prefix . str_pad($urut, 2, '0', STR_PAD_LEFT);
        }

        $panitia->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Panitia berhasil diperbarui']);
        }

        return redirect()->route('panitia.index')->with('success', 'Panitia berhasil diperbarui');
    }

    /**
     * Remove the specified panitia from storage
     */
    public function destroy(Request $request, Panitia $panitia)
    {
        $panitia->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Panitia berhasil dihapus']);
        }

        return redirect()->route('panitia.index')->with('success', 'Panitia berhasil dihapus');
    }
}
