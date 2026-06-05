<html>
<body>
    <p>Halo {{ $mahasantri->nama_lengkap }},</p>
    <p>Berikut adalah hasil ujian Anda:</p>

    <p><strong>Nama:</strong> {{ $mahasantri->nama_lengkap }}</p>
    <p><strong>Tanggal Ujian:</strong> {{ $hasil->jadwalTes ? $hasil->jadwalTes->tanggal : '-' }}</p>
    <p><strong>Jam:</strong> {{ $hasil->jadwalTes ? $hasil->jadwalTes->jam : '-' }}</p>
    <p><strong>Total Nilai:</strong> {{ $hasil->total_nilai }}</p>

    <p>File terlampir berisi detail lengkap hasil ujian Anda.</p>
    <p>Terima kasih.</p>
</body>
</html>