<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Panitia</title>
    <style>
        .watermark-wrapper {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 360px;
            text-align: center;
            opacity: 0.10;
            z-index: -1000;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 24px;
        }

        .header {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 3px solid #059669;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 20px;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header p {
            margin: 2px 0;
            color: #64748b;
        }

        .summary {
            width: 100%;
            margin: 0 0 18px;
            border-collapse: collapse;
        }

        .summary td {
            padding: 4px 0;
            vertical-align: top;
        }

        .summary .label {
            width: 120px;
            font-weight: 600;
            color: #475569;
        }

        .summary .value {
            font-weight: 700;
            color: #065f46;
        }

        table.detail {
            width: 100%;
            border-collapse: collapse;
        }

        table.detail th {
            padding: 9px 10px;
            background: #065f46;
            color: #fff;
            border: 1px solid #047857;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
        }

        table.detail td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        table.detail tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="watermark-wrapper">
        @php
            $imagePath = public_path('assets/Logo_2.png');
            $base64 = '';

            if (file_exists($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $base64 = 'data:image/png;base64,' . $imageData;
            }
        @endphp

        @if($base64)
            <img src="{{ $base64 }}" style="width: 100%;" alt="Logo Ma'had Rafifah Andalusia MQ" />
        @endif
    </div>

    <div class="header">
        <h1>Daftar Panitia</h1>
        <p>Ma'had Rafifah Andalusia MQ</p>
        <p>Dicetak: {{ $date }}</p>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Total Data</td>
            <td>: <span class="value">{{ $panitias->count() }} panitia</span></td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th width="12%">ID</th>
                <th width="28%">Nama Lengkap</th>
                <th width="18%">Username</th>
                <th width="20%">No. HP</th>
                <th width="22%">Jabatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($panitias as $panitia)
                <tr>
                    <td>{{ $panitia->id_panitia }}</td>
                    <td>{{ $panitia->nama_lengkap }}</td>
                    <td>{{ $panitia->username }}</td>
                    <td>{{ $panitia->no_hp }}</td>
                    <td>{{ $panitia->jabatan }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Belum ada data panitia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} Ma'had Rafifah Andalusia MQ
    </div>
</body>
</html>
