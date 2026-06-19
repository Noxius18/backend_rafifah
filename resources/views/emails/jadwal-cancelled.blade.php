<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $jenis === 'Dibatalkan' ? 'Jadwal Dibatalkan' : 'Jadwal Dijadwalkan Ulang' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #eef2f7; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

    <!-- Outer wrapper -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #eef2f7;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <!-- Email card -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

                    <!-- ===== HEADER ===== -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #065f46 0%, #059669 60%, #34d399 100%); padding: 36px 40px 32px 40px; text-align: center;">
                            <!-- Icon badge -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin-bottom: 16px;">
                                <tr>
                                    <td style="background-color: rgba(255,255,255,0.15); border-radius: 50%; width: 56px; height: 56px; text-align: center; vertical-align: middle; font-size: 26px; line-height: 56px;">
                                        {{ $jenis === 'Dibatalkan' ? '❌' : '🔄' }}
                                    </td>
                                </tr>
                            </table>
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 700; margin: 0 0 6px 0; letter-spacing: -0.3px;">
                                {{ $jenis === 'Dibatalkan' ? 'Jadwal Dibatalkan' : 'Jadwal Dijadwalkan Ulang' }}
                            </h1>
                            <p style="color: rgba(255,255,255,0.75); font-size: 13px; margin: 0;">{{ $subtitle ?? 'Notifikasi resmi dari sistem kami' }}</p>
                        </td>
                    </tr>

                    <!-- ===== BODY ===== -->
                    <tr>
                        <td style="padding: 36px 40px 28px 40px;">

                            <!-- Greeting -->
                            <p style="color: #064e3b; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                                Halo <strong>Ketua Panitia</strong>,
                            </p>
                            <p style="color: #64748b; font-size: 14px; line-height: 1.7; margin: 0 0 28px 0;">
                                Seorang panitia telah {{ $jenis === 'Dibatalkan' ? 'membatalkan' : 'menjadwalkan ulang' }} jadwal seleksi. Berikut informasinya.
                            </p>

                            <!-- Info card -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 24px 24px 8px 24px;">
                                        <p style="color: #94a3b8; font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; margin: 0 0 16px 0;">Informasi Jadwal</p>
                                    </td>
                                </tr>

                                <!-- Row: Tanggal -->
                                <tr>
                                    <td style="padding: 0 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px; width: 45%; vertical-align: middle;">
                                                    📅&nbsp; Tanggal
                                                </td>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #064e3b; font-size: 14px; font-weight: 600; vertical-align: middle;">
                                                    {{ $tanggal }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Row: Dibuat Oleh -->
                                <tr>
                                    <td style="padding: 0 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px; width: 45%; vertical-align: middle;">
                                                    👤&nbsp; {{ $jenis === 'Dibatalkan' ? 'Dibatalkan' : 'Dijadwalkan Ulang' }} Oleh
                                                </td>
                                                <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #064e3b; font-size: 14px; font-weight: 600; vertical-align: middle;">
                                                    {{ $pembatal }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Row: Alasan (highlighted) -->
                                <tr>
                                    <td style="padding: 0 24px 24px 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #fefce8; border-left: 4px solid #eab308; border-radius: 6px; margin-top: 12px;">
                                            <tr>
                                                <td style="padding: 14px 16px; color: #854d0e; font-size: 13px; line-height: 1.6;">
                                                    📝 <strong>Alasan:</strong> {{ $alasan }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #64748b; font-size: 14px; line-height: 1.7; margin: 0;">
                                Silakan login ke sistem untuk melihat detailnya.
                            </p>

                        </td>
                    </tr>

                    <!-- ===== DIVIDER ===== -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">
                        </td>
                    </tr>

                    <!-- ===== FOOTER ===== -->
                    <tr>
                        <td style="padding: 24px 40px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 13px; margin: 0 0 4px 0; line-height: 1.6;">
                                Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
                            </p>
                            <p style="color: #cbd5e1; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'Rafifah') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Email card -->

            </td>
        </tr>
    </table>
    <!-- /Outer wrapper -->

</body>
</html>