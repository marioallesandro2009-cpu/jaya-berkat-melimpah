# Aktifkan PHP 8.3 untuk sesi PowerShell ini:  . .\use-php83.ps1
# (XAMPP PHP 8.2 ada di PATH Machine; skrip ini menaruh PHP 8.3 di depan PATH
#  hanya untuk sesi terminal saat ini, tanpa mengubah pengaturan sistem.)
$php83 = if ($env:PHP83_DIR) { $env:PHP83_DIR } else { "$env:USERPROFILE\php83" }
$env:Path = "$php83;" + (($env:Path -split ';' | Where-Object { $_ -and $_ -notlike '*xampp\php*' }) -join ';')
# Folder ini global XAMPP tidak dipakai (ekstensinya untuk PHP 8.2). GD dibutuhkan untuk
# konversi WebP dan favicon.ico: jika php.ini PHP 8.3 tidak memuatnya, pakai
# scripts\php-ini\jbm.ini dari proyek ini (juga berlaku untuk php artisan serve).
$env:PHP_INI_SCAN_DIR = ''
if (-not ((php -m) -match '^gd$')) {
    $env:PHP_INI_SCAN_DIR = Join-Path $PSScriptRoot 'scripts\php-ini'
}
php -v
if ((php -m) -match '^gd$') { 'GD: aktif' } else { 'GD: TIDAK aktif (konversi WebP mati)' }
