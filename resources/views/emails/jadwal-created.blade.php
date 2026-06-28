<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $isRevision ? 'Perbaikan Jadwal Perlu Persetujuan' : 'Jadwal Baru Perlu Persetujuan' }}</title>
    </head>
<body style="margin: 0; padding: 0; background-color: #eef2f7; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #eef2f7;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background: linear-gradient(135deg, #065f46 0%, #059669 60%, #34d399 100%); padding: 36px 40px 32px 40px; text-align: center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin-bottom: 16px;">
                                <tr>
                                    <td style="background-color: rgba(255,255,255,0.15); border-radius: 50%; width: 56px; height: 56px; text-align: center; vertical-align: middle; font-size: 26px; line-height: 56px;">
                                        {{ $isRevision ? '🔄' : '🔔' }}
                                    </td>
                                </tr>
                            </table>
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 700; margin: 0 0 6px 0; letter-spacing: -0.3px;">
                                {{ $isRevision ? 'Perbaikan / Revisi Jadwal Ujian' : 'Pengajuan Jadwal Ujian Baru' }}
                            </h1>
                            <p style="color: rgba(255,255,255,0.75); font-size: 13px; margin: 0;">
                                {{ $isRevision ? 'Pemberitahuan pembaruan data seleksi mahasantri' : 'Jadwal seleksi baru memerlukan persetujuan Anda' }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 36px 40px 28px 40px;">

                            <p style="color: #064e3b; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                                Assalamu'alaikum Wr. Wb. Yth. {{ $namaKetua }}
                            </p>
                            <p style="color: #64748b; font-size: 14px; line-height: 1.7; margin: 0 0 28px 0;">
                                @if($isRevision)
                                    Panitia <strong>{{ $pembuat->nama_lengkap }}</strong> telah melakukan <strong>perbaikan / revisi</strong> terhadap jadwal ujian seleksi mahasantri yang sebelumnya diajukan perubahan. Berikut adalah rincian data jadwal yang telah diperbarui.
                                @else
                                    Seorang panitia bernama <strong>{{ $pembuat->nama_lengkap }}</strong> telah membuat ajuan <strong>jadwal ujian seleksi mahasantri baru</strong> yang memerlukan peninjauan dan persetujuan Anda.
                                @endif
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 24px 24px 8px 24px;">
                                        <p style="color: #94a3b8; font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; margin: 0 0 16px 0;">
                                            {{ $isRevision ? 'Rincian Perbaikan Jadwal' : 'Detail Pengajuan Jadwal' }}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 0 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px; width: 45%; vertical-align: middle;">
                                                    📅&nbsp; Tanggal Pelaksanaan
                                                </td>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #064e3b; font-size: 14px; font-weight: 600; vertical-align: middle;">
                                                    {{ \Carbon\Carbon::parse($jadwalTes->tanggal)->locale('id')->translatedFormat('d F Y') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 0 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px; width: 45%; vertical-align: middle;">
                                                    🕐&nbsp; Jam Mulai Ujian
                                                </td>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #064e3b; font-size: 14px; font-weight: 600; vertical-align: middle;">
                                                    {{ \Carbon\Carbon::parse($jadwalTes->jam)->format('H:i') }} WIB
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 0 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px; width: 45%; vertical-align: middle;">
                                                    👥&nbsp; Jumlah Kuota Mahasantri
                                                </td>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #064e3b; font-size: 14px; font-weight: 600; vertical-align: middle;">
                                                    {{ $jumlah }} Orang
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 0 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px; width: 45%; vertical-align: middle;">
                                                    🔗&nbsp; Link Fasilitas Zoom
                                                </td>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #059669; font-size: 13px; font-weight: 600; vertical-align: middle; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    @if($jadwalTes->link_zoom)
                                                        <a href="{{ $jadwalTes->link_zoom }}" target="_blank" style="color: #059669; text-decoration: underline;">Buka Link Zoom</a>
                                                    @else
                                                        <span style="color: #94a3b8; font-style: italic;">Belum Diatur</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 0 24px 24px 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #ecfdf5; border-radius: 8px; margin-top: 12px;">
                                            <tr>
                                                <td style="padding: 14px 16px; color: #065f46; font-size: 13px; font-weight: 600; width: 45%; vertical-align: middle;">
                                                    👤&nbsp; Penanggung Jawab
                                                </td>
                                                <td style="padding: 14px 16px; color: #059669; font-size: 15px; font-weight: 700; vertical-align: middle; text-align: right;">
                                                    {{ $pembuat->nama_lengkap }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #64748b; font-size: 14px; line-height: 1.7; margin: 0;">
                                Silakan masuk ke dashboard Sistem Manajemen Pendaftaran Mahasantri Ma'had Rafifah Andalusia MQ untuk memeriksa detail penugasan penguji, memberikan catatan revisi, atau langsung menyetujui ajuan jadwal ini secara keseluruhan.
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px 40px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0 0 4px 0; line-height: 1.6;">
                                Email ini dikirim secara otomatis oleh sistem aplikasi. Mohon untuk tidak membalas pesan email ini.
                            </p>
                            <p style="color: #cbd5e1; font-size: 11px; margin: 0;">
                                &copy; {{ date('Y') }} Ma'had Rafifah Andalusia MQ. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                </td>
        </tr>
    </table>
    </body>
</html>