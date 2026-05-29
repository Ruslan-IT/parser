#!/bin/bash
set -e

echo "🚀 Деплой начался..."

# --- Git ---
echo "📦 Обновление кода..."
git pull origin master

# --- PHP (Laravel) ---
echo "⚙️ Установка зависимостей..."
composer install --no-dev --optimize-autoloader

# --- Очистка старого кеша ---
echo "🧹 Очистка кеша..."
php8.3 artisan optimize:clear

# --- Production кеш ---
echo "⚡ Кеширование..."
php8.3 artisan cache:clear
php8.3 artisan config:clear
php8.3 artisan route:clear
php8.3 artisan view:clear
php8.3 artisan event:clear

echo "✅ Деплой завершён"
