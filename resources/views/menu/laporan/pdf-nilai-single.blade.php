<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Kelulusan {{ $mahasantri->nama_lengkap }}</title>
    <style>
        /* Ukuran margin pas untuk 1 halaman penuh tanpa terkesan sesak */
        @page { size: A4; margin: 25px 40px; }
        
        /* Font size standar surat resmi (14px) agar enak dibaca */
        body { font-family: 'Times New Roman', Times, serif; font-size: 14px; color: #000; line-height: 1.35; }
        
        /* KOP SURAT */
        .kop-surat { text-align: center; border-bottom: 3px solid #000; padding-bottom: 8px; margin-bottom: 15px; margin-top: 5px; }
        .kop-surat h1 { font-size: 20px; margin: 0; font-weight: bold; letter-spacing: 1px; }
        .kop-surat h2 { font-size: 26px; margin: 3px 0; font-weight: bold; color: #065f46; letter-spacing: 1.5px; }
        .kop-surat p { font-size: 13px; margin: 0; font-style: italic; }

        /* JUDUL SURAT */
        .judul-surat { text-align: center; margin-bottom: 15px; }
        
        /* PENGATURAN GAMBAR BASMALAH YANG SUDAH DI-CUT */
        /* Tinggi diset 45px agar pas, margin negatif dihapus supaya ada jarak dengan teks bawahnya */
        .judul-surat img.basmalah { height: 45px; width: auto; margin-top: 5px; margin-bottom: 10px; object-fit: contain; }
        
        .judul-surat .teks-basmalah { font-size: 24px; font-family: 'Traditional Arabic', 'Amiri', 'DejaVu Sans', serif; margin-bottom: 5px; }
        .judul-surat h3 { font-size: 16px; margin: 0; text-decoration: underline; font-weight: bold; }
        .judul-surat h4 { font-size: 14px; margin: 3px 0 0; font-weight: bold; }
        .judul-surat p { font-size: 12px; margin: 5px 0 0; }

        /* KONTEN SURAT */
        .content { margin-bottom: 10px; }
        
        table.biodata { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.biodata td { padding: 4px 5px; vertical-align: top; }
        table.biodata td.label { width: 190px; }
        table.biodata td.titikdua { width: 15px; text-align: center; }

        /* STATUS KELULUSAN */
        .status-box { text-align: center; margin: 15px 0; }
        .status-text { font-size: 26px; font-weight: bold; margin: 5px 0; letter-spacing: 2px; }
        .status-lulus { color: #059669; }
        .status-tidak { color: #dc2626; }
        .status-pertimbangan { color: #d97706; }

        /* TABEL NILAI */
        table.nilai { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
        table.nilai th, table.nilai td { border: 1px solid #000; padding: 6px 12px; text-align: center; }
        table.nilai th { background-color: #f3f4f6; font-weight: bold; text-transform: uppercase; font-size: 13px; }
        table.nilai td:nth-child(2) { text-align: left; }

        /* TANDA TANGAN */
        table.ttd-box { width: 100%; margin-top: 25px; text-align: center; }
        table.ttd-box td { width: 50%; vertical-align: top; position: relative; }
        .nama-ttd { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>

    @php
        $h = $hasil ?? (isset($hasilTes) ? $hasilTes->first() : null);
        
        $nama = $mahasantri->nama_lengkap ?? '-';
        $tempat_lahir = $mahasantri->tempat_lahir ?? '-';
        $tgl_lahir = $mahasantri->tanggal_lahir ? \Carbon\Carbon::parse($mahasantri->tanggal_lahir)->locale('id')->translatedFormat('d F Y') : '-';
        
        $alamat = $mahasantri->alamat ?? '-';

        // Cari nama Ketua Panitia dari user dengan jabatan Pengawas
        $ketuaPanitia = \App\Models\Panitia::where('jabatan', 'Pengawas')->first();
        $namaKetuaPanitia = $ketuaPanitia ? $ketuaPanitia->nama_lengkap : '-';

        $tahun = date('Y');
        $idNum = preg_replace('/[^0-9]/', '', $mahasantri->id_mahasantri ?? '001');
        $nomorSurat = str_pad($idNum ?: '1', 3, '0', STR_PAD_LEFT) . "/PMB/RAMQ/{$tahun}";

        // Trik Base64 Gambar tetap dipertahankan supaya email tidak error
        $basmalahPath = public_path('images/basmalah.png');
        $basmalahBase64 = null;
        if (file_exists($basmalahPath)) {
            $basmalahData = base64_encode(file_get_contents($basmalahPath));
            $basmalahBase64 = 'data:image/png;base64,' . $basmalahData;
        }
    @endphp

    <div class="kop-surat">
        <h1>PONDOK PESANTREN TAHFIDZ</h1>
        <h2>RAFIFAH ANDALUSIA MQ</h2>
        <p>Cijayanti, Babakan Madang, Bogor, Jawa Barat, Indonesia</p>
    </div>

    <div class="judul-surat">
        @if($basmalahBase64)
            <img src="{{ $basmalahBase64 }}" class="basmalah" alt="Bismillah">
        @else
            <div class="teks-basmalah">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</div>
        @endif

        <h3>SURAT KELULUSAN TEST</h3>
        <h4>PENERIMAAN MAHASANTRI BARU</h4>
        <p>Nomor: {{ $nomorSurat }}</p>
    </div>

    <div class="content">
        <p><i>Assalamu'alaikum warahmatullahi wabarakatuh</i></p>
        <p>Berdasarkan hasil seleksi dan tes penerimaan mahasantri baru Tahun Ajaran {{ $tahun }}/{{ $tahun + 1 }}. Maka dengan ini kami menyatakan bahwa:</p>

        <table class="biodata">
            <tr>
                <td class="label">Nama</td>
                <td class="titikdua">:</td>
                <td><strong>{{ $nama }}</strong></td>
            </tr>
            <tr>
                <td class="label">Tempat/Tanggal Lahir</td>
                <td class="titikdua">:</td>
                <td>{{ $tempat_lahir }}, {{ $tgl_lahir }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="titikdua">:</td>
                <td>{{ $alamat }}</td>
            </tr>
        </table>

        <div class="status-box">
            <p style="margin-bottom: 5px; font-size: 14px; color: #000; font-weight: normal; letter-spacing: normal;">Dinyatakan:</p>
            @if ($h && $h->status === 'Lulus')
                <div class="status-text status-lulus">LULUS</div>
            @elseif ($h && $h->status === 'Tidak Lulus')
                <div class="status-text status-tidak">TIDAK LULUS</div>
            @elseif ($h && $h->status === 'Pertimbangan')
                <div class="status-text status-pertimbangan">DIPERTIMBANGKAN</div>
            @else
                <div class="status-text" style="font-size: 22px;">MENUNGGU HASIL KELULUSAN</div>
            @endif
        </div>

        <p>Sebagai calon Mahasantri baru di:</p>
        <table class="biodata" style="margin-bottom: 10px;">
            <tr>
                <td class="label">Nama Lembaga</td>
                <td class="titikdua">:</td>
                <td><strong>Pondok Pesantren Tahfidz Rafifah Andalusia MQ</strong></td>
            </tr>
            <tr>
                <td class="label">Program</td>
                <td class="titikdua">:</td>
                <td><strong>Takhassus Al-Qur'an 30 Juz</strong></td>
            </tr>
        </table>

        @if($h)
        <table class="nilai">
            <thead>
                <tr>
                    <th style="width: 10%;">NO</th>
                    <th style="width: 60%;">Komponen Penilaian</th>
                    <th style="width: 30%;">Hasil</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Bacaan Al Qur'an</td>
                    <td>{{ $h->nilai_bacaan_al_quran ?? 0 }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Tajwid dan Tahsin</td>
                    <td>{{ $h->nilai_tajwid_tahsin ?? 0 }}</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Hafalan</td>
                    <td>{{ $h->nilai_hafalan ?? 0 }}</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Wawancara</td>
                    <td>{{ $h->nilai_wawancara ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        <p>Demikian Surat kelulusan ini disampaikan untuk dipergunakan sebagaimana mestinya.</p>
        <p><i>Wassalamu'alaikum warahmatullahi wabarakatuh</i></p>
    </div>

    <table class="ttd-box">
        <tr>
            <td>
                <p style="margin-bottom: 5px;">Ketua Panitia PMB</p>
                <div style="height: 65px;"></div>
                <p class="nama-ttd">{{ $namaKetuaPanitia }}</p>
            </td>
            <td>
                <p style="margin-bottom: 5px;">Kepala Ma'had</p>
                <div style="height: 65px;"></div>
                <p class="nama-ttd">Ustadz Faikurrahman, S.Ag</p>
            </td>
        </tr>
    </table>

</body>
</html>