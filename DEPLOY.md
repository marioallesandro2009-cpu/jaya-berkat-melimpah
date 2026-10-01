# Panduan Deploy: PT Jaya Berkat Melimpah ke cPanel hosting

> **Deploy pertama: ikuti `GO_LIVE_CHECKLIST.md`** (lima langkah dengan urutan yang tidak boleh dibalik: http dulu, SSL, baru HTTPS paksa, cron, uji `deploy.sh` per langkah). Dokumen ini adalah penjelasan lengkapnya.

Alur: **laptop → GitHub → `git pull` di server** lewat Terminal cPanel.

- Stack: Laravel 13, PHP 8.3, Filament 5 (admin), Blade; CSS/JS/font statis di `public/` (tanpa Vite/Node)
- User cPanel: `CPANELUSER` (home: `/home/CPANELUSER`)
- Domain: `jbmelimpah.com` (addon domain), document root awal `/home/CPANELUSER/public_html/jbmelimpah.com`
- Struktur akhir di server:

```
/home/CPANELUSER/jbm/                          <- repo (di luar public_html: .env, vendor, dll tidak bisa diakses publik)
/home/CPANELUSER/public_html/jbmelimpah.com  <- symlink ke /home/CPANELUSER/jbm/public
```

- Tidak ada langkah build asset: `public/css`, `public/js`, `public/fonts` dilayani apa adanya (URL-nya membawa `?v=<waktu ubah file>` sehingga browser otomatis mengambil versi baru). Server tidak perlu Node/npm.
- Session, cache, dan antrean memakai `database` / `sync`: tidak butuh Redis atau queue worker.
- Ada satu tugas terjadwal (`leads:prune`, retensi data pribadi pada Pesan Masuk), jadi butuh **satu entri cron** (A13).
- Pengaturan keamanan (HTTPS paksa, HSTS, CSP, proxy Cloudflare, Turnstile, path admin) semuanya lewat `.env`, dengan default aman untuk deploy pertama tanpa SSL. Daftar pengaturan manual di luar kode: `SECURITY_CHECKLIST_DEPLOY.md`; temuan dan status perbaikan: `SECURITY_AUDIT.md`.
- Dua template env: `.env.example` untuk **lokal** (sqlite, debug) dan `.env.production.example` untuk **server** (MySQL, production).

> ### ⚠️ Penting: `deploy.sh` belum pernah dijalankan sungguhan
>
> Script `deploy.sh` baru diperiksa sintaksnya (`bash -n`), **belum pernah dijalankan di server** (atau di lingkungan cPanel mana pun). Karena itu, **deploy pertama JANGAN langsung `bash deploy.sh`.** Jalankan tiap perintah pada bagian A7 dan A12 **satu per satu, manual**, sambil membaca outputnya. Setelah terbukti setiap langkah berhasil, baru pakai `bash deploy.sh` penuh untuk update-update berikutnya (bagian B). Jika `deploy.sh` ternyata gagal di suatu langkah, perbaiki scriptnya dulu lalu commit, jangan dipaksa.

---

## A. Setup awal (sekali saja)

Semua langkah di bagian ini dijalankan di **Terminal cPanel** (atau SSH), kecuali disebutkan lain.

### A1. Set PHP 8.3 dan ekstensi

1. cPanel → **MultiPHP Manager** → centang `jbmelimpah.com` → pilih **PHP 8.3** → Apply.
2. cPanel → **Select PHP Version** (atau **MultiPHP INI Editor**) → pastikan ekstensi berikut aktif:

   `ctype`, `curl`, `dom`, `exif`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `intl`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `phar`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `zlib`

   `intl` (dipakai Filament), `zip`, `exif`, dan `gd` (konversi WebP) paling sering tidak aktif secara bawaan.
3. Di MultiPHP INI Editor, sarankan: `memory_limit = 256M`, `upload_max_filesize = 8M`, `post_max_size = 16M` (upload gambar CMS maks. 5 MB).
4. Di MultiPHP INI Editor (mode Editor): **`expose_php = Off`** (menyembunyikan versi PHP di header; aplikasi juga menghapus `X-Powered-By`). Ekstensi `gd` wajib aktif: semua gambar unggahan di-encode ulang dengan GD untuk membuang metadata dan muatan tersembunyi.

### A2. Cek PHP dan Composer di terminal

```bash
php -v
/opt/cpanel/ea-php83/root/usr/bin/php -v
composer -V
```

- `php -v` di terminal sering masih versi lama. Yang penting `/opt/cpanel/ea-php83/root/usr/bin/php -v` menampilkan 8.3.x. `deploy.sh` otomatis memakai path ini; jika berbeda, set `PHP_BIN` (lihat bagian B).
- Kalau `composer` tidak ada, pasang `composer.phar` lokal di home:

```bash
cd ~
/opt/cpanel/ea-php83/root/usr/bin/php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
/opt/cpanel/ea-php83/root/usr/bin/php composer-setup.php --install-dir=$HOME --filename=composer.phar
rm composer-setup.php
/opt/cpanel/ea-php83/root/usr/bin/php ~/composer.phar -V
```

`deploy.sh` otomatis memakai `~/composer.phar` bila ada.

### A3. Buat SSH key dan pasang sebagai Deploy Key GitHub

```bash
ssh-keygen -t ed25519 -C "deploy-jbm@CPANELUSER" -f ~/.ssh/jbm_deploy -N ""
cat ~/.ssh/jbm_deploy.pub
```

Salin seluruh isi public key (satu baris `ssh-ed25519 AAAA...`).

Di GitHub: repo → **Settings → Deploy keys → Add deploy key** → beri judul (mis. `cPanel hosting`), tempel key, **jangan** centang "Allow write access" (read-only) → Add key.

Buat config SSH supaya key ini dipakai untuk GitHub:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/jbm_deploy
    IdentitiesOnly yes
EOF
chmod 700 ~/.ssh && chmod 600 ~/.ssh/config ~/.ssh/jbm_deploy
```

Tes koneksi (jawab `yes` saat ditanya fingerprint):

```bash
ssh -T git@github.com
```

Berhasil jika muncul: `Hi <user>/<repo>! You've successfully authenticated, but GitHub does not provide shell access.`

> Satu deploy key hanya bisa dipakai di satu repo. Jika nanti ada repo lain di server, buat key dan `Host` alias terpisah.

### A4. Clone repo

```bash
cd ~
git clone git@github.com:<AKUN-GITHUB>/<NAMA-REPO>.git jbm
cd jbm
git branch --show-current   # harus: main
```

### A5. Buat database di cPanel

cPanel → **MySQL Databases**:

1. **Create New Database** → nama `jbm` → hasilnya `ACCOUNT_jbm`.
2. **MySQL Users → Add New User** → nama `jbm` → hasilnya `ACCOUNT_jbm` (maks. 16 karakter total; pakai password kuat, simpan).
3. **Add User To Database** → pilih user dan database tadi → **ALL PRIVILEGES**.

Prefix `CPANELUSER_` **wajib** ada di `DB_DATABASE` dan `DB_USERNAME`.

### A6. Isi `.env`

```bash
cd ~/jbm
cp .env.production.example .env
nano .env
```

> Di server pakai `.env.production.example`, **bukan** `.env.example` (itu untuk lokal: sqlite dan `APP_DEBUG=true`).

Yang harus diisi/dicek:

| Variabel | Isi |
|---|---|
| `APP_URL` | **deploy pertama: `http://jbmelimpah.com`** (alamat gambar unggahan dibangun dari nilai ini, jadi `https://` sebelum SSL aktif membuat gambar rusak); diubah ke `https://jbmelimpah.com` pada A10. Tanpa `/` di akhir; menentukan domain kanonik www/non-www |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | dari langkah A5; `DB_HOST=localhost` |
| `FORCE_HTTPS`, `HSTS_MAX_AGE` | **biarkan `false` / `300` dulu**; dinyalakan setelah SSL aktif (A10) |
| `CSP_MODE` | `report-only` (default): hanya mencatat pelanggaran di konsol browser; ganti ke `enforce` setelah diperiksa |
| `TRUSTED_PROXIES` | kosong sekarang; `cloudflare` setelah Cloudflare aktif (bagian E) |
| `ADMIN_PATH` | alamat panel admin (default `admin`); bila diubah, semua catatan yang menyebut `/admin` ikut berubah |
| `TURNSTILE_*` | kosong/`false` (opsional, lihat bagian E) |
| `LEADS_ANONYMIZE_AFTER_DAYS`, `LEADS_DELETE_AFTER_MONTHS` | retensi Pesan Masuk (60 hari / 12 bulan); butuh cron (A13) |
| `APP_TIMEZONE` | biarkan `UTC`; jangan diubah setelah ada data |
| `ADMIN_*` | **tidak ada di template produksi.** Admin pertama dibuat dengan `php artisan admin:create` (A7) |
| `WHATSAPP_NUMBER` | nomor awal (opsional, bisa diubah di CMS) |
| `MAIL_*` | **wajib** agar email leads terkirim: SMTP akun email cPanel (lihat A11) |

Simpan di nano: `Ctrl+O`, Enter, `Ctrl+X`. Lalu buat kunci aplikasi:

```bash
/opt/cpanel/ea-php83/root/usr/bin/php artisan key:generate
```

Amankan file `.env`:

```bash
chmod 600 .env
```

### A7. Install dependency, migrasi, seeder, storage link

```bash
cd ~/jbm
PHP=/opt/cpanel/ea-php83/root/usr/bin/php
$PHP ~/composer.phar install --no-dev --optimize-autoloader --no-interaction   # atau: composer install ...
$PHP artisan migrate --force
$PHP artisan db:seed --force      # data awal: pengaturan situs, menu, teks halaman, produk (DUMMY), dll. (TIDAK membuat admin di production)
ln -s "$HOME/jbm/storage/app/public" public/storage   # bukan artisan storage:link: PHP di sini menonaktifkan symlink()/exec()
$PHP artisan filament:assets
$PHP artisan admin:create          # admin pertama: tanya email, nama, lalu password (input tersembunyi)
```

- **`admin:create`** meminta password dengan input tersembunyi (tidak tersimpan di `.env` atau riwayat shell). Aturan password: minimal 12 karakter, huruf besar dan kecil, angka, simbol, dan tidak boleh termasuk password yang pernah bocor. Akun tanpa tanda admin tidak bisa membuka panel. Lupa password: `php artisan admin:create --email=... --reset-password`. Setelah login, password bisa diganti di menu profil (klik nama di pojok kanan atas).
- `storage/app/public/.htaccess` (dari git) mencegah skrip apa pun berjalan di folder upload; `deploy.sh` berhenti bila file itu hilang.
- **Jalankan `db:seed` hanya sekali**, di setup awal. Menjalankannya lagi setelah konten diedit lewat CMS bisa menimpa/menduplikasi data awal.
- Perintah `filament:assets` menyalin CSS/JS panel admin ke `public/` (folder itu di-ignore git, jadi dibuat di server).

### A8. Ganti document root dengan symlink

Pastikan folder domain masih kosong/tidak berisi data penting, lalu:

```bash
rm -rf ~/public_html/jbmelimpah.com
ln -s ~/jbm/public ~/public_html/jbmelimpah.com
ls -ld ~/public_html/jbmelimpah.com     # harus tampil: -> /home/CPANELUSER/jbm/public
```

Jika cPanel menolak (folder document root domain harus ada), buat ulang lewat cPanel → **Domains** dan ulangi langkah ini.

### A9. Permission storage dan bootstrap/cache

```bash
cd ~/jbm
mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Direktori `~/jbm` dan `~` sendiri sebaiknya `755` (bukan `777`), agar Apache bisa menelusuri symlink: `chmod 755 ~ ~/jbm ~/jbm/public`.

### A10. AutoSSL, lalu nyalakan HTTPS paksa

1. cPanel → **SSL/TLS Status** → centang `jbmelimpah.com` dan `www.jbmelimpah.com` → **Run AutoSSL**. Tunggu beberapa menit sampai sertifikat aktif, lalu pastikan `https://jbmelimpah.com` terbuka tanpa peringatan.
2. **Baru setelah itu**, di `~/jbm/.env`:

   ```
   APP_URL=https://jbmelimpah.com
   FORCE_HTTPS=true
   HSTS_MAX_AGE=300
   ```

   `SESSION_SECURE_COOKIE` mengikuti `FORCE_HTTPS` (jangan di-set sendiri), lalu `php artisan config:clear && php artisan cache:clear && php artisan config:cache`. **`cache:clear` wajib** setiap kali `APP_URL` berubah: alamat gambar tersimpan di cache konten, dan tanpa dikosongkan gambar tetap beralamat `http://` (mixed content, gambar rusak di halaman https). Efeknya: semua alamat `http://` dialihkan ke `https://`, cookie session hanya lewat https, dan header HSTS dikirim 300 detik.
3. **Jangan menyalakan `FORCE_HTTPS` sebelum SSL aktif**: situs tidak bisa dibuka, dan cookie secure lewat http tidak tersimpan sehingga login dan form gagal (419). Bila terlanjur: ubah kembali `FORCE_HTTPS=false` di `.env` dan `config:cache`.
4. Naikkan `HSTS_MAX_AGE` bertahap setelah beberapa hari tanpa masalah: 300, 86400 (1 hari), 2592000 (30 hari), 31536000 (1 tahun). Tanpa `preload`. Browser mengingat nilai terakhir sepanjang durasinya, jadi jangan melompat terlalu cepat.
5. `CSP_MODE=report-only` mencatat pelanggaran kebijakan konten di konsol browser (F12) tanpa memblokir apa pun. Jelajahi beranda, halaman perusahaan, halaman produk, berita, dan form Kontak; bila konsol bersih, ganti ke `CSP_MODE=enforce` dan `config:cache`.

### A11. Email leads dari form Kontak: akun email, SMTP, SPF/DKIM, From vs Reply-To

**Status saat ini:** form Kontak di beranda (`#contact`) **sungguh mengirim email**. Setiap pesan disimpan dulu ke database (admin: **Pesan Masuk**), lalu diemail ke daftar penerima di **Pengaturan Situs → tab Kontak** setelah respons dikirim ke pengunjung. Jika SMTP gagal, pengunjung tetap melihat sukses (pesan sudah tersimpan) dan pesan itu ditandai **"Email gagal terkirim"** di admin. Tanpa langkah di bawah ini, email akan gagal dan pesan hanya terbaca di admin.

**1. Buat akun email pengirim** (cPanel → **Email Accounts**): `noreply@jbmelimpah.com` dengan password kuat. Akun ini hanya dipakai untuk mengirim (SMTP); kotak masuknya tidak perlu dibaca.

**2. Isi `.env` di server** (template: `.env.production.example`):

```
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.jbmelimpah.com
MAIL_PORT=465
MAIL_USERNAME=noreply@jbmelimpah.com
MAIL_PASSWORD=<password akun email>
MAIL_FROM_ADDRESS="noreply@jbmelimpah.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Lalu `php artisan config:clear && php artisan config:cache`. Nilai dan port pastinya ada di cPanel → Email Accounts → **Connect Devices** (ikuti "Secure SSL/TLS Settings"; jika 465 tidak jalan pakai port 587 dengan `MAIL_SCHEME=null`). `MAIL_FROM_ADDRESS` **tidak bisa diubah dari admin**: hanya di `.env`.

**3. SPF dan DKIM** (cPanel → **Email Deliverability**, pilih `jbmelimpah.com`):
- Status akan menampilkan masalah bila SPF/DKIM belum ada. Klik **Repair** / **Install the suggested record** untuk kedua record. Jika DNS domain **tidak** dikelola di cPanel (nameserver lain, mis. Cloudflare), klik **Manage → Customize/Copy**, lalu tambahkan record TXT yang disarankan di penyedia DNS (jangan diproksi/oranye untuk record TXT; TXT tidak bisa diproksi).
- SPF, contoh bentuknya (pakai nilai yang disarankan cPanel/hosting, bukan contoh ini):
  `@  TXT  "v=spf1 +a +mx +ip4:<IP-server> include:<domain-spf-hosting> ~all"`
- DKIM: record TXT `default._domainkey` berisi public key yang dibuat cPanel.
- Hanya boleh ada **satu** record SPF per domain; gabungkan `include:` bila sudah ada.
- Tunggu propagasi (hingga beberapa jam), lalu buka lagi Email Deliverability: keduanya harus berstatus **valid**.

**4. DMARC** (disarankan), record TXT di DNS domain, mulai longgar:

```
_dmarc  TXT  "v=DMARC1; p=none; rua=mailto:<email-penerima-laporan>"
```

Setelah beberapa minggu laporan bersih, naikkan ke `p=quarantine`.

**5. Isi daftar penerima di admin** (Pengaturan Situs → tab **Kontak**): satu alamat per baris, boleh Gmail pribadi dan lebih dari satu (maks. 10). Bisa diganti kapan saja tanpa deploy. Selama daftar ini kosong, admin menampilkan banner peringatan dan notifikasi sementara jatuh ke email publik di tab Umum.

**6. Tes:**
1. Buka situs, kirim pesan lewat form Kontak dengan email Anda sendiri.
2. Cek admin → **Pesan Masuk**: pesan muncul dan **tidak** ada badge merah "Email gagal terkirim".
3. Cek Gmail penerima (juga folder Spam): email masuk dengan subjek sesuai bahasa pengunjung. Buka **Show original**: `SPF`, `DKIM`, dan `DMARC` harus `PASS`.
4. Tekan **Balas** di Gmail: kolom "Kepada" harus berisi email pengunjung (Reply-To), bukan `noreply@`.
5. Jika ada badge "Email gagal terkirim": lihat `storage/logs/laravel-*.log` (baris "Contact email failed" berisi alasannya, mis. autentikasi SMTP salah), perbaiki `.env`, lalu balas pesan itu manual dari admin ("Balas via email").

**7. From vs Reply-To** (yang dilakukan aplikasi):

| Header | Isi | Alasan |
|---|---|---|
| `From` | **selalu alamat domain sendiri**: `noreply@jbmelimpah.com` (`MAIL_FROM_ADDRESS`) | SPF/DKIM hanya lolos untuk domain yang Anda kendalikan. Memakai alamat pengunjung (mis. `pengunjung@example.com`) sebagai `From` akan gagal SPF/DKIM/DMARC dan masuk spam atau ditolak. |
| `Reply-To` | **email pengunjung** yang mengisi form | Saat tim menekan "Balas", balasan langsung ke calon klien, bukan ke `noreply@`. |
| `To` | tiap alamat di daftar penerima (satu email per alamat) | Satu alamat yang bermasalah tidak menggagalkan yang lain. |

Subjek mengikuti bahasa pengunjung (diatur di tab Kontak; `:name` diganti nama pengirim), isi email selalu Bahasa Indonesia. Nama pengunjung dibersihkan dari karakter kontrol dan diberi validasi email sehingga tidak bisa menyisipkan header (header injection).

**8. Catatan operasional:**
- Pengiriman email berjalan **setelah** respons (`afterResponse`, tanpa queue worker). Kegagalan SMTP tidak terlihat oleh pengunjung; pantau badge "Email gagal terkirim" dan badge angka **Pesan Masuk** di sidebar admin.
- Batas kirim per jam dari hosting (umumnya ratusan email/jam per akun) jauh di atas beban form ini, yang sendirinya dibatasi 5 kiriman per 15 menit per IP.
- Form dilindungi CSRF, honeypot tersembunyi, dan rate limit per IP. Di belakang Cloudflare kelak, IP pengunjung baru terbaca benar setelah proxy tepercaya dikonfigurasi; sampai itu, batas per IP akan menghitung IP Cloudflare.
- Pesan menyimpan IP dan user-agent pengunjung (data pribadi; hanya tampil di detail pesan di admin). Lihat catatan retensi di `docs/kesiapan-produksi.md`.

### A12. Cache produksi dan tes (manual, satu per satu)

**Deploy pertama: jangan `bash deploy.sh`** (script belum pernah diuji, lihat peringatan di atas). Jalankan perintah berikut satu per satu, baca outputnya, dan berhenti bila ada error:

```bash
cd ~/jbm
PHP=/opt/cpanel/ea-php83/root/usr/bin/php

$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
```

(Langkah `composer install`, `migrate`, symlink `public/storage`, dan `filament:assets` sudah dijalankan manual di A7.)

Buka `https://jbmelimpah.com`, lalu `https://jbmelimpah.com/admin` dan login dengan akun dari `admin:create`. Cek juga `https://jbmelimpah.com/up` (health check, harus 200).

Setelah situs terbukti jalan, ujilah `deploy.sh` sekali pada perubahan kecil (mis. edit satu teks, push, lalu `bash deploy.sh` sambil mengawasi outputnya). Baru sejak itu ia dianggap teruji untuk update rutin.

### A13. Cron scheduler

Proyek ini punya satu tugas terjadwal: `leads:prune` (harian 03:15) yang menghapus IP dan user-agent lead setelah 60 hari dan seluruh pesan setelah 12 bulan. Agar berjalan, tambahkan **satu** entri di cPanel → **Cron Jobs** (setiap menit; Laravel sendiri yang memilih tugas mana yang jatuh tempo):

```
* * * * * cd /home/CPANELUSER/jbm && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Periksa: `php artisan schedule:list` menampilkan `leads:prune`. Uji tanpa mengubah data: `php artisan leads:prune --dry-run`. Tanpa cron ini data pribadi tidak pernah dibersihkan.

---

## B. Alur update rutin

**Di laptop:**

```bash
vendor/bin/pest && vendor/bin/pint --test && vendor/bin/phpstan analyse   # semua harus hijau
npm run build          # perkecil public/css/style.css dan public/js/main.js (hasilnya *.min.* ikut di-commit)
git add -A
git commit -m "Deskripsi perubahan"
git push origin main
```

Setelah mengubah `public/css/style.css` atau `public/js/main.js`, jalankan `npm run build` (sekali `npm install` di laptop) dan commit file `*.min.*`. Situs otomatis memakai file `.min` selama lebih baru dari sumbernya, jadi lupa menjalankannya hanya membuat situs memakai file yang lebih besar, bukan versi usang. Foto dan teks diubah lewat admin, bukan lewat git.

**Di server (Terminal cPanel):**

> Hanya setelah `deploy.sh` teruji (lihat peringatan di awal dokumen dan A12). Pada deploy pertama, jalankan perintah A7 dan A12 secara manual.

```bash
cd ~/jbm && bash deploy.sh
```

`deploy.sh` akan: `git pull --ff-only`, `composer install --no-dev`, `migrate --force`, symlink `public/storage` (`ln -s`, jika belum ada), `filament:assets`, `optimize:clear`, lalu `config:cache`, `route:cache`, `view:cache`, dan mengatur permission `storage`/`bootstrap/cache`. Aman dijalankan berulang dan berhenti di error pertama.

Jika path PHP/Composer di server berbeda:

```bash
PHP_BIN=/usr/local/bin/php bash deploy.sh
COMPOSER_BIN="/opt/cpanel/ea-php83/root/usr/bin/php $HOME/composer.phar" bash deploy.sh
```

Catatan:
- Perubahan konten dilakukan lewat **CMS** dan tersimpan di database server. Jangan menimpanya dengan seeder.
- Setelah `deploy.sh`, cache konten depan otomatis dibersihkan (`optimize:clear`).
- Jika `git pull` menolak karena ada perubahan lokal di server (mis. seseorang mengedit file di server), simpan/buang dulu: `git status`, lalu `git stash` atau `git checkout -- <file>`.

---

## C. Troubleshooting

**Error 500 / halaman kosong**
- Lihat log: `tail -n 50 ~/jbm/storage/logs/laravel-*.log` (log `daily`, nama berisi tanggal).
- Jika log kosong, cek log Apache: cPanel → **Errors** atau `~/logs/`.
- Penyebab umum: `.env` belum ada / `APP_KEY` kosong, versi PHP salah, `vendor/` belum ada, permission `storage`, kredensial DB salah.
- Sementara debugging: set `APP_DEBUG=true` di `.env`, lalu `php artisan config:clear`. **Kembalikan ke `false` setelahnya.**

**403 Forbidden**
- Symlink rusak atau tidak boleh diikuti: cek `ls -ld ~/public_html/jbmelimpah.com` menunjuk ke `/home/CPANELUSER/jbm/public`.
- Permission jalur: `chmod 755 ~ ~/jbm ~/jbm/public`; file dalam `public` `644`.
- Jika `public/.htaccess` memicu error `Options`, hapus baris `Options -MultiViews -Indexes` (host tertentu melarang `Options`).

**Versi PHP salah** (`Your PHP version does not satisfy...`, atau error syntax pada `vendor/`)
- Cek di web: MultiPHP Manager harus 8.3 untuk domain ini. Cek di terminal: gunakan `/opt/cpanel/ea-php83/root/usr/bin/php`, bukan `php` telanjang.
- Jalankan `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy.sh`.

**Permission / "Permission denied" / gagal tulis log & cache**
- `cd ~/jbm && chmod -R ug+rwX storage bootstrap/cache`
- Pastikan folder ada: `mkdir -p storage/framework/{cache/data,sessions,views} storage/logs`.

**Asset (CSS/JS) tidak muncul atau tampilan rusak**
- Pastikan `public/css/style.css`, `public/js/main.js`, dan `public/fonts/` ada di server (`ls ~/jbm/public`).
- `APP_URL` harus `https://jbmelimpah.com` (bukan `http://` atau domain lain).
- Panel admin tanpa gaya: jalankan `php artisan filament:assets` (sudah otomatis di `deploy.sh`).
- Perubahan CSS tidak tampil: tekan Ctrl+F5; alamat file membawa `?v=` sehingga pengunjung lain otomatis mendapat versi baru.

**Gambar upload CMS tidak tampil**
- Pastikan `public/storage` ada: `ls -l ~/jbm/public/storage` (symlink ke `storage/app/public`). Jika tidak: `ln -s ~/jbm/storage/app/public ~/jbm/public/storage` (bukan `artisan storage:link`: di hosting `symlink()` dan `exec()` PHP dinonaktifkan sehingga errornya `Call to undefined function exec()``.
- Pastikan `MEDIA_DISK=public` di `.env`, lalu `php artisan config:cache`.
- Upload gagal untuk file besar: naikkan `upload_max_filesize` dan `post_max_size` di MultiPHP INI Editor.

**419 "Sesi kedaluwarsa" saat login atau mengirim form**
- Paling sering: `FORCE_HTTPS=true` (cookie secure) padahal halaman dibuka lewat `http://`, atau cookie lama. Buka lewat `https://`; bila SSL belum aktif set `FORCE_HTTPS=false`, lalu `config:cache`.
- Di belakang Cloudflare: jangan cache halaman HTML (token CSRF ikut membeku). Hanya aset statis yang boleh di-cache.
- Bersihkan cookie situs, muat ulang, coba lagi.

**Redirect berulang (ERR_TOO_MANY_REDIRECTS)**
- `FORCE_HTTPS=true` di belakang proxy yang mengirim ke origin lewat http (mis. Cloudflare mode SSL **Flexible**): ganti ke **Full (strict)** dan isi `TRUSTED_PROXIES=cloudflare`.
- Domain utama di `APP_URL` (www atau tidak) harus sama dengan yang Anda buka; middleware mengalihkan ke host `APP_URL`.

**Login admin: "Terlalu banyak percobaan"**
- Setelah 5 kali gagal dari satu IP untuk satu email, login dikunci 15 menit (10 kali lintas IP, 30 kali dari satu IP untuk banyak email). Tunggu, atau hapus kunci: `php artisan cache:clear`. Bila semua pengunjung tampak satu IP (Cloudflare tanpa `TRUSTED_PROXIES`), kunci ini ikut mengenai semua orang: lihat bagian E.
- Jejak percobaan ada di `storage/logs/security-*.log`.

**Gambar upload error 500 atau hilang setelah deploy**
- `storage/app/public/.htaccess` memakai `Options`/`RemoveHandler`; bila hosting melarang, hapus hanya baris `Options -Indexes -ExecCGI` atau blok `<IfModule mod_mime.c>` di file itu dan uji lagi. Jangan menghapus blok `FilesMatch` (itu yang menolak skrip).
- Uji keamanan folder upload: buat file `x.php` berisi teks di `storage/app/public/` lalu buka `https://jbmelimpah.com/storage/x.php`: harus 403, **bukan** isi/eksekusi. Hapus file ujinya.

**Form Kontak butuh JavaScript setelah Turnstile diaktifkan**
- Turnstile memerlukan JS. Pengunjung tanpa JS diminta memakai WhatsApp. Matikan dengan `TURNSTILE_ENABLED=false` bila tidak diinginkan.

**Mixed content (peringatan https di browser)**
- `APP_URL` harus `https://...`. Setelah mengubah `.env`, jalankan `php artisan config:cache`.
- Pastikan AutoSSL aktif dan tidak ada URL `http://` yang ditulis manual di konten CMS.

**Perubahan `.env` tidak berpengaruh**
- Karena `config:cache`, jalankan `php artisan config:clear && php artisan config:cache` (atau `bash deploy.sh`).

**`SQLSTATE ... Access denied` / `Unknown database`**
- Periksa prefix `CPANELUSER_` di `DB_DATABASE` dan `DB_USERNAME`, user sudah ditambahkan ke database dengan ALL PRIVILEGES, dan `DB_HOST=localhost`.

**`git pull` gagal: `Permission denied (publickey)`**
- Ulangi `ssh -T git@github.com`; pastikan deploy key terpasang di repo yang benar dan isi `~/.ssh/config` sesuai bagian A3.

**Gagal `ssh-keygen` / `composer` kehabisan memori**
- Jalankan `php -d memory_limit=-1 ~/composer.phar install --no-dev --optimize-autoloader`. Bila akun membatasi proses, ulangi di jam sepi.

---

## D. Backup dan pemulihan

Yang perlu dibackup (kode sudah aman di GitHub):

| Data | Lokasi | Alasan |
|---|---|---|
| Database MySQL | `ACCOUNT_jbm` | seluruh konten CMS, akun admin, pengaturan |
| Upload CMS | `~/jbm/storage/app/public/` | foto, logo, ikon yang diunggah lewat admin |
| `.env` | `~/jbm/.env` | kredensial DB, `APP_KEY`, akun admin awal (simpan di password manager, **bukan** di GitHub) |

### D1. Backup manual (dari Terminal cPanel)

```bash
mkdir -p ~/backups
STAMP=$(date +%Y%m%d-%H%M)

# 1. Database (password diminta interaktif, atau baca dari .env)
mysqldump --single-transaction --quick --no-tablespaces \
    -u ACCOUNT_jbm -p ACCOUNT_jbm | gzip > ~/backups/db-$STAMP.sql.gz

# 2. Upload CMS
tar -czf ~/backups/storage-$STAMP.tar.gz -C ~/jbm/storage/app public

# 3. .env (izin ketat)
cp ~/jbm/.env ~/backups/env-$STAMP && chmod 600 ~/backups/env-$STAMP

ls -lh ~/backups
```

Unduh hasilnya ke laptop (cPanel → **File Manager** → folder `backups` → Download, atau `scp CPANELUSER@<server>:backups/* .`). Backup yang hanya ada di server yang sama tidak menolong jika akun bermasalah.

### D2. Backup otomatis berkala (cron cPanel)

cPanel → **Cron Jobs** → tambahkan (harian 02:30, simpan 14 hari):

```
30 2 * * * cd /home/CPANELUSER/jbm && bash scripts/backup.sh >/dev/null 2>&1
```

`scripts/backup.sh` membaca kredensial DB dari `.env`, membuat dump database dan arsip `storage/app/public` di `~/backups`, lalu menghapus backup yang lebih tua dari 14 hari.

Selain itu aktifkan backup bawaan cPanel/hosting (JetBackup / **Backup Wizard**) bila paketnya menyediakan, dan unduh backup penuh sebulan sekali.

### D3. Pemulihan

**Database** (menimpa isi database saat ini, pastikan backup-nya benar):

```bash
cd ~/jbm
php artisan down                      # opsional: mode pemeliharaan
gunzip -c ~/backups/db-YYYYMMDD-HHMM.sql.gz | mysql -u ACCOUNT_jbm -p ACCOUNT_jbm
php artisan up
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Bila database kosong/baru, buat dulu database dan user di cPanel (bagian A5), lalu jalankan perintah `mysql` di atas.

**Upload CMS:**

```bash
tar -xzf ~/backups/storage-YYYYMMDD-HHMM.tar.gz -C ~/jbm/storage/app
chmod -R ug+rwX ~/jbm/storage
ls -l ~/jbm/public/storage        # jika hilang: ln -s ~/jbm/storage/app/public ~/jbm/public/storage
```

**Server hilang total** (akun baru): ikuti bagian A dari awal (PHP, SSH key, clone, `.env` dari backup, database), **lewati `db:seed`**, impor dump database (D3), ekstrak arsip upload, lalu `bash deploy.sh`.

**Uji pemulihan** minimal sekali sebelum go-live dan setiap beberapa bulan: impor dump ke database uji dan pastikan `/admin` bisa login serta gambar tampil. Backup yang belum pernah diuji dianggap belum ada.

---

## E. Cloudflare (nanti)

Belum dipakai. Saat dipasang, urutannya:

1. Tambahkan situs di Cloudflare, ganti nameserver di pendaftar domain, pastikan record DNS (A, MX, TXT SPF/DKIM/DMARC) ikut terbawa. Record email (MX, mail, TXT) **tidak boleh diproksi** (awan abu-abu).
2. SSL/TLS: mode **Full (strict)** (bukan Flexible).
3. Di `.env`: `TRUSTED_PROXIES=cloudflare`, lalu `php artisan config:cache`. Tanpa ini IP semua pengunjung tampak sebagai IP Cloudflare, sehingga batas 5 kiriman form Kontak per 15 menit dan kunci login menjadi satu ember untuk seluruh situs. Perbarui daftar IP Cloudflare berkala: `php artisan security:cloudflare-ips` (daftar bawaan dipakai bila gagal).
4. Aturan cache: **jangan cache HTML** (Cache Level Standard, tanpa "Cache Everything"). Aset di `/css/`, `/js/`, `/fonts/` dan `/storage/` boleh.
5. Naikkan `HSTS_MAX_AGE` setelah semuanya stabil (A10 langkah 4).
6. Opsional: Turnstile. Buat widget di dash.cloudflare.com, menu Turnstile, isi `TURNSTILE_SITE_KEY` dan `TURNSTILE_SECRET_KEY`, set `TURNSTILE_ENABLED=true`, `config:cache`. Aktif di form Kontak dan login admin. Bila Cloudflare tidak terjangkau, form tetap diterima (honeypot dan batas kiriman tetap berlaku) dan kejadian dicatat di log keamanan.
7. Sebaiknya origin tidak dapat diakses langsung lewat IP server (firewall hanya IP Cloudflare), karena `TRUSTED_PROXIES` mempercayai header `X-Forwarded-For` dari proxy tepercaya.
