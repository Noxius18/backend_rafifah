<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Nilai Mahasantri</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; margin: 25px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; color: #475569; margin-top: 0; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 3px solid #10b981; }
        .header p { font-size: 11px; color: #94a3b8; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge-lulus { color: #059669; font-weight: bold; }
        .badge-tidak-lulus { color: #dc2626; font-weight: bold; }
        .badge-pertimbangan { color: #d97706; font-weight: bold; }
        .badge-belum { color: #94a3b8; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Nilai Mahasantri</h1>
        @if ($jadwal)
            <h2>{{ $jadwal->periode }} — {{ \Carbon\Carbon::parse($jadwal->tanggal)->format('d F Y') }}</h2>
        @else
            <h2>Semua Jadwal Tes</h2>
        @endif
        <p>Dicetak: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Mahasantri</th>
                <th class="text-center">Tajwid</th>
                <th class="text-center">Tahsin</th>
                <th class="text-center">Kelancaran</th>
                <th class="text-center">Wawancara</th>
                <th class="text-center">Total</th>
                <th class="text-center">Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($hasilTes as $index => $h)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $h->mahasantri->nama_lengkap ?? 'Unknown' }}</td>
                    <td class="text-center">{{ $h->nilai_tajwid ?? '-' }}</td>
                    <td class="text-center">{{ $h->nilai_tahsin ?? '-' }}</td>
                    <td class="text-center">{{ $h->nilai_kelancaran ?? '-' }}</td>
                    <td class="text-center">{{ $h->nilai_wawancara ?? '-' }}</td>
                    <td class="text-center"><strong>{{ $h->total_nilai ?? '-' }}</strong></td>
                    <td class="text-center">
                        @if ($h->status === 'Lulus')
                            <span class="badge-lulus">Lulus</span>
                        @elseif ($h->status === 'Tidak Lulus')
                            <span class="badge-tidak-lulus">Tidak Lulus</span>
                        @elseif ($h->status === 'Pertimbangan')
                            <span class="badge-pertimbangan">Pertimbangan</span>
                        @else
                            <span class="badge-belum">Belum Tes</span>
                        @endif
                    </td>
                    <td>{{ $h->catatan_penguji ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">
                        Belum ada data nilai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>© {{ date('Y') }} Ma'had Rafifah Andalusia MQ — Sistem Manajemen Pendaftaran Santri Baru</p>
    </div>
</body>
</html>