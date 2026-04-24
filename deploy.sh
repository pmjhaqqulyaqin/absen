#!/bin/bash

# Pastikan script berhenti jika ada error
set -e

echo "==========================================="
echo "🚀 Memulai Deployment Absensi Digital..."
echo "==========================================="

echo "🔐 0. Memeriksa konfigurasi environment (.env)..."
if [ ! -f .env ]; then
    echo "   ⚠️ File .env tidak ditemukan. Membuat dari .env.example..."
    cp .env.example .env

    # Generate random password untuk keamanan
    RANDOM_ROOT=$(cat /dev/urandom 2>/dev/null | tr -dc 'a-zA-Z0-9' 2>/dev/null | fold -w 24 2>/dev/null | head -n 1 || echo "absen_root_$(date +%s)")
    RANDOM_PASS=$(cat /dev/urandom 2>/dev/null | tr -dc 'a-zA-Z0-9' 2>/dev/null | fold -w 20 2>/dev/null | head -n 1 || echo "absen_db_$(date +%s)")

    sed -i "s/MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=${RANDOM_ROOT}/g" .env
    sed -i "s/MYSQL_PASSWORD=.*/MYSQL_PASSWORD=${RANDOM_PASS}/g" .env

    echo "   ✅ File .env dibuat dengan password unik."
else
    echo "   ✅ File .env sudah ada."
fi

echo "📥 1. Menarik pembaruan kode terbaru dari GitHub..."
git fetch origin
git reset --hard origin/main

echo "🛠️  2. Membangun ulang Docker Images..."

# Build PHP + Nginx container
echo "   📦 Building Absensi App (PHP + Nginx)..."
docker compose build --no-cache absen-app

echo "🔄 3. Menghentikan container lama dan menjalankan yang baru..."
docker compose up -d --force-recreate

echo "⏳ 4. Menunggu database siap..."
sleep 10

echo "📂 5. Memastikan permission folder uploads..."
docker exec absen-app sh -c "chown -R nobody:nobody /var/www/html/uploads && chmod -R 775 /var/www/html/uploads" 2>/dev/null || true

echo "🧹 6. Membersihkan image yang tidak terpakai..."
docker image prune -f

echo "==========================================="
echo "✅ Deployment Absensi Digital selesai!"
echo "   🌐 Aplikasi: http://localhost:18082"
echo "   🗄️  Database: localhost:18083"
echo "==========================================="
