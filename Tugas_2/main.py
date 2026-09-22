"""
Main Entry Point
Sistem Akses, Tampilan, dan Kontrol Kamera IoT & Embedded Systems.
Mendukung peluncuran mode GUI Tkinter Modern maupun mode OpenCV HighGUI mandiri.
"""

import os
os.environ.setdefault("OPENCV_LOG_LEVEL", "ERROR")
import sys
import json
import argparse
import logging
import tkinter as tk

from camera_controller import CameraController
from input_manager import InputManager
from app_gui import CameraAppGUI


def load_config(config_path="config.json"):
    """Memuat konfigurasi sistem dari file JSON."""
    if os.path.exists(config_path):
        try:
            with open(config_path, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception as e:
            logging.error(f"Gagal memuat config.json: {e}")
    # Fallback konfigurasi default jika file tidak ditemukan
    return {
        "camera": {
            "index": 0,
            "backend": "AUTO",
            "default_resolution": {"width": 1280, "height": 720},
            "resolution_presets": [
                {"label": "640x480 (SD 4:3)", "width": 640, "height": 480},
                {"label": "1280x720 (HD 16:9)", "width": 1280, "height": 720},
                {"label": "1920x1080 (Full HD 16:9)", "width": 1920, "height": 1080}
            ],
            "exposure": {"default": -5, "min": -13, "max": 0, "auto": False},
            "iso": {"default": 100, "min": 100, "max": 1600}
        },
        "capture": {
            "save_directory": "captures",
            "image_format": "jpg",
            "jpeg_quality": 95,
            "burst_max_fps": 15
        },
        "key_mappings": {
            "single_capture": ["space", "c"],
            "burst_capture": "b",
            "reconnect": "r",
            "quit": "Escape"
        }
    }


def print_banner(camera_idx, backend, width, height, save_dir):
    """Menampilkan banner informasi sistem dan panduan tombol kontrol."""
    banner = f"""
======================================================================
     IoT & EMBEDDED SYSTEMS - CAMERA ACQUISITION & CONTROL STUDIO
======================================================================
 [1] Index Kamera         : {camera_idx}
 [2] Backend Driver       : {backend}
 [3] Resolusi Awal        : {width} x {height}
 [4] Folder Penyimpanan   : {os.path.abspath(save_dir)}
----------------------------------------------------------------------
 PANDUAN KONTROL & KEY MAPPING:
   • [SPACE] / [C]        : SINGLE CAPTURE (Ambil 1 foto berkualitas)
   • [TAHAN TOMBOL B]     : BURST CAPTURE (Ambil beruntun s/d dilepas)
   • [R]                  : RECONNECT KAMERA
   • [Q] / [ESC]          : KELUAR APLIKASI
   • SLIDER RESOLUSI/EXP/ISO : Dapat diatur langsung di panel GUI!
======================================================================
"""
    print(banner)


def run_gui_mode(args, config):
    """Menjalankan aplikasi dalam mode GUI Dark Studio berbasis Tkinter."""
    cam_cfg = config.get("camera", {})
    cap_cfg = config.get("capture", {})

    from camera_controller import detect_available_cameras
    available_cams, best_idx = detect_available_cameras()

    if args.camera is not None:
        cam_idx = args.camera
    else:
        cfg_idx = cam_cfg.get("index")
        valid_indices = [c["index"] for c in available_cams]
        if cfg_idx is not None and cfg_idx in valid_indices:
            cam_idx = cfg_idx
        else:
            cam_idx = best_idx

    backend = args.backend if args.backend is not None else cam_cfg.get("backend", "AUTO")
    width = args.width if args.width is not None else cam_cfg.get("default_resolution", {}).get("width", 1280)
    height = args.height if args.height is not None else cam_cfg.get("default_resolution", {}).get("height", 720)
    save_dir = cap_cfg.get("save_directory", "captures")

    print_banner(cam_idx, backend, width, height, save_dir)

    # Inisialisasi Kontroler Kamera & Input
    cam = CameraController(
        camera_index=cam_idx,
        backend_name=backend,
        target_width=width,
        target_height=height
    )
    cam.start()

    keys_cfg = config.get("key_mappings", {})
    single_keys = keys_cfg.get("single_capture", ["space", "c"])
    burst_key = keys_cfg.get("burst_capture", "b")

    im = InputManager(single_keys=single_keys, burst_key=burst_key)

    # Peluncuran GUI Tkinter
    root = tk.Tk()
    app = CameraAppGUI(root, cam, im, config, available_cams=available_cams)
    root.mainloop()


def run_opencv_cli_mode(args, config):
    """Mode alternatif murni HighGUI OpenCV (cocok untuk embedded device headless/terminal)."""
    import cv2
    import time

    cam_cfg = config.get("camera", {})
    cap_cfg = config.get("capture", {})
    save_dir = cap_cfg.get("save_directory", "captures")
    os.makedirs(save_dir, exist_ok=True)

    from camera_controller import detect_available_cameras
    available_cams, best_idx = detect_available_cameras()

    if args.camera is not None:
        cam_idx = args.camera
    else:
        cfg_idx = cam_cfg.get("index")
        valid_indices = [c["index"] for c in available_cams]
        if cfg_idx is not None and cfg_idx in valid_indices:
            cam_idx = cfg_idx
        else:
            cam_idx = best_idx
    backend = args.backend if args.backend is not None else cam_cfg.get("backend", "AUTO")
    width = args.width if args.width is not None else cam_cfg.get("default_resolution", {}).get("width", 1280)
    height = args.height if args.height is not None else cam_cfg.get("default_resolution", {}).get("height", 720)

    print_banner(cam_idx, backend, width, height, save_dir)
    print("[INFO] Menjalankan dalam mode OpenCV HighGUI Window...")

    cam = CameraController(cam_idx, backend, width, height)
    cam.start()

    window_name = "Camera Stream (OpenCV HighGUI)"
    cv2.namedWindow(window_name, cv2.WINDOW_NORMAL)
    cv2.resizeWindow(window_name, 960, 540)

    try:
        while True:
            ret, frame, meta = cam.get_latest_frame()
            if ret and frame is not None:
                # Gambar OSD
                info_text = f"FPS: {meta['fps']} | Res: {meta['width']}x{meta['height']} | Exp: {meta['exposure']} | ISO: {meta['iso']}"
                cv2.putText(frame, info_text, (20, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 200), 2)
                cv2.imshow(window_name, frame)

            key = cv2.waitKey(15) & 0xFF
            if key == 27 or key == ord('q'):
                break
            elif key == 32 or key == ord('c'):  # SPACE / C
                ret, clean = cam.get_clean_capture_frame()
                if ret:
                    fname = os.path.join(save_dir, f"capture_{time.strftime('%Y%m%d_%H%M%S')}.jpg")
                    cv2.imwrite(fname, clean)
                    print(f"[CAPTURE] Tersimpan: {fname}")
            elif key == ord('r'):
                cam.reconnect()
    finally:
        cam.stop()
        cv2.destroyAllWindows()


def main():
    parser = argparse.ArgumentParser(description="IoT Camera Acquisition & Control Studio")
    parser.add_argument("--camera", "-c", type=int, default=None, help="Index kamera (default: 0)")
    parser.add_argument("--backend", "-b", type=str, default=None, choices=["AUTO", "DSHOW", "V4L2", "MSMF"], help="Backend kamera")
    parser.add_argument("--width", "-w", type=int, default=None, help="Lebar frame awal (misal: 1280)")
    parser.add_argument("--height", type=int, default=None, help="Tinggi frame awal (misal: 720)")
    parser.add_argument("--config", type=str, default="config.json", help="Path file konfigurasi json")
    parser.add_argument("--cli", action="store_true", help="Jalankan dalam mode OpenCV HighGUI tanpa Tkinter")

    args = parser.parse_args()
    config = load_config(args.config)

    if args.cli:
        run_opencv_cli_mode(args, config)
    else:
        run_gui_mode(args, config)


if __name__ == "__main__":
    main()
