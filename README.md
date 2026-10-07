# Presensi QR (Laravel 12, tanpa Breeze)

Aplikasi presensi karyawan berbasis QR Code. Autentikasi dibuat manual (`AuthController`), tanpa Breeze/Jetstream/Fortify.

## Cara kerja
1. Karyawan login → halaman **Presensi Hari Ini** menampilkan QR Code pribadi (berganti tiap hari, ditandatangani HMAC).
2. Komputer kantor membuka halaman utama (`/`) = **halaman Scan QR publik** (tanpa login) → webcam membaca QR karyawan. Tombol **Masuk** di pojok kanan atas untuk login.
3. Scan pertama = **absen masuk** (otomatis *Terlambat* jika lewat jam masuk + toleransi), scan kedua = **absen pulang**. Data langsung masuk database.

## Fitur
**Karyawan:** login/logout, kelola profil & password, tampil QR, status presensi real-time, pengajuan izin/sakit/cuti (+lampiran), riwayat kehadiran bulanan.
**Admin:** dashboard analisis (chart), CRUD karyawan, CRUD divisi, pengaturan jam kerja (shift) & toleransi, persetujuan izin/sakit/cuti, rekap + export CSV (buka di Excel).

## Instalasi
Syarat: PHP >= 8.2, Composer, MySQL (XAMPP/Laragon boleh).

```bash
composer install
cp .env.example .env          # Windows CMD: copy .env.example .env
php artisan key:generate
# buat database kosong bernama: presensi_qr  (atur DB_* di .env kalau perlu)
php artisan migrate --seed
php artisan storage:link      # untuk lampiran izin
php artisan serve
```
Buka http://localhost:8000

## Akun demo (password: `password`)
| Role | Email |
|---|---|
| Admin | admin@presensi.test |
| Karyawan | karyawan1@presensi.test (juga karyawan2, karyawan3) |

## Catatan
- Kamera browser hanya jalan di `localhost` atau **HTTPS**. Kalau server dideploy, pakai HTTPS.
- Desain mengikuti design system "Technolife Corporate" (Crimson #DC143C, Poppins + Inter, radius 12px). Token warna/font ada di `resources/views/partials/head.blade.php`.
- Tailwind, Chart.js, qrcodejs, dan html5-qrcode dimuat lewat CDN (butuh internet), jadi tidak perlu `npm install`.
- Jam/zona waktu diatur di `.env` (`APP_TIMEZONE=Asia/Jakarta`).
- Mau pakai SQLite? Ubah `DB_CONNECTION=sqlite` dan hapus baris DB_* lainnya.

## Struktur penting
- `app/Services/QrToken.php` – buat & verifikasi payload QR
- `app/Services/AttendanceService.php` – logika masuk/pulang/terlambat
- `app/Http/Controllers/KaryawanController.php` & `Admin/*` – controller
- `routes/web.php` – semua route
