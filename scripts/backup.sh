#!/usr/bin/env bash
#
# Backup database + upload CMS ke ~/backups (lihat DEPLOY.md bagian D).
# Jalankan dari root proyek: bash scripts/backup.sh
# Kredensial DB dibaca dari .env; backup lebih tua dari KEEP_DAYS hari dihapus.
#
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

KEEP_DAYS="${KEEP_DAYS:-14}"
DEST="${BACKUP_DIR:-$HOME/backups}"
STAMP="$(date +%Y%m%d-%H%M)"

env_value() {
    # Baca satu nilai dari .env (buang tanda kutip di ujung).
    grep -E "^$1=" .env | head -n 1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'
}

DB_HOST="$(env_value DB_HOST)"
DB_PORT="$(env_value DB_PORT)"
DB_NAME="$(env_value DB_DATABASE)"
DB_USER="$(env_value DB_USERNAME)"
DB_PASS="$(env_value DB_PASSWORD)"

mkdir -p "$DEST"
chmod 700 "$DEST"

# Password lewat file opsi sementara, supaya tidak tampil di daftar proses.
OPTS="$(mktemp)"
trap 'rm -f "$OPTS"' EXIT
chmod 600 "$OPTS"
printf '[client]\nuser=%s\npassword="%s"\nhost=%s\nport=%s\n' "$DB_USER" "$DB_PASS" "${DB_HOST:-localhost}" "${DB_PORT:-3306}" > "$OPTS"

mysqldump --defaults-extra-file="$OPTS" --single-transaction --quick --no-tablespaces "$DB_NAME" | gzip > "$DEST/db-$STAMP.sql.gz"
tar -czf "$DEST/storage-$STAMP.tar.gz" -C storage/app public

find "$DEST" -type f \( -name 'db-*.sql.gz' -o -name 'storage-*.tar.gz' \) -mtime +"$KEEP_DAYS" -delete

echo "Backup selesai: $DEST/db-$STAMP.sql.gz, $DEST/storage-$STAMP.tar.gz"
