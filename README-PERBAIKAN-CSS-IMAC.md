# Perbaikan CSS untuk Safari lama (iMac macOS Catalina atau lebih lama)

Perubahan ini memindahkan Tailwind CSS dari CDN ke file CSS lokal yang dikompilasi oleh Vite, serta menurunkan Tailwind dari v4 ke v3.4 agar lebih ramah untuk browser Safari yang lebih lama.

## Yang diubah

- Menghapus pemanggilan `https://cdn.tailwindcss.com` dan konfigurasi Tailwind CDN dari `resources/views/partials/head.blade.php`.
- Memuat aset lokal melalui `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- Memindahkan style Tailwind khusus ke `resources/css/app.css`.
- Menambahkan `tailwind.config.js` dan `postcss.config.js`.
- Menggunakan Tailwind CSS 3.4 + Autoprefixer untuk menghasilkan CSS dengan prefix browser yang diperlukan.
- Mempertahankan warna, dark mode, komponen tombol, kartu, input, dan navigasi yang sudah ada.

## WAJIB dilakukan di PC kantor (komputer yang menjalankan Laravel)

Jalankan dari folder utama project, yaitu folder yang berisi `artisan` dan `package.json`:

```bash
npm install
npm run build
php artisan optimize:clear
```

`npm run build` akan membuat file CSS/JS produksi di `public/build`. Website kemudian mengambil CSS dari komputer kantor sendiri, bukan dari Tailwind CDN. Jangan menjalankan `npm run dev` untuk penggunaan harian melalui WiFi kantor.

Jika PC kantor memakai PowerShell dan `npm` diblokir oleh Execution Policy, jalankan perintah melalui Command Prompt (CMD), atau gunakan `npm.cmd install` dan `npm.cmd run build`.

## Pengujian dari iMac

1. Pastikan iMac dan PC kantor terhubung ke WiFi/LAN yang sama.
2. Buka alamat lokal website seperti biasanya.
3. Lakukan hard refresh di Safari: tekan `Command + Option + R`.
4. Jika masih tanpa CSS, buka Developer > Show JavaScript Console dan periksa apakah file `/build/assets/*.css` menghasilkan status 404. Pastikan document root web server mengarah ke folder `public` Laravel dan folder `public/build` berhasil dibuat.

Catatan: Google Fonts masih memakai layanan eksternal sebagai peningkatan tampilan font. Jika layanan itu tidak dapat diakses, browser tetap memakai font fallback lokal; layout dan utility CSS tidak bergantung pada Google Fonts.
