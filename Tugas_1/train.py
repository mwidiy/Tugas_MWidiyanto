import os
import sys
import shutil
from pathlib import Path
import yaml

# Tentukan direktori dasar proyek (lokasi script train.py ini berada)
BASE_DIR = Path(__file__).resolve().parent

def check_dataset_paths():
    """
    Memeriksa ketersediaan direktori dataset (train, valid/val, test)
    dan menyesuaikan konfigurasi YAML agar menggunakan path absolut yang valid.
    """
    print("\n" + "=" * 60)
    print("1. MEMERIKSA STRUKTUR DATASET...")
    print("=" * 60)
    
    # Lokasi data.yaml asli
    data_yaml_path = BASE_DIR / "data.yaml"
    
    # Kemungkinan lokasi folder dataset
    candidate_dirs = [
        BASE_DIR,                
        BASE_DIR.parent,            
        BASE_DIR / "dataset",
        BASE_DIR / "fruits",
    ]
    # Tambahkan subfolder yang ada di dalam BASE_DIR dan parent-nya
    for parent_folder in [BASE_DIR, BASE_DIR.parent]:
        if parent_folder.exists():
            for sub in parent_folder.iterdir():
                if sub.is_dir() and sub not in candidate_dirs:
                    candidate_dirs.append(sub)
    
    dataset_root = None
    train_dir = None
    val_dir = None
    test_dir = None
    
    for candidate in candidate_dirs:
        # Cek train images
        t_img = candidate / "train" / "images"
        if not t_img.exists():
            t_img = candidate / "train"
            
        v_img = candidate / "valid" / "images"
        if not v_img.exists():
            v_img = candidate / "val" / "images"
        if not v_img.exists():
            v_img = candidate / "valid"
            
        if t_img.exists() and any(t_img.iterdir() if t_img.is_dir() else []):
            dataset_root = candidate
            train_dir = t_img
            val_dir = v_img
            test_dir = candidate / "test" / "images"
            break

    if dataset_root is None:
        print("\n[PERINGATAN] Dataset gambar belum ditemukan!")
        print("-" * 60)
        print("Direktori yang diperiksa:")
        for c in candidate_dirs:
            print(f" - {c}")
        print("\nLangkah yang harus dilakukan:")
        print("1. Unduh dataset dari Kaggle:")
        print("   https://www.kaggle.com/datasets/kapturovalexander/fruits-by-yolo-fruits-detection")
        print(f"2. Ekstrak file zip sehingga folder 'train', 'valid', dan 'test' berada di:")
        print(f"   {BASE_DIR}")
        print("-" * 60)
        return None

    print(f"[OK] Direktori dataset ditemukan di: {dataset_root}")
    print(f" - Train images: {train_dir}")
    print(f" - Valid images: {val_dir}")
    if test_dir and test_dir.exists():
        print(f" - Test images : {test_dir}")

    # Buat atau update konfigurasi YAML dengan path absolut
    runtime_yaml_path = BASE_DIR / "data_runtime.yaml"
    
    # Daftar kelas buah
    names = ['Apple', 'Banana', 'Grapes', 'Kiwi', 'Mango', 'Orange', 'Pineapple', 'Sugerapple', 'Watermelon']
    
    yaml_config = {
        'path': str(dataset_root.as_posix()),
        'train': str(train_dir.relative_to(dataset_root).as_posix()),
        'val': str(val_dir.relative_to(dataset_root).as_posix()) if val_dir and val_dir.exists() else str(train_dir.relative_to(dataset_root).as_posix()),
        'test': str(test_dir.relative_to(dataset_root).as_posix()) if test_dir and test_dir.exists() else None,
        'nc': len(names),
        'names': names
    }
    
    with open(runtime_yaml_path, 'w', encoding='utf-8') as f:
        yaml.dump(yaml_config, f, default_flow_style=False, sort_keys=False)
        
    print(f"[OK] File konfigurasi training siap: {runtime_yaml_path}")
    return runtime_yaml_path


def select_device():
    """
    Mendeteksi ketersediaan GPU NVIDIA CUDA untuk akselerasi training.
    Jika tidak tersedia, fallback ke CPU.
    """
    import torch
    
    print("\n" + "=" * 60)
    print("2. MEMERIKSA PERANGKAT HARDWARE (DEVICE)...")
    print("=" * 60)
    
    if torch.cuda.is_available():
        gpu_name = torch.cuda.get_device_name(0)
        gpu_memory = torch.cuda.get_device_properties(0).total_memory / (1024 ** 3)
        print(f"[OK] GPU Terdeteksi: {gpu_name} ({gpu_memory:.2f} GB VRAM)")
        print("Training akan menggunakan akselerasi CUDA GPU (device=0).")
        return 0
    else:
        print("[INFO] CUDA GPU tidak terdeteksi pada PyTorch.")
        print("Training akan berjalan pada CPU (device='cpu').")
        return 'cpu'


def train_model(
    data_yaml,
    model_name="yolov8n.pt",
    epochs=25,
    imgsz=640,
    batch=16,
    device=0,
    project_dir=None
):
    """
    Melakukan training model YOLO menggunakan Ultralytics.
    """
    from ultralytics import YOLO
    
    if project_dir is None:
        project_dir = BASE_DIR / "runs" / "train"
        
    print("\n" + "=" * 60)
    print("3. MEMULAI TRAINING MODEL YOLO...")
    print("=" * 60)
    print(f"Model Dasar : {model_name}")
    print(f"Data Config : {data_yaml}")
    print(f"Epochs      : {epochs}")
    print(f"Batch Size  : {batch}")
    print(f"Image Size  : {imgsz}")
    print(f"Device      : {device}")
    print(f"Output Dir  : {project_dir}")
    print("-" * 60)
    
    # Inisialisasi model (transfer learning dari bobot pre-trained YOLOv8)
    model = YOLO(model_name)
    
    # Jalankan training
    results = model.train(
        data=str(data_yaml),
        epochs=epochs,
        imgsz=imgsz,
        batch=batch,
        device=device,
        project=str(project_dir),
        name="fruits_model",
        exist_ok=True,
        pretrained=True,
        verbose=True,
        workers=2,
        plots=True
    )
    
    print("\n" + "=" * 60)
    print("4. TRAINING SELESAI!")
    print("=" * 60)
    
    # Cari weights best.pt
    run_train_dir = project_dir / "fruits_model"
    best_pt_source = run_train_dir / "weights" / "best.pt"
    
    if best_pt_source.exists():
        # Salin ke root direktori tugas untuk memenuhi persyaratan pengumpulan berkas
        best_pt_dest = BASE_DIR / "best.pt"
        shutil.copy2(best_pt_source, best_pt_dest)
        print(f"[BERHASIL] File weights 'best.pt' telah disimpan di:")
        print(f"  -> {best_pt_dest}")
    else:
        print(f"[PERINGATAN] File {best_pt_source} tidak ditemukan.")
        
    # Validasi model dan cetak metrik evaluasi
    print("\n" + "=" * 60)
    print("5. EVALUASI MODEL (VALIDATION)...")
    print("=" * 60)
    try:
        val_results = model.val()
        print("\n--- METRIK HASIL EVALUASI ---")
        print(f"mAP50-95 : {val_results.box.map:.4f}")
        print(f"mAP50    : {val_results.box.map50:.4f}")
        print(f"mAP75    : {val_results.box.map75:.4f}")
    except Exception as e:
        print(f"Info validasi: {e}")
        
    print("\n" + "=" * 60)
    print("SELESAI. Model siap digunakan untuk Script Inference!")
    print("=" * 60)


def main():
    import argparse
    
    parser = argparse.ArgumentParser(description="Train YOLO Model for Fruits Detection")
    parser.add_argument("--epochs", type=int, default=25, help="Jumlah epoch training (default: 25)")
    parser.add_argument("--batch", type=int, default=16, help="Batch size (default: 16)")
    parser.add_argument("--imgsz", type=int, default=640, help="Resolusi gambar (default: 640)")
    parser.add_argument("--model", type=str, default="yolov8n.pt", help="Pretrained model (default: yolov8n.pt)")
    parser.add_argument("--check-only", action="store_true", help="Hanya periksa konfigurasi dataset tanpa training")
    args = parser.parse_args()
    
    # 1. Periksa path dataset
    data_yaml = check_dataset_paths()
    if data_yaml is None:
        sys.exit(1)
        
    if args.check_only:
        print("\n[OK] Pengecekan dataset berhasil selesai. Siap menjalankan training.")
        return
        
    # 2. Periksa device (GPU / CPU)
    device = select_device()
    
    # Jika menggunakan GPU GTX 1650 4GB, batch size 16 optimal. Bila memori sempit bisa batch 8.
    batch_size = args.batch
    if device == 0 and batch_size > 16:
        print(f"[ADVICE] Menyesuaikan batch size ke 16 untuk stabilitas VRAM 4GB.")
        batch_size = 16
        
    # 3. Jalankan training
    train_model(
        data_yaml=data_yaml,
        model_name=args.model,
        epochs=args.epochs,
        imgsz=args.imgsz,
        batch=batch_size,
        device=device
    )

if __name__ == "__main__":
    main()
