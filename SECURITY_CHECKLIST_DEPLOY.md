# SECURITY_CHECKLIST_DEPLOY.md: hal manual di luar kode

Hal-hal yang **tidak bisa** dikerjakan kode dan harus Anda lakukan di hosting, GitHub, Cloudflare, atau di kebiasaan kerja.
Langkah teknis lengkap ada di `DEPLOY.md`; temuan dan statusnya di `SECURITY_AUDIT.md`.
Centang satu per satu saat deploy; kolom "Uji" adalah cara membuktikan bahwa langkahnya benar.

## 1. Sebelum go-live (server)

| ☐ | Langkah | Uji |
|---|---|---|
| ☐ | **PHP 8.3** untuk domain (cPanel → MultiPHP Manager) dan ekstensi wajib aktif, terutama `gd`, `intl`, `zip`, `exif`, `pdo_mysql` (DEPLOY A1) | `php -v` memakai `/opt/cpanel/ea-php83/root/usr/bin/php`; `php -m` memuat `gd` |
| ☐ | **`expose_php = Off`** (cPanel → MultiPHP INI Editor → mode Editor). Aplikasi juga menghapus `X-Powered-By`, tetapi ini lapisan PHP-nya | `curl -sI https://jbmelimpah.com/` tidak menampilkan `X-Powered-By` |
| ☐ | (Opsional, bila tidak ada fitur yang memakainya) `disable_functions = exec,passthru,shell_exec,system,proc_open,popen` di INI web. Proyek ini tidak memanggil fungsi tersebut. Composer/git berjalan di CLI dan tidak terpengaruh | situs tetap normal; ulangi uji upload gambar dan form Kontak |
| ☐ | **`.env`** diisi dari `.env.production.example`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://jbmelimpah.com`, `LOG_LEVEL=error`, DB, `MAIL_*`. `chmod 600 .env`. `key:generate` baru di server (jangan salin `APP_KEY` dari laptop) | `curl -sI https://jbmelimpah.com/.env` → 403/404 (bukan 200) |
| ☐ | **Database**: user MySQL khusus (bukan akun cPanel utama), password acak ≥ 20 karakter, hak hanya ke database ini, akses MySQL jarak jauh (Remote MySQL) **tidak** dibuka | cPanel → Remote MySQL kosong |
| ☐ | **Symlink** `public_html/jbmelimpah.com` → `~/jbm/public` (bukan `~/jbm`) | `curl -sI https://jbmelimpah.com/composer.json` dan `/artisan` → 403/404 |
| ☐ | **Izin**: folder `755`, berkas `644`, `.env` `600`; hanya `storage` dan `bootstrap/cache` yang writable (`ug+rwX`). Tidak ada `777` | `find ~/jbm -perm -0002 -not -type l` kosong |
| ☐ | **Folder upload aman**: `storage/app/public/.htaccess` ada (dari git) | taruh `x.php` di `storage/app/public/`, buka `https://…/storage/x.php` → 403; hapus filenya |
| ☐ | **Admin pertama**: `php artisan admin:create` (password tersembunyi, ≥ 12 karakter, lalu disimpan di password manager). Tidak ada password admin di `.env` | login `/admin` berhasil; akun tanpa tanda admin ditolak |
| ☐ | **Cron** `schedule:run` (DEPLOY A13) agar `leads:prune` jalan | `php artisan schedule:list`; `php artisan leads:prune --dry-run` |
| ☐ | **Email**: akun `noreply@jbmelimpah.com`, SPF + DKIM + DMARC (cPanel → Email Deliverability), lalu kirim satu pesan lewat form Kontak (DEPLOY A11) | "Show original" di Gmail: SPF, DKIM, DMARC = PASS; tombol Balas → email pengunjung |
| ☐ | Daftar penerima leads diisi di **Pengaturan Situs → Kontak** (banner peringatan hilang) | halaman Pesan Masuk tanpa banner kuning |

## 2. Urutan HTTPS (jangan dibalik)

1. Deploy pertama berjalan di `http://` dengan `FORCE_HTTPS=false`.
2. cPanel → **SSL/TLS Status → Run AutoSSL**; tunggu sertifikat aktif; buka `https://…` tanpa peringatan.
3. Baru setelah itu `.env`: `FORCE_HTTPS=true`, `HSTS_MAX_AGE=300` (cookie session ikut secure otomatis), lalu `php artisan config:cache`.
4. Setelah beberapa hari tanpa masalah naikkan `HSTS_MAX_AGE` bertahap: 300 → 86400 → 2592000 → 31536000. **Jangan** pakai `preload`. Tanpa `includeSubDomains` kecuali semua subdomain sudah https.
5. `CSP_MODE=report-only` (default): jelajahi situs dengan konsol browser (F12) terbuka; bila **tidak ada** pesan "Content Security Policy", ubah ke `CSP_MODE=enforce` dan `config:cache`. Bila ada pesan, catat URL/sumbernya dan laporkan sebelum enforce.

Uji: `curl -sI https://jbmelimpah.com/ | grep -iE "strict-transport|x-frame|x-content-type|referrer-policy|permissions-policy|content-security"`; `curl -sI http://jbmelimpah.com/` → 301 ke `https://`.

## 3. Backup (di luar server juga)

- [ ] Aktifkan backup hosting (JetBackup / Backup Wizard di cPanel bila paketnya ada) dan cek bahwa backup benar-benar terbentuk.
- [ ] Backup database + `storage/app/public` berkala dengan `scripts/backup.sh` (cron harian, DEPLOY bagian D) **dan salin ke luar server** (unduh manual atau `rclone`/`scp` ke penyimpanan lain). Backup yang hanya ada di server yang sama bukan backup.
- [ ] **Uji pemulihan** sekali sebelum go-live dan tiap beberapa bulan (DEPLOY D3). Backup yang belum diuji dianggap belum ada.
- [ ] Simpan `.env` di password manager (bukan di GitHub).
- [ ] Ingat: backup memuat data pribadi yang sudah dihapus `leads:prune`. Di server `scripts/backup.sh` menyimpan 14 hari (`KEEP_DAYS`); untuk salinan di luar server, tetapkan sendiri berapa lama disimpan (mis. 30–90 hari) dan hapus yang lebih tua.

## 4. Akun dan akses (2FA dan kebersihan)

- [ ] **2FA di cPanel / akun hosting**: aktifkan (cPanel → Security → Two-Factor Authentication; Clientzone di pengaturan akun). Akun hosting yang bocor = semua hal di atas tidak berarti.
- [ ] **2FA di GitHub** untuk akun pemilik repo. **Deploy key read-only** (jangan centang "Allow write access"); satu key per repo.
- [ ] Password cPanel, email, dan database semuanya berbeda, acak, dan tersimpan di password manager.
- [ ] Hanya satu-dua akun admin CMS yang benar-benar perlu. Hapus/nonaktifkan akun yang tidak dipakai (`is_admin` ditandai hanya lewat `admin:create` atau database).
- [ ] Tinjau `storage/logs/security-*.log` tiap minggu: lonjakan `login.failed`/`login.lockout`, `user.updated`, `upload.added` yang tidak Anda kenal.

### 2FA untuk panel admin (sudah terpasang)

Panel admin mendukung 2FA lewat aplikasi authenticator (Google Authenticator, Authy, dll.) lengkap dengan recovery code. Kuncinya disimpan terenkripsi di database.

- [ ] Di `.env` produksi: `ADMIN_REQUIRE_2FA=true` (sudah demikian di `.env.production.example`). Admin yang belum mengaktifkannya langsung diarahkan ke halaman pengaturan setelah login.
- [ ] Setelah `admin:create`, login, scan kode QR, **simpan recovery code di password manager**, lalu login ulang untuk membuktikan 2FA jalan.
- [ ] Bila HP admin hilang dan recovery code juga hilang: di Terminal cPanel jalankan `php artisan tinker`, lalu `User::where('email','...')->update(['app_authentication_secret'=>null,'app_authentication_recovery_codes'=>null]);` dan atur ulang.

## 5. Antivirus dan integritas file

- [ ] Bila paket hosting menyediakan **Imunify360 / ClamAV**: aktifkan pemindaian terjadwal untuk `~/jbm` (terutama `storage/app/public`) dan dapatkan notifikasinya.
- [ ] ClamAV **tidak** diimplementasikan di aplikasi (sesuai keputusan); perlindungan upload ada di kode (gambar di-encode ulang, hanya PNG/JPEG/WebP/ICO, nama acak, folder tanpa eksekusi skrip).
- [ ] Setelah setiap deploy, `git status` di server harus bersih: file asing/berubah = tanda ada yang mengutak-atik server.

## 6. Cloudflare (saat diaktifkan nanti)

1. Tambah situs di Cloudflare; salin semua record DNS yang ada (A, MX, TXT SPF/DKIM/DMARC, CNAME). Record email **tidak** diproksi (awan abu-abu).
2. Ganti nameserver di pendaftar domain ke milik Cloudflare; tunggu aktif.
3. SSL/TLS → **Full (strict)**; aktifkan "Always Use HTTPS" (boleh, karena `FORCE_HTTPS` juga aktif).
4. `.env`: `TRUSTED_PROXIES=cloudflare` → `config:cache`. **Wajib**, agar rate limit form Kontak dan kunci login membaca IP asli. Jadwalkan `php artisan security:cloudflare-ips` (sebulan sekali) untuk menyegarkan daftar IP.
5. Caching: **jangan cache HTML**; cache aset `/css/*`, `/js/*`, `/fonts/*` dan `/storage/*` saja. Menyimpan HTML membekukan token CSRF (form memberi 419).
6. Naikkan `HSTS_MAX_AGE` setelah stabil.
7. Opsional: Turnstile (widget di dash.cloudflare.com, isi `TURNSTILE_*`, `TURNSTILE_ENABLED=true`). Catatan: dengan Turnstile aktif form Kontak butuh JavaScript.
8. Bila memungkinkan, batasi origin ke IP Cloudflare (firewall hosting) agar header `X-Forwarded-For` tidak bisa dipalsukan langsung ke origin.
9. Uji setelah aktif: form Kontak (kirim 6 kali dari perangkat yang sama: yang ke-6 ditolak, dari perangkat lain tidak), login admin gagal 5 kali mengunci hanya penyerang, `curl -sI` masih menampilkan header keamanan.

## 7. Uji keamanan cepat setelah deploy (5 menit)

```bash
D=https://jbmelimpah.com
curl -sI $D/ | grep -iE "strict-transport|x-frame-options|x-content-type|referrer-policy|permissions-policy|content-security|x-powered-by"
for p in .env .git/config composer.json artisan storage/logs/laravel.log; do printf "%s -> " $p; curl -s -o /dev/null -w "%{http_code}\n" $D/$p; done   # semua 403/404
curl -sI $D/admin/login | grep -i x-robots-tag            # noindex
curl -s $D/robots.txt | head                               # Disallow: /livewire (dan /admin bila path default)
curl -s -o /dev/null -w "%{http_code}\n" $D/halaman-tidak-ada   # 404 dengan halaman merek, tanpa jejak stack
```

Lalu manual: login admin salah 5 kali (terkunci, pesan generik), kirim form Kontak (masuk ke Pesan Masuk + email), unggah satu gambar (muncul, `upload.added` di log keamanan).

## 8. Yang sengaja belum / tidak dikerjakan

- CSP halaman publik: skrip wajib bernonce dan tanpa `'unsafe-eval'`; `style-src` masih `'unsafe-inline'` (atribut style kecil di template). Risiko rendah karena skrip tetap diblokir tanpa nonce.
- CSP baru mode **report-only** sampai Anda memeriksa konsol lalu menyalakan `enforce`.
- 2FA panel admin, ClamAV, Turnstile aktif: lihat di atas.
- `laravel/sail` dan `laravel/pail` masih ada di dev dependency (tidak ada di server dengan `--no-dev`); boleh dihapus bila tidak dipakai.
