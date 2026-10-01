# Deploy tanpa SSH (hanya File Manager, phpMyAdmin, dan Cron cPanel)

Dipakai bila hosting tidak menyediakan Terminal/SSH. Kalau Terminal ada, **lebih mudah** mengikuti `GO_LIVE_CHECKLIST.md`.
Paket zip sudah berisi `vendor/` (hasil `composer install --no-dev`) dan aset panel admin, jadi tidak ada yang perlu di-install.

Paket zip memuat folder `jbm/` dan satu folder tambahan `deploy-no-ssh/` (hanya ada di zip):

| Isi `deploy-no-ssh/` | Fungsi |
| --- | --- |
| `jbm-initial.sql` | struktur database + isi awal (menu, teks halaman, produk DUMMY, dll.). **Tanpa akun admin.** |
| `storage-public-seed/` | foto-foto isi awal; disalin ke `storage/app/public/` |
| `create-admin.sql.example` | contoh perintah SQL untuk membuat akun admin pertama |

> Syarat hosting: PHP **8.3**+ dengan ekstensi `gd`, `intl`, `mbstring`, `fileinfo`, `exif`, `zip`, `pdo_mysql`; MySQL/MariaDB.
> Atur versi PHP di cPanel > *Select PHP Version* (atau *MultiPHP Manager*).

## 1. Unggah dan ekstrak

1. cPanel > **File Manager** > buka folder home (`/home/NAMAUSER`, **di luar** `public_html`).
2. Unggah `jbm-deploy-v1.0.zip`, lalu klik kanan > **Extract**. Hasilnya `/home/NAMAUSER/jbm/` dan `/home/NAMAUSER/deploy-no-ssh/`.
   (Memindahkan `deploy-no-ssh/` ke luar `jbm/` memang disengaja: isinya tidak boleh bisa diakses dari web.)

## 2. Arahkan domain ke folder `public`

Document root domain harus `/home/NAMAUSER/jbm/public`. Di cPanel > **Domains** > pilih domain > **Manage** > ubah *Document Root*.

Jika document root domain utama **tidak bisa diubah**: salin isi `jbm/public/` ke `public_html/`, lalu di `public_html/index.php` ubah dua path
`__DIR__.'/../vendor/autoload.php'` dan `__DIR__.'/../bootstrap/app.php'` menjadi `__DIR__.'/../jbm/vendor/autoload.php'` dan `__DIR__.'/../jbm/bootstrap/app.php'`
(dan `/../storage/framework/maintenance.php` menjadi `/../jbm/storage/framework/maintenance.php`).

## 3. Database

1. cPanel > **MySQL Databases**: buat database dan user, beri user semua hak pada database itu. Catat namanya (berawalan nama akun).
2. cPanel > **phpMyAdmin** > pilih database > **Import** > pilih `deploy-no-ssh/jbm-initial.sql` > **Go**.
   (File dibuat dari MariaDB 10.4. Bila impor di MySQL menolak sintaks tertentu, hubungi pembuat paket atau pakai jalur SSH.)

## 4. File `.env`

1. Di `jbm/`: salin `.env.production.example` menjadi `.env` (File Manager > Copy/Rename). Izin file `600`.
2. Isi: `APP_URL` (**http://** dulu; baru https:// setelah SSL aktif, lihat `GO_LIVE_CHECKLIST.md`), `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `MAIL_*`.
3. **`APP_KEY`**: tanpa Terminal harus dibuat di komputer sendiri. Di PowerShell/CMD yang punya PHP:

   ```
   php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```

   Tempel hasilnya sebagai `APP_KEY=...`. Simpan nilainya (jangan dibagikan); mengganti key nanti membuat sesi login lama tidak berlaku.

## 5. Foto isi awal dan izin folder

1. Salin **isi** `deploy-no-ssh/storage-public-seed/` (folder bernomor `1`, `2`, ...) ke `jbm/storage/app/public/`. File `.htaccess` dan `.gitignore` di sana jangan dihapus.
2. Izin `775` untuk `jbm/storage` dan `jbm/bootstrap/cache` beserta isinya (File Manager > Change Permissions > centang *Recurse*).

## 6. Symlink `public/storage` (supaya foto tampil)

Foto dilayani lewat `public/storage` yang harus menjadi symlink ke `storage/app/public`. Tanpa Terminal, bisa lewat **Cron Jobs**:

1. cPanel > **Cron Jobs** > tambah cron (mis. "tiap menit"), perintah:

   ```
   /bin/ln -s /home/NAMAUSER/jbm/storage/app/public /home/NAMAUSER/jbm/public/storage
   ```

2. Tunggu semenit, buka situs dan cek foto muncul. **Lalu hapus cron itu** (perintahnya hanya perlu jalan sekali; kalau symlink sudah ada, hasilnya error tak berbahaya).

Bila cron tidak tersedia: salin folder `jbm/storage/app/public/` menjadi `jbm/public/storage/` (folder biasa). Foto isi awal tampil, tetapi **foto yang diunggah lewat admin tidak akan muncul** sampai symlink dibuat.

## 7. Akun admin pertama

Tidak ada `php artisan admin:create` tanpa Terminal, jadi akun dibuat lewat SQL:

1. Di komputer Anda buat hash password (ganti `PasswordKuat123!` dengan password sendiri: 12+ karakter, huruf besar/kecil, angka, simbol):

   ```
   php -r "echo password_hash('PasswordKuat123!', PASSWORD_BCRYPT, ['cost' => 12]).PHP_EOL;"
   ```

2. phpMyAdmin > database > tab **SQL**, jalankan (ganti nama, email, dan hash):

   ```sql
   INSERT INTO users (name, email, email_verified_at, password, is_admin, created_at, updated_at)
   VALUES ('Administrator', 'admin@jbmelimpah.com', NOW(), 'HASH_DARI_LANGKAH_1', 1, NOW(), NOW());
   ```

3. Buka `https://domain-anda/admin` dan login. Segera ubah password lewat menu profil bila perlu, dan **jangan menyimpan password asli di file apa pun di server**.

## 8. Uji dan lanjutkan

- Beranda, `/id`, `/company`, `/admin/login` terbuka; foto tampil; form penawaran terkirim (cek menu *Pesan Masuk*).
- Lanjut ke urutan HTTPS di `GO_LIVE_CHECKLIST.md` (langkah 2 dan seterusnya) dan `SECURITY_CHECKLIST_DEPLOY.md`.
- **Cron scheduler** (`php artisan schedule:run` tiap menit) untuk retensi data pribadi: tambahkan di Cron Jobs dengan perintah
  `/usr/local/bin/php /home/NAMAUSER/jbm/artisan schedule:run >> /dev/null 2>&1` (path PHP bisa `ea-php83`; lihat petunjuk di halaman Cron).
- Isi **semua data DUMMY** lewat admin (angka, sejarah, pimpinan, sertifikasi, kontak, logo, foto produk) sebelum mengumumkan situs.

## Catatan

- Cache konfigurasi/route/view **sengaja tidak** disertakan di zip: cache semacam itu menyimpan path dan nilai `.env` mesin pembuatnya.
  Situs berjalan normal tanpa cache. Bila nanti ada Terminal, jalankan `php artisan optimize` di server.
- Update berikutnya: unggah zip baru, ekstrak menimpa `jbm/` (jangan timpa `.env` dan `storage/app/public/`), lalu impor migrasi baru
  (atau jalankan `php artisan migrate --force` lewat cron satu kali).
- Membuat ulang paket dari kode: `git archive`, `composer install --no-dev --optimize-autoloader`, `php artisan filament:assets`, lalu zip.
