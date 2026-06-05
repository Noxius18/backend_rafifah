<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-weight: normal;
        }
        .content {
            margin-top: 10px;
        }
        .content p {
            margin: 5px 0;
        }
        .content strong {
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Hasil Pengujian {{ $judulTanggal }}</h2>
    </div>

    <div class="content">
        <p><strong>Nama Mahasiswa:</strong> {{ $mahasantri->nama_lengkap }}</p>
        <p><strong>Email:</strong> {{ $mahasantri->email }}</p>
        <p><strong>NIM:</strong> {{ $mahasantri->nim }}</p>

        <p><strong>Hasil Nilai:</strong></p>
        <ul>
            <li><strong>Nilai Tajwid:</strong> {{ $hasil->nilai_tajwid }}</li>
            <li><strong>Nilai Tahsin:</strong> {{ $hasil->nilai_tahsin }}</li>
            <li><strong>Nilai Kelancaran:</strong> {{ $hasil->nilai_kelancaran }}</li>
            <li><strong>Nilai Wawancara:</strong> {{ $hasil->nilai_wawancara }}</li>
            <li><strong>Total Nilai:</strong> {{ $hasil->total_nilai }}</li>
        </ul>

        <p><strong>Catatan Penguji:</strong> {{ $hasil->catatan_penguji }}</p>
    </div>

    <div class="footer">
        {{ now()->format('d F Y') }}
    </div>
</body>
</html>