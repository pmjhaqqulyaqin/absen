#!/bin/bash
set -e

echo "==========================================="
echo "🚀 Deployment Absensi Digital"
echo "==========================================="

# ---- ENV ----
if [ ! -f .env ]; then
    echo "⚠️  File .env tidak ditemukan. Membuat dari .env.example..."
    cp .env.example .env
    RANDOM_ROOT=$(cat /dev/urandom 2>/dev/null | tr -dc 'a-zA-Z0-9' 2>/dev/null | fold -w 24 2>/dev/null | head -n 1 || echo "absen_root_$(date +%s)")
    RANDOM_PASS=$(cat /dev/urandom 2>/dev/null | tr -dc 'a-zA-Z0-9' 2>/dev/null | fold -w 20 2>/dev/null | head -n 1 || echo "absen_db_$(date +%s)")
    sed -i "s/MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=${RANDOM_ROOT}/g" .env
    sed -i "s/MYSQL_PASSWORD=.*/MYSQL_PASSWORD=${RANDOM_PASS}/g" .env
    echo "✅ .env dibuat."
fi

# ---- GIT PULL ----
echo "📥 1. Menarik kode terbaru..."
git fetch origin
git reset --hard origin/main
echo "✅ Kode diperbarui."

# ---- CEK APAKAH PERLU REBUILD ----
# Rebuild hanya jika Dockerfile, nginx.conf, atau supervisord.conf berubah
NEEDS_REBUILD=false
CHANGED=$(git diff HEAD~1 --name-only 2>/dev/null || echo "")

if echo "$CHANGED" | grep -qE "^(Dockerfile|nginx.conf|supervisord.conf|docker-compose.yml)$"; then
    NEEDS_REBUILD=true
fi

# Jika container belum ada, perlu build
if ! docker compose ps --status running 2>/dev/null | grep -q "absen-app"; then
    NEEDS_REBUILD=true
fi

if [ "$NEEDS_REBUILD" = true ]; then
    echo "🛠️  2. Perubahan infrastruktur terdeteksi → Rebuild Docker..."
    docker compose build --no-cache absen-app
    docker compose up -d --force-recreate
    echo "⏳ Menunggu database..."
    sleep 8
else
    echo "⚡ 2. Hanya perubahan kode → Restart cepat (tanpa rebuild)..."
    # Karena bind mount, kode sudah ter-update otomatis dari git pull
    # Restart PHP-FPM agar cache PHP ter-clear
    docker exec absen-app sh -c "kill -USR2 1" 2>/dev/null || docker compose restart absen-app
fi

# ---- PERMISSION ----
echo "📂 3. Memastikan permission..."
docker exec absen-app sh -c "chown -R nobody:nobody /var/www/html/uploads 2>/dev/null; chmod -R 775 /var/www/html/uploads 2>/dev/null" || true

# ---- CLEANUP ----
echo "🧹 4. Cleanup..."
docker image prune -f 2>/dev/null || true

echo "==========================================="
echo "✅ Deploy selesai!"
echo "   🌐 https://absen.mandualotim.sch.id"
echo "==========================================="
