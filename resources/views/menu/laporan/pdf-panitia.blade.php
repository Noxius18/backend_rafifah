<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Panitia Seleksi</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #059669; }
        .header h1 { font-size: 20px; color: #065f46; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #94a3b8; margin: 3px 0; }
        .header .sub { font-size: 12px; color: #475569; font-weight: 600; }

        .section { margin-bottom: 25px; }
        .section h2 { font-size: 13px; color: #065f46; border-bottom: 2px solid #a7f3d0; padding-bottom: 5px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── Metadata Informasi (Sejajar) ── */
        .meta-info {
            margin: 15px 0 25px 0;
            padding: 10px 0;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .meta-info table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
        }
        .meta-info td {
            border: none;
            padding: 4px 8px;
            text-align: left;
            font-size: 12px;
            vertical-align: top;
        }
        .meta-info .label {
            font-weight: 600;
            color: #475569;
            width: 140px;
        }
        .meta-info .value {
            font-weight: 700;
            color: #065f46;
        }

        /* ── Tabel Detail Penguji ── */
        table.detail { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.detail th { background: #065f46; color: #fff; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; border: 1px solid #047857; }
        table.detail th:nth-child(2) { text-align: left; }
        table.detail td { padding: 8px 10px; border: 1px solid #cbd5e1; text-align: center; font-size: 12px; vertical-align: middle; }
        table.detail td:nth-child(2) { text-align: left; font-weight: 600; }
        table.detail tr:nth-child(even) { background: #f8fafc; }
        table.detail tr:nth-child(odd) { background: #ffffff; }

        .footer { margin-top: 35px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Panitia & Jadwal Seleksi</h1>
        <div class="sub">Ma'had Rafifah Andalusia MQ</div>
        <p>Dicetak: {{ $date }}</p>
    </div>

    <div class="section">
        {{-- Bagian Informasi --}}
        <div class="meta-info">
            <table>
                <tr>
                    <td class="label">Gelombang Seleksi</td>
                    <td>: <span class="value">{{ $gelombang->nama }}</span></td>
                </tr>
                <tr>
                    <td class="label">Periode Pelaksanaan</td>
                    <td>: <span class="value" style="color: #334155;">{{ \Carbon\Carbon::parse($gelombang->start_date)->format('d F Y') }} - {{ \Carbon\Carbon::parse($gelombang->end_date)->format('d F Y') }}</span></td>
                </tr>
                <tr>
                    <td class="label">Penanggung Jawab</td>
                    <td>: <span class="value">{{ $penanggungJawab->nama_lengkap ?? 'Belum Ditentukan' }}</span></td>
                </tr>
            </table>
        </div>

        {{-- Tabel Penguji --}}
        <h2>Daftar Penguji Bertugas</h2>
        <table class="detail">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Penguji</th>
                    <th width="50%">Bidang / Keahlian Uji</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengujiList as $index => $penguji)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $penguji['nama'] }}</td>
                        <td style="text-align: center;">{{ $penguji['bidang_uji'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #94a3b8; font-style: italic; padding: 15px;">Belum ada penguji yang ditugaskan untuk gelombang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Ma'had Rafifah Andalusia MQ — Sistem Manajemen Pendaftaran Santri Baru</p>
    </div>
</body>
</html>