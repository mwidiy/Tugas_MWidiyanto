# Tugas 2: IoT & Embedded Systems (Camera Control System)

Aplikasi berbasis Python dan OpenCV untuk mengakses stream video kamera (Webcam internal / USB Camera), menampilkan *live preview*, serta mengontrol parameter kamera dan fitur penangkapan citra (*Single & Burst Capture*) melalui antarmuka grafis (GUI) modern.

---

## 📦 Komponen Berkas

1. `main.py`: Entry point utama aplikasi.
2. `app_gui.py`: Modul antarmuka grafis modern berbasis Tkinter dengan tema *dark studio*.
3. `camera_controller.py`: Controller pengelola akses kamera multi-threading, deteksi webcam fisik, dan pengaturan parameter citra.
4. `input_manager.py`: Modul penanganan tombol keyboard dan event listener.
5. `config.json`: File konfigurasi preset resolusi, exposure, ISO, dan direktori penyimpanan.
6. `requirements.txt`: Daftar dependensi library Python.
7. `captures/`: Direktori penyimpanan otomatis hasil tangkapan gambar.

---

## ⚙️ Persyaratan & Instalasi

Pastikan Python 3 sudah terinstal pada komputer. Jalankan instalasi dependensi melalui terminal:

```bash
pip install -r requirements.txt
```

Dependensi utama:
- `opencv-python`: Akses streaming dan pemrosesan frame kamera.
- `pillow`: Integrasi rendering citra OpenCV ke antarmuka GUI Tkinter.

---

## 🚀 Cara Menjalankan

Jalankan script utama melalui terminal atau command prompt:

```bash
python main.py
```

---

## 🎮 Pemetaan Tombol (Key Mapping)

Saat jendela aplikasi aktif, pengguna dapat mengoperasikan aplikasi menggunakan pintasan keyboard berikut:

| Pintasan Keyboard | Aksi | Keterangan |
|---|---|---|
| `Space` atau `C` | **Single Capture** | Mengambil dan menyimpan 1 lembar foto ke direktori `captures/`. |
| **Tahan tombol** `B` | **Burst Capture** | Mengambil foto beruntun secara cepat selama tombol ditahan, dan langsung berhenti saat tombol dilepas. Hasil disimpan rapi di subfolder `captures/burst_YYYYMMDD_HHMMSS/`. |
| `R` | **Reconnect** | Menginisialisasi ulang sambungan kamera jika terjadi gangguan stream. |
| `Esc` atau `Q` | **Keluar** | Menutup aplikasi dan membebaskan resource kamera. |

---

## 🎛️ Fitur & Pengaturan Parameter Kamera

1. **Pilihan Perangkat Kamera**: Dropdown untuk mendeteksi dan memilih perangkat kamera fisik yang terpasang secara dinamis.
2. **Resolusi (Resolution Presets)**:
   - `640x480` (SD 4:3) - Preset tercepat & ter-responsif.
   - `1280x720` (HD 16:9).
   - `1920x1080` (Full HD 16:9).
3. **Shutter Speed / Exposure**: Slider pengaturan pencahayaan manual (-13 s.d. 0 EV) serta checkbox mode *Auto Exposure*.
4. **ISO (Sensor Gain)**: Slider penguatan sinyal sensor digital (ISO 100 s.d. 1600).
5. **Burst Capture**: Menangkap rentetan frame secara berurutan dengan throttle FPS otomatis.
6. **Live Thumbnail & Status Banner**: Menampilkan preview thumbnail foto terakhir yang berhasil diambil beserta indikator notifikasi visual.

---

## 💡 Catatan Performa Hardware

- Tingkat kelancaran (FPS) dan responsivitas kamera sangat dipengaruhi oleh spesifikasi sensor webcam serta bandwidth driver USB perangkat.
- Jika live preview terasa berat pada resolusi tinggi (720p/1080p), hal tersebut merupakan limitasi hardware sensor standar. Preset `640x480` direkomendasikan untuk pengalaman streaming paling ringan dan responsif.
