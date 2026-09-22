@echo off
setlocal
echo =========================================================
echo   Menjalankan CodeIgniter 4 Development Server (Tugas 3)
echo =========================================================

REM Auto-create .env from env template if missing
if not exist ".env" (
    if exist "env" (
        copy "env" ".env" >nul
        echo [INFO] File .env otomatis dibuat dari template env.
    )
)

REM Pastikan folder vendor CodeIgniter 4 terinstall
if not exist "vendor" (
    echo [INFO] Folder vendor belum ditemukan. Menjalankan composer install...
    where composer >nul 2>nul
    if %errorlevel% equ 0 (
        call composer install
    ) else (
        echo [ERROR] Composer tidak ditemukan! Silakan jalankan 'composer install' secara manual.
        pause
        exit /b 1
    )
)

REM Cek apakah PHP ada di sistem PATH
where php >nul 2>nul
if %errorlevel% equ 0 (
    set PHP_CMD=php
    goto run_server
)

REM Cek Laragon PHP
if exist "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" (
    set PHP_CMD="C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
    goto run_server
)

REM Cek XAMPP PHP
if exist "C:\xampp\php\php.exe" (
    set PHP_CMD="C:\xampp\php\php.exe"
    goto run_server
)

echo [ERROR] PHP tidak ditemukan di PATH, Laragon, maupun XAMPP!
echo Silakan pastikan PHP telah terinstall atau tambahkan ke PATH.
pause
exit /b 1

:run_server
echo Menggunakan PHP: %PHP_CMD%
echo Server akan berjalan di: http://localhost:8080
echo Tekan CTRL+C untuk menghentikan server.
echo.
%PHP_CMD% spark serve --port 8080
pause
