<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; padding: 20px;">
<h2>{{ $jenis === 'Dibatalkan' ? '❌ Jadwal Dibatalkan' : '🔄 Jadwal Dijadwalkan Ulang' }}</h2>
<p>Halo <strong>Ketua Panitia</strong>,</p>
<p>Seorang panitia telah {{ $jenis === 'Dibatalkan' ? 'membatalkan' : 'menjadwalkan ulang' }} jadwal:</p>
<table style="border-collapse: collapse; width: 100%; max-width: 500px;">
<tr><td style="padding: 6px 8px; font-weight: bold;">Tanggal</td><td>: {{ $tanggal }}</td></tr>
<tr><td style="padding: 6px 8px; font-weight: bold;">Dibatalkan Oleh</td><td>: {{ $pembatal }}</td></tr>
<tr><td style="padding: 6px 8px; font-weight: bold;">Alasan</td><td>: {{ $alasan }}</td></tr>
</table>
<p>Silakan login ke sistem untuk melihat detailnya.</p>
<p>Salam,<br>Sistem Pendaftaran Mahasantri</p>
</body>
</html>
