# Aktifkan PHP 8.3 untuk sesi Git Bash ini:  source ./use-php83.sh
PHP83_DIR="${PHP83_DIR:-$HOME/php83}"
export PATH="$PHP83_DIR:$(echo "$PATH" | tr ':' '\n' | grep -vi 'xampp/php' | paste -sd:)"
# Folder ini global XAMPP tidak dipakai (ekstensinya untuk PHP 8.2). GD dibutuhkan untuk
# konversi WebP dan favicon.ico: jika php.ini PHP 8.3 tidak memuatnya, pakai
# scripts/php-ini/jbm.ini dari proyek ini (juga berlaku untuk php artisan serve).
export PHP_INI_SCAN_DIR=
if ! php -m 2>/dev/null | grep -qix gd; then
    export PHP_INI_SCAN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd -W 2>/dev/null || pwd)/scripts/php-ini"
fi
php -v
php -m | grep -qix gd && echo "GD: aktif" || echo "GD: TIDAK aktif (konversi WebP mati)"
