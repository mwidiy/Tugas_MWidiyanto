# Technical Assessment & Portfolio Submission

Repositori terpadu (Monorepo) yang berisi implementasi dan dokumentasi 3 tugas seleksi teknis oleh M. Widiyanto, mencakup bidang Computer Vision & AI, IoT & Embedded Systems (Camera Control GUI), dan Web Development & Database Transaction Simulation.


## Spesifikasi Lingkungan Pengujian (Hardware & OS)

Seluruh aplikasi telah diuji dan berjalan optimal pada lingkungan perangkat keras berikut:

- Sistem Operasi: Windows 11 Home Single Language 64-bit (Build 26200)
- Model Perangkat: Acer Nitro AN515-56
- Prosesor (CPU): 11th Gen Intel(R) Core(TM) i5-11300H @ 3.10GHz (8 CPUs)
- Grafis (GPU): NVIDIA GeForce GTX 1650 (4 GB GDDR6)
- Memori (RAM): 8 GB DDR4
- Penyimpanan (Storage): 512 GB NVMe SSD

---

## Struktur Direktori Repositori

```text
Tugas_M.Widiyanto/
│
├── .gitignore               # Aturan ignore file sampah, vendor, cache & log
├── README.md                # Dokumentasi utama repositori
│
├── Tugas_1/                 # PROYEK 1: Deteksi Objek Buah (YOLO)
│   ├── README.md            # Panduan lengkap Tugas 1
│   ├── best.pt              # Bobot model terlatih (trained weights)
│   ├── data.yaml            # Konfigurasi kelas & path dataset
│   ├── dataset/             # Dataset buah (train, valid, test images & labels)
│   ├── infer.py             # Script inferensi pop-up interaktif
│   ├── train.py             # Script pelatihan model YOLO
│   ├── setup_dataset.py     # Script konversi raw Kaggle dataset ke format YOLO
│   ├── requirements.txt     # Dependensi library Python
│   └── runs/                # Metrik evaluasi training (Confusion Matrix, PR Curve, dll)
│
├── Tugas_2/                 # PROYEK 2: IoT Camera Control GUI
│   ├── README.md            # Panduan lengkap Tugas 2
│   ├── main.py              # Entry-point aplikasi kamera
│   ├── app_gui.py           # GUI antarmuka pengguna (Tkinter modern dark UI)
│   ├── camera_controller.py # Multi-threaded camera grabber & parameter controls
│   ├── input_manager.py     # Key binding & event listener
│   ├── config.json          # Konfigurasi preset resolusi, exposure, & direktori simpan
│   ├── requirements.txt     # Dependensi library Python
│   └── captures/            # Direktori penyimpanan hasil foto & burst capture (.gitkeep)
│
└── Tugas_3/                 # PROYEK 3: Web CMS & Simulasi Toko (CodeIgniter 4)
    ├── README.md            # Panduan lengkap Tugas 3
    ├── app/                 # Source code MVC CodeIgniter 4 (Controllers, Models, Views)
    ├── cms_simulasi_toko.sql# Dump database MySQL siap import (UTF-8)
    ├── composer.json        # Konfigurasi dependency PHP
    ├── env                  # Template konfigurasi environment database & app
    ├── public/              # Web root & asset statis
    ├── serve.bat            # One-click dev server runner (Port 8080)
    ├── setup_db.bat         # One-click automated database migration & seeder
    ├── spark                # CodeIgniter 4 CLI tool
    └── writable/            # Direktori runtime CI4 (cache, session, logs)
```

---

## Panduan Menjalankan Masing-Masing Tugas

### 1. Menjalankan Tugas 1 (Deteksi Objek Buah)
Buka terminal pada direktori Tugas_1:
```bash
cd Tugas_1
pip install -r requirements.txt
python infer.py
```
- Navigasi Gambar: Tekan Space / N (Next) atau P (Previous).
- Sensitivitas Keyakinan: Tekan + untuk menaikkan threshold keyakinan, atau - untuk menurunkan threshold secara real-time.
- Simpan Hasil: Tekan S untuk menyimpan gambar deteksi ke folder runs/inference_output.
- Keluar: Tekan Esc atau Q.

---

### 2. Menjalankan Tugas 2 (IoT Camera Control GUI)
Buka terminal pada direktori Tugas_2:
```bash
cd Tugas_2
pip install -r requirements.txt
python main.py
```
- Single Capture: Tekan tombol Space atau C (hasil otomatis tersimpan di folder captures/).
- Burst Capture: Tekan dan tahan tombol B pada keyboard untuk continuous high-speed capture (otomatis tersimpan per folder sesi di captures/burst_YYYYMMDD_HHMMSS/).
- Reconnect: Tekan tombol R jika terjadi gangguan sambungan kamera.
- Keluar: Tekan Esc atau Q.

---

### 3. Menjalankan Tugas 3 (Web CMS & Simulasi Toko)
Pastikan MySQL (XAMPP / Laragon) sudah berjalan di port 3306.
Buka direktori Tugas_3:

#### Langkah A: Inisialisasi Database
- Cara Otomatis: Klik dua kali file setup_db.bat, atau
- Cara Manual via Terminal:
  ```bash
  cd Tugas_3
  php spark migrate
  php spark db:seed DatabaseSeeder
  ```
  (Atau import file cms_simulasi_toko.sql ke database MySQL cms_simulasi_toko melalui phpMyAdmin).

#### Langkah B: Menjalankan Server Web
- Cara Otomatis: Klik dua kali file serve.bat, atau
- Cara Manual via Terminal:
  ```bash
  php spark serve --port 8080
  ```
- Buka browser dan akses: http://localhost:8080

---

## Profil Pengembang

- Nama: M. Widiyanto
- Tujuan: Pengumpulan Tugas Seleksi Kerja / Technical Test
