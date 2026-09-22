import os
import sys
from pathlib import Path
import cv2
import numpy as np
from ultralytics import YOLO

# =============================================================================
# KONFIGURASI FILE PATH (DITENTUKAN LANGSUNG DI DALAM SCRIPT)
# =============================================================================
BASE_DIR = Path(__file__).resolve().parent

# 1. Path direktori gambar uji (Default: folder test images)
DEFAULT_IMAGE_DIR = BASE_DIR / "dataset" / "test" / "images"

# 2. Path model hasil training (Default: best.pt di root folder tugas)
DEFAULT_MODEL_PATH = BASE_DIR / "best.pt"

# 3. Path direktori penyimpanan hasil preview (opsional)
OUTPUT_DIR = BASE_DIR / "runs" / "inference_output"

# Daftar 9 Kelas Buah
CLASS_NAMES = [
    'Apple', 'Banana', 'Grapes', 'Kiwi', 'Mango',
    'Orange', 'Pineapple', 'Sugerapple', 'Watermelon'
]

# Warna khusus (BGR) untuk masing-masing kelas agar tampilan visual menarik & jelas
CLASS_COLORS = {
    'Apple':       (40, 40, 230),    # Merah Cerah
    'Banana':      (0, 215, 255),    # Kuning Cerah
    'Grapes':      (180, 50, 130),   # Ungu
    'Kiwi':        (45, 120, 90),    # Hijau Zaitun / Kiwi
    'Mango':       (20, 160, 255),   # Oranye Keemasan
    'Orange':      (0, 128, 255),    # Oranye Pekat
    'Pineapple':   (0, 200, 220),    # Emas Nanas
    'Sugerapple':  (120, 200, 80),   # Hijau Muda / Srikaya
    'Watermelon':  (80, 180, 50),    # Hijau Semangka
}

def resolve_paths(image_dir_override=None, model_path_override=None):
    """
    Memastikan path model dan direktori gambar tersedia.
    Jika folder default dipindahkan, script memiliki fallback otomatis.
    """
    # 1. Validasi Model Path
    model_path = Path(model_path_override) if model_path_override else DEFAULT_MODEL_PATH
    if not model_path.exists():
        # Fallback ke runs/train/fruits_model/weights/best.pt
        fallback_model = BASE_DIR / "runs" / "train" / "fruits_model" / "weights" / "best.pt"
        if fallback_model.exists():
            model_path = fallback_model
        else:
            print(f"[ERROR] Model weights '{model_path}' tidak ditemukan!")
            print("Pastikan Anda sudah menjalankan 'python train.py' terlebih dahulu.")
            sys.exit(1)
            
    # 2. Validasi Direktori Gambar
    img_dir = Path(image_dir_override) if image_dir_override else DEFAULT_IMAGE_DIR
    if not img_dir.exists():
        # Coba fallback ke lokasi dataset lain
        fallbacks = [
            BASE_DIR / "dataset" / "valid" / "images",
            BASE_DIR / "Fruits by YOLO" / "Fruits by YOLO" / "test",
            BASE_DIR / "Fruits by YOLO" / "Fruits by YOLO" / "valid",
        ]
        found = False
        for fb in fallbacks:
            if fb.exists() and any(fb.glob("*.*")):
                img_dir = fb
                found = True
                break
        if not found:
            print(f"[ERROR] Direktori gambar '{img_dir}' tidak ditemukan!")
            sys.exit(1)
            
    return model_path, img_dir


def draw_custom_annotations(img, boxes, class_names, conf_thresh=0.20):
    """
    Menggambar bounding box dan label kelas yang elegan & kontras
    pada gambar hasil inferensi.
    """
    annotated_img = img.copy()
    h, w = img.shape[:2]
    detected_summary = {}

    for box in boxes:
        conf = float(box.conf[0])
        if conf < conf_thresh:
            continue
            
        cls_id = int(box.cls[0])
        cls_name = class_names[cls_id] if cls_id < len(class_names) else f"ID {cls_id}"
        detected_summary[cls_name] = detected_summary.get(cls_name, 0) + 1
        
        # Koordinat Bounding Box
        x1, y1, x2, y2 = map(int, box.xyxy[0])
        x1, y1 = max(0, x1), max(0, y1)
        x2, y2 = min(w - 1, x2), min(h - 1, y2)
        
        color = CLASS_COLORS.get(cls_name, (0, 255, 0))
        
        # Gambar Bounding Box Utama
        cv2.rectangle(annotated_img, (x1, y1), (x2, y2), color, thickness=2, lineType=cv2.LINE_AA)
        
        # Teks Label
        label_text = f"{cls_name} {int(conf * 100)}%"
        font = cv2.FONT_HERSHEY_SIMPLEX
        font_scale = 0.6
        font_thickness = 1
        
        # Hitung ukuran banner teks
        (t_w, t_h), baseline = cv2.getTextSize(label_text, font, font_scale, font_thickness)
        
        # Posisi banner teks di atas box (atau di dalam jika mepet tepi atas)
        b_y1 = max(0, y1 - t_h - baseline - 6)
        b_y2 = y1 if y1 - t_h - baseline - 6 >= 0 else y1 + t_h + baseline + 6
        b_x2 = min(w - 1, x1 + t_w + 10)
        
        # Background banner label
        cv2.rectangle(annotated_img, (x1, b_y1), (b_x2, b_y2), color, -1)
        
        # Teks label (putih untuk kontras tinggi)
        text_y = b_y2 - baseline - 2 if y1 - t_h - baseline - 6 >= 0 else b_y2 - 4
        cv2.putText(
            annotated_img,
            label_text,
            (x1 + 5, text_y),
            font,
            font_scale,
            (255, 255, 255),
            thickness=font_thickness,
            lineType=cv2.LINE_AA
        )
        
    return annotated_img, detected_summary


def add_info_header(img, current_idx, total_count, filename, detected_summary, current_conf=0.20):
    """
    Menambahkan header status bar di bagian atas window pop-up untuk
    memberikan preview informasi deteksi dan petunjuk navigasi keyboard.
    """
    header_height = 65
    h, w = img.shape[:2]
    
    # Buat kanvas dengan header di atas
    canvas = np.zeros((h + header_height, w, 3), dtype=np.uint8)
    canvas[:header_height] = (30, 30, 35)  # Dark sleek header
    canvas[header_height:] = img
    
    # Baris 1: Nomor Gambar & Nama File & Hasil Deteksi
    det_str = ", ".join([f"{k}: {v}" for k, v in detected_summary.items()]) if detected_summary else "Tidak ada buah terdeteksi (Coba tekan [-] untuk turunkan threshold)"
    info_line1 = f"[{current_idx + 1}/{total_count}] {filename}  |  Buah: {det_str}"
    
    # Baris 2: Petunjuk Kontrol Keyboard & Nilai Ambang Batas Saat Ini
    info_line2 = f"Threshold: {int(current_conf * 100)}% ([+] Naik / [-] Turun)  |  [Space/N]: Next  |  [P]: Prev  |  [S]: Save  |  [Q]: Keluar"
    
    cv2.putText(canvas, info_line1, (15, 26), cv2.FONT_HERSHEY_SIMPLEX, 0.52, (240, 240, 240), 1, cv2.LINE_AA)
    cv2.putText(canvas, info_line2, (15, 52), cv2.FONT_HERSHEY_SIMPLEX, 0.44, (160, 210, 255), 1, cv2.LINE_AA)
    
    return canvas


def run_inference():
    """
    Fungsi utama untuk menjalankan inferensi model YOLO dan menampilkan pop-up window.
    Mendukung interaksi dinamis untuk menaikkan/menurunkan confidence threshold secara real-time.
    """
    print("\n" + "=" * 65)
    print("SCRIPT INFERENCE: DETEKSI OBJEK BUAH DENGAN YOLO")
    print("=" * 65)
    
    # 1. Resolusi Path
    model_path, image_dir = resolve_paths()
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    
    print(f"[1] Memuat Model Weights : {model_path}")
    print(f"[2] Direktori Gambar Uji  : {image_dir}")
    print(f"[3] Folder Simpan Preview: {OUTPUT_DIR}")
    print("-" * 65)
    
    # 2. Load Model YOLO
    model = YOLO(str(model_path))
    
    # Dapatkan nama-nama kelas dari model
    model_classes = model.names if hasattr(model, 'names') and model.names else CLASS_NAMES
    
    # 3. Kumpulkan seluruh file gambar pada direktori
    valid_extensions = {".jpg", ".jpeg", ".png", ".bmp", ".webp"}
    image_files = sorted([
        f for f in image_dir.iterdir()
        if f.is_file() and f.suffix.lower() in valid_extensions
    ])
    
    total_images = len(image_files)
    if total_images == 0:
        print(f"[PERINGATAN] Tidak ditemukan file gambar di '{image_dir}'!")
        return
        
    print(f"[OK] Ditemukan {total_images} gambar untuk inferensi.")
    print("\n[INFO] MEMBUKA POP-UP WINDOW PREVIEW...")
    print("Petunjuk Kontrol Interaktif:")
    print("  - Tekan [SPACE] atau [N] : Pindah ke gambar BERIKUTNYA")
    print("  - Tekan [P]              : Kembali ke gambar SEBELUMNYA")
    print("  - Tekan [+] / [=]        : MENAIKKAN confidence threshold (+5%)")
    print("  - Tekan [-] / [_]        : MENURUNKAN confidence threshold (-5%)")
    print("  - Tekan [S]              : Simpan gambar beranotasi ke disk")
    print("  - Tekan [Q] atau [ESC]   : Keluar dari pop-up window")
    print("=" * 65 + "\n")
    
    window_name = "Deteksi Buah - YOLOv8 Inference Preview Window"
    cv2.namedWindow(window_name, cv2.WINDOW_NORMAL)
    
    # Default confidence threshold diatur ke 0.20 untuk meminimalkan False Negative
    conf_thresh = 0.20
    idx = 0
    
    while 0 <= idx < total_images:
        img_path = image_files[idx]
        raw_img = cv2.imread(str(img_path))
        if raw_img is None:
            idx += 1
            continue
            
        # Lakukan Inferensi dengan ambang batas minimal rendah untuk menangkap semua calon box
        results = model.predict(raw_img, conf=min(0.05, conf_thresh), iou=0.45, verbose=False)
        boxes = results[0].boxes if len(results) > 0 else []
        
        # Gambar Bounding Box & Label Kelas berdasarkan conf_thresh saat ini
        annotated, summary = draw_custom_annotations(raw_img, boxes, model_classes, conf_thresh=conf_thresh)
        
        # Tambahkan Status Bar Header
        display_img = add_info_header(annotated, idx, total_images, img_path.name, summary, current_conf=conf_thresh)
        
        # Tampilkan pop-up window
        cv2.imshow(window_name, display_img)
        
        # Tunggu input tombol keyboard
        key = cv2.waitKey(0) & 0xFF
        
        if key in [ord('q'), ord('Q'), 27]:  # 'q' atau ESC -> Keluar
            print("\n[INFO] Menutup pop-up window. Selesai.")
            break
        elif key in [ord('n'), ord('N'), 32]:  # 'n', Space -> Next
            if idx < total_images - 1:
                idx += 1
            else:
                print("[INFO] Sudah mencapai gambar terakhir.")
        elif key in [ord('p'), ord('P')]:  # 'p' -> Previous
            if idx > 0:
                idx -= 1
            else:
                print("[INFO] Sudah berada di gambar pertama.")
        elif key in [ord('+'), ord('='), 82]:  # '+' atau '=' atau Panah Atas -> Naikkan threshold
            if conf_thresh < 0.90:
                conf_thresh = round(conf_thresh + 0.05, 2)
                print(f"[THRESHOLD] Menaikkan confidence threshold ke: {int(conf_thresh * 100)}%")
        elif key in [ord('-'), ord('_'), 84]:  # '-' atau '_' atau Panah Bawah -> Turunkan threshold
            if conf_thresh > 0.05:
                conf_thresh = round(conf_thresh - 0.05, 2)
                print(f"[THRESHOLD] Menurunkan confidence threshold ke: {int(conf_thresh * 100)}%")
        elif key in [ord('s'), ord('S')]:  # 's' -> Simpan gambar
            save_path = OUTPUT_DIR / f"deteksi_{img_path.name}"
            cv2.imwrite(str(save_path), display_img)
            print(f"[BERHASIL DISIMPAN] {save_path}")
            
    cv2.destroyAllWindows()


def main():
    """
    Entry point script inference.
    Mendukung eksekusi langsung tanpa argumen terminal,
    serta mendukung opsi opsional --source atau --model.
    """
    import argparse
    parser = argparse.ArgumentParser(description="YOLO Fruits Detection Inference Script with Pop-up Window")
    parser.add_argument("--source", type=str, default=None, help="Direktori gambar (opsional, default ada di script)")
    parser.add_argument("--model", type=str, default=None, help="Path model weights (opsional, default best.pt)")
    args = parser.parse_args()
    
    run_inference()

if __name__ == "__main__":
    main()
