# GO_LIVE_CHECKLIST.md: deploy pertama, urutan WAJIB

Lima langkah di bawah **harus dikerjakan berurutan, satu per satu, dan tidak boleh dibalik atau digabung**.
Kalau urutannya salah: situs tidak bisa dibuka, atau login dan form gagal terus (error 419).
Jangan lanjut ke langkah berikutnya sebelum kotak **"Lolos bila"** pada langkah ini terpenuhi.

Penjelasan lengkap tiap perintah ada di `DEPLOY.md`; daftar manual lain di `SECURITY_CHECKLIST_DEPLOY.md`.
Semua perintah dijalankan di **Terminal cPanel**, di `~/jbm`, dengan:

```bash
cd ~/jbm
PHP=/opt/cpanel/ea-php83/root/usr/bin/php
```

---

## LANGKAH 1. Deploy awal TANPA HTTPS paksa dan TANPA cookie secure

Prasyarat (sudah dikerjakan sesuai `DEPLOY.md` A1 sampai A5): PHP 8.3, SSH key + deploy key, `git clone` ke `~/jbm`, database dan user MySQL dibuat.

1. Buat `.env` dari template produksi:

   ```bash
   cp .env.production.example .env
   chmod 600 .env
   nano .env
   ```

2. Di `.env`, pastikan **persis** begini untuk tahap ini (jangan diubah dulu):

   ```
   APP_URL=http://jbmelimpah.com
   FORCE_HTTPS=false
   HSTS_MAX_AGE=300
   ```

   - **`APP_URL` sementara `http://`** (bukan `https://`). Alamat semua gambar unggahan dibangun dari `APP_URL`; kalau sudah `https://` padahal SSL belum ada, gambar tidak tampil.
   - **Jangan** menambahkan baris `SESSION_SECURE_COOKIE` (biarkan tetap tertulis `# SESSION_SECURE_COOKIE=`). Cookie ikut aman otomatis saat `FORCE_HTTPS=true` nanti. Cookie secure lewat `http://` tidak pernah tersimpan, sehingga login dan form gagal (419).
   - Isi juga `DB_*` dan `MAIL_*` (lihat `DEPLOY.md` A6, A11).

3. Jalankan **satu per satu**, baca outputnya, berhenti bila ada error:

   ```bash
   $PHP artisan key:generate
   $PHP ~/composer.phar install --no-dev --optimize-autoloader --no-interaction
   $PHP artisan migrate --force
   $PHP artisan db:seed --force
   ln -s "$HOME/jbm/storage/app/public" public/storage     # BUKAN artisan storage:link (di sebagian hosting symlink()/exec() PHP dinonaktifkan)
   $PHP artisan filament:assets
   $PHP artisan admin:create
   ```

   (`admin:create` meminta email, nama, lalu password dengan input tersembunyi: minimal 12 karakter, huruf besar dan kecil, angka, simbol. Simpan di password manager.)

4. Izin dan symlink (`DEPLOY.md` A8 dan A9):

   ```bash
   mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
   chmod -R ug+rwX storage bootstrap/cache
   chmod 755 ~ ~/jbm ~/jbm/public
   rm -rf ~/public_html/jbmelimpah.com
   ln -s ~/jbm/public ~/public_html/jbmelimpah.com
   ls -ld ~/public_html/jbmelimpah.com     # harus: -> /home/CPANELUSER/jbm/public
   ```

5. Bangun cache:

   ```bash
   $PHP artisan optimize:clear
   $PHP artisan config:cache
   $PHP artisan route:cache
   $PHP artisan view:cache
   ```

**Lolos bila** (semua lewat `http://`, belum https):
- [ ] `http://jbmelimpah.com` terbuka, gambar tampil.
- [ ] `http://jbmelimpah.com/admin` terbuka, login dengan akun dari `admin:create` **berhasil dan tetap login** setelah pindah halaman.
- [ ] Mengirim satu pesan lewat form Kontak berhasil (muncul di admin → Pesan Masuk).
- [ ] `http://jbmelimpah.com/up` memberi 200.

**BERHENTI bila** login terpental terus atau form memberi 419: periksa `.env` tidak berisi `SESSION_SECURE_COOKIE=true` dan `FORCE_HTTPS=false`, lalu `$PHP artisan config:cache`. **Jangan lanjut ke langkah 2.**

---

## LANGKAH 2. Pasang SSL (AutoSSL) dan buktikan https jalan

1. cPanel → **SSL/TLS Status** → centang `jbmelimpah.com` dan `www.jbmelimpah.com` → **Run AutoSSL**.
2. Tunggu beberapa menit sampai status menunjukkan sertifikat terpasang.
3. **Jangan ubah apa pun di `.env` dulu.** Cukup uji dari browser dan terminal.

**Lolos bila** (semua harus benar):
- [ ] `https://jbmelimpah.com` terbuka **tanpa peringatan sertifikat** (gembok normal).
- [ ] `https://www.jbmelimpah.com` juga tanpa peringatan.
- [ ] `curl -sI https://jbmelimpah.com/ | head -1` menampilkan `HTTP/2 200` (atau `HTTP/1.1 200`).
- [ ] `http://jbmelimpah.com` masih terbuka (belum dialihkan; itu wajar, ini belum dinyalakan).

**BERHENTI bila** https belum jalan atau ada peringatan sertifikat. Ulangi AutoSSL, tunggu, atau hubungi penyedia hosting. **Jangan lanjut ke langkah 3 sebelum semua kotak di atas terpenuhi.**

---

## LANGKAH 3. BARU SEKARANG nyalakan FORCE_HTTPS dan cookie secure

Hanya setelah langkah 2 lolos penuh.

1. Edit `.env`:

   ```bash
   nano .env
   ```

   Ubah tepat tiga baris ini:

   ```
   APP_URL=https://jbmelimpah.com
   FORCE_HTTPS=true
   HSTS_MAX_AGE=300
   ```

   Cookie session otomatis menjadi secure karena mengikuti `FORCE_HTTPS` (tidak perlu menulis `SESSION_SECURE_COOKIE`).

2. Terapkan. **`cache:clear` wajib**: daftar konten beranda (termasuk alamat semua gambar) tersimpan di cache database dan dibangun saat `APP_URL` masih `http://`; tanpa dikosongkan, gambar tetap berupa alamat `http://` dan rusak di halaman https (mixed content):

   ```bash
   $PHP artisan config:clear
   $PHP artisan cache:clear
   $PHP artisan config:cache
   ```

3. Uji:

   ```bash
   curl -sI http://jbmelimpah.com/ | head -3        # harus 301 dan Location: https://jbmelimpah.com/
   curl -sI https://jbmelimpah.com/ | grep -i strict-transport   # harus: max-age=300
   curl -s https://jbmelimpah.com/ | grep -o 'http://[^" ]*storage[^" ]*' | head -3   # harus KOSONG (tidak ada gambar beralamat http://)
   ```

**Lolos bila:**
- [ ] `http://…` otomatis berpindah ke `https://…` (301).
- [ ] Header `Strict-Transport-Security: max-age=300` muncul di https.
- [ ] Login admin **berhasil dan tetap login**; kirim satu pesan lewat form Kontak berhasil.
- [ ] Gambar unggahan tampil dan alamatnya `https://…`.

**Jika situs mati, redirect berulang, atau login/form gagal terus (419):** kembalikan SEGERA, lalu cari sebabnya:

```bash
nano .env        # FORCE_HTTPS=false  dan  APP_URL=http://jbmelimpah.com
$PHP artisan cache:clear
$PHP artisan config:cache
```

(HSTS 300 detik hanya diingat browser 5 menit, jadi aman untuk dibatalkan.) Jangan menaikkan `HSTS_MAX_AGE` sekarang; naikkan bertahap beberapa hari kemudian (`DEPLOY.md` A10 langkah 4).

---

## LANGKAH 4. Pasang cron `schedule:run` di cPanel

Tanpa ini tugas harian `leads:prune` (penghapusan IP/user-agent setelah 60 hari dan pesan setelah 12 bulan) **tidak pernah jalan**.

1. cPanel → **Cron Jobs** → **Add New Cron Job**.
2. **Common Settings**: pilih **Once Per Minute (\* \* \* \* \*)**.
3. **Command**: tempel **persis** satu baris ini (satu baris, tanpa spasi tambahan di awal):

   ```
   cd /home/CPANELUSER/jbm && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

   (Atau isi manual: Minute/Hour/Day/Month/Weekday semuanya `*`, lalu Command seperti di atas.)
4. Klik **Add New Cron Job**.

**Lolos bila:**
- [ ] `$PHP artisan schedule:list` menampilkan baris `leads:prune` (jam 03:15).
- [ ] `$PHP artisan leads:prune --dry-run` mencetak `[dry-run] Dihapus: 0, IP/user-agent dihapus: 0.` (angka boleh lain; ini tidak mengubah data).
- [ ] Setelah beberapa menit, daftar cron di cPanel masih ada dan tidak ada email error dari cron.

---

## LANGKAH 5. Percobaan pertama `deploy.sh`: langkah per langkah, BUKAN langsung dijalankan penuh

`deploy.sh` belum pernah dijalankan sungguhan. **Dilarang** mengetik `bash deploy.sh` pada percobaan pertama.
Jalankan isi `deploy.sh` **satu perintah per kali**, baca outputnya, lanjut hanya bila sukses (urutan persis seperti di script):

```bash
cd ~/jbm
PHP=/opt/cpanel/ea-php83/root/usr/bin/php

git pull --ff-only origin main                                              # 1. ambil kode (jika "Already up to date", itu benar)
$PHP ~/composer.phar install --no-dev --optimize-autoloader --no-interaction --prefer-dist   # 2. dependency
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache                                     # 3. folder writable
ls -l storage/app/public/.htaccess                                          # 4. HARUS ada; kalau tidak: git checkout -- storage/app/public/.htaccess
$PHP artisan migrate --force                                                # 5. migrasi
[ -e public/storage ] || ln -s "$HOME/jbm/storage/app/public" public/storage   # 6. symlink upload (dilewati bila sudah ada)
$PHP artisan filament:assets                                                # 7. aset admin
$PHP artisan optimize:clear                                                 # 8. bersihkan cache
$PHP artisan config:cache                                                   # 9. cache config
$PHP artisan route:cache                                                    # 10. cache route
$PHP artisan view:cache                                                     # 11. cache view
```

Setelah ke-11 perintah sukses satu per satu, **barulah** satu kali jalankan script utuh sambil mengawasi outputnya, untuk membuktikan script sama hasilnya:

```bash
bash deploy.sh
```

**Lolos bila:**
- [ ] Tiap perintah di atas selesai tanpa error; `bash deploy.sh` selesai dengan tulisan `Selesai.`
- [ ] Situs, login admin, dan form Kontak masih berfungsi lewat `https://` setelahnya.
- [ ] `git status` di server bersih (tidak ada perubahan file).

**Jika sebuah perintah gagal:** berhenti, jangan ulangi membabi buta. Perbaiki penyebabnya (lihat `DEPLOY.md` bagian C), perbaiki `deploy.sh` bila script-nya yang salah, commit di laptop, push, lalu ulangi percobaan dari awal. Hanya setelah percobaan ini lolos, `bash deploy.sh` boleh dipakai untuk update rutin.

---

## Ringkasan urutan (jangan dibalik)

| # | Langkah | Status `.env` setelahnya |
|---|---|---|
| 1 | Deploy awal lewat `http://` | `APP_URL=http://…`, `FORCE_HTTPS=false` |
| 2 | AutoSSL, buktikan `https://` jalan | tidak berubah |
| 3 | Nyalakan HTTPS paksa dan cookie secure | `APP_URL=https://…`, `FORCE_HTTPS=true` |
| 4 | Cron `schedule:run` | tidak berubah |
| 5 | Uji `deploy.sh` per langkah, lalu utuh | tidak berubah |

Setelah kelima langkah lolos: ikuti `SECURITY_CHECKLIST_DEPLOY.md` (backup, 2FA hosting, SPF/DKIM, `CSP_MODE=enforce` setelah konsol bersih, Cloudflare nanti).
