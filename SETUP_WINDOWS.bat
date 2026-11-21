@echo off
setlocal
cd /d %~dp0
if not exist .env copy .env.example .env
where php >nul 2>&1 || (echo PHP is not available in PATH.&pause&exit /b 1)
if not exist vendor\autoload.php (
  where composer >nul 2>&1 || (echo Composer is not available in PATH.&pause&exit /b 1)
  composer install
)
findstr /C:"APP_KEY=base64:" .env >nul || php artisan key:generate
php artisan migrate --seed
if errorlevel 1 exit /b 1
if exist package.json (
  where npm >nul 2>&1 && npm install && npm run build
)
echo.
echo Alazzazi InvoiceShelf setup completed.
echo No demo records or default passwords are created.
echo Set the BOOTSTRAP_ADMIN_NAME, BOOTSTRAP_ADMIN_EMAIL, and BOOTSTRAP_ADMIN_PASSWORD values in .env, then run php artisan db:seed to create an administrator.
pause
