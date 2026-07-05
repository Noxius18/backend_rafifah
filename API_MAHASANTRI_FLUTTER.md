# API Mahasantri Flutter

Dokumentasi ini menjelaskan endpoint REST API yang dipakai frontend Flutter untuk alur mahasantri pada backend Laravel 12 saat ini.

## Ringkasan Flow

1. Flutter cek gelombang aktif lewat `GET /api/gelombang/active`
2. User register akun lewat `POST /api/mahasantri/register`
3. User login lewat `POST /api/mahasantri/login`
4. Flutter simpan `access_token`
5. Flutter ambil profil login lewat `GET /api/mahasantri/me`
6. User isi wizard pendaftaran dan submit final lewat `POST /api/mahasantri/pendaftaran/submit`
7. Flutter polling / refresh status lewat `GET /api/mahasantri/status`
8. User logout lewat `POST /api/mahasantri/logout`

## Base URL

Sesuaikan dengan environment backend.

```text
http://127.0.0.1:8000/api
```

## Authentication

Endpoint yang membutuhkan login memakai Bearer token.

```http
Authorization: Bearer <access_token>
Accept: application/json
```

Token diperoleh dari endpoint login.

## Status yang Perlu Dipahami Frontend

### 1. Status Mahasantri

Field:

```text
data.mahasantri.status
```

Nilai yang mungkin saat ini:

- `Pendaftar Baru`
- `Terverifikasi`
- `Lulus`
- `Tidak Lulus`

Arti umum:

- `Pendaftar Baru`: akun sudah dibuat, proses seleksi belum selesai
- `Terverifikasi`: data / berkas sudah diverifikasi
- `Lulus`: hasil akhir lulus
- `Tidak Lulus`: hasil akhir tidak lulus

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

- `null`: hasil belum tersedia
- `Pertimbangan`: hasil belum final
- `Lulus`: lulus seleksi
- `Tidak Lulus`: tidak lulus seleksi

### 3. Status Berkas

Field:

```text
data.berkas[].status_verifikasi
```

Tipe data:

- string enum

Nilai enum yang valid:

- `menunggu`
- `disetujui`
- `ditolak`

Catatan:

- meskipun kolom database sekarang memakai enum, response JSON ke Flutter tetap dikirim sebagai string
- backend hanya akan mengembalikan salah satu dari tiga nilai di atas
- jika `status_verifikasi = ditolak`, tampilkan `catatan_revisi`
- jika user upload ulang file yang ditolak lewat endpoint submit final, status akan kembali ke `menunggu` dan `catatan_revisi` akan dihapus

### 4. Completion Flags

Field:

- `data.mahasantri.profile_completed`
- `data.mahasantri.orangtua_completed`
- `data.mahasantri.documents_completed`

Arti:

- `profile_completed`: biodata wajib sudah terisi
- `orangtua_completed`: data orangtua sudah ada
- `documents_completed`: semua dokumen wajib tersedia

## Endpoint List

### 1. Cek Gelombang Aktif

**Endpoint**

```http
GET /api/gelombang/active
```

**Auth**

Tidak perlu login.

**Response sukses**

```json
{
  "data": {
    "id": 1,
    "nama": "Gelombang 1",
    "start_date": "2026-07-01",
    "end_date": "2026-07-31"
  }
}
```

**Response saat tidak ada gelombang aktif**

```json
{
  "message": "Tidak ada gelombang aktif.",
  "data": null
}
```

**Catatan frontend**

- endpoint ini cocok dipanggil sebelum register
- jika `404`, frontend bisa menonaktifkan tombol daftar atau menampilkan info pendaftaran belum dibuka

### 2. Register Akun

**Endpoint**

```http
POST /api/mahasantri/register
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
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

**Validasi utama**

- `nama_lengkap`: wajib, string, maksimum 35 karakter
- `email`: wajib, format email, maksimum 100 karakter, unik
- `password`: wajib, minimum 8 karakter, wajib cocok dengan `password_confirmation`
- register gagal jika tidak ada gelombang aktif

**Response sukses**

Status code: `201 Created`

```json
{
  "message": "Pendaftaran berhasil.",
  "data": {
    "id_mahasantri": "260101",
    "nama_lengkap": "Ahmad Rafif",
    "email": "ahmad@example.com",
    "nik": null,
    "nisn": null,
    "tempat_lahir": null,
    "alamat": null,
    "tanggal_lahir": null,
    "status": "Pendaftar Baru",
    "tanggal_daftar": "2026-07-05T10:00:00.000000Z",
    "profile_completed": false,
    "orangtua_completed": false,
    "documents_completed": false,
    "orangtua": [],
    "berkas": []
  }
}
```

**Response error umum**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

Jika gelombang tidak aktif:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "gelombang": [
      "Tidak ada gelombang aktif untuk tanggal pendaftaran saat ini."
    ]
  }
}
```

### 3. Login

**Endpoint**

```http
POST /api/mahasantri/login
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
```

**Body**

```json
{
  "email": "ahmad@example.com",
  "password": "password123",
  "device_name": "flutter"
}
```

**Validasi utama**

- `email`: wajib, format email
- `password`: wajib
- `device_name`: opsional, string, maksimum 100 karakter

**Response sukses**

```json
{
  "message": "Login berhasil.",
  "token_type": "Bearer",
  "access_token": "TOKEN_DI_SINI",
  "data": {
    "id_mahasantri": "260101",
    "nama_lengkap": "Ahmad Rafif",
    "email": "ahmad@example.com",
    "nik": null,
    "nisn": null,
    "tempat_lahir": null,
    "alamat": null,
    "tanggal_lahir": null,
    "status": "Pendaftar Baru",
    "tanggal_daftar": "2026-07-05T10:00:00.000000Z",
    "profile_completed": false,
    "orangtua_completed": null,
    "documents_completed": null,
    "orangtua": [],
    "berkas": []
  }
}
```

**Response jika email / password salah**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "Email atau password salah."
    ]
  }
}
```

**Catatan frontend**

- simpan `access_token`
- kirim `token_type` dan `access_token` sebagai Bearer token ke endpoint auth
- user lama yang belum punya password tidak bisa login sampai punya password di backend

### 4. Ambil Profil Mahasantri Login

**Endpoint**

```http
GET /api/mahasantri/me
```

**Headers**

```http
Authorization: Bearer <access_token>
Accept: application/json
```

**Response sukses**

```json
{
  "data": {
    "id_mahasantri": "260101",
    "nama_lengkap": "Ahmad Rafif",
    "email": "ahmad@example.com",
    "nik": "1234567890123456",
    "nisn": "1234567890",
    "tempat_lahir": "Bandung",
    "alamat": "Jl. Pesantren No. 1",
    "tanggal_lahir": "2010-01-10",
    "status": "Pendaftar Baru",
    "tanggal_daftar": "2026-07-05T10:00:00.000000Z",
    "profile_completed": true,
    "orangtua_completed": true,
    "documents_completed": true,
    "orangtua": [
      {
        "id_orangtua": "ORT01",
        "tipe_hubungan": "Ayah",
        "nama_lengkap": "Bapak Ahmad",
        "pekerjaan": "Wiraswasta",
        "alamat": "Jl. Pesantren No. 1",
        "no_wa": "081234567890"
      },
      {
        "id_orangtua": "ORT02",
        "tipe_hubungan": "Ibu",
        "nama_lengkap": "Ibu Ahmad",
        "pekerjaan": "Ibu Rumah Tangga",
        "alamat": "Jl. Pesantren No. 1",
        "no_wa": "081298765432"
      }
    ],
    "berkas": [
      {
        "id_berkas": "BR001",
        "tipe_berkas": "KTP",
        "status_verifikasi": "menunggu",
        "catatan_revisi": null,
        "tanggal_upload": "2026-07-05T10:30:00.000000Z",
        "file_available": true,
        "download_status": "success",
        "error_message": null
      }
    ]
  }
}
```

**Kegunaan**

- ambil data akun / profil yang sedang login
- hydrate ulang local state setelah app dibuka kembali
- `status_verifikasi` di setiap item `berkas` selalu bernilai `menunggu`, `disetujui`, atau `ditolak`

### 5. Submit Data Pendaftaran Final

Endpoint ini dipanggil saat user menekan submit di langkah akhir wizard.

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

**Aturan orangtua**

- array `orangtua` wajib, minimal 2 item, maksimal 3 item
- `Ayah` wajib ada
- `Ibu` wajib ada
- `Wali` opsional
- `tipe_hubungan` yang valid: `Ayah`, `Ibu`, `Wali`

**Aturan file**

- `ktp`, `kk`, `ijazah`, `surat_izin_orangtua`: `pdf`, `jpg`, `jpeg`, `png`
- `pas_foto`: `jpg`, `jpeg`, `png`
- ukuran maksimum tiap file: `5 MB`
- file hanya wajib untuk dokumen yang belum ada sebelumnya
- jika dokumen sudah ada dan tidak ingin diganti, field file itu boleh tidak dikirim

**Validasi utama**

- `nik`: wajib, 16 digit, unik kecuali milik user saat ini
- `nisn`: wajib, 10 digit, unik kecuali milik user saat ini
- `tempat_lahir`: wajib, maksimum 50 karakter
- `alamat`: wajib, maksimum 255 karakter
- `tanggal_lahir`: wajib, format tanggal valid
- `no_wa`: opsional, tetapi jika diisi harus nomor Indonesia valid seperti `08xx` atau `+62xx`
- nomor WA tidak boleh duplikat dalam satu request
- nomor WA tidak boleh dipakai mahasantri lain

**Contoh body tekstual**

```text
nik: 1234567890123456
nisn: 1234567890
tempat_lahir: Bandung
alamat: Jl. Pesantren No. 1
tanggal_lahir: 2010-01-10
orangtua[0][tipe_hubungan]: Ayah
orangtua[0][nama_lengkap]: Bapak Ahmad
orangtua[0][pekerjaan]: Wiraswasta
orangtua[0][alamat]: Jl. Pesantren No. 1
orangtua[0][no_wa]: 081234567890
orangtua[1][tipe_hubungan]: Ibu
orangtua[1][nama_lengkap]: Ibu Ahmad
orangtua[1][pekerjaan]: Ibu Rumah Tangga
orangtua[1][alamat]: Jl. Pesantren No. 1
orangtua[1][no_wa]: 081298765432
```

**Response sukses**

```json
{
  "message": "Data pendaftaran berhasil diperbarui.",
  "data": {
    "id_mahasantri": "260101",
    "nama_lengkap": "Ahmad Rafif",
    "email": "ahmad@example.com",
    "nik": "1234567890123456",
    "nisn": "1234567890",
    "tempat_lahir": "Bandung",
    "alamat": "Jl. Pesantren No. 1",
    "tanggal_lahir": "2010-01-10",
    "status": "Pendaftar Baru",
    "tanggal_daftar": "2026-07-05T10:00:00.000000Z",
    "profile_completed": true,
    "orangtua_completed": true,
    "documents_completed": true,
    "orangtua": [
      {
        "id_orangtua": "ORT01",
        "tipe_hubungan": "Ayah",
        "nama_lengkap": "Bapak Ahmad",
        "pekerjaan": "Wiraswasta",
        "alamat": "Jl. Pesantren No. 1",
        "no_wa": "081234567890"
      },
      {
        "id_orangtua": "ORT02",
        "tipe_hubungan": "Ibu",
        "nama_lengkap": "Ibu Ahmad",
        "pekerjaan": "Ibu Rumah Tangga",
        "alamat": "Jl. Pesantren No. 1",
        "no_wa": "081298765432"
      }
    ],
    "berkas": [
      {
        "id_berkas": "BR001",
        "tipe_berkas": "KTP",
        "status_verifikasi": "menunggu",
        "catatan_revisi": null,
        "tanggal_upload": "2026-07-05T10:30:00.000000Z",
        "file_available": true,
        "download_status": "success",
        "error_message": null
      }
    ]
  }
}
```

**Contoh error**

Ayah / Ibu tidak lengkap:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "orangtua": [
      "Data Ibu wajib diisi."
    ]
  }
}
```

Token tidak ada / tidak valid:

```json
{
  "message": "Unauthenticated."
}
```

**Catatan frontend**

- kirim request sebagai `multipart/form-data`
- wizard bisa disimpan lokal per step, tetapi backend baru menerima saat submit final
- treat `status_verifikasi` sebagai enum aplikasi di Flutter, bukan string bebas
- jika ada dokumen yang ditolak, frontend cukup kirim ulang file yang direvisi bersama field biodata / orangtua yang tetap wajib dikirim

### 6. Ambil Status Pendaftaran, Jadwal, dan Hasil

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

- menampilkan status pendaftaran
- menampilkan progres kelengkapan data
- menampilkan status review berkas
- menampilkan jadwal seleksi
- menampilkan hasil seleksi

**Response contoh saat belum ada jadwal dan hasil**

```json
{
  "data": {
    "mahasantri": {
      "id_mahasantri": "260101",
      "nama_lengkap": "Ahmad Rafif",
      "email": "ahmad@example.com",
      "nik": "1234567890123456",
      "nisn": "1234567890",
      "tempat_lahir": "Bandung",
      "alamat": "Jl. Pesantren No. 1",
      "tanggal_lahir": "2010-01-10",
      "status": "Pendaftar Baru",
      "tanggal_daftar": "2026-07-05T10:00:00.000000Z",
      "profile_completed": true,
      "orangtua_completed": true,
      "documents_completed": true,
      "orangtua": [
        {
          "id_orangtua": "ORT01",
          "tipe_hubungan": "Ayah",
          "nama_lengkap": "Bapak Ahmad",
          "pekerjaan": "Wiraswasta",
          "alamat": "Jl. Pesantren No. 1",
          "no_wa": "081234567890"
        }
      ],
      "berkas": [
        {
          "id_berkas": "BR001",
          "tipe_berkas": "KTP",
          "status_verifikasi": "menunggu",
          "catatan_revisi": null,
          "tanggal_upload": "2026-07-05T10:30:00.000000Z",
          "file_available": true,
          "download_status": "success",
          "error_message": null
        }
      ]
    },
    "berkas": [
      {
        "id_berkas": "BR001",
        "tipe_berkas": "KTP",
        "status_verifikasi": "menunggu",
        "catatan_revisi": null,
        "tanggal_upload": "2026-07-05T10:30:00.000000Z",
        "file_available": true,
        "download_status": "success",
        "error_message": null
      }
    ],
    "jadwal": null,
    "hasil": null
  }
}
```

Pada semua response di atas, `status_verifikasi` tetap dikirim sebagai string JSON dengan nilai terbatas:

- `menunggu`
- `disetujui`
- `ditolak`

**Response contoh saat jadwal sudah ada**

```json
{
  "data": {
    "mahasantri": {
      "status": "Terverifikasi"
    },
    "berkas": [],
    "jadwal": {
      "id_jadwal": "JDS01",
      "tanggal": "2026-07-10",
      "jam": "08:30",
      "link_zoom": "https://zoom.us/j/123456789",
      "status_jadwal": "Disetujui",
      "catatan_ketua": null,
      "catatan_perubahan": null,
      "penanggung_jawab": null,
      "penguji": []
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
    "berkas": [],
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

- jika `jadwal = null`, jadwal seleksi belum ada
- jika `hasil = null`, hasil seleksi belum ada
- tetap tampilkan `mahasantri.status` sebagai status utama user
- tampilkan status tiap item di `berkas`
- mapping `berkas[].status_verifikasi` ke badge / label UI dengan enum tetap: `menunggu`, `disetujui`, `ditolak`
- jika ada `status_verifikasi = ditolak`, tampilkan `catatan_revisi`
- response ini mengandung data berkas dua kali:
  - `data.mahasantri.berkas`
  - `data.berkas`
- untuk Flutter, lebih aman pilih satu sumber utama secara konsisten, disarankan `data.berkas`

### 7. Logout

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

**Catatan frontend**

- hapus token lokal setelah logout sukses
- token lama tidak bisa dipakai lagi

## Error Umum

### 401 Unauthenticated

Terjadi jika:

- token tidak dikirim
- token salah
- token sudah di-logout
- token sudah expired

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
- kirim header `Accept: application/json`
- pakai Bearer token untuk endpoint auth
- submit pendaftaran final sebagai `multipart/form-data`
- tampilkan `mahasantri.status`
- tampilkan `berkas[].status_verifikasi` dan `berkas[].catatan_revisi`
- treat `berkas[].status_verifikasi` sebagai enum/string terbatas dengan nilai `menunggu`, `disetujui`, atau `ditolak`
- tampilkan `jadwal.tanggal`, `jadwal.jam`, dan `jadwal.link_zoom` jika `jadwal` tidak null
- tampilkan `hasil.status` dan `hasil.total_nilai` jika `hasil` tidak null
- gunakan `profile_completed`, `orangtua_completed`, `documents_completed` untuk indikator progres

## Endpoint Aktif Saat Ini

- `GET /api/gelombang/active`
- `POST /api/mahasantri/register`
- `POST /api/mahasantri/login`
- `GET /api/mahasantri/me`
- `POST /api/mahasantri/pendaftaran/submit`
- `GET /api/mahasantri/status`
- `POST /api/mahasantri/logout`
