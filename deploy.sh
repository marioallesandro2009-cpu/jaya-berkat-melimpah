#!/usr/bin/env bash
#
# Deploy update di server (cPanel shared hosting). Jalankan dari Terminal cPanel:
#
#     cd ~/jbm && bash deploy.sh
#
# Aman dijalankan berulang; berhenti di error pertama (set -e).
# Override lewat environment bila path di server berbeda, contoh:
#
#     PHP_BIN=/usr/local/bin/php bash deploy.sh
#     COMPOSER_BIN="/opt/cpanel/ea-php83/root/usr/bin/php $HOME/composer.phar" bash deploy.sh
#     BRANCH=main bash deploy.sh
#
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

BRANCH="${BRANCH:-main}"

# --- PHP: default ke PHP 8.3 cPanel (EasyApache), lalu 'php' di PATH -------------
if [ -z "${PHP_BIN:-}" ]; then
    for candidate in /opt/cpanel/ea-php83/root/usr/bin/php /usr/local/bin/ea-php83 php; do
        if command -v "$candidate" >/dev/null 2>&1; then
            PHP_BIN="$candidate"
            break
        fi
    done
fi

if [ -z "${PHP_BIN:-}" ]; then
    echo "!! PHP tidak ditemukan. Set PHP_BIN=/path/ke/php" >&2
    exit 1
fi

# Wajib PHP >= 8.3 (composer.json: ^8.3).
if ! "$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'; then
    echo "!! $PHP_BIN versi $("$PHP_BIN" -r 'echo PHP_VERSION;'), butuh 8.3+. Set PHP_BIN ke php 8.3." >&2
    exit 1
fi

# --- Composer: 'composer' di PATH, atau composer.phar di home --------------------
if [ -z "${COMPOSER_BIN:-}" ]; then
    if [ -f "$HOME/composer.phar" ]; then
        COMPOSER_BIN="$PHP_BIN $HOME/composer.phar"
    elif command -v composer >/dev/null 2>&1; then
        # Jalankan lewat PHP_BIN supaya "@php artisan" di script composer memakai versi yang sama.
        COMPOSER_BIN="$PHP_BIN $(command -v composer)"
    else
        echo "!! Composer tidak ditemukan. Lihat DEPLOY.md (install composer.phar di home)." >&2
        exit 1
    fi
fi


ARTISAN="$PHP_BIN artisan"

step() { printf '\n==> %s\n' "$1"; }

if [ ! -f .env ]; then
    echo "!! File .env belum ada. Ikuti setup awal di DEPLOY.md dulu." >&2
    exit 1
fi

step "Ambil kode terbaru (origin/$BRANCH)"
git pull --ff-only origin "$BRANCH"

step "Install dependency PHP (tanpa dev)"
# shellcheck disable=SC2086
# --no-scripts: some hosts disable proc_open (Rumahweb does) and Composer's own post-install scripts then fail; the
# steps those scripts would run (package:discover, filament:upgrade) are done below with artisan directly.
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

step "Siapkan folder writable"
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Uploads live here and are served at /storage: this file stops any script in them from
# running (SECURITY_AUDIT F-1). It is tracked in git; refuse to go on without it.
if [ ! -f storage/app/public/.htaccess ]; then
    echo "!! storage/app/public/.htaccess hilang. Jalankan: git checkout -- storage/app/public/.htaccess" >&2
    exit 1
fi

step "Langkah pasca-install Composer (package:discover, filament:upgrade)"
$ARTISAN package:discover --ansi
$ARTISAN filament:upgrade

step "Migrasi database"
$ARTISAN migrate --force

step "Symlink public/storage"
# Plain shell "ln -s", not "artisan storage:link": some hosts (shared hosting) disable PHP's symlink()
# and exec(), and storage:link then fails with "Call to undefined function exec()".
if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    ln -s "$(pwd)/storage/app/public" public/storage
else
    echo "public/storage sudah ada, dilewati."
fi

step "Publish asset Filament (admin)"
$ARTISAN filament:assets

step "Bersihkan dan bangun ulang cache"
$ARTISAN optimize:clear
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache

echo
echo "Selesai."
