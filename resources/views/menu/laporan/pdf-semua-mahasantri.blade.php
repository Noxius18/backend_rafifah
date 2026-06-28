<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Seluruh Calon Mahasantri</title>
    <style>
        /* CSS Watermark Center Pemikat Dosen */
    .watermark-wrapper {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.10; /* Dibuat sangat tipis (5%) agar tulisan laporan tetap tajam dibaca */
        z-index: -1000;
        text-align: center;
        width: 380px; /* Ukuran pas di tengah kertas */
    }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; margin: 10px; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 2px solid #059669; }
        .header h1 { font-size: 16px; color: #065f46; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #065f46; color: #fff; padding: 6px 4px; font-weight: bold; border: 1px solid #047857; text-align: center; }
        td { padding: 5px 4px; border: 1px solid #cbd5e1; text-align: left; }
        tr:nth-child(even) { background: #f8fafc; }
    </style>
</head>
<body>
    <!-- Watermark Logo Anti-Gagal (Base64) -->
<div class="watermark-wrapper">
    @php
        $imagePath = public_path('assets/Logo_2.png'); // Pastikan sudah di-convert ke PNG ya bro
        $base64 = '';
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            $base64 = 'data:image/png;base64,' . $imageData;
        }
    @endphp
    
    @if($base64)
        <img src="{{ $base64 }}" style="width: 100%;" />
    @endif
</div>
    <div class="header">
        <h1>DATA CALON MAHASANTRI BARU</h1>
        <p>Ma'had Rafifah Andalusia MQ — Dicetak: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID</th>
                <th>Nama Lengkap</th>
                <th>NIK</th>
                <th>NISN</th>
                <th>Tempat/Tgl Lahir</th>
                <th>Alamat</th>
                <th>Ayah</th>
                <th>No WA Ayah</th>
                <th>Ibu</th>
                <th>No WA Ibu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mahasantris as $index => $m)
                @php
                    $ayah = $m->orangtuas->where('tipe_hubungan', 'Ayah')->first();
                    $ibu = $m->orangtuas->where('tipe_hubungan', 'Ibu')->first();
                @endphp
                <tr>
                    <td align="center">{{ $index + 1 }}</td>
                    <td align="center"><code>{{ $m->id_mahasantri }}</code></td>
                    <td>{{ $m->nama_lengkap }}</td>
                    <td>{{ $m->nik ?? '-' }}</td>
                    <td>{{ $m->nisn ?? '-' }}</td>
                    <td>{{ $m->tempat_lahir ?? '-' }}, {{ $m->tanggal_lahir ? \Carbon\Carbon::parse($m->tanggal_lahir)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $m->alamat ?? '-' }}</td>
                    <td>{{ $ayah->nama_lengkap ?? '-' }}</td>
                    <td>{{ $ayah->no_wa ?? '-' }}</td>
                    <td>{{ $ibu->nama_lengkap ?? '-' }}</td>
                    <td>{{ $ibu->no_wa ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>