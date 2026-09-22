@echo off
setlocal
echo =========================================================
echo   Setup Database dan Migrasi CodeIgniter 4 (Tugas 3)
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

where php >nul 2>nul
if %errorlevel% equ 0 (
    set PHP_CMD=php
    goto run_setup
)

if exist "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" (
    set PHP_CMD="C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
    goto run_setup
)

if exist "C:\xampp\php\php.exe" (
    set PHP_CMD="C:\xampp\php\php.exe"
    goto run_setup
)

echo [ERROR] PHP tidak ditemukan!
pause
exit /b 1

:run_setup
echo 0. Menyiapkan Database Schema (cms_simulasi_toko)...
%PHP_CMD% spark db:create cms_simulasi_toko

echo 1. Menjalankan Database Migrations...
%PHP_CMD% spark migrate

echo 2. Menjalankan Database Seeder (Data Awal Dummy)...
%PHP_CMD% spark db:seed DatabaseSeeder

echo.
echo =========================================================
echo   Setup Database Selesai!
echo =========================================================
pause
