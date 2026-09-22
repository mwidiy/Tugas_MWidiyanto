"""
Camera Controller Module
Mengelola akses kamera (OpenCV/V4L2/DirectShow), pembacaan frame asinkron (multi-threading),
pengaturan parameter kamera (Resolusi, Shutter Speed/Exposure, ISO/Gain), deteksi otomatis webcam fisik,
dan fallback pemrosesan citra digital.
"""

import os
os.environ.setdefault("OPENCV_LOG_LEVEL", "ERROR")
import cv2
import numpy as np
import threading
import time
import sys
import subprocess
import logging

logging.basicConfig(level=logging.INFO, format="[%(asctime)s] [%(levelname)s] %(message)s")


def detect_available_cameras(max_check=4):
    """
    Mendeteksi seluruh perangkat kamera yang terhubung pada sistem,
    mengekstrak nama perangkat (misal: 'HD User Facing' vs 'TranScreen Camera'),
    dan mengidentifikasi kamera fisik terbaik (bukan virtual camera yang layarnya hitam).
    """
    cameras = []
    backend = cv2.CAP_DSHOW if os.name == "nt" else cv2.CAP_ANY

    # Coba ambil nama perangkat di Windows melalui PowerShell WMI
    names = {}
    if os.name == "nt":
        try:
            ps_cmd = (
                "powershell -NoProfile -Command "
                "\"Get-CimInstance Win32_PnPEntity | "
                "Where-Object { $_.PNPClass -eq 'Camera' -or $_.PNPClass -eq 'Image' } | "
                "Select-Object -ExpandProperty Name\""
            )
            res = subprocess.run(
                ps_cmd,
                shell=True,
                capture_output=True,
                text=True,
                timeout=3,
                creationflags=0x08000000 if hasattr(subprocess, "CREATE_NO_WINDOW") else 0
            )
            if res.returncode == 0:
                lines = [line.strip() for line in res.stdout.strip().splitlines() if line.strip()]
                for idx, name in enumerate(lines):
                    names[idx] = name
        except Exception:
            pass

    best_index = 0
    highest_brightness = -1.0

    for i in range(max_check):
        try:
            cap = cv2.VideoCapture(i, backend)
            if cap.isOpened():
                ret, frame = False, None
                for _ in range(3):
                    ret, frame = cap.read()
                mean_b = float(np.mean(frame)) if (ret and frame is not None) else 0.0
                cap.release()

                raw_name = names.get(i, f"Camera {i}")
                is_virtual = "transcreen" in raw_name.lower() or (mean_b < 0.5 and i == 0 and 1 in names)
                status_tag = "Virtual/Gelap" if is_virtual else ("Aktif" if mean_b > 1.0 else "Siap")
                label = f"Kamera {i}: {raw_name} [{status_tag}]"

                cameras.append({
                    "index": i,
                    "name": raw_name,
                    "label": label,
                    "mean_brightness": mean_b,
                    "is_virtual": is_virtual
                })

                # Pilih kamera fisik yang memiliki cahaya aktif sebagai rekomendasi utama
                if not is_virtual and mean_b > highest_brightness:
                    highest_brightness = mean_b
                    best_index = i
        except Exception:
            pass

    if not cameras:
        cameras.append({
            "index": 0,
            "name": "Default Camera",
            "label": "Kamera 0: Default Camera",
            "mean_brightness": 0.0,
            "is_virtual": False
        })
        best_index = 0

    return cameras, best_index


class CameraController:
    """Mengontrol perangkat kamera dengan threaded frame grabber untuk performa real-time bebas lag."""

    def __init__(self, camera_index=1, backend_name="AUTO", target_width=1280, target_height=720):
        self.camera_index = camera_index
        self.backend_name = backend_name
        self.target_width = target_width
        self.target_height = target_height

        # Parameter Kamera (Auto Exposure ON by default agar langsung terang)
        self.exposure_val = -4
        self.iso_val = 100
        self.auto_exposure = True
        self.actual_width = target_width
        self.actual_height = target_height

        # Threading & Status State
        self.cap = None
        self.is_running = False
        self.thread = None
        self.lock = threading.Lock()
        self.latest_raw_frame = None
        self.latest_processed_frame = None
        self.last_frame_time = time.time()
        self.fps = 0.0
        self.frame_count = 0
        self.is_connected = False

        # Inisialisasi awal
        self.init_camera()

    def _resolve_backend(self):
        """Menentukan backend OpenCV yang optimal sesuai platform sistem operasi."""
        name = self.backend_name.upper()
        if name == "DSHOW":
            return cv2.CAP_DSHOW
        elif name == "V4L2":
            return cv2.CAP_V4L2
        elif name == "MSMF":
            return cv2.CAP_MSMF
        else:  # AUTO
            # Gunakan CAP_ANY agar Windows menggunakan native high-speed pipeline (15-30 FPS)
            # alih-alih DSHOW yang membatasi beberapa webcam laptop ke 4 FPS.
            return cv2.CAP_ANY

    def init_camera(self):
        """Membuka koneksi ke kamera dan menerapkan parameter awal."""
        with self.lock:
            if self.cap is not None:
                self.cap.release()

            backend = self._resolve_backend()
            logging.info(f"Membuka kamera index {self.camera_index} dengan backend: {backend}")
            
            try:
                self.cap = cv2.VideoCapture(self.camera_index, backend)
            except Exception as e:
                logging.warning(f"Gagal membuka dengan backend spesifik, mencoba default: {e}")
                self.cap = cv2.VideoCapture(self.camera_index)

            if not self.cap.isOpened():
                logging.error(f"Kamera index {self.camera_index} tidak dapat dibuka.")
                self.is_connected = False
                return False

            # Set buffer size ke 1 dan request FPS tinggi untuk latensi minimum
            try:
                self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
                self.cap.set(cv2.CAP_PROP_FPS, 30)
            except Exception:
                pass

            # Set resolusi ke hardware driver
            self._apply_hardware_resolution(self.target_width, self.target_height)
            self._apply_hardware_exposure(self.exposure_val, self.auto_exposure)
            self._apply_hardware_gain(self.iso_val)

            # Baca ukuran aktual dari driver kamera
            self.actual_width = int(self.cap.get(cv2.CAP_PROP_FRAME_WIDTH)) or self.target_width
            self.actual_height = int(self.cap.get(cv2.CAP_PROP_FRAME_HEIGHT)) or self.target_height
            self.is_connected = True
            logging.info(f"Kamera aktif. Resolusi aktual hardware: {self.actual_width}x{self.actual_height}")
            return True

    def _apply_hardware_resolution(self, width, height):
        """Mengatur resolusi ke driver hardware kamera."""
        if not self.cap or not self.cap.isOpened():
            return
        # Aktifkan MJPG jika didukung untuk throughput tinggi
        try:
            self.cap.set(cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*"MJPG"))
        except Exception:
            pass
        self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, width)
        self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, height)

    def _apply_hardware_exposure(self, exposure, auto_exp):
        """Mengatur exposure pada hardware kamera."""
        if not self.cap or not self.cap.isOpened():
            return
        try:
            if auto_exp:
                # DirectShow: 0.75 adalah Auto Exposure, V4L2: 3
                val = 0.75 if os.name == "nt" else 3
                self.cap.set(cv2.CAP_PROP_AUTO_EXPOSURE, val)
            else:
                # DirectShow: 0.25 adalah Manual Exposure, V4L2: 1
                val = 0.25 if os.name == "nt" else 1
                self.cap.set(cv2.CAP_PROP_AUTO_EXPOSURE, val)
                # Batasi hardware exposure ke maks -5.0 (~1/32 detik) agar framerate sensor tidak drop ke 1-2 FPS.
                # Kenaikan exposure di atas -5.0 ditangani secara digital dan real-time oleh software gain (0% lag).
                safe_hw_exp = min(-5.0, float(exposure))
                self.cap.set(cv2.CAP_PROP_EXPOSURE, safe_hw_exp)
        except Exception as e:
            logging.debug(f"Hardware exposure tidak didukung: {e}")

    def _apply_hardware_gain(self, iso):
        """Mengatur gain analog/digital pada hardware kamera."""
        if not self.cap or not self.cap.isOpened():
            return
        try:
            # Konversi nilai ISO (100-1600) ke skala gain (0-100)
            gain_val = (iso - 100) / 1500.0 * 100.0
            self.cap.set(cv2.CAP_PROP_GAIN, float(gain_val))
        except Exception as e:
            logging.debug(f"Hardware gain tidak didukung: {e}")

    def start(self, wait_first_frame=True, timeout=3.0):
        """Memulai thread pembacaan frame asinkron."""
        if self.is_running:
            return True
        if not self.is_connected:
            if not self.init_camera():
                return False

        self.is_running = True
        self.thread = threading.Thread(target=self._capture_loop, daemon=True)
        self.thread.start()
        logging.info("Camera capture loop thread dimulai.")

        if wait_first_frame:
            start_t = time.time()
            while time.time() - start_t < timeout:
                with self.lock:
                    if self.latest_processed_frame is not None:
                        return True
                time.sleep(0.05)
            logging.warning("Waktu tunggu frame pertama habis, namun thread tetap berjalan.")
        return True

    def stop(self):
        """Menghentikan thread pembacaan frame dan melepaskan sumber daya kamera."""
        self.is_running = False
        if self.thread and self.thread.is_alive():
            self.thread.join(timeout=1.0)
        with self.lock:
            if self.cap and self.cap.isOpened():
                self.cap.release()
            self.is_connected = False
        logging.info("Camera capture loop dihentikan dan kamera dilepaskan.")

    def reconnect(self):
        """Me-restart koneksi kamera secara aman."""
        self.stop()
        time.sleep(0.3)
        success = self.init_camera()
        if success:
            self.start(wait_first_frame=True)
        return success

    def switch_camera(self, new_index):
        """Beralih ke perangkat kamera lain secara dinamis."""
        if new_index == self.camera_index and self.is_connected:
            return True
        logging.info(f"Beralih dari Kamera {self.camera_index} ke Kamera {new_index}...")
        self.camera_index = new_index
        return self.reconnect()

    def _capture_loop(self):
        """Loop asinkron kontinu untuk mengambil frame dari kamera tanpa memblokir GUI."""
        fps_counter = 0
        fps_start_time = time.time()

        while self.is_running:
            try:
                if not self.cap or not self.cap.isOpened():
                    time.sleep(0.05)
                    continue

                ret, frame = self.cap.read()
                now = time.time()

                if not ret or frame is None:
                    time.sleep(0.01)
                    continue

                # Hitung real-time FPS
                fps_counter += 1
                elapsed = now - fps_start_time
                if elapsed >= 1.0:
                    self.fps = round(fps_counter / elapsed, 1)
                    fps_counter = 0
                    fps_start_time = now

                # Terapkan pengolahan parameter (Hardware / Software Fallback)
                processed_frame = self._process_frame_parameters(frame)

                with self.lock:
                    self.latest_raw_frame = frame
                    self.latest_processed_frame = processed_frame
                    self.last_frame_time = now
                    self.frame_count += 1

                time.sleep(0.002)
            except Exception as e:
                logging.error(f"Error dalam capture loop: {e}")
                time.sleep(0.05)

    def _process_frame_parameters(self, frame):
        """
        Menerapkan parameter pencahayaan (Exposure & ISO) hanya jika mode manual aktif,
        tanpa beban resize di capture thread agar FPS tetap maksimal.
        """
        if not self.auto_exposure:
            ev_diff = self.exposure_val - (-4)
            exposure_factor = 2.0 ** (ev_diff * 0.35)
            iso_factor = (self.iso_val / 100.0) ** 0.45
            total_gain = exposure_factor * iso_factor

            # Operasi C++ ultra cepat (<1ms)
            if abs(total_gain - 1.0) > 0.05:
                frame = cv2.convertScaleAbs(frame, alpha=total_gain, beta=0)

        return frame

    # --- Setter & Getter Parameter Kamera ---

    def set_resolution(self, width, height):
        """Mengatur resolusi target kamera."""
        with self.lock:
            self.target_width = int(width)
            self.target_height = int(height)
            self._apply_hardware_resolution(self.target_width, self.target_height)
            if self.cap and self.cap.isOpened():
                self.actual_width = int(self.cap.get(cv2.CAP_PROP_FRAME_WIDTH)) or self.target_width
                self.actual_height = int(self.cap.get(cv2.CAP_PROP_FRAME_HEIGHT)) or self.target_height
        logging.info(f"Resolusi diset ke: {self.target_width}x{self.target_height} (Hardware: {self.actual_width}x{self.actual_height})")

    def set_exposure(self, val):
        """Mengatur nilai Shutter Speed / Exposure (-13 s/d 0)."""
        with self.lock:
            self.exposure_val = int(val)
        self._apply_hardware_exposure(self.exposure_val, self.auto_exposure)
        logging.info(f"Exposure diset ke: {self.exposure_val}")

    def set_iso(self, val):
        """Mengatur nilai ISO / Gain (100 s/d 1600)."""
        with self.lock:
            self.iso_val = int(val)
        self._apply_hardware_gain(self.iso_val)
        logging.info(f"ISO diset ke: {self.iso_val}")

    def set_auto_exposure(self, enabled):
        """Mengaktifkan atau menonaktifkan Auto-Exposure."""
        with self.lock:
            self.auto_exposure = bool(enabled)
        self._apply_hardware_exposure(self.exposure_val, self.auto_exposure)
        logging.info(f"Auto Exposure: {'AKTIF' if self.auto_exposure else 'MANUAL'}")

    def get_latest_frame(self):
        """Mengembalikan frame terproses terbaru secara instan dan efisien (0.01ms)."""
        with self.lock:
            if self.latest_processed_frame is None:
                return False, None, {}
            
            # Subsample 1 pixel tiap 64 untuk deteksi layar gelap tanpa membebani CPU (0.001ms)
            is_black = (float(np.mean(self.latest_processed_frame[::64, ::64, 0])) < 0.5)

            meta = {
                "fps": self.fps,
                "frame_id": self.frame_count,
                "width": self.target_width,
                "height": self.target_height,
                "hardware_width": self.actual_width,
                "hardware_height": self.actual_height,
                "exposure": self.exposure_val,
                "iso": self.iso_val,
                "auto_exposure": self.auto_exposure,
                "is_connected": self.is_connected,
                "is_black_screen": is_black,
                "mean_brightness": round(float(np.mean(self.latest_processed_frame[::64, ::64, 0])), 1),
                "camera_index": self.camera_index,
                "timestamp": self.last_frame_time
            }
            return True, self.latest_processed_frame, meta

    def get_clean_capture_frame(self):
        """Mengembalikan frame berkualitas murni untuk disimpan ke media penyimpanan."""
        with self.lock:
            target_f = None
            if self.latest_processed_frame is not None:
                target_f = self.latest_processed_frame.copy()
            elif self.latest_raw_frame is not None:
                target_f = self.latest_raw_frame.copy()

            if target_f is not None:
                h, w = target_f.shape[:2]
                if (w, h) != (self.target_width, self.target_height):
                    target_f = cv2.resize(target_f, (self.target_width, self.target_height), interpolation=cv2.INTER_LINEAR)
                return True, target_f
            return False, None
