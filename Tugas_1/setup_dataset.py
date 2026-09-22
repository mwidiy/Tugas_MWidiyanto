import os
import sys
import csv
import cv2
import shutil
from pathlib import Path
import yaml

BASE_DIR = Path(__file__).resolve().parent

CLASSES = [
    'Apple', 'Banana', 'Grapes', 'Kiwi', 'Mango', 
    'Orange', 'Pineapple', 'Sugerapple', 'Watermelon'
]

def find_raw_dataset():
    """Mencari folder dataset yang berisi _classes.csv"""
    candidates = [
        BASE_DIR / "Fruits by YOLO" / "Fruits by YOLO",
        BASE_DIR / "Fruits by YOLO",
        BASE_DIR,
    ]
    for c in candidates:
        if (c / "train" / "_classes.csv").exists():
            return c
    return None

def compute_bbox_for_image(img_path):
    """
    Menghitung bounding box ternormalisasi (x_center, y_center, width, height)
    dari gambar menggunakan kontur objek buah.
    """
    img = cv2.imread(str(img_path))
    if img is None:
        return 0.5, 0.5, 0.8, 0.8  # Fallback centered box
        
    h, w = img.shape[:2]
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # Adaptive threshold untuk memisahkan objek buah dari background
    thresh = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY_INV, 11, 2)
    contours, _ = cv2.findContours(thresh, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    
    if contours:
        # Ambil kontur dengan area terbesar
        c = max(contours, key=cv2.contourArea)
        area = cv2.contourArea(c)
        if area > (h * w * 0.02):  # Minimal 2% dari ukuran gambar
            x, y, bw, bh = cv2.boundingRect(c)
            # Tambahkan sedikit padding
            pad_w = int(bw * 0.05)
            pad_h = int(bh * 0.05)
            x1 = max(0, x - pad_w)
            y1 = max(0, y - pad_h)
            x2 = min(w, x + bw + pad_w)
            y2 = min(h, y + bh + pad_h)
            
            box_w = (x2 - x1) / w
            box_h = (y2 - y1) / h
            box_x = (x1 + x2) / (2.0 * w)
            box_y = (y1 + y2) / (2.0 * h)
            return round(box_x, 6), round(box_y, 6), round(box_w, 6), round(box_h, 6)
            
    return 0.5, 0.5, 0.8, 0.8

def process_split(raw_split_dir, dest_split_dir):
    """Memproses satu split (train, valid, atau test)"""
    csv_file = raw_split_dir / "_classes.csv"
    if not csv_file.exists():
        print(f"[SKIP] File {csv_file} tidak ditemukan.")
        return 0
        
    images_dest = dest_split_dir / "images"
    labels_dest = dest_split_dir / "labels"
    images_dest.mkdir(parents=True, exist_ok=True)
    labels_dest.mkdir(parents=True, exist_ok=True)
    
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        header = [h.strip() for h in next(reader)]
        
        # Petakan indeks kolom untuk masing-masing kelas
        col_to_cls_idx = {}
        for idx, col in enumerate(header[1:], start=1):
            if col in CLASSES:
                col_to_cls_idx[idx] = CLASSES.index(col)
                
        count = 0
        rows = list(reader)
        total = len(rows)
        print(f"[PROSES] Mengonversi {total} gambar di {raw_split_dir.name}...")
        
        for row in rows:
            if not row:
                continue
            filename = row[0].strip()
            src_img = raw_split_dir / filename
            if not src_img.exists():
                continue
                
            # Cari kelas aktif (nilai 1)
            active_cls = None
            for c_idx, cls_id in col_to_cls_idx.items():
                if c_idx < len(row) and row[c_idx].strip() == '1':
                    active_cls = cls_id
                    break
                    
            if active_cls is None:
                continue
                
            # Hitung bounding box
            bx, by, bw, bh = compute_bbox_for_image(src_img)
            
            # Salin gambar ke images/
            target_img = images_dest / filename
            if not target_img.exists():
                shutil.copy2(src_img, target_img)
                
            # Buat file label YOLO .txt
            label_name = Path(filename).stem + ".txt"
            target_label = labels_dest / label_name
            with open(target_label, 'w', encoding='utf-8') as lf:
                lf.write(f"{active_cls} {bx} {by} {bw} {bh}\n")
                
            count += 1
            if count % 500 == 0 or count == total:
                print(f" -> {count}/{total} selesai")
                
    return count

def update_data_yaml(target_dataset_dir):
    """Mengupdate data.yaml agar menunjuk ke direktori yang sudah siap"""
    yaml_path = BASE_DIR / "data.yaml"
    config = {
        'path': str(target_dataset_dir.as_posix()),
        'train': 'train/images',
        'val': 'valid/images',
        'test': 'test/images',
        'nc': len(CLASSES),
        'names': CLASSES
    }
    with open(yaml_path, 'w', encoding='utf-8') as f:
        yaml.dump(config, f, default_flow_style=False, sort_keys=False)
    print(f"[OK] data.yaml berhasil diperbarui: {yaml_path}")

def main():
    print("=" * 60)
    print("SETUP & KONVERSI DATASET FRUITS KE FORMAT YOLO")
    print("=" * 60)
    
    raw_dir = find_raw_dataset()
    if raw_dir is None:
        print("[ERROR] Folder dataset Kaggle ('Fruits by YOLO') belum ditemukan!")
        print("Pastikan folder berada di:")
        print(f"  {BASE_DIR / 'Fruits by YOLO'}")
        sys.exit(1)
        
    print(f"[OK] Folder dataset terdeteksi: {raw_dir}")
    
    target_dataset = BASE_DIR / "dataset"
    target_dataset.mkdir(exist_ok=True)
    
    for split in ["train", "valid", "test"]:
        raw_split = raw_dir / split
        dest_split = target_dataset / split
        if raw_split.exists():
            process_split(raw_split, dest_split)
            
    update_data_yaml(target_dataset)
    
    print("\n" + "=" * 60)
    print("DATASET SELESAI DISIAPKAN DENGAN LENGKAP!")
    print(f"Lokasi: {target_dataset}")
    print("Sekarang Anda tinggal menjalankan perintah berikut untuk training:")
    print("  python train.py")
    print("=" * 60)

if __name__ == "__main__":
    main()
