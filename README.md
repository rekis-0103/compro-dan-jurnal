# Company Profile dan E-Recruitment PT Waindo Specterra

Aplikasi ini adalah website company profile berbasis PHP native yang dilengkapi modul e-recruitment. Website menyediakan halaman informasi perusahaan, produk, layanan, mitra kerja, berita/kegiatan, galeri, kontak, serta alur rekrutmen untuk pelamar, HRD, admin, dan pengelola konten.

## Fitur Utama

### Halaman Publik

- Beranda
- Tentang Kami
- Produk dan detail produk
- Layanan
- Mitra Kerja
- Berita, kegiatan, webinar, live, dan galeri
- Hubungi Kami
- Bergabung / lowongan kerja
- Registrasi dan login pelamar

### Admin

- Dashboard admin
- Kelola data user
- Kelola data pendidikan
- Lihat dan ekspor log aktivitas
- Ekspor data karyawan

### HRD

- Dashboard HRD
- Kelola lowongan kerja
- Kelola lamaran pelamar
- Lihat detail kandidat
- Catatan kandidat
- Ekspor data pelamar ke PDF
- Update status seleksi dan kirim notifikasi/email

### Konten

- Dashboard konten
- Kelola berita/kegiatan
- Kelola webinar
- Kelola live streaming
- Kelola galeri
- Kelola produk
- Kelola layanan

### Pelamar

- Dashboard pelamar
- Kelola profil
- Upload CV
- Lihat lowongan
- Lamar pekerjaan
- Lihat status lamaran dan notifikasi
- Batalkan lamaran

## Teknologi

- PHP native
- MySQL
- HTML, CSS, dan JavaScript
- Composer
- PHPMailer untuk pengiriman email
- FPDF untuk ekspor PDF
- Laragon sebagai rekomendasi server lokal

## Struktur Folder

```text
.
├── admin/                  # Modul admin
├── ajax/                   # Endpoint AJAX untuk produk dan data dinamis
├── assets/                 # Gambar, CSS, dan aset publik
├── connect/                # Konfigurasi koneksi database dan email
├── hrd/                    # Modul HRD
├── includes/               # Komponen include seperti navbar
├── js/                     # JavaScript halaman publik
├── konten/                 # Modul pengelola konten
├── pelamar/                # Modul pelamar
├── uploads/                # File upload konten, CV, popup, galeri, dan manual
├── vendor/                 # Dependensi Composer
├── e-recruitment.sql       # Dump database
└── index.php               # Halaman utama
```

## Kebutuhan Sistem

- PHP 8.x atau versi yang kompatibel dengan aplikasi
- MySQL / MariaDB
- Composer
- Web server lokal seperti Apache melalui Laragon, XAMPP, atau sejenisnya

## Cara Instalasi

1. Clone repository ini ke folder web server lokal.

   ```bash
   git clone https://github.com/rekis-0103/compro-dan-jurnal.git
   ```

2. Masuk ke folder proyek.

   ```bash
   cd compro-dan-jurnal
   ```

3. Install dependensi Composer.

   ```bash
   composer install
   ```

4. Buat database MySQL dengan nama berikut.

   ```sql
   CREATE DATABASE `e-recruitment`;
   ```

5. Import file database.

   ```bash
   mysql -u root -p e-recruitment < e-recruitment.sql
   ```

   Jika menggunakan phpMyAdmin, buat database `e-recruitment`, lalu import file `e-recruitment.sql`.

6. Sesuaikan konfigurasi database di `connect/koneksi.php`.

   ```php
   $db_config = [
       'host' => 'localhost',
       'username' => 'root',
       'password' => '',
       'database' => 'e-recruitment'
   ];
   ```

7. Sesuaikan konfigurasi email di `connect/email_config.php` jika fitur email akan digunakan.

   ```php
   define('EMAIL_HOST', 'smtp.gmail.com');
   define('EMAIL_PORT', 587);
   define('EMAIL_USERNAME', 'email@example.com');
   define('EMAIL_PASSWORD', 'app-password');
   define('EMAIL_FROM_NAME', 'PT Waindo Specterra HRD');
   define('EMAIL_FROM_ADDRESS', 'email@example.com');
   ```

8. Jalankan aplikasi melalui web server lokal.

   Contoh jika folder proyek berada di `C:\laragon\www\compro`:

   ```text
   http://localhost/compro/
   ```

## Akun dan Hak Akses

Data awal akun tersedia dari file `e-recruitment.sql`. Role yang digunakan aplikasi:

- `admin`
- `hrd`
- `konten`
- `pelamar`

Setelah login, aplikasi akan mengarahkan user ke dashboard sesuai role masing-masing.

## Konfigurasi Penting

- `connect/koneksi.php`: konfigurasi koneksi database.
- `connect/email_config.php`: konfigurasi SMTP dan helper pengiriman email.
- `e-recruitment.sql`: struktur dan data awal database.
- `uploads/`: folder penyimpanan file upload. Pastikan folder ini dapat ditulis oleh web server.
- `vendor/`: folder dependensi Composer. Jika folder ini tidak tersedia, jalankan `composer install`.

## Alur Penggunaan Singkat

1. Admin membuat atau mengelola user dan memantau log aktivitas.
2. Konten mengelola informasi publik seperti berita, produk, layanan, kegiatan, webinar, live, dan galeri.
3. HRD membuat lowongan dan memproses lamaran pelamar.
4. Pelamar registrasi, melengkapi profil, upload CV, melamar lowongan, lalu memantau status lamaran.

## Catatan Pengembangan

- Aplikasi masih menggunakan PHP native, sehingga setiap modul memiliki file PHP, CSS, dan JavaScript terpisah.
- Gunakan Composer untuk mengelola dependensi, terutama PHPMailer dan FPDF.
- Hindari menyimpan password email asli langsung di repository. Gunakan app password khusus SMTP dan ganti konfigurasi sebelum deploy.
- Pastikan file upload divalidasi dan folder upload diamankan saat aplikasi dipasang di server produksi.

## Lisensi

Repository ini digunakan untuk pengembangan website company profile dan fitur e-recruitment PT Waindo Specterra sebagai kebutuhan proyek/jurnal.
