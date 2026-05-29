@echo off
echo 🚀 Автодеплой запущен...

npm run build
git add -f public/build
git commit -m "fix build"
git push

echo ✅ Деплой завершен
pause
