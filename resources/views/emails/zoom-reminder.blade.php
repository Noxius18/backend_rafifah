<html>
<body>
    <p>Halo {{ $mahasantri->nama_lengkap }},</p>
    <p>Jadwal ujian Anda telah ditetapkan:</p>
    <ul>
        <li>Tanggal: {{ $jadwalTes->tanggal }}</li>
        <li>Jam: {{ $jadwalTes->jam }}</li>
    </ul>
    <p>Link Zoom: <a href="{{ $jadwalTes->link_zoom }}">{{ $jadwalTes->link_zoom }}</a></p>
    <p>Selamat mengerjakan. Sampai jumpa!</p>
</body>
</html>