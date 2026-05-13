<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-emerald-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <div class="bg-white rounded-2xl shadow-lg shadow-emerald-200/50 border border-emerald-100 p-8">
            {{-- Icon --}}
            <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9.364-7.364A9 9 0 1112 3a9 9 0 017.364 4.636z" />
                </svg>
            </div>

            {{-- Title --}}
            <h1 class="text-6xl font-bold text-emerald-900 mb-2">403</h1>
            <h2 class="text-xl font-semibold text-slate-700 mb-4">Akses Ditolak</h2>

            {{-- Description --}}
            <p class="text-slate-500 mb-8">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
                Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
            </p>

            {{-- Button --}}
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Dashboard
            </a>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-sm text-slate-400">
            &copy; {{ date('Y') }} Ma'had Rafifah Andalusia MQ
        </p>
    </div>
</body>
</html>