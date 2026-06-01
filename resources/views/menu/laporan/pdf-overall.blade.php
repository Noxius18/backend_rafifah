<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Overall</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; margin: 30px; }
        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #059669; }
        .header h1 { font-size: 22px; color: #065f46; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 11px; color: #94a3b8; margin: 3px 0; }
        .header .sub { font-size: 13px; color: #475569; font-weight: 600; }

        .section { margin-bottom: 22px; }
        .section h2 { font-size: 14px; color: #065f46; border-bottom: 2px solid #a7f3d0; padding-bottom: 5px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 0.5px; }

        table.summary { width: 100%; border-collapse: collapse; }
        table.summary td { padding: 8px 12px; border: 1px solid #d1d5db; font-size: 13px; }
        table.summary .label { background: #f0fdf4; font-weight: 600; color: #065f46; width: 25%; }
        table.summary .value { font-weight: 700; font-size: 15px; text-align: center; width: 12%; }
        table.summary .value-green { color: #059669; }
        table.summary .value-red { color: #dc2626; }
        table.summary .value-amber { color: #d97706; }
        table.summary .value-slate { color: #475569; }

        table.detail { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.detail th { background: #065f46; color: #fff; padding: 8px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; }
        table.detail th:first-child { text-align: left; }
        table.detail td { padding: 7px 12px; border-bottom: 1px solid #e5e7eb; text-align: center; font-size: 12px; }
        table.detail td:first-child { text-align: left; font-weight: 600; }
        table.detail tr:nth-child(even) { background: #f9fafb; }
        table.detail tr:nth-child(odd) { background: #fff; }

        .footer { margin-top: 25px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Hasil Seleksi</h1>
        <div class="sub">Ma'had Rafifah Andalusia MQ</div>
        <p>Dicetak: {{ $date }}</p>
    </div>

    <div class="section">
        <h2>Ringkasan Umum</h2>
        <table class="summary">
            <tr>
                <td class="label">Total Mahasantri</td>
                <td class="value value-slate">{{ $summary['total_mahasantri'] }}</td>
                <td class="label">Total Jadwal Tes</td>
                <td class="value value-slate">{{ $summary['total_jadwal'] }}</td>
            </tr>
            <tr>
                <td class="label">Lulus</td>
                <td class="value value-green">{{ $summary['total_lulus'] }}</td>
                <td class="label">Tidak Lulus</td>
                <td class="value value-red">{{ $summary['total_tidak_lulus'] }}</td>
            </tr>
            <tr>
                <td class="label">Pertimbangan</td>
                <td class="value value-amber">{{ $summary['total_pertimbangan'] }}</td>
                <td class="label">Belum Tes</td>
                <td class="value value-slate">{{ $summary['total_belum_tes'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Detail Per Jadwal Tes</h2>
        <table class="detail">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Gelombang</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Lulus</th>
                    <th>Tidak Lulus</th>
                    <th>Pertimbangan</th>
                    <th>Belum Tes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perJadwal as $index => $pj)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $pj['jadwal']->mahasantri ? \App\Models\User::extractGelombangNama($pj['jadwal']->mahasantri->id_mahasantri) : '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($pj['jadwal']->tanggal)->format('d/m/Y') }}</td>
                        <td>{{ $pj['total'] }}</td>
                        <td style="color: #059669; font-weight: 700;">{{ $pj['lulus'] }}</td>
                        <td style="color: #dc2626; font-weight: 700;">{{ $pj['tidak_lulus'] }}</td>
                        <td style="color: #d97706; font-weight: 700;">{{ $pj['pertimbangan'] }}</td>
                        <td>{{ $pj['belum_tes'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">
                            Belum ada jadwal tes.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Ma'had Rafifah Andalusia MQ — Sistem Manajemen Pendaftaran Santri Baru</p>
        <p>Dokumen ini digenerate secara otomatis.</p>
    </div>
</body>
</html>