<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Nilai {{ $mahasantri->nama_lengkap }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 3px solid #059669; }
        .header h1 { font-size: 18px; color: #065f46; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #94a3b8; margin: 2px 0; }

        .card { border: 1.5px solid #d1d5db; border-radius: 8px; margin-bottom: 18px; overflow: hidden; }
        .card-header { background: #065f46; color: #fff; padding: 8px 14px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-body { padding: 14px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 8px; font-size: 13px; }
        .info-table .label { color: #64748b; width: 140px; font-weight: 600; }
        .info-table .value { font-weight: 700; color: #1e293b; }

        .nilai-grid { display: flex; gap: 12px; margin-top: 10px; }
        .nilai-item { flex: 1; text-align: center; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 6px; background: #f8fafc; }
        .nilai-item .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .nilai-item .value { font-size: 22px; font-weight: 800; color: #065f46; margin-top: 4px; }
        .nilai-item .penguji { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        .catatan-box { margin-top: 14px; padding: 10px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; }
        .catatan-box .label { font-size: 11px; color: #92400e; font-weight: 600; }
        .catatan-box .value { font-size: 13px; color: #78350f; margin-top: 2px; }

        .result-row { display: flex; gap: 20px; margin-top: 14px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 6px; }
        .result-item { flex: 1; }
        .result-item .label { font-size: 11px; color: #64748b; }
        .result-item .value { font-size: 16px; font-weight: 700; }
        .result-item .value-green { color: #059669; }
        .result-item .value-red { color: #dc2626; }
        .result-item .value-amber { color: #d97706; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Data Nilai {{ $mahasantri->nama_lengkap }}</h1>
        <p>Dicetak: {{ $date }}</p>
    </div>

    @forelse ($hasilTes as $h)
    @php
        $pengujiTajwid = $h->jadwalTes->pengujiTajwid?->nama_lengkap ?? '';
        $pengujiTahsin = $h->jadwalTes->pengujiTahsin?->nama_lengkap ?? '';
        $pengujiKelancaran = $h->jadwalTes->pengujiKelancaran?->nama_lengkap ?? '';
        $pengujiWawancara = $h->jadwalTes->pengujiWawancara?->nama_lengkap ?? '';
    @endphp
    <div class="card">
        <div class="card-header">Hasil Tes</div>
        <div class="card-body">
            <table class="info-table">
                <tr>
                    <td class="label">Tanggal Daftar</td>
                    <td class="value">{{ $mahasantri->tanggal_daftar ? \Carbon\Carbon::parse($mahasantri->tanggal_daftar)->format('d/m/Y') : '-' }}</td>
                    <td class="label">Tanggal Ujian</td>
                    <td class="value">{{ $h->jadwalTes?->tanggal ? \Carbon\Carbon::parse($h->jadwalTes->tanggal)->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Jam</td>
                    <td class="value">{{ $h->jadwalTes?->jam ? \Carbon\Carbon::parse($h->jadwalTes->jam)->format('H:i') : '-' }}</td>
                    <td class="label">Gelombang</td>
                    <td class="value">{{ $mahasantri->id_mahasantri ? \App\Models\User::extractGelombangNama($mahasantri->id_mahasantri) : '-' }}</td>
                </tr>
            </table>

            <div class="nilai-grid">
                <div class="nilai-item">
                    <div class="label">Tajwid</div>
                    <div class="value">{{ $h->nilai_tajwid ?? '-' }}</div>
                    @if($pengujiTajwid) <div class="penguji">{{ $pengujiTajwid }}</div> @endif
                </div>
                <div class="nilai-item">
                    <div class="label">Tahsin</div>
                    <div class="value">{{ $h->nilai_tahsin ?? '-' }}</div>
                    @if($pengujiTahsin) <div class="penguji">{{ $pengujiTahsin }}</div> @endif
                </div>
                <div class="nilai-item">
                    <div class="label">Kelancaran</div>
                    <div class="value">{{ $h->nilai_kelancaran ?? '-' }}</div>
                    @if($pengujiKelancaran) <div class="penguji">{{ $pengujiKelancaran }}</div> @endif
                </div>
                <div class="nilai-item">
                    <div class="label">Wawancara</div>
                    <div class="value">{{ $h->nilai_wawancara ?? '-' }}</div>
                    @if($pengujiWawancara) <div class="penguji">{{ $pengujiWawancara }}</div> @endif
                </div>
            </div>

            @if($h->catatan_penguji)
            <div class="catatan-box">
                <div class="label">Catatan Penguji</div>
                <div class="value">{{ $h->catatan_penguji }}</div>
            </div>
            @endif

            <div class="result-row">
                <div class="result-item">
                    <div class="label">Total Nilai</div>
                    <div class="value" style="color: #065f46;">{{ $h->total_nilai ?? '-' }}</div>
                </div>
                <div class="result-item">
                    <div class="label">Status</div>
                    <div class="value {{ $h->status === 'Lulus' ? 'value-green' : ($h->status === 'Tidak Lulus' ? 'value-red' : ($h->status === 'Pertimbangan' ? 'value-amber' : '')) }}">
                        {{ $h->status ?? 'Belum Tes' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-header">Hasil Tes</div>
        <div class="card-body" style="text-align: center; padding: 30px; color: #94a3b8;">Belum ada nilai untuk mahasantri ini.</div>
    </div>
    @endforelse

    <div class="footer"><p>© {{ date('Y') }} Ma'had Rafifah Andalusia MQ — Sistem Manajemen Pendaftaran Santri Baru</p></div>
</body>
</html>