<?php

namespace App\Http\Controllers;

use App\Models\Gelombang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GelombangController extends Controller
{
    public function index()
    {
        $gelombang = Gelombang::orderBy('nomor')->get();
        return view('menu.gelombang.index', compact('gelombang'));
    }

    public function update(Request $request, Gelombang $gelombang)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.required'    => 'Tanggal mulai wajib diisi.',
            'start_date.date'        => 'Format tanggal mulai tidak valid.',
            'end_date.required'      => 'Tanggal akhir wajib diisi.',
            'end_date.date'          => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
        ]);

        $gelombang->update(['updated_by' => auth()->user()->id_panitia, ...$validated]);

        return back()->with('success', 'Rentang gelombang berhasil diperbarui.');
    }
}
