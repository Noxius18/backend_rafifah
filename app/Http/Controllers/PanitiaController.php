<?php

namespace App\Http\Controllers;

use App\Models\Panitia;
use Illuminate\Http\Request;

class PanitiaController extends Controller
{
    /**
     * Display a listing of all panitia
     */
    public function index()
    {
        $panitias = Panitia::all();
        
        return view('menu.panitia', [
            'panitias' => $panitias
        ]);
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
            'password' => 'required|string|min:8',
            'jabatan' => 'required|in:Pengawas,Panitia,Penguji',
        ]);

        $kodeJabatan = [
            'Panitia' => 'PNT',
            'Pengawas' => 'PNG',
            'Penguji' => 'PNJ',
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
            'jabatan' => 'required|in:Pengawas,Panitia,Penguji',
        ]);

        $panitia->update($validated);

        return redirect()->route('panitia.index')->with('success', 'Panitia berhasil diperbarui');
    }

    /**
     * Remove the specified panitia from storage
     */
    public function destroy(Panitia $panitia)
    {
        $panitia->delete();

        return redirect()->route('panitia.index')->with('success', 'Panitia berhasil dihapus');
    }
}
