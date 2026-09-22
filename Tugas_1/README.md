# Tugas 1: Deteksi Objek Buah (YOLOv8)

Repositori ini berisi implementasi Object Detection untuk mendeteksi 9 jenis buah (Apple, Banana, Grapes, Kiwi, Mango, Orange, Pineapple, Sugerapple, Watermelon) menggunakan framework Ultralytics YOLOv8 dan PyTorch.

---

## Berkas Pengumpulan Tugas

1. best.pt: Model hasil pelatihan berbobot optimal (trained weights).
2. train.py: Script untuk melakukan training model dengan deteksi otomatis GPU CUDA atau CPU fallback.
3. infer.py: Script utama untuk inferensi dengan pop-up window interaktif dan kontrol keyboard real-time.
4. requirements.txt: Daftar dependensi library Python.
5. data.yaml: Konfigurasi kelas dan dataset dengan path relatif.
6. setup_dataset.py: Script untuk memproses dataset raw Kaggle ke format YOLO jika diperlukan.
7. runs/: Log metrik evaluasi model (Confusion Matrix, Precision-Recall Curve, Loss Graphs).

---

## Cara Instalasi

Pastikan Python 3 sudah terpasang di sistem, lalu pasang seluruh dependensi:

```bash
pip install -r requirements.txt
```

---

## Cara Menjalankan

### 1. Menjalankan Inference (Pop-Up Window Interaktif)

Script langsung membaca gambar uji dari direktori dataset/test/images menggunakan model best.pt:

```bash
python infer.py
```

Script akan memunculkan pop-up window yang menampilkan visualisasi bounding box, label nama buah, serta skor keyakinan (confidence score).

Kontrol Keyboard pada Window:
- Space / N : Pindah ke gambar pengujian berikutnya
- P : Kembali ke gambar sebelumnya
- + (Plus) : Menaikkan batas keyakinan (Confidence Threshold) secara real-time
- - (Minus) : Menurunkan batas keyakinan (Confidence Threshold) secara real-time
- S : Menyimpan gambar hasil deteksi ke runs/inference_output
- Esc / Q : Menutup window inferensi

---

### 2. Menjalankan Training Ulang Model

Untuk melatih ulang model pada dataset:

```bash
python train.py
```

- Script akan otomatis mendeteksi akselerasi GPU NVIDIA (CUDA). Jika tidak tersedia, script otomatis beralih ke mode CPU.
- Setelah pelatihan selesai, file bobot terbaik otomatis diperbarui pada best.pt.

---

## Fitur Kontrol Threshold Interaktif

Pada saat pop-up window terbuka, pengguna bisa menaikkan atau menurunkan batas keyakinan (confidence threshold) secara langsung menggunakan tombol + dan -.

Alasan Fitur Ini Dibuat:
Kondisi pencahayaan dan sudut pengambilan foto buah pada dataset sangat bervariasi (terdapat objek yang gelap, buram, atau saling bertumpuk). Jika threshold dipasang kaku terlalu tinggi, buah rawan tidak terdeteksi (false negative). Dengan fitur ini, reviewer dapat langsung menguji sensitivitas deteksi secara fleksibel tanpa harus mengubah baris kode apapun.

---

## Catatan Evaluasi & Performa

- Grafik evaluasi model lengkap seperti Confusion Matrix dan PR Curve dapat dilihat pada direktori runs/train/fruits_model/.
- Catatan: Model masih mengalami misklasifikasi pada beberapa gambar tertentu dengan pencahayaan ekstrem atau kemiripan bentuk tinggi, yang dapat dioptimalkan lebih lanjut dengan penambahan variasi data augmentasi dan peningkatan durasi epoch pelatihan.
