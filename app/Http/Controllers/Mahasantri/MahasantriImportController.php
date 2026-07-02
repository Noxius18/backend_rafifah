<?php

namespace App\Http\Controllers\Mahasantri;

use App\Http\Controllers\Controller;
use App\Services\Mahasantri\MahasantriImportService;
use Illuminate\Http\Request;

class MahasantriImportController extends Controller
{
    public function __construct(private readonly MahasantriImportService $importService)
    {
    }

    public function import()
    {
        return view('menu.mahasantri.import');
    }

    public function processImport(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403, 'Hanya panitia yang bisa upload data mahasantri');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes' => 'File harus berformat Excel (xlsx, xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $result = $this->importService->import($request->file('file'));
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal mengimpor data: ' . $e->getMessage()], 500);
            }

            return redirect()->route('mahasantri.import.form')
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('mahasantri.index')
            ->with('success', $result['message'])
            ->with('import_errors', $result['errors']);
    }
}
