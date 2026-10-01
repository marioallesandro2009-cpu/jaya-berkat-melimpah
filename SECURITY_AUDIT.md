# SECURITY_AUDIT.md: PT Jaya Berkat Melimpah

Tinjauan keamanan kode situs ini (Laravel 13 + Filament 5). Daftar manual di luar kode (hosting, backup,
Cloudflare, uji pasca-deploy) ada di `SECURITY_CHECKLIST_DEPLOY.md`; urutan deploy di `GO_LIVE_CHECKLIST.md`.

Status: **Ada** = sudah di kode dan diuji; **Konfigurasi** = tersedia, dinyalakan lewat `.env` saat deploy;
**Manual** = dikerjakan di server/hosting.

## Permukaan serangan

Situs publik hanya punya satu jalur masukan: **form penawaran** (`POST /contact`, `/id/kontak`). Semua isi lain
dibuat admin yang login. Tidak ada registrasi publik, API, atau unggah dari pengunjung.

## Kontrol dan status

| # | Risiko | Kontrol | Status | Tes |
| --- | --- | --- | --- | --- |
| 1 | Spam dan bot di form | honeypot tersembunyi, batas 5 kiriman / 15 menit / IP, Turnstile opsional | Ada + Konfigurasi | `SecurityTest` (limit, honeypot) |
| 2 | Injeksi (SQL) | Eloquent/parameter terikat; tidak ada SQL mentah dari input | Ada | `SiteTest` |
| 3 | XSS dari isi admin atau pengunjung | Blade meng-escape semua teks; teks bebas dirender `e()` per paragraf; rich text (berita, produk) lewat sanitizer Filament; CSS warna dibangun hanya dari hex tervalidasi | Ada | `SecurityTest` (script dibuang/di-escape, termasuk di email) |
| 4 | Tautan berbahaya (`javascript:`, `data:`, `//host`) dari admin | validasi form + `Links::safe()` saat render untuk menu, tombol blok, peta, partner | Ada | `SecurityTest` |
| 5 | Header injection / email | nama dan field di-bersihkan dari karakter kontrol; Reply-To = pengunjung, From dari server; Markdown email dengan secured encoding | Ada | `SecurityTest` |
| 6 | CSRF | token pada form (fetch memakai FormData berisi `_token`), cookie sesi `SameSite=Lax` | Ada | `SiteTest` |
| 7 | Clickjacking, sniffing, referrer | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, COOP | Ada | `SecurityTest` |
| 8 | Skrip sisipan | CSP halaman publik: skrip wajib bernonce, **tanpa `unsafe-eval`**, `object-src 'none'`, `frame-ancestors 'self'`. Mode `report-only` dulu, lalu `enforce` | Ada + Konfigurasi | `SecurityTest`; Lighthouse: 0 error konsol saat `enforce` |
| 9 | Downgrade HTTPS | `FORCE_HTTPS`, HSTS bertahap, host kanonis (`EnforceCanonicalHost`) | Konfigurasi | `SecurityTest` (HSTS) |
| 10 | Brute force login admin | kunci per email+IP, per email, per IP; pesan generik; log keamanan | Ada | `HardeningTest` |
| 12 | Akses admin oleh non-admin | `is_admin` (tidak bisa di-mass-assign), `canAccessPanel`, admin `noindex` | Ada | `SiteTest`, `SecurityTest` |
| 13 | Unggahan berbahaya | gambar di-encode ulang (GD), nama acak, ekstensi dari mime terdeteksi, SVG ditolak, batas 4000 px; `storage/app/public/.htaccess` mematikan eksekusi skrip | Ada | `HardeningTest` |
| 14 | Berkas sensitif terbuka | `public/.htaccess` menolak dotfile, `composer.*`, `.log`, `.sql`, `.md`, dst.; document root ke `public/` | Ada + Manual | `SECURITY_CHECKLIST_DEPLOY.md` bagian 7 |
| 15 | Data pribadi terlalu lama disimpan | `leads:prune` harian: IP/user-agent dihapus setelah 60 hari, pesan setelah 12 bulan | Ada + Manual (cron) | `HardeningTest` |
| 16 | Dependensi rentan | `composer audit` (CI dan manual): **tidak ada advisori** per tinjauan ini; Dependabot aktif | Ada | CI |
| 17 | Kebocoran detail error | `APP_DEBUG=false` di produksi; halaman error bermerek tanpa jejak stack | Konfigurasi | `SecurityTest` (404) |
| 18 | Kehilangan data | `scripts/backup.sh` (DB + unggahan) + backup hosting | Manual | `DEPLOY.md` bagian D |

## Keputusan dan risiko yang diterima

- `style-src` masih `'unsafe-inline'` (beberapa atribut `style` kecil dan satu tag `<style>` untuk warna admin).
  Skrip tetap diblokir tanpa nonce, jadi risikonya rendah. Menghapusnya butuh memindahkan semua gaya inline ke CSS.
- Email notifikasi dikirim setelah respons; kegagalan SMTP tidak terlihat pengunjung, hanya menandai pesan
  (`email_failed`) agar tim melihatnya di admin. **Cek Pesan Masuk secara berkala** selama email belum terbukti jalan.
- Tidak ada WAF/antivirus di level aplikasi (Cloudflare dan ClamAV/Imunify ada di checklist manual).

## Pemeriksaan yang sudah dijalankan

- `vendor/bin/pest`: 61 tes (publik EN/ID, semua halaman admin, edit via admin, keamanan, form dan email),
  juga dijalankan penuh terhadap **MariaDB 10.4** (hasil sama: 100% lolos).
- `vendor/bin/phpstan analyse`: 0 error. `vendor/bin/pint --test`: bersih. `composer audit`: bersih.
- Lighthouse (mobile, server lokal, CSP `enforce`): Accessibility 100, Best Practices 100, SEO 100,
  Performance 93-97 (desktop 100); 0 error konsol.
