<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; padding: 20px;">
<h2>🔔 Jadwal Baru Perlu Persetujuan</h2>
<p>Halo <strong>Ketua Panitia</strong>,</p>
<p>Seorang panitia telah membuat jadwal seleksi baru yang perlu Anda setujui:</p>
<table style="border-collapse: collapse; width: 100%; max-width: 500px;">
<tr><td style="padding: 6px 8px; font-weight: bold;">Tanggal</td><td>: {{ $jadwal->tanggal }}</td></tr>
<tr><td style="padding: 6px 8px; font-weight: bold;">Jam Mulai</td><td>: {{ $jadwal->jam }}</td></tr>
<tr><td style="padding: 6px 8px; font-weight: bold;">Jumlah Mahasantri</td><td>: {{ $jumlahMahasantri }}</td></tr>
<tr><td style="padding: 6px 8px; font-weight: bold;">Dibuat Oleh</td><td>: {{ $pembuat->nama_lengkap }}</td></tr>
</table>
<p>Silakan login ke sistem untuk menyetujui atau mengajukan perubahan jadwal ini.</p>
<p>Salam,<br>Sistem Pendaftaran Mahasantri</p>
</body>
</html>
