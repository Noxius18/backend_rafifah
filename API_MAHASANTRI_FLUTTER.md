# API Mahasantri Flutter

Dokumentasi ini menjelaskan endpoint REST API yang dipakai aplikasi Flutter untuk alur mahasantri:

1. Register akun
2. Login
3. Isi data pendaftaran melalui wizard
4. Melihat status pendaftaran, jadwal seleksi, dan hasil seleksi
5. Logout

## Ringkasan Flow

1. User membuat akun lewat `POST /api/mahasantri/register`
2. User login lewat `POST /api/mahasantri/login`
3. Setelah login, user membuka menu `Isi Data Pendaftaran`
4. Flutter menampung isian wizard secara lokal
5. Saat user menekan tombol submit di langkah evaluasi, Flutter mengirim satu request ke `POST /api/mahasantri/pendaftaran/submit`
6. Flutter menampilkan progres dan status akhir dari `GET /api/mahasantri/status`

## Base URL

Sesuaikan dengan environment backend. Contoh:

```text
http://127.0.0.1:8000/api
```

## Authentication

Endpoint yang membutuhkan login memakai Bearer token:

```http
Authorization: Bearer <access_token>
Accept: application/json
```

Token diperoleh dari endpoint login.

## Status yang Perlu Dipahami Frontend

Ada dua jenis status yang berbeda:

### 1. Status Mahasantri

Field:

```text
data.mahasantri.status
```

Nilai yang mungkin:

- `Pendaftar Baru`
- `Terverifikasi`
- `Lulus`
- `Tidak Lulus`

Arti umum:

- `Pendaftar Baru`: akun sudah ada, tetapi belum diverifikasi panitia
- `Terverifikasi`: data dan berkas sudah diverifikasi panitia
- `Lulus`: hasil seleksi akhir lulus
- `Tidak Lulus`: hasil seleksi akhir tidak lulus

### 2. Status Hasil Tes

Field:

```text
data.hasil.status
```

Nilai yang mungkin:

- `Lulus`
- `Tidak Lulus`
- `Pertimbangan`
- `null`

Arti umum:

- `null`: hasil tes belum tersedia
- `Pertimbangan`: hasil tes masih menunggu review akhir
- `Lulus`: hasil seleksi lulus
- `Tidak Lulus`: hasil seleksi tidak lulus

### 3. Status Berkas

Field:

```text
data.berkas[].status_verifikasi
```

Nilai yang mungkin:

- `menunggu`
- `disetujui`
- `ditolak`

Arti umum:

- `menunggu`: berkas sedang menunggu review panitia
- `disetujui`: berkas sudah diverifikasi panitia
- `ditolak`: berkas ditolak dan mahasantri harus upload ulang

Catatan tambahan:

- jika `status_verifikasi = ditolak`, frontend harus menampilkan `data.berkas[].catatan_revisi`
- saat file pengganti diupload lewat endpoint submit yang sama, status otomatis kembali ke `menunggu`

## Completion Flags

Field ini ada di response `GET /api/mahasantri/status`:

- `data.mahasantri.profile_completed`
- `data.mahasantri.orangtua_completed`
- `data.mahasantri.documents_completed`

Arti:

- `profile_completed`: biodata pribadi sudah lengkap
- `orangtua_completed`: data orangtua sudah ada
- `documents_completed`: semua berkas wajib sudah ada

## Endpoint List

### 1. Register Akun

**Endpoint**

```http
POST /api/mahasantri/register
```

**Body**

```json
{
  "nama_lengkap": "Ahmad Rafif",
  "email": "ahmad@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response sukses**

```json
{
  "message": "Pendaftaran berhasil.",
  "data": {
    "id_mahasantri": "260101",
    "nama_lengkap": "Ahmad Rafif",
    "email": "ahmad@example.com",
    "nik": null,
    "nisn": null,
    "jenis_kelamin": null,
    "tempat_lahir": null,
    "alamat": null,
    "tanggal_lahir": null,
    "status": "Pendaftar Baru",
    "tanggal_daftar": "2026-06-25T10:00:00.000000Z",
    "profile_completed": false,
    "orangtua_completed": false,
    "documents_completed": false,
    "orangtua": [],
    "berkas": []
  }
}
```

### 2. Login

**Endpoint**

```http
POST /api/mahasantri/login
```

**Body**

```json
{
  "email": "ahmad@example.com",
  "password": "password123",
  "device_name": "flutter"
}
```

**Response sukses**

```json
{
  "message": "Login berhasil.",
  "token_type": "Bearer",
  "access_token": "TOKEN_DI_SINI",
  "data": {
    "id_mahasantri": "260101",
    "nama_lengkap": "Ahmad Rafif",
    "email": "ahmad@example.com"
  }
}
```

Simpan `access_token` di Flutter dan pakai untuk request terautentikasi.

### 3. Ambil Profil Mahasantri Login

**Endpoint**

```http
GET /api/mahasantri/me
```

**Headers**

```http
Authorization: Bearer <access_token>
Accept: application/json
```

**Kegunaan**

- menampilkan data akun yang sedang login
- cek data dasar profil

### 4. Submit Data Pendaftaran Final

Endpoint ini dipanggil sekali saat user menyelesaikan wizard dan menekan tombol submit di langkah evaluasi.

**Endpoint**

```http
POST /api/mahasantri/pendaftaran/submit
```

**Headers**

```http
Authorization: Bearer <access_token>
Accept: application/json
```

**Content-Type**

```text
multipart/form-data
```

**Field text**

```text
nik
nisn
jenis_kelamin
tempat_lahir
alamat
tanggal_lahir
orangtua[0][tipe_hubungan]
orangtua[0][nama_lengkap]
orangtua[0][pekerjaan]
orangtua[0][alamat]
orangtua[0][no_wa]
orangtua[1][tipe_hubungan]
orangtua[1][nama_lengkap]
orangtua[1][pekerjaan]
orangtua[1][alamat]
orangtua[1][no_wa]
orangtua[2][tipe_hubungan]
orangtua[2][nama_lengkap]
orangtua[2][pekerjaan]
orangtua[2][alamat]
orangtua[2][no_wa]
```

**Field file**

```text
berkas[ktp]
berkas[kk]
berkas[ijazah]
berkas[surat_izin_orangtua]
berkas[pas_foto]
```

**Aturan field orangtua**

- `Ayah` wajib
- `Ibu` wajib
- `Wali` opsional
- maksimal 3 item: `Ayah`, `Ibu`, `Wali`

**Contoh field orangtua**

```text
orangtua[0][tipe_hubungan]: Ayah
orangtua[0][nama_lengkap]: Budi Santoso
orangtua[0][pekerjaan]: Wiraswasta
orangtua[0][alamat]: Jl. Melati No. 10, Bandung
orangtua[0][no_wa]: 081234567890

orangtua[1][tipe_hubungan]: Ibu
orangtua[1][nama_lengkap]: Siti Aminah
orangtua[1][pekerjaan]: Ibu Rumah Tangga
orangtua[1][alamat]: Jl. Melati No. 10, Bandung
orangtua[1][no_wa]: 081298765432

orangtua[2][tipe_hubungan]: Wali
orangtua[2][nama_lengkap]: H. Rahmat
orangtua[2][pekerjaan]: Guru
orangtua[2][alamat]: Jl. Kenanga No. 2, Bandung
orangtua[2][no_wa]: 081277788899
```

**Contoh field file**

```text
berkas[ktp]                 -> file PDF/JPG/JPEG/PNG
berkas[kk]                  -> file PDF/JPG/JPEG/PNG
berkas[ijazah]              -> file PDF/JPG/JPEG/PNG
berkas[surat_izin_orangtua] -> file PDF/JPG/JPEG/PNG
berkas[pas_foto]            -> file JPG/JPEG/PNG
```

**Validasi**

- `nik`: wajib, 16 digit, unik
- `nisn`: wajib, 10 digit, unik
- `jenis_kelamin`: wajib, `L` atau `P`
- `tempat_lahir`: wajib, maksimal 50 karakter
- `alamat`: wajib, maksimal 255 karakter
- `tanggal_lahir`: wajib, format tanggal valid
- `no_wa`: format nomor Indonesia valid
- semua berkas wajib ada
- ukuran maksimal tiap file: `5 MB`

**Response sukses**

```json
{
  "message": "Data pendaftaran berhasil dikirim.",
  "data": {
    "id_mahasantri": "260101",
    "nama_lengkap": "Ahmad Rafif",
    "email": "ahmad@example.com",
    "nik": "1234567890123456",
    "nisn": "1234567890",
    "jenis_kelamin": "L",
    "tempat_lahir": "Bandung",
    "alamat": "Jl. Melati No. 10, Bandung",
    "tanggal_lahir": "2010-01-15",
    "status": "Pendaftar Baru",
    "profile_completed": true,
    "orangtua_completed": true,
    "documents_completed": true,
    "orangtua": [
      {
        "tipe_hubungan": "Ayah",
        "nama_lengkap": "Budi Santoso"
      },
      {
        "tipe_hubungan": "Ibu",
        "nama_lengkap": "Siti Aminah"
      }
    ],
    "berkas": [
      {
        "id_berkas": "BR001",
        "tipe_berkas": "KTP",
        "status_verifikasi": "menunggu",
        "catatan_revisi": null,
        "file_available": true
      }
    ]
  }
}
```

### 5. Ambil Status Pendaftaran, Jadwal, dan Hasil

**Endpoint**

```http
GET /api/mahasantri/status
```

**Headers**

```http
Authorization: Bearer <access_token>
Accept: application/json
```

**Kegunaan**

Endpoint ini dipakai Flutter untuk:

- menampilkan status pendaftaran
- menampilkan progres kelengkapan data
- menampilkan jadwal seleksi
- menampilkan link Zoom
- menampilkan hasil seleksi

**Response contoh saat belum ada jadwal dan hasil**

```json
{
  "data": {
    "mahasantri": {
      "id_mahasantri": "260101",
      "nama_lengkap": "Ahmad Rafif",
      "status": "Pendaftar Baru",
      "profile_completed": true,
      "orangtua_completed": true,
      "documents_completed": true
    },
    "berkas": [],
    "jadwal": null,
    "hasil": null
  }
}
```

**Response contoh saat jadwal sudah ada**

```json
{
  "data": {
    "mahasantri": {
      "status": "Terverifikasi"
    },
    "jadwal": {
      "id_jadwal": "JDS01",
      "tanggal": "2026-07-10",
      "jam": "08:30",
      "link_zoom": "https://zoom.us/j/123456789",
      "status_jadwal": "Disetujui",
      "catatan_ketua": null,
      "catatan_perubahan": null
    },
    "hasil": null
  }
}
```

**Response contoh saat hasil seleksi sudah ada**

```json
{
  "data": {
    "mahasantri": {
      "status": "Lulus"
    },
    "jadwal": {
      "tanggal": "2026-07-10",
      "jam": "08:30",
      "link_zoom": "https://zoom.us/j/123456789"
    },
    "hasil": {
      "id_hasil": "HSL01",
      "total_nilai": 88,
      "status": "Lulus"
    }
  }
}
```

**Catatan frontend**

- jika `jadwal = null`, berarti panitia belum membuat jadwal seleksi
- jika `hasil = null`, berarti hasil seleksi belum tersedia
- `mahasantri.status` tetap harus ditampilkan karena itu status utama user
- `hasil.status` dipakai untuk detail hasil tes bila sudah ada
- tampilkan status tiap item `berkas`
- jika ada `status_verifikasi = ditolak`, tampilkan `catatan_revisi` dan minta user upload ulang file itu

### 6. Logout

**Endpoint**

```http
POST /api/mahasantri/logout
```

**Headers**

```http
Authorization: Bearer <access_token>
Accept: application/json
```

**Response sukses**

```json
{
  "message": "Logout berhasil."
}
```

Setelah logout, token lama tidak bisa dipakai lagi.

## Error Umum

### 401 Unauthenticated

Terjadi jika:

- token tidak dikirim
- token salah
- token sudah di-logout

Contoh:

```json
{
  "message": "Unauthenticated."
}
```

### 422 Validation Error

Terjadi jika request tidak valid.

Contoh:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "nik": [
      "The nik field is required."
    ]
  }
}
```

## Checklist Integrasi Flutter

- simpan `access_token` setelah login
- pakai `Bearer token` untuk endpoint yang butuh auth
- wizard pendaftaran disubmit sekali di langkah evaluasi
- kirim request submit final sebagai `multipart/form-data`
- tampilkan `mahasantri.status`
- tampilkan `berkas[].status_verifikasi` dan `berkas[].catatan_revisi` bila ada
- tampilkan `jadwal.tanggal`, `jadwal.jam`, dan `jadwal.link_zoom` jika `jadwal` tidak null
- tampilkan `hasil.status` dan `hasil.total_nilai` jika `hasil` tidak null
- gunakan `profile_completed`, `orangtua_completed`, `documents_completed` untuk indikator progres

## Endpoint Aktif Saat Ini

- `POST /api/mahasantri/register`
- `POST /api/mahasantri/login`
- `GET /api/mahasantri/me`
- `POST /api/mahasantri/pendaftaran/submit`
- `GET /api/mahasantri/status`
- `POST /api/mahasantri/logout`
- `GET /api/gelombang/active`
