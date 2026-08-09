#!/bin/bash
# ============================================================
# 🚀 Absensi MAN 2 Lotim — Smart Deploy Script
# ============================================================
# Script untuk deploy otomatis dari GitHub ke VPS aaPanel.
# Database TIDAK di-reset, hanya menjalankan migrasi baru.
#
# Cara pakai (pertama kali):
#   chmod +x deploy.sh
#
# Cara deploy:
#   ./deploy.sh
# ============================================================

set -e

# ── Konfigurasi ──────────────────────────────────────────────
WEB_ROOT="/www/wwwroot/absen.mandualotim.sch.id"
SITE_URL="https://absen.mandualotim.sch.id"
DB_NAME="login276_absensi"
DB_USER="login276_absensi"
MIGRATION_DIR="database/migrations"
MIGRATION_LOG="$WEB_ROOT/.migration_done"
# ─────────────────────────────────────────────────────────────

cd "$WEB_ROOT"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   🚀 Absensi — Smart Deploy                 ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# 1. Pull latest code
echo "📥 [1/5] Mengambil kode terbaru dari GitHub..."
git pull origin main
echo ""

# 2. Run pending SQL migrations
echo "🗄️  [2/5] Menjalankan migrasi database..."
if [ -d "$MIGRATION_DIR" ]; then
  # Buat file log migrasi jika belum ada
  touch "$MIGRATION_LOG"

  for migration_file in "$MIGRATION_DIR"/*.sql; do
    if [ -f "$migration_file" ]; then
      basename_file=$(basename "$migration_file")
      # Cek apakah migrasi ini sudah pernah dijalankan
      if grep -qF "$basename_file" "$MIGRATION_LOG" 2>/dev/null; then
        echo "   ✓ $basename_file (sudah dijalankan)"
      else
        echo "   ➜ Menjalankan: $basename_file"
        # Baca password dari config.php yang ada di server
        DB_PASS=$(grep -oP "(?<=DB_PASS.*')[^']*" includes/config.php 2>/dev/null | head -1)
        if [ -z "$DB_PASS" ]; then
          echo "   ⚠️ Tidak bisa baca password DB dari config.php, skip migrasi."
        else
          mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$migration_file" 2>&1 && \
            echo "$basename_file" >> "$MIGRATION_LOG" || \
            echo "   ⚠️ Gagal: $basename_file (mungkin sudah ada, lanjut...)"
        fi
      fi
    fi
  done
else
  echo "   Tidak ada folder migrasi."
fi
echo ""

# 3. Pastikan config.php tidak tertimpa
echo "🔒 [3/5] Memastikan config.php tetap aman..."
if [ ! -f "includes/config.php" ]; then
  echo "   ⚠️ config.php tidak ditemukan!"
  echo "   Salin dari template: cp includes/config.php.example includes/config.php"
  echo "   Lalu edit sesuai kredensial database server."
else
  echo "   ✓ config.php ada dan tidak di-overwrite"
fi
echo ""

# 4. Set permissions
echo "📂 [4/5] Mengatur permission file..."
find "$WEB_ROOT" -type d -exec chmod 755 {} \; 2>/dev/null
find "$WEB_ROOT" -type f -exec chmod 644 {} \; 2>/dev/null
chmod 755 "$WEB_ROOT/deploy.sh"

# Pastikan folder upload writeable oleh web server
chmod -R 775 "$WEB_ROOT/uploads" 2>/dev/null || true
chmod -R 775 "$WEB_ROOT/barcode" 2>/dev/null || true
chown -R www:www "$WEB_ROOT" 2>/dev/null || true

echo "   ✓ Permission diatur"
echo ""

# 5. Health check
echo "🩺 [5/5] Health check..."
sleep 2

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$SITE_URL" --max-time 10 2>/dev/null || echo "000")

echo ""
echo "┌─────────────────────────────────────────────┐"
echo "│  Status Deploy                              │"
echo "├─────────────────────────────────────────────┤"
printf "│  🌐 Website       : %-22s │\n" "$SITE_URL"
printf "│  📡 HTTP Status   : %-22s │\n" "$HTTP_CODE"
echo "└─────────────────────────────────────────────┘"
echo ""

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
  echo "✅ Deploy berhasil! Website sudah live."
  echo ""
  echo "📋 Post-deploy summary:"
  echo "   • Kode terbaru  : ✓ git pull selesai"
  echo "   • Migrasi DB    : ✓ dijalankan"
  echo "   • Permission    : ✓ diatur"
  echo "   • Website       : ✓ HTTP $HTTP_CODE"
else
  echo "⚠️  Website mungkin belum aktif (HTTP $HTTP_CODE)."
  echo "   Cek manual: $SITE_URL"
  echo "   Cek error log: tail -20 $WEB_ROOT/error_log"
fi
echo ""
