<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Nilai</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 3px solid #059669; }
        .header h1 { font-size: 20px; color: #065f46; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #94a3b8; margin: 2px 0; }

        .card { border: 1.5px solid #d1d5db; border-radius: 8px; margin-bottom: 18px; overflow: hidden; }
        .card-header { background: #065f46; color: #fff; padding: 8px 14px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-body { padding: 14px; }
        
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px 8px; font-size: 12px; border-bottom: 1px dashed #e2e8f0; }
        .info-table tr:last-child td { border-bottom: none; }
        .info-table .label { color: #64748b; width: 130px; font-weight: 600; }
        .info-table .value { font-weight: 700; color: #1e293b; }

        .nilai-grid { display: flex; gap: 12px; margin-top: 15px; }
        .nilai-item { flex: 1; text-align: center; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 6px; background: #f8fafc; }
        .nilai-item .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
        .nilai-item .value { font-size: 24px; font-weight: 800; color: #065f46; margin-top: 6px; }

        .result-row { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding: 12px 14px; background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 6px; }
        .result-item .label { font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .result-item .value { font-size: 18px; font-weight: 800; margin-top: 3px; }
        .result-item .value-green { color: #059669; }
        .result-item .value-red { color: #dc2626; }
        .result-item .value-amber { color: #d97706; }

        .catatan-box { margin-top: 15px; padding: 12px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; }
        .catatan-box .label { font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
        .catatan-box .value { font-size: 13px; color: #78350f; line-height: 1.5; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    @php
        $mhsNamaTitle = $jadwal ? ($jadwal->mahasantri->nama_lengkap ?? '') : 'KESELURUHAN';
    @endphp

    <div class="header">
        <h1>LAPORAN NILAI ({{ strtoupper($mhsNamaTitle) }})</h1>
        <p>Dicetak: {{ $date }}</p>
    </div>

    @forelse ($hasilTes as $h)
    @php
        // Tarik data mahasantri lewat jadwal agar lebih akurat relasinya
        $mhs = $h->jadwalTes->mahasantri ?? $h->mahasantri;
        $jadwal = $h->jadwalTes;
        
        // Ambil nilai per aspek dari jadwal_penguji
        $nilaiPerAspek = [];
        $pengujiPerAspek = [];
        if ($jadwal && $jadwal->relationLoaded('jadwalPenguji')) {
            foreach ($jadwal->jadwalPenguji as $jp) {
                $nilaiPerAspek[$jp->aspek_penguji] = $jp->nilai;
                $pengujiPerAspek[$jp->aspek_penguji] = $jp->panitia?->nama_lengkap ?? '-';
            }
        }
        
        $nilaiBacaan = $nilaiPerAspek['Bacaan Al-Quran'] ?? '-';
        $nilaiTajwid = $nilaiPerAspek['Tajwid/Tahsin'] ?? '-';
        $nilaiHafalan = $nilaiPerAspek['Hafalan'] ?? '-';
        $nilaiWawancara = $nilaiPerAspek['Wawancara'] ?? '-';
        
        $pengujiBacaan = $pengujiPerAspek['Bacaan Al-Quran'] ?? '-';
        $pengujiTajwid = $pengujiPerAspek['Tajwid/Tahsin'] ?? '-';
        $pengujiHafalan = $pengujiPerAspek['Hafalan'] ?? '-';
        $pengujiWawancara = $pengujiPerAspek['Wawancara'] ?? '-';
    @endphp
    <div class="card">
        <div class="card-header">HASIL TES — {{ strtoupper($mhs->nama_lengkap ?? 'MAHASANTRI') }}</div>
        <div class="card-body">
            <table class="info-table">
                <tr>
                    <td class="label">Nama Mahasantri</td>
                    <td class="value">{{ $mhs->nama_lengkap ?? '-' }}</td>
                    <td class="label">Tanggal Daftar</td>
                    <td class="value">{{ $mhs->tanggal_daftar ? \Carbon\Carbon::parse($mhs->tanggal_daftar)->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Gelombang</td>
                    <td class="value">{{ $mhs->gelombang ?? '-' }}</td>
                    <td class="label">Tanggal Ujian</td>
                    <td class="value">{{ $jadwal?->tanggal ? \Carbon\Carbon::parse($jadwal->tanggal)->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Jam Pelaksanaan</td>
                    <td class="value">{{ $jadwal?->jam ? \Carbon\Carbon::parse($jadwal->jam)->format('H:i') : '-' }}</td>
                    <td class="label">ID Jadwal</td>
                    <td class="value">{{ $h->id_jadwal ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Penguji Bacaan Al-Qur'an</td>
                    <td class="value">{{ $pengujiBacaan }}</td>
                    <td class="label">Penguji Tajwid/Tahsin</td>
                    <td class="value">{{ $pengujiTajwid }}</td>
                </tr>
                <tr>
                    <td class="label">Penguji Hafalan</td>
                    <td class="value">{{ $pengujiHafalan }}</td>
                    <td class="label">Penguji Wawancara</td>
                    <td class="value">{{ $pengujiWawancara }}</td>
                </tr>
            </table>

            <div class="nilai-grid">
                <div class="nilai-item"><div class="label">Bacaan Al-Qur'an</div><div class="value">{{ $nilaiBacaan }}</div></div>
                <div class="nilai-item"><div class="label">Tajwid/Tahsin</div><div class="value">{{ $nilaiTajwid }}</div></div>
                <div class="nilai-item"><div class="label">Hafalan</div><div class="value">{{ $nilaiHafalan }}</div></div>
                <div class="nilai-item"><div class="label">Wawancara</div><div class="value">{{ $nilaiWawancara }}</div></div>
            </div>

            <div class="result-row">
                <div class="result-item">
                    <div class="label">Total Nilai Rata-Rata</div>
                    <div class="value" style="color: #065f46; font-size: 22px;">{{ $h->total_nilai ?? '-' }}</div>
                </div>
                <div class="result-item" style="text-align: right;">
                    <div class="label">Status Kelulusan</div>
                    <div class="value {{ $h->status === 'Lulus' ? 'value-green' : ($h->status === 'Tidak Lulus' ? 'value-red' : ($h->status === 'Pertimbangan' ? 'value-amber' : '')) }}" style="font-size: 22px;">
                        {{ $h->status ?? 'Belum Tes' }}
                    </div>
                </div>
            </div>

            @if($jadwal?->catatan_ketua)
            <div class="catatan-box">
                <div class="label">Catatan / Keterangan</div>
                <div class="value">{{ $jadwal->catatan_ketua }}</div>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-header">HASIL TES</div>
        <div class="card-body" style="text-align: center; padding: 30px; color: #94a3b8;">Belum ada nilai untuk jadwal ini.</div>
    </div>
    @endforelse

    <div class="footer">
        <p>© {{ date('Y') }} Ma'had Rafifah Andalusia MQ — Sistem Manajemen Pendaftaran Santri Baru</p>
    </div>
</body>
</html>