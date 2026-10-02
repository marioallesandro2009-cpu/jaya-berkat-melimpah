# Website PT Jaya Berkat Melimpah

Laravel 13 + panel admin Filament 5 di `/admin`, database (SQLite lokal, MySQL di produksi),
dua bahasa (English di `/`, Indonesia di `/id`), dan media library (foto otomatis dikonversi ke
WebP). Halaman publik adalah Blade biasa (HTML + CSS + sedikit JS di `public/`): tanpa Node di
server (CSS/JS cukup diperkecil di laptop dengan `npm run build`). Semua teks, foto, dan angka di situs berasal dari database dan
diubah lewat admin.

Versi statis sebelumnya ada di `../jbm-static-backup` (di luar proyek ini).

## Menjalankan di lokal (Windows)

PHP 8.3 diaktifkan per terminal (XAMPP memakai PHP 8.2):

```bash
source ./use-php83.sh        # Git Bash   (PowerShell: . .\use-php83.ps1)
composer install
cp .env.example .env         # isi ADMIN_EMAIL dan ADMIN_PASSWORD (kuat: 12+ karakter, huruf besar/kecil, angka, simbol)
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

- Situs: http://127.0.0.1:8000 (Indonesia: `/id`)
- Admin: http://127.0.0.1:8000/admin (akun dari `ADMIN_EMAIL` / `ADMIN_PASSWORD`)

Seeder aman dijalankan ulang: isi yang sudah ada tidak ditimpa.

## Apa yang diubah di admin

| Menu admin | Mengubah |
| --- | --- |
| Pengaturan Situs | nama, logo, favicon, kontak (email, telepon, WhatsApp, alamat), sosial media, penerima notifikasi form, SEO, **warna brand** (tab Warna), **semua label** tombol/form/footer/berita (tab Teks & Label), kecepatan slide hero |
| Menu | isi, urutan, dan tujuan menu navbar dan kolom footer (bagian beranda, halaman, atau URL) |
| Blok Halaman | teks dan foto tiap blok beranda/perusahaan; **seret untuk mengubah urutan**, sembunyikan blok, atau **tambah blok sendiri** (4 tata letak) |
| Angka Skala | angka yang naik saat di-scroll (tahun, pasar, tim, kota) |
| Slide Hero | foto tambahan hero; dua atau lebih slide aktif = hero berganti otomatis |
| Produk | katalog seafood (juga pilihan di form penawaran), **kategori** per produk, dan **halaman detail** per produk (pengantar, spesifikasi, isi, galeri, SEO). Semua produk tampil di halaman **/products** (`/id/produk`) dengan filter kategori. Spesifikasi produk tampil sebagai metadata di halaman itu |
| Kategori Produk | tambah, ubah, urutkan, sembunyikan, dan hapus kategori (mis. Air laut, Air payau, Air tawar). Menghapus kategori tidak menghapus produknya. Produk bisa dipindah kategori massal dari daftar Produk |
| Blok Teks > Bentuk produk | isi grup "Bentuk produk" (Whole, Fillet, dst.) bila perusahaan memang menyediakannya: tampil di bagian "What we supply" halaman /products. Kosong = baris bentuk tidak tampil |
| FAQ, Lokasi, Logo Partner | blok FAQ (dengan data terstruktur Google), daftar lokasi, dan baris logo partner; masing-masing tampil di beranda hanya jika ada isinya |
| Perjalanan (rantai nilai) | tahap Source → Market beserta fotonya |
| Blok Teks | baris Mutu, titik pemeriksaan, bab Keberlanjutan, nilai perusahaan |
| Sejarah / Kepemimpinan / Sertifikasi | halaman Perusahaan |
| Blog | artikel dengan **skor SEO** langsung per bahasa (panel di form dan kolom di daftar), focus keyword, foto sampul (menu "Blog" di situs baru muncul setelah ada artikel terbit; `/news`, `/id/berita`) |
| Pesan Masuk | permintaan penawaran dari form, plus notifikasi email |

Setiap teks punya kolom EN dan ID. Kolom ID yang kosong atau berstatus "Draf" menampilkan teks EN
di `/id`; tandai "Sudah dicek" agar versi ID tampil.

## Data contoh yang HARUS diganti sebelum rilis

Baris dengan penanda **Contoh** di admin adalah data DUMMY dari seeder: angka skala, sejarah,
nama pimpinan, dan sertifikasi (HACCP, ISO 22000, Halal). Kontak di Pengaturan Situs (email,
telepon, WhatsApp, alamat) dan `logo` juga dummy. Penanda hilang setelah baris disimpan dari admin.
Jangan menayangkan sertifikasi yang tidak dimiliki perusahaan.

## Pemeriksaan

```bash
vendor/bin/pest                      # tes otomatis (halaman publik + admin + form)
vendor/bin/pint --test               # gaya kode
vendor/bin/phpstan analyse           # analisis statis
```

## Produksi (cPanel)

Unggah proyek (tanpa `node_modules`), arahkan document root ke `public/`, salin
`.env.example` menjadi `.env`, isi `APP_URL`, MySQL (`DB_*`), `MAIL_*`, lalu:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate && php artisan migrate --force && php artisan storage:link
php artisan admin:create            # akun admin (seeder tidak membuat admin di production)
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Butuh PHP 8.3+ dengan ekstensi `gd`, `intl`, `mbstring`, `fileinfo`, `exif`, `zip`, `pdo_mysql`.
Pengaturan keamanan produksi (HTTPS, HSTS, CSP, proxy) ada di `config/security.php` dan `.env`.
Set `FORCE_HTTPS=true` dan `CSP_MODE=enforce` setelah SSL aktif.

## Dokumen deploy dan keamanan

| Dokumen | Isi |
| --- | --- |
| `GO_LIVE_CHECKLIST.md` | urutan deploy pertama (5 langkah, tidak boleh dibalik) |
| `DEPLOY.md` | penjelasan lengkap: setup cPanel, update rutin, troubleshooting, backup, Cloudflare |
| `SECURITY_AUDIT.md` | kontrol keamanan, status, dan tes yang membuktikannya |
| `SECURITY_CHECKLIST_DEPLOY.md` | hal manual di luar kode (2FA hosting, backup luar server, uji pasca-deploy) |
| `.env.production.example` | template `.env` produksi (MySQL, SMTP, `CSP_MODE`) |
| `DEPLOY_TANPA_SSH.md` | deploy hanya dengan File Manager, phpMyAdmin, dan Cron (hosting tanpa SSH) |
| `deploy.sh`, `scripts/backup.sh` | update di server dan backup database + unggahan |

Sebelum commit, jika `public/css/style.css` atau `public/js/main.js` berubah: `npm install` (sekali) lalu `npm run build`.
