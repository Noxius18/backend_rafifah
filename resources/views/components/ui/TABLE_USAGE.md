# Table Component - Panduan Penggunaan

Component table yang reusable dengan daisyUI dan Alpine.js

## Props

| Prop | Type | Default | Deskripsi |
|------|------|---------|-----------|
| `headers` | Array | `[]` | Header kolom tabel |
| `rows` | Array | `[]` | Data baris tabel |
| `striped` | Boolean | `true` | Baris bergaris-garis |
| `hover` | Boolean | `true` | Efek hover pada baris |
| `compact` | Boolean | `false` | Mode kompak (lebih kecil) |
| `searchable` | Boolean | `false` | Aktifkan pencarian |
| `sortable` | Boolean | `false` | Aktifkan pengurutan |

## Contoh Penggunaan

### 1. Table Sederhana

```blade
<x-ui.table 
  :headers="['No', 'Nama', 'Job', 'Warna Favorit']"
  :rows="[
    ['No' => 1, 'Nama' => 'Cy Ganderton', 'Job' => 'QC Specialist', 'Warna Favorit' => 'Blue'],
    ['No' => 2, 'Nama' => 'Hart Hagerty', 'Job' => 'Desktop Support', 'Warna Favorit' => 'Purple'],
  ]"
/>
```

### 2. Table dengan Dinamis Data dari Database

```blade
<x-ui.table 
  :headers="['ID', 'Nama Orang Tua', 'Email', 'Telepon']"
  :rows="$orangtuas->map(fn($item) => [
    'ID' => $item->id,
    'Nama Orang Tua' => $item->name,
    'Email' => $item->email,
    'Telepon' => $item->phone
  ])->toArray()"
/>
```

### 3. Table dengan Header Lanjut (Custom Formatting)

```blade
@php
  $headers = [
    'id' => ['label' => 'ID', 'key' => 'id'],
    'name' => ['label' => 'Nama', 'key' => 'name'],
    'created_at' => [
      'label' => 'Dibuat',
      'key' => 'created_at',
      'format' => fn($date) => \Carbon\Carbon::parse($date)->format('d M Y')
    ],
  ];
  
  $rows = $berkas->map(fn($item) => [
    'id' => $item->id,
    'name' => $item->name,
    'created_at' => $item->created_at,
  ])->toArray();
@endphp

<x-ui.table :headers="$headers" :rows="$rows" />
```

### 4. Table dengan Pencarian

```blade
<x-ui.table 
  :headers="['No', 'Nama', 'Email']"
  :rows="$users"
  searchable
/>
```

### 5. Table dengan Pengurutan

```blade
<x-ui.table 
  :headers="['ID', 'Nama', 'Email']"
  :rows="$users"
  sortable
/>
```

### 6. Table Lengkap (Pencarian + Pengurutan + Styling)

```blade
<x-ui.table 
  :headers="[
    'id' => ['label' => 'ID', 'key' => 'id'],
    'nama' => ['label' => 'Nama', 'key' => 'nama'],
    'email' => ['label' => 'Email', 'key' => 'email'],
  ]"
  :rows="$data"
  striped
  hover
  searchable
  sortable
/>
```

### 7. Table Kompak (Kecil)

```blade
<x-ui.table 
  :headers="['No', 'Nama', 'Status']"
  :rows="$items"
  compact
/>
```

## Features

### ✅ Responsive
- Otomatis scroll horizontal pada layar kecil

### 🔍 Searchable
- Input pencarian yang mencari di seluruh kolom
- Real-time filtering

### ↕️ Sortable
- Click header untuk sort ascending/descending
- Indikator visual (▲▼)
- Support untuk string dan number

### 🎨 DaisyUI Styling
- Menggunakan class daisyUI
- Mudah dikustomisasi dengan Tailwind

### 📱 Flexible
- Support berbagai format data
- Custom formatting untuk setiap kolom

## Tips

1. **Untuk formatting khusus** (tanggal, currency, dll), gunakan parameter `format` di header
2. **Untuk performa tabel besar**, pertimbangkan pagination di controller
3. **Untuk aksi (edit, delete)**, tambahkan kolom terakhir dengan slot khusus
4. **Semua headers dapat dicari** ketika `searchable` diaktifkan
