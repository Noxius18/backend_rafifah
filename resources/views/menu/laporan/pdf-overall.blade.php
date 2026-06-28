<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Overall</title>
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
            /* Base font dikecilkan ke 11px dan margin dipersempit agar hemat kertas */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 15px; }
        .header { text-align: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 3px solid #059669; }
        .header h1 { font-size: 18px; color: #065f46; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 9px; color: #94a3b8; margin: 2px 0; }
        .header .sub { font-size: 11px; color: #475569; font-weight: 600; }

        .section { margin-bottom: 15px; }
        .section h2 { font-size: 12px; color: #065f46; border-bottom: 2px solid #a7f3d0; padding-bottom: 3px; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── Header Gelombang (Dibuat lebih tipis) ── */
        .gelombang-header {
            display: flex; justify-content: space-between; align-items: center;
            background: #f0fdf4; border: 1.5px solid #059669; border-radius: 4px;
            padding: 6px 12px; margin: 12px 0 6px; page-break-after: avoid;
        }
        .gelombang-header .gelombang-nama { font-size: 13px; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px; }
        .gelombang-header .periode { font-size: 11px; color: #475569; }

        /* ── Tabel Detail (Padding diceperkan agar rapat & irit kertas) ── */
        table.detail { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.detail th { background: #065f46; color: #fff; padding: 6px 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; border: 1px solid #047857; }
        table.detail th:nth-child(2) { text-align: left; }
        table.detail td { padding: 5px 8px; border: 1px solid #cbd5e1; text-align: center; font-size: 11px; vertical-align: middle; }
        table.detail td:nth-child(2) { text-align: left; font-weight: 600; }
        table.detail td:last-child { text-align: left; color: #b91c1c; font-style: italic; font-size: 10px; }
        table.detail tr:nth-child(even) { background: #f8fafc; }
        table.detail tr:nth-child(odd) { background: #ffffff; }

        .badge-lulus { color: #059669; font-weight: bold; }
        .badge-tidak-lulus { color: #dc2626; font-weight: bold; }
        .badge-pertimbangan { color: #d97706; font-weight: bold; }
        .badge-belum { color: #94a3b8; }

        /* Rincian Nilai */
        .rincian-nilai { font-size: 10px; color: #334155; }
        .rincian-nilai .baris { display: block; margin-bottom: 1px; }
        .rincian-nilai .label-aspek { display: inline-block; width: 125px; font-weight: 600; }
        .rincian-nilai .nilai-aspek { font-weight: 700; color: #065f46; }
        .rincian-nilai .penguji-aspek { color: #64748b; font-size: 9px; }
        .rincian-nilai .catatan-aspek { display: block; font-size: 9px; color: #6b7280; font-style: italic; margin: 0 0 2px 130px; }

        /* ── Tabel Ringkasan (Paling Bawah) ── */
        .summary-wrapper { page-break-inside: avoid; margin-top: 20px; }
        table.summary { width: 100%; border-collapse: collapse; }
        table.summary td { padding: 8px 10px; border: 1px solid #d1d5db; font-size: 12px; }
        table.summary .label { background: #f0fdf4; font-weight: 600; color: #065f46; width: 25%; }
        table.summary .value { font-weight: 700; font-size: 13px; text-align: center; width: 12%; }
        table.summary .value-green { color: #059669; }
        table.summary .value-red { color: #dc2626; }
        table.summary .value-amber { color: #d97706; }
        table.summary .value-slate { color: #475569; }

        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <<!-- Watermark Logo Anti-Gagal (Base64) -->
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
        <h1>Laporan Hasil Tes Mahasantri</h1>
        <div class="sub">Ma'had Rafifah Andalusia MQ</div>
        <p>Dicetak: {{ $date }}</p>
    </div>

    {{-- DAFTAR MAHASANTRI PER GELOMBANG --}}
    <div class="section">
        @forelse ($gelombangData as $gelombang)
            {{-- Header Gelombang --}}
            <div class="gelombang-header">
                <div class="gelombang-nama">{{ $gelombang['nama'] }}</div>
                <div class="periode">Periode: {{ $gelombang['periode'] }}</div>
            </div>

            {{-- Tabel Mahasantri per Gelombang --}}
            <table class="detail">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th>Nama Mahasantri</th>
                        <th width="12%">Tgl Daftar</th>
                        <th width="12%">Tgl Ujian</th>
                        <th width="8%">Jam</th>
                        <th style="width: 38%;">Hasil &amp; Rincian</th>
                        <th width="10%">Status</th>
                        <th style="width: 20%">Keterangan Ketua Panitia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gelombang['mahasantri'] as $index => $h)
                        @php
                            $mhs = $h->jadwalTes->mahasantri ?? $h->mahasantri;
                            $jadwal = $h->jadwalTes;
                            
                            // Ambil nilai, penguji & catatan dari jadwal_penguji
                            $nilaiBacaan = '-';
                            $nilaiTajwid = '-';
                            $nilaiHafalan = '-';
                            $nilaiWawancara = '-';
                            $pengujiBacaanAlquran = '';
                            $pengujiTajwidTahsin = '';
                            $pengujiHafalan = '';
                            $pengujiWawancara = '';
                            $catatanBacaan = '';
                            $catatanTajwid = '';
                            $catatanHafalan = '';
                            $catatanWawancara = '';

                            if ($jadwal && $jadwal->relationLoaded('jadwalPenguji')) {
                                foreach ($jadwal->jadwalPenguji as $jp) {
                                    switch($jp->aspek_penguji) {
                                        case 'Bacaan Al-Quran':
                                            $nilaiBacaan = $jp->nilai ?? '-';
                                            $pengujiBacaanAlquran = $jp->panitia?->nama_lengkap ?? '';
                                            $catatanBacaan = $jp->catatan_penguji ?? '';
                                            break;
                                        case 'Tajwid/Tahsin':
                                            $nilaiTajwid = $jp->nilai ?? '-';
                                            $pengujiTajwidTahsin = $jp->panitia?->nama_lengkap ?? '';
                                            $catatanTajwid = $jp->catatan_penguji ?? '';
                                            break;
                                        case 'Hafalan':
                                            $nilaiHafalan = $jp->nilai ?? '-';
                                            $pengujiHafalan = $jp->panitia?->nama_lengkap ?? '';
                                            $catatanHafalan = $jp->catatan_penguji ?? '';
                                            break;
                                        case 'Wawancara':
                                            $nilaiWawancara = $jp->nilai ?? '-';
                                            $pengujiWawancara = $jp->panitia?->nama_lengkap ?? '';
                                            $catatanWawancara = $jp->catatan_penguji ?? '';
                                            break;
                                    }
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $mhs->nama_lengkap ?? '-' }}</td>
                            <td>{{ $mhs->tanggal_daftar ? \Carbon\Carbon::parse($mhs->tanggal_daftar)->locale('id')->translatedFormat('d/m/Y') : '-' }}</td>
                            <td>{{ $h->jadwalTes->tanggal ? \Carbon\Carbon::parse($h->jadwalTes->tanggal)->locale('id')->translatedFormat('d/m/Y') : '-' }}</td>
                            <td>{{ $h->jadwalTes->jam ? \Carbon\Carbon::parse($h->jadwalTes->jam)->format('H:i') : '-' }}</td>
                            <td style="vertical-align: top; text-align: left;">
                                {{-- Baris "Rata-rata:" sudah dibuang, langsung merender list rincian nilai --}}
                                <div class="rincian-nilai">
                                    <span class="baris">
                                        <span class="label-aspek">Bacaan Al-Qur'an</span>
                                        <span class="nilai-aspek">{{ $nilaiBacaan }}</span>
                                        @if($pengujiBacaanAlquran) <span class="penguji-aspek">({{ $pengujiBacaanAlquran }})</span> @endif
                                    </span>
                                    @if($catatanBacaan)
                                    <span class="catatan-aspek">› {{ $catatanBacaan }}</span>
                                    @endif
                                    <span class="baris">
                                        <span class="label-aspek">Tajwid &amp; Tahsin</span>
                                        <span class="nilai-aspek">{{ $nilaiTajwid }}</span>
                                        @if($pengujiTajwidTahsin) <span class="penguji-aspek">({{ $pengujiTajwidTahsin }})</span> @endif
                                    </span>
                                    @if($catatanTajwid)
                                    <span class="catatan-aspek">› {{ $catatanTajwid }}</span>
                                    @endif
                                    <span class="baris">
                                        <span class="label-aspek">Hafalan</span>
                                        <span class="nilai-aspek">{{ $nilaiHafalan }}</span>
                                        @if($pengujiHafalan) <span class="penguji-aspek">({{ $pengujiHafalan }})</span> @endif
                                    </span>
                                    @if($catatanHafalan)
                                    <span class="catatan-aspek">› {{ $catatanHafalan }}</span>
                                    @endif
                                    <span class="baris">
                                        <span class="label-aspek">Wawancara</span>
                                        <span class="nilai-aspek">{{ $nilaiWawancara }}</span>
                                        @if($pengujiWawancara) <span class="penguji-aspek">({{ $pengujiWawancara }})</span> @endif
                                    </span>
                                    @if($catatanWawancara)
                                    <span class="catatan-aspek">› {{ $catatanWawancara }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($h->status === 'Lulus') <span class="badge-lulus">Lulus</span>
                                @elseif ($h->status === 'Tidak Lulus') <span class="badge-tidak-lulus">Tidak Lulus</span>
                                @elseif ($h->status === 'Pertimbangan') <span class="badge-pertimbangan">Pertimbangan</span>
                                @else <span class="badge-belum">Belum Tes</span>
                                @endif
                            </td>
                            <td>{{ $h->jadwalTes->catatan_ketua ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada data nilai.</p>
        @endforelse
    </div>

    {{-- STATUS / RINGKASAN DI PALING BAWAH ── (Dibuat lebih ceper) --}}
    <div class="section summary-wrapper">
        <h2>Ringkasan Status Kelulusan Keseluruhan</h2>
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

    <div class="footer">
        <p>© {{ date('Y') }} Ma'had Rafifah Andalusia MQ — Sistem Manajemen Pendaftaran Santri Baru</p>
    </div>
</body>
</html>