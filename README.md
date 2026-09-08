# Website CRUD Izin Kantor

Aplikasi Web Internal Pengajuan dan Persetujuan Izin Kantor untuk **General Solusindo** dan **Tabinaco**, menggantikan Google Forms dengan sistem yang terstruktur, validasi otomatis, single-step approval, notifikasi email, dan pre-filled WhatsApp Click-to-Chat CTA.

---

## 1. Fitur Utama

- **Karyawan Tanpa Akun**:
  - Mengajukan izin melalui **SATU form dinamis** di `/ajukan-izin` (didukung Alpine.js).
  - Data pengaju (Nama, Email, WhatsApp, Departemen, Jabatan) dicatat sebagai snapshot pada setiap pengajuan.
  - Setiap pengajuan menghasilkan nomor unik berformat `IZN-YYYY-XXXXXX` (contoh: `IZN-2026-000001`).
  - Karyawan dapat mengecek status secara mandiri di `/cek-status` (verifikasi aman No. Pengajuan + Email tanpa login).
- **5 Jenis Izin & Aturan Bisnis Server-Side (`Asia/Jakarta`)**:
  1. **Izin Terlambat (`late`)**:
     - Khusus tanggal hari ini.
     - Batas pengajuan normal: maksimal pukul **07.00 WIB**.
     - Pengajuan setelah pukul 07.00 WIB wajib menandai kondisi darurat dan mengisi alasannya.
     - Estimasi kedatangan maksimal pukul **09.30 WIB**.
     - Pernyataan persetujuan wajib dicentang.
  2. **Izin Setengah Hari (`half_day`)**:
     - Pengajuan minimal **H-1** (mulai besok).
     - Durasi dihitung otomatis dari jam mulai & selesai dengan peringatan jika jauh dari ±4 jam kerja.
  3. **Cuti (`leave`)**:
     - Pengajuan minimal **H-7** dari tanggal hari ini.
  4. **Izin Pribadi (`personal`)**:
     - Normal minimal **H-1**.
     - Pengajuan pada hari H diperbolehkan jika kondisi mendesak/darurat dan disertai alasan.
  5. **Izin Sakit (`sick`)**:
     - Jam masuk kantor: **08.30 WIB**.
     - Pengajuan pada hari H setelah 08.30 WIB wajib menyertakan alasan kondisi darurat.
     - Jika durasi sakit **> 1 hari**, surat dokter **wajib** diunggah.
     - Jika sakit 1 hari tanpa bukti medis, sistem menampilkan catatan kebijakan kantor kepada reviewer bahwa sakit tanpa bukti medis dapat diperhitungkan sebagai izin pribadi.
- **Persetujuan Satu Tahap (Single-Step Approval)**:
  - Hanya ada 2 role: `admin` (role tertinggi) dan `hrd`.
  - Keputusan bersifat final: pengajuan pending dapat diproses oleh Admin **ATAU** HRD.
  - Begitu disetujui (`approved`) atau ditolak (`rejected`), status langsung final dan tidak dapat diubah kembali.
  - Penolakan **wajib** menyertakan alasan penolakan.
  - Row-level lock concurrency protection: mencegah race condition jika dua staf membuka pengajuan yang sama.
- **Notifikasi Email & WhatsApp**:
  - **Email**: Otomatis dikirim ke email karyawan saat disetujui atau ditolak (kegagalan SMTP tidak membatalkan status approval di database).
  - **WhatsApp**: Click to Chat (`wa.me`) dengan pesan prefilled siap kirim. Reviewer cukup menekan tombol "Kirim via WhatsApp" lalu menekan send di WhatsApp Web/App.
- **Dashboard, Riwayat & Laporan**:
  - Dashboard terpisah untuk Admin (`/admin/dashboard`) dan HRD (`/hrd/dashboard`).
  - Riwayat lengkap pengajuan dengan filter status, jenis izin, departemen, dan pencarian.
  - Ringkasan laporan statistik dan ekspor ke format **CSV**.
  - Akses dokumen pendukung privat terproteksi otentikasi (`/pengajuan/{id}/attachment/{attachment_id}`).

---

## 2. Tech Stack

- **Framework**: Laravel 13
- **Language**: PHP 8.3+
- **Frontend**: Blade Templates, Tailwind CSS v4, Alpine.js, Vite
- **Database Production**: PostgreSQL di **Supabase** (`DB_CONNECTION=pgsql`)
- **Database Testing**: SQLite (`DB_CONNECTION=sqlite`)
- **Storage**: Laravel Private Filesystem (`storage/app/private`)
- **Notification**: Laravel Mailables (SMTP) & WhatsApp Click to Chat (`wa.me`)

---

## 3. Prasyarat Sistem

1. **PHP 8.3** atau lebih baru dengan ekstensi:
   - `pdo`, `pdo_pgsql` / `pgsql` (untuk Supabase), `pdo_sqlite` (untuk automated tests), `fileinfo`, `mbstring`, `openssl`.
2. **Composer 2.x**
3. **Node.js 20+** & **npm**

---

## 4. Panduan Instalasi & Menjalankan Aplikasi

### 4.1 Clone & Masuk ke Direktori
```bash
git clone <repository_url>
cd perizinan-kantor
```

### 4.2 Install Dependensi PHP & JavaScript
```bash
composer install
npm install
```

### 4.3 Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```

Generate App Key:
```bash
php artisan key:generate
```

### 4.4 Konfigurasi Database

#### Opsi A: Menggunakan Supabase PostgreSQL (Production / Staging)
Dapatkan connection string atau kredensial database dari Dashboard Supabase Anda:
* **Settings** &rarr; **Database** &rarr; **Connection parameters (Session/Direct pooler)**

Atur di `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com # sesuaikan host Supabase Anda
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.YOUR_PROJECT_REF
DB_PASSWORD=YOUR_SUPABASE_PASSWORD
# DB_SSLMODE=require
```

Jalankan migrasi dan seeder ke Supabase:
```bash
php artisan migrate --seed
```

#### Opsi B: Menggunakan SQLite (Local Zero-Config Development & Testing)
Jika ingin menjalankan secara lokal tanpa koneksi internet ke Supabase:
```env
DB_CONNECTION=sqlite
```
Lalu jalankan migrasi & seeder:
```bash
php artisan migrate --seed
```

### 4.5 Konfigurasi Pengiriman Email (SMTP)
Di file `.env`, lengkapi kredensial SMTP kantor Anda:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org # atau penyedia SMTP kantor lainnya
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@kantor.com"
MAIL_FROM_NAME="${APP_NAME}"
```
*Catatan: Pada environment lokal, jika `MAIL_MAILER=log`, email yang dikirim akan dicatat secara otomatis ke `storage/logs/laravel.log` tanpa memerlukan server SMTP.*

### 4.6 Build Aset Frontend
```bash
# Untuk production build
npm run build

# Atau jalankan vite development server
npm run dev
```

### 4.7 Jalankan Server Lokal
```bash
php artisan serve
```
Akses aplikasi melalui browser di: [http://localhost:8000](http://localhost:8000)

---

## 5. Akun Staf Demo (Development)

Seeder otomatis menyediakan dua akun peninjau internal:

| Role | Email | Password | Deskripsi |
|---|---|---|---|
| **Admin** | `admin@example.com` | `password` | Role tertinggi, akses dashboard admin & seluruh pengajuan |
| **HRD** | `hrd@example.com` | `password` | Role HRD, akses dashboard HRD & approval pengajuan |

*Karyawan tidak memerlukan akun/login untuk mengajukan izin maupun mengecek status.*

---

## 6. Menjalankan Automated Tests

Aplikasi dilengkapi test suite PHPUnit komprehensif yang menguji otentikasi, validasi seluruh 5 jenis izin, single-step approval, pencegahan race condition, notifikasi, dan ekspor CSV:

```bash
php artisan test
```

Semua 26 pengujian fitur berjalan di SQLite in-memory secara independen dan cepat tanpa mempengaruhi database production.
