<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Overall</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #059669; }
        .header h1 { font-size: 20px; color: #065f46; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #94a3b8; margin: 3px 0; }
        .header .sub { font-size: 12px; color: #475569; font-weight: 600; }

        .section { margin-bottom: 20px; }
        .section h2 { font-size: 13px; color: #065f46; border-bottom: 2px solid #a7f3d0; padding-bottom: 5px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── Header Gelombang ── */
        .gelombang-header {
            display: flex; justify-content: space-between; align-items: center;
            background: #f0fdf4; border: 1.5px solid #059669; border-radius: 6px;
            padding: 8px 14px; margin: 16px 0 8px; page-break-after: avoid;
        }
        .gelombang-header .gelombang-nama { font-size: 14px; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px; }
        .gelombang-header .periode { font-size: 11px; color: #475569; }
        .gelombang-header .penanggung-jawab { font-size: 11px; color: #047857; }
        .gelombang-header .penanggung-jawab span { font-weight: 600; }

        /* ── Tabel Detail ── */
        table.detail { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.detail th { background: #065f46; color: #fff; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; border: 1px solid #047857; }
        table.detail th:nth-child(2) { text-align: left; }
        table.detail td { padding: 8px 10px; border: 1px solid #cbd5e1; text-align: center; font-size: 12px; vertical-align: middle; }
        table.detail td:nth-child(2) { text-align: left; font-weight: 600; }
        table.detail td:last-child { text-align: left; color: #b91c1c; font-style: italic; font-size: 11px; }
        table.detail tr:nth-child(even) { background: #f8fafc; }
        table.detail tr:nth-child(odd) { background: #ffffff; }

        .badge-lulus { color: #059669; font-weight: bold; }
        .badge-tidak-lulus { color: #dc2626; font-weight: bold; }
        .badge-pertimbangan { color: #d97706; font-weight: bold; }
        .badge-belum { color: #94a3b8; }

        /* Rincian Nilai */
        .rincian-nilai { font-size: 10px; color: #334155; margin-top: 4px; border-top: 1px dashed #cbd5e1; padding-top: 4px; }
        .rincian-nilai .baris { display: block; margin-bottom: 2px; }
        .rincian-nilai .label-aspek { display: inline-block; width: 130px; font-weight: 600; }
        .rincian-nilai .nilai-aspek { font-weight: 700; color: #065f46; }
        .rincian-nilai .penguji-aspek { color: #64748b; font-size: 9px; }

        /* ── Tabel Ringkasan (Paling Bawah) ── */
        .summary-wrapper { page-break-inside: avoid; margin-top: 30px; }
        table.summary { width: 100%; border-collapse: collapse; }
        table.summary td { padding: 10px 12px; border: 1px solid #d1d5db; font-size: 13px; }
        table.summary .label { background: #f0fdf4; font-weight: 600; color: #065f46; width: 25%; }
        table.summary .value { font-weight: 700; font-size: 15px; text-align: center; width: 12%; }
        table.summary .value-green { color: #059669; }
        table.summary .value-red { color: #dc2626; }
        table.summary .value-amber { color: #d97706; }
        table.summary .value-slate { color: #475569; }

        .footer { margin-top: 25px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Hasil Seleksi Mahasantri</h1>
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
                        <th>No</th>
                        <th>Nama Mahasantri</th>
                        <th>Tgl Daftar</th>
                        <th>Tgl Ujian</th>
                        <th>Jam</th>
                        <th style="width: 25%;">Hasil (Rata-rata & Rincian)</th>
                        <th>Status</th>
                        <th style="width: 20%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gelombang['mahasantri'] as $index => $h)
                        @php
                            $mhs = $h->jadwalTes->mahasantri ?? $h->mahasantri;
                            $pengujiBacaanAlquran = $h->jadwalTes->pengujiBacaanAlquran?->nama_lengkap ?? '';
                            $pengujiTajwidTahsin = $h->jadwalTes->pengujiTajwidTahsin?->nama_lengkap ?? '';
                            $pengujiHafalan = $h->jadwalTes->pengujiHafalan?->nama_lengkap ?? '';
                            $pengujiWawancara = $h->jadwalTes->pengujiWawancara?->nama_lengkap ?? '';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $mhs->nama_lengkap ?? '-' }}</td>
                            <td>{{ $mhs->tanggal_daftar ? \Carbon\Carbon::parse($mhs->tanggal_daftar)->format('d/m/Y') : '-' }}</td>
                            <td>{{ $h->jadwalTes->tanggal ? \Carbon\Carbon::parse($h->jadwalTes->tanggal)->format('d/m/Y') : '-' }}</td>
                            <td>{{ $h->jadwalTes->jam ? \Carbon\Carbon::parse($h->jadwalTes->jam)->format('H:i') : '-' }}</td>
                            <td style="vertical-align: top;">
                                <strong style="font-size: 13px; color: #065f46;">Nilai Akhir: {{ $h->total_nilai ?? '-' }}</strong>
                                <div class="rincian-nilai">
                                    <span class="baris">
                                        <span class="label-aspek">Bacaan Al-Qur'an</span>
                                        <span class="nilai-aspek">{{ $h->nilai_bacaan_al_quran ?? '-' }}</span>
                                        @if($pengujiBacaanAlquran) <span class="penguji-aspek">({{ $pengujiBacaanAlquran }})</span> @endif
                                    </span>
                                    <span class="baris">
                                        <span class="label-aspek">Tajwid &amp; Tahsin</span>
                                        <span class="nilai-aspek">{{ $h->nilai_tajwid_tahsin ?? '-' }}</span>
                                        @if($pengujiTajwidTahsin) <span class="penguji-aspek">({{ $pengujiTajwidTahsin }})</span> @endif
                                    </span>
                                    <span class="baris">
                                        <span class="label-aspek">Hafalan</span>
                                        <span class="nilai-aspek">{{ $h->nilai_hafalan ?? '-' }}</span>
                                        @if($pengujiHafalan) <span class="penguji-aspek">({{ $pengujiHafalan }})</span> @endif
                                    </span>
                                    <span class="baris">
                                        <span class="label-aspek">Wawancara</span>
                                        <span class="nilai-aspek">{{ $h->nilai_wawancara ?? '-' }}</span>
                                        @if($pengujiWawancara) <span class="penguji-aspek">({{ $pengujiWawancara }})</span> @endif
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if ($h->status === 'Lulus') <span class="badge-lulus">Lulus</span>
                                @elseif ($h->status === 'Tidak Lulus') <span class="badge-tidak-lulus">Tidak Lulus</span>
                                @elseif ($h->status === 'Pertimbangan') <span class="badge-pertimbangan">Pertimbangan</span>
                                @else <span class="badge-belum">Belum Tes</span>
                                @endif
                            </td>
                            <td>{{ $h->catatan_penguji ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada data nilai.</p>
        @endforelse
    </div>

    {{-- STATUS / RINGKASAN DI PALING BAWAH --}}
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