"""
App GUI Module
Antarmuka pengguna grafis (GUI) modern bernuansa Dark Studio berbasis Tkinter + ttk + Pillow.
Menampilkan live preview real-time dengan OSD, slider parameter kamera (Resolusi, Exposure, ISO),
tombol interaktif, live feedback banner, galeri thumbnail, dan integrasi penahanan tombol Burst Capture.
"""

import os
import sys
import time
import subprocess
import threading
import tkinter as tk
from tkinter import ttk, messagebox
import cv2
import numpy as np
from PIL import Image, ImageTk


class CameraAppGUI:
    """GUI Utama Sistem Kontrol Kamera & Akuisisi Gambar."""

    def __init__(self, root, camera_controller, input_manager, config):
        self.root = root
        self.cam = camera_controller
        self.im = input_manager
        self.config = config

        # Window Setup
        self.root.title("IoT Camera Studio - Embedded Control System")
        self.root.geometry("1240x780")
        self.root.minsize(1050, 680)
        self.root.configure(bg="#121418")

        # Storage directory
        self.save_dir = os.path.abspath(self.config.get("capture", {}).get("save_directory", "captures"))
        os.makedirs(self.save_dir, exist_ok=True)

        # State Variables
        self.burst_active = False
        self.burst_session_dir = ""
        self.burst_frame_counter = 0
        self.last_burst_capture_time = 0.0
        self.burst_fps = self.config.get("capture", {}).get("burst_max_fps", 15)
        self.burst_interval = 1.0 / self.burst_fps

        self.last_captured_image_path = None
        self.thumbnail_photo = None
        self.flash_banner_text = ""
        self.flash_banner_expiry = 0.0

        # High-Performance Canvas & Frame Sync State
        self.canvas_image_id = None
        self.last_rendered_frame_id = -1

        # Register Input Manager Callbacks
        self.im.register_callbacks(
            on_single=self.trigger_single_capture,
            on_burst_start=self.start_burst_session,
            on_burst_stop=self.stop_burst_session,
            on_reconnect=self.reconnect_camera,
            on_quit=self.on_close
        )
        self.im.attach_tk_bindings(self.root)

        # Build GUI Components
        self._setup_theme()
        self._build_header()
        self._build_main_layout()

        # Handle window close
        self.root.protocol("WM_DELETE_WINDOW", self.on_close)

        # Start Video Refresh Loop
        self._update_loop()

    def _setup_theme(self):
        """Mengatur styling kustom ttk untuk tampilan dark mode profesional."""
        style = ttk.Style()
        style.theme_use("clam")

        # Palette: Dark #121418, Card #1a1d24, Border #2d323f, Accent #00cec9, Red #ff4757
        style.configure(".", background="#121418", foreground="#f5f6fa", font=("Segoe UI", 9))
        style.configure("TFrame", background="#121418")
        style.configure("Card.TFrame", background="#1a1d24", relief="flat")
        style.configure("TLabel", background="#121418", foreground="#f5f6fa")
        style.configure("Card.TLabel", background="#1a1d24", foreground="#f5f6fa")
        style.configure("Header.TLabel", font=("Segoe UI", 12, "bold"), foreground="#00cec9", background="#1a1d24")
        style.configure("Muted.TLabel", foreground="#8395a7", background="#1a1d24", font=("Segoe UI", 8))
        style.configure("Badge.TLabel", font=("Segoe UI", 9, "bold"), foreground="#10ac84", background="#1a1d24")

        # Buttons
        style.configure("Primary.TButton", font=("Segoe UI", 10, "bold"), background="#0984e3", foreground="#ffffff", borderwidth=0)
        style.map("Primary.TButton", background=[("active", "#74b9ff")])
        style.configure("Danger.TButton", font=("Segoe UI", 10, "bold"), background="#d63031", foreground="#ffffff", borderwidth=0)
        style.map("Danger.TButton", background=[("active", "#ff7675")])
        style.configure("Secondary.TButton", font=("Segoe UI", 9), background="#2d3436", foreground="#dfe6e9", borderwidth=0)
        style.map("Secondary.TButton", background=[("active", "#636e72")])

        # Combobox
        style.configure("TCombobox", fieldbackground="#2d3436", background="#1a1d24", foreground="#f5f6fa", borderwidth=0)
        style.map("TCombobox", fieldbackground=[("readonly", "#2d3436")])

    def _build_header(self):
        """Header Bar dengan judul aplikasi, status koneksi, dan shortcut keyboard."""
        header_frame = tk.Frame(self.root, bg="#1a1d24", height=50)
        header_frame.pack(side=tk.TOP, fill=tk.X, padx=10, pady=(10, 5))

        # Title & Subtitle
        title_box = tk.Frame(header_frame, bg="#1a1d24")
        title_box.pack(side=tk.LEFT, padx=15, pady=8)
        lbl_title = tk.Label(title_box, text="⚡ IoT CAMERA CONTROL STUDIO", font=("Segoe UI", 13, "bold"), fg="#00cec9", bg="#1a1d24")
        lbl_title.pack(anchor="w")
        lbl_sub = tk.Label(title_box, text="OpenCV V4L2/DShow Acquisition Engine • Embedded Systems", font=("Segoe UI", 8), fg="#747d8c", bg="#1a1d24")
        lbl_sub.pack(anchor="w")

        # Quick Key Cheatsheet Badge
        keys_badge = tk.Label(
            header_frame,
            text="[SPACE] Single Capture  |  [Tahan B] Burst Mode  |  [R] Reconnect  |  [ESC] Exit",
            font=("Consolas", 8, "bold"),
            bg="#2f3542",
            fg="#eccc68",
            padx=12,
            pady=4,
            relief="flat"
        )
        keys_badge.pack(side=tk.RIGHT, padx=15, pady=10)

    def _build_main_layout(self):
        """Membagi area utama menjadi Viewport Preview di kiri dan Control Panel di kanan."""
        content = tk.Frame(self.root, bg="#121418")
        content.pack(side=tk.TOP, fill=tk.BOTH, expand=True, padx=10, pady=5)

        # Left Column: Video Viewport
        self.left_frame = tk.Frame(content, bg="#1a1d24", relief="flat")
        self.left_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=(0, 6), pady=0)

        self.canvas_width = 820
        self.canvas_height = 540
        self.preview_canvas = tk.Canvas(
            self.left_frame,
            bg="#0d0e11",
            highlightthickness=1,
            highlightbackground="#2d3436"
        )
        self.preview_canvas.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)

        # Bottom Telemetry HUD below canvas
        self.hud_frame = tk.Frame(self.left_frame, bg="#1a1d24")
        self.hud_frame.pack(side=tk.BOTTOM, fill=tk.X, padx=12, pady=(0, 8))

        self.lbl_hud_fps = tk.Label(self.hud_frame, text="FPS: --", font=("Consolas", 9, "bold"), fg="#2ed573", bg="#1a1d24")
        self.lbl_hud_fps.pack(side=tk.LEFT, padx=(0, 15))

        self.lbl_hud_res = tk.Label(self.hud_frame, text="Resolusi: --", font=("Consolas", 9), fg="#70a1ff", bg="#1a1d24")
        self.lbl_hud_res.pack(side=tk.LEFT, padx=10)

        self.lbl_hud_status = tk.Label(self.hud_frame, text="Status: IDLE", font=("Consolas", 9), fg="#eccc68", bg="#1a1d24")
        self.lbl_hud_status.pack(side=tk.LEFT, padx=10)

        self.lbl_hud_burst = tk.Label(self.hud_frame, text="", font=("Segoe UI", 9, "bold"), fg="#ff4757", bg="#1a1d24")
        self.lbl_hud_burst.pack(side=tk.RIGHT, padx=10)

        # Right Column: Control Sidebar
        self.right_frame = tk.Frame(content, bg="#1a1d24", width=340)
        self.right_frame.pack(side=tk.RIGHT, fill=tk.Y, padx=(6, 0), pady=0)
        self.right_frame.pack_propagate(False)

        self._build_sidebar_controls()

    def _build_sidebar_controls(self):
        """Membangun kontrol parameter kamera, tombol capture, dan thumbnail galeri."""
        container = tk.Frame(self.right_frame, bg="#1a1d24")
        container.pack(fill=tk.BOTH, expand=True, padx=14, pady=12)

        # --- SECTION 0: PILIH PERANGKAT KAMERA ---
        lbl_sec0 = tk.Label(container, text="0. PERANGKAT KAMERA", font=("Segoe UI", 10, "bold"), fg="#00cec9", bg="#1a1d24")
        lbl_sec0.pack(anchor="w", pady=(0, 4))

        from camera_controller import detect_available_cameras
        self.available_cams, best_idx = detect_available_cameras()
        self.cam_labels = [c["label"] for c in self.available_cams]
        self.cam_map = {c["label"]: c["index"] for c in self.available_cams}

        self.combo_cam = ttk.Combobox(container, values=self.cam_labels, state="readonly", font=("Segoe UI", 8))
        curr_idx = self.cam.camera_index
        default_cam_label = next((c["label"] for c in self.available_cams if c["index"] == curr_idx), (self.cam_labels[0] if self.cam_labels else ""))
        self.combo_cam.set(default_cam_label)
        self.combo_cam.pack(fill=tk.X, pady=(0, 8))
        self.combo_cam.bind("<<ComboboxSelected>>", self._on_camera_select)

        # Divider
        tk.Frame(container, bg="#2f3542", height=1).pack(fill=tk.X, pady=(0, 8))

        # --- SECTION 1: PARAMETER KAMERA ---
        lbl_sec1 = tk.Label(container, text="1. PARAMETER KAMERA", font=("Segoe UI", 10, "bold"), fg="#00cec9", bg="#1a1d24")
        lbl_sec1.pack(anchor="w", pady=(0, 6))

        # A. Resolusi
        lbl_res = tk.Label(container, text="a. Resolusi Pengambilan Gambar:", font=("Segoe UI", 9), fg="#dfe6e9", bg="#1a1d24")
        lbl_res.pack(anchor="w", pady=(4, 2))

        preset_list = self.config.get("camera", {}).get("resolution_presets", [])
        self.preset_labels = [p["label"] for p in preset_list]
        self.preset_map = {p["label"]: (p["width"], p["height"]) for p in preset_list}

        self.combo_res = ttk.Combobox(container, values=self.preset_labels, state="readonly", font=("Segoe UI", 9))
        default_res = self.config.get("camera", {}).get("default_resolution", {"width": 1280, "height": 720})
        default_label = next((p["label"] for p in preset_list if p["width"] == default_res["width"] and p["height"] == default_res["height"]), self.preset_labels[0])
        self.combo_res.set(default_label)
        self.combo_res.pack(fill=tk.X, pady=(0, 10))
        self.combo_res.bind("<<ComboboxSelected>>", self._on_resolution_change)

        # B. Shutter Speed / Exposure
        self.lbl_exp_title = tk.Label(container, text="b. Shutter Speed / Exposure: (AUTO)", font=("Segoe UI", 9), fg="#dfe6e9", bg="#1a1d24")
        self.lbl_exp_title.pack(anchor="w", pady=(2, 2))

        self.slider_exp = tk.Scale(
            container,
            from_=-13,
            to=0,
            orient=tk.HORIZONTAL,
            bg="#1a1d24",
            fg="#f5f6fa",
            highlightthickness=0,
            troughcolor="#2f3542",
            activebackground="#00cec9",
            state="disabled",
            command=self._on_exposure_change
        )
        self.slider_exp.set(self.config.get("camera", {}).get("exposure", {}).get("default", -4))
        self.slider_exp.pack(fill=tk.X, pady=(0, 10))

        # C. ISO / Sensor Gain
        self.lbl_iso_title = tk.Label(container, text="c. ISO / Sensor Gain: (AUTO)", font=("Segoe UI", 9), fg="#dfe6e9", bg="#1a1d24")
        self.lbl_iso_title.pack(anchor="w", pady=(2, 2))

        self.slider_iso = tk.Scale(
            container,
            from_=100,
            to=1600,
            resolution=100,
            orient=tk.HORIZONTAL,
            bg="#1a1d24",
            fg="#f5f6fa",
            highlightthickness=0,
            troughcolor="#2f3542",
            activebackground="#00cec9",
            state="disabled",
            command=self._on_iso_change
        )
        self.slider_iso.set(self.config.get("camera", {}).get("iso", {}).get("default", 100))
        self.slider_iso.pack(fill=tk.X, pady=(0, 8))

        # Auto Exposure Checkbox (ON by default)
        self.auto_exp_var = tk.BooleanVar(value=True)
        self.chk_auto_exp = tk.Checkbutton(
            container,
            text="Auto Exposure & Gain Mode",
            variable=self.auto_exp_var,
            font=("Segoe UI", 8),
            bg="#1a1d24",
            fg="#a4b0be",
            selectcolor="#2f3542",
            activebackground="#1a1d24",
            activeforeground="#f5f6fa",
            command=self._on_auto_exposure_toggle
        )
        self.chk_auto_exp.pack(anchor="w", pady=(0, 12))

        # Divider
        tk.Frame(container, bg="#2f3542", height=1).pack(fill=tk.X, pady=6)

        # --- SECTION 2: KONTROL CAPTURE ---
        lbl_sec2 = tk.Label(container, text="2. AKUISISI & KEY MAPPING", font=("Segoe UI", 10, "bold"), fg="#00cec9", bg="#1a1d24")
        lbl_sec2.pack(anchor="w", pady=(4, 6))

        # Single Capture Button
        self.btn_capture = tk.Button(
            container,
            text="📸 SINGLE CAPTURE  [SPACE / C]",
            font=("Segoe UI", 10, "bold"),
            bg="#0984e3",
            fg="#ffffff",
            activebackground="#74b9ff",
            activeforeground="#ffffff",
            relief="flat",
            padx=10,
            pady=7,
            cursor="hand2",
            command=self.trigger_single_capture
        )
        self.btn_capture.pack(fill=tk.X, pady=(2, 6))

        # Burst Capture Instruction Badge / Card
        self.burst_card = tk.Frame(container, bg="#2f3542", padx=8, pady=6)
        self.burst_card.pack(fill=tk.X, pady=(2, 10))

        self.lbl_burst_card_title = tk.Label(
            self.burst_card,
            text="⚡ BURST CAPTURE (Poin Tambahan)",
            font=("Segoe UI", 9, "bold"),
            fg="#eccc68",
            bg="#2f3542"
        )
        self.lbl_burst_card_title.pack(anchor="w")

        self.lbl_burst_card_desc = tk.Label(
            self.burst_card,
            text="TAHAN tombol keyboard [B] untuk menangkap gambar beruntun. Berhenti otomatis saat dilepas.",
            font=("Segoe UI", 8),
            fg="#dfe6e9",
            bg="#2f3542",
            wraplength=280,
            justify="left"
        )
        self.lbl_burst_card_desc.pack(anchor="w", pady=(2, 0))

        # Divider
        tk.Frame(container, bg="#2f3542", height=1).pack(fill=tk.X, pady=6)

        # --- SECTION 3: GALERI & PENYIMPANAN ---
        lbl_sec3 = tk.Label(container, text="3. HASIL TANGKAPAN TERAKHIR", font=("Segoe UI", 10, "bold"), fg="#00cec9", bg="#1a1d24")
        lbl_sec3.pack(anchor="w", pady=(4, 6))

        # Thumbnail Box
        self.thumb_canvas = tk.Canvas(container, width=280, height=140, bg="#0d0e11", highlightthickness=1, highlightbackground="#2f3542")
        self.thumb_canvas.pack(fill=tk.X, pady=(0, 4))
        self.thumb_canvas.create_text(140, 70, text="Belum ada foto yang diambil", fill="#57606f", font=("Segoe UI", 8))

        self.lbl_last_file = tk.Label(container, text="Folder: captures/", font=("Segoe UI", 8), fg="#747d8c", bg="#1a1d24")
        self.lbl_last_file.pack(anchor="w", pady=(0, 6))

        # Open Folder & Reconnect Buttons in one row
        btn_row = tk.Frame(container, bg="#1a1d24")
        btn_row.pack(fill=tk.X, pady=(2, 0))

        btn_open_folder = tk.Button(
            btn_row,
            text="📁 Buka Folder",
            font=("Segoe UI", 8, "bold"),
            bg="#2d3436",
            fg="#dfe6e9",
            activebackground="#636e72",
            relief="flat",
            padx=8,
            pady=4,
            cursor="hand2",
            command=self.open_capture_folder
        )
        btn_open_folder.pack(side=tk.LEFT, expand=True, fill=tk.X, padx=(0, 4))

        btn_reconnect = tk.Button(
            btn_row,
            text="🔄 Reconnect [R]",
            font=("Segoe UI", 8),
            bg="#2d3436",
            fg="#dfe6e9",
            activebackground="#636e72",
            relief="flat",
            padx=8,
            pady=4,
            cursor="hand2",
            command=self.reconnect_camera
        )
        btn_reconnect.pack(side=tk.RIGHT, expand=True, fill=tk.X, padx=(4, 0))

    # --- Video Loop & Rendering ---

    def _update_loop(self):
        """Loop non-blocking untuk mengambil frame terbaru dan me-render ke Canvas UI."""
        ret, frame, meta = self.cam.get_latest_frame()

        if ret and frame is not None:
            frame_id = meta.get("frame_id", 0)

            # Hanya render dan proses jika benar-benar ada frame baru dari kamera (Hemat 80% CPU)
            if frame_id != self.last_rendered_frame_id:
                self.last_rendered_frame_id = frame_id

                # 1. Update Telemetry HUD
                self.lbl_hud_fps.config(text=f"FPS: {meta.get('fps', 0.0):.1f}")
                self.lbl_hud_res.config(text=f"Resolusi: {meta.get('width', 0)}x{meta.get('height', 0)} (HW: {meta.get('hardware_width', 0)}x{meta.get('hardware_height', 0)})")

                # 2. Burst Capture Processing if currently holding down burst key
                if self.burst_active:
                    now = time.time()
                    if (now - self.last_burst_capture_time) >= self.burst_interval:
                        self._save_burst_frame()
                        self.last_burst_capture_time = now

                # 3. Gambar OSD (On-Screen Display) ke frame preview
                display_frame = frame.copy()
                self._render_osd(display_frame, meta)

                # 4. Render Frame ke Tkinter Canvas (Item Reuse bebas lag)
                self._draw_frame_to_canvas(display_frame)
        else:
            self.lbl_hud_status.config(text="Status: Menghubungkan Kamera...", fg="#ff4757")

        # Jadwalkan pengecekan berikutnya secara halus (10ms = ~100Hz responsive check)
        self.root.after(10, self._update_loop)

    def _render_osd(self, frame, meta):
        """Menggambar informasi OSD (On-Screen Display) langsung di atas frame preview."""
        h, w = frame.shape[:2]

        # A. Timestamp OSD di pojok kanan atas
        now_str = time.strftime("%Y-%m-%d %H:%M:%S")
        cv2.putText(frame, now_str, (w - 240, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 0), 3, cv2.LINE_AA)
        cv2.putText(frame, now_str, (w - 240, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 1, cv2.LINE_AA)

        # B. Parameter OSD di pojok kiri atas
        exp_txt = f"EXP: {meta.get('exposure', -5)} EV"
        iso_txt = f"ISO: {meta.get('iso', 100)}"
        cv2.putText(frame, f"{exp_txt}  {iso_txt}", (20, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 0), 3, cv2.LINE_AA)
        cv2.putText(frame, f"{exp_txt}  {iso_txt}", (20, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 206, 201), 1, cv2.LINE_AA)

        # C. Banner REC Berkedip jika Burst Active
        if self.burst_active:
            if int(time.time() * 3.3) % 2 == 0:
                cv2.circle(frame, (35, 70), 10, (0, 0, 255), -1)
                cv2.putText(frame, f"BURST REC [ {self.burst_frame_counter} FRAMES ]", (55, 76), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 0), 3, cv2.LINE_AA)
                cv2.putText(frame, f"BURST REC [ {self.burst_frame_counter} FRAMES ]", (55, 76), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2, cv2.LINE_AA)

        # D. Flash Capture Banner
        if time.time() < self.flash_banner_expiry:
            banner = self.flash_banner_text
            cv2.rectangle(frame, (10, h - 55), (w - 10, h - 15), (0, 168, 150), -1)
            cv2.putText(frame, banner, (25, h - 26), cv2.FONT_HERSHEY_SIMPLEX, 0.65, (255, 255, 255), 2, cv2.LINE_AA)

        # E. Peringatan Sinyal Visual Rendah / Kamera Gelap
        if meta.get("is_black_screen", False):
            box_w = min(680, w - 40)
            box_h = 100
            x1 = (w - box_w) // 2
            y1 = (h - box_h) // 2
            x2 = x1 + box_w
            y2 = y1 + box_h

            # Panel kartu modern elegan
            cv2.rectangle(frame, (x1, y1), (x2, y2), (24, 27, 34), -1)
            cv2.rectangle(frame, (x1, y1), (x2, y2), (70, 80, 95), 1)
            cv2.rectangle(frame, (x1, y1), (x1 + 6, y2), (0, 165, 255), -1)

            cv2.putText(frame, "[PERHATIAN] Sinyal Input Visual Rendah / Layar Gelap", (x1 + 22, y1 + 30),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.58, (235, 240, 245), 2, cv2.LINE_AA)
            cv2.putText(frame, "1. Pastikan penutup fisik kamera (privacy shutter) dalam posisi terbuka.", (x1 + 22, y1 + 58),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.48, (180, 190, 205), 1, cv2.LINE_AA)
            cv2.putText(frame, "2. Jika gambar tetap gelap, silakan ganti perangkat kamera pada menu di sebelah kanan.", (x1 + 22, y1 + 82),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.48, (230, 200, 100), 1, cv2.LINE_AA)

    def _draw_frame_to_canvas(self, bgr_frame):
        """Mengubah ukuran dan menampilkan frame OpenCV BGR ke Tkinter Canvas tanpa membuat ulang item canvas."""
        canvas_w = self.preview_canvas.winfo_width()
        canvas_h = self.preview_canvas.winfo_height()

        if canvas_w < 50 or canvas_h < 50:
            canvas_w = self.canvas_width
            canvas_h = self.canvas_height

        # Pertahankan aspect ratio frame
        fh, fw = bgr_frame.shape[:2]
        scale = min(canvas_w / fw, canvas_h / fh)
        new_w = max(1, int(fw * scale))
        new_h = max(1, int(fh * scale))

        resized_bgr = cv2.resize(bgr_frame, (new_w, new_h), interpolation=cv2.INTER_LINEAR)
        rgb_frame = cv2.cvtColor(resized_bgr, cv2.COLOR_BGR2RGB)

        img = Image.fromarray(rgb_frame)
        self.tk_image = ImageTk.PhotoImage(image=img)

        # REUSE Canvas Item (Kunci utama performa: tidak delete 'all' sehingga 0% lag)
        cx, cy = canvas_w // 2, canvas_h // 2
        if self.canvas_image_id is None:
            self.preview_canvas.delete("all")
            self.canvas_image_id = self.preview_canvas.create_image(cx, cy, anchor=tk.CENTER, image=self.tk_image)
        else:
            self.preview_canvas.itemconfig(self.canvas_image_id, image=self.tk_image)
            self.preview_canvas.coords(self.canvas_image_id, cx, cy)

    # --- Parameter Handlers ---

    def _on_camera_select(self, event=None):
        label = self.combo_cam.get()
        if label in self.cam_map:
            new_idx = self.cam_map[label]
            self._show_toast(f"Beralih ke Kamera {new_idx}...")
            self.lbl_hud_status.config(text=f"Status: Menghubungkan Kamera {new_idx}...", fg="#ff9f43")
            threading.Thread(target=self._async_switch_cam, args=(new_idx,), daemon=True).start()

    def _async_switch_cam(self, new_idx):
        success = self.cam.switch_camera(new_idx)
        if success:
            self.root.after(0, lambda: self._show_toast(f"Kamera {new_idx} terhubung & aktif!"))
            self.root.after(0, lambda: self.lbl_hud_status.config(text="Status: IDLE", fg="#eccc68"))
        else:
            self.root.after(0, lambda: self._show_toast(f"Gagal membuka Kamera {new_idx}!"))
            self.root.after(0, lambda: self.lbl_hud_status.config(text="Status: Gagal Terhubung", fg="#ff4757"))

    def _on_resolution_change(self, event=None):
        label = self.combo_res.get()
        if label in self.preset_map:
            w, h = self.preset_map[label]
            self.cam.set_resolution(w, h)
            self._show_toast(f"Resolusi diubah ke {w}x{h}")

    def _on_exposure_change(self, val):
        if not self.auto_exp_var.get():
            exp_val = int(val)
            self.cam.set_exposure(exp_val)
            self.lbl_exp_title.config(text=f"b. Shutter Speed / Exposure: ({exp_val} EV)")

    def _on_iso_change(self, val):
        if not self.auto_exp_var.get():
            iso_val = int(val)
            self.cam.set_iso(iso_val)
            self.lbl_iso_title.config(text=f"c. ISO / Sensor Gain: (ISO {iso_val})")

    def _on_auto_exposure_toggle(self):
        is_auto = self.auto_exp_var.get()
        self.cam.set_auto_exposure(is_auto)
        if is_auto:
            self.slider_exp.config(state="disabled")
            self.slider_iso.config(state="disabled")
            self.lbl_exp_title.config(text="b. Shutter Speed: (AUTO)")
            self.lbl_iso_title.config(text="c. ISO / Gain: (AUTO)")
        else:
            self.slider_exp.config(state="normal")
            self.slider_iso.config(state="normal")
            self.lbl_exp_title.config(text=f"b. Shutter Speed: ({self.slider_exp.get()} EV)")
            self.lbl_iso_title.config(text=f"c. ISO: (ISO {self.slider_iso.get()})")

    # --- Capture & Storage Actions ---

    def trigger_single_capture(self):
        """Menangkap dan menyimpan 1 frame foto murni (Single Capture)."""
        ret, frame = self.cam.get_clean_capture_frame()
        if not ret or frame is None:
            self._show_toast("Gagal menangkap frame!")
            return

        timestamp_str = time.strftime("%Y%m%d_%H%M%S")
        filename = f"single_{timestamp_str}_{int(time.time()*1000)%1000:03d}.jpg"
        filepath = os.path.join(self.save_dir, filename)

        # Simpan dalam thread terpisah agar I/O disk tidak membebani tampilan
        threading.Thread(target=self._write_image_to_disk, args=(filepath, frame, True)).start()

    def start_burst_session(self, session_id):
        """Memulai sesi penangkapan beruntun (Burst Capture)."""
        self.burst_active = True
        self.burst_frame_counter = 0
        self.last_burst_capture_time = 0.0
        
        # Buat subfolder khusus sesi burst
        self.burst_session_dir = os.path.join(self.save_dir, f"burst_{session_id}")
        os.makedirs(self.burst_session_dir, exist_ok=True)

        self.lbl_hud_status.config(text="Status: ● BURST ACTIVE", fg="#ff4757")
        self.lbl_hud_burst.config(text="● BURST CAPTURING...")
        self.burst_card.config(bg="#d63031")
        self.lbl_burst_card_title.config(bg="#d63031", text="● BURST RUNNING (TETAP TAHAN TOMBOL)")
        self.lbl_burst_card_desc.config(bg="#d63031")

    def _save_burst_frame(self):
        """Menyimpan satu frame dalam sesi burst capture yang sedang aktif."""
        ret, frame = self.cam.get_clean_capture_frame()
        if not ret or frame is None:
            return

        self.burst_frame_counter += 1
        self.im.burst_count = self.burst_frame_counter
        filename = f"frame_{self.burst_frame_counter:04d}.jpg"
        filepath = os.path.join(self.burst_session_dir, filename)

        # Simpan asinkron
        threading.Thread(target=self._write_image_to_disk, args=(filepath, frame, False)).start()
        self.lbl_hud_burst.config(text=f"● BURST: {self.burst_frame_counter} frames")

    def stop_burst_session(self, session_id, count):
        """Menghentikan sesi penangkapan beruntun saat tombol keyboard dilepas."""
        self.burst_active = False
        self.lbl_hud_status.config(text="Status: IDLE", fg="#eccc68")
        self.lbl_hud_burst.config(text="")
        self.burst_card.config(bg="#2f3542")
        self.lbl_burst_card_title.config(bg="#2f3542", text="⚡ BURST CAPTURE (Poin Tambahan)")
        self.lbl_burst_card_desc.config(bg="#2f3542")

        msg = f"Burst Selesai: {self.burst_frame_counter} foto tersimpan di burst_{session_id}/"
        self._show_toast(msg)

        # Update thumbnail ke frame terakhir burst
        if self.burst_frame_counter > 0:
            last_file = os.path.join(self.burst_session_dir, f"frame_{self.burst_frame_counter:04d}.jpg")
            self._update_thumbnail_preview(last_file)

    def _write_image_to_disk(self, filepath, frame, update_thumb=True):
        """Menyimpan file citra ke harddisk dengan kompresi JPEG kualitas tinggi."""
        try:
            cv2.imwrite(filepath, frame, [cv2.IMWRITE_JPEG_QUALITY, 95])
            if update_thumb:
                self.root.after(0, lambda: self._update_thumbnail_preview(filepath))
                self.root.after(0, lambda: self._show_toast(f"Tersimpan: {os.path.basename(filepath)}"))
        except Exception as e:
            print(f"[ERROR] Gagal menyimpan citra {filepath}: {e}")

    def _update_thumbnail_preview(self, image_path):
        """Memperbarui widget pratinjau thumbnail citra terakhir di sidebar."""
        if not os.path.exists(image_path):
            return
        try:
            img = Image.open(image_path)
            img.thumbnail((280, 140), Image.Resampling.LANCZOS)
            self.thumbnail_photo = ImageTk.PhotoImage(img)

            self.thumb_canvas.delete("all")
            self.thumb_canvas.create_image(140, 70, anchor=tk.CENTER, image=self.thumbnail_photo)
            self.lbl_last_file.config(text=f"Foto: {os.path.basename(image_path)}")
        except Exception as e:
            print(f"[ERROR] Gagal memuat thumbnail: {e}")

    def _show_toast(self, text, duration=2.5):
        """Menampilkan teks banner OSD sementara di bagian bawah video."""
        self.flash_banner_text = f"✔ {text}"
        self.flash_banner_expiry = time.time() + duration

    def open_capture_folder(self):
        """Membuka folder penyimpanan di File Explorer."""
        try:
            if os.name == "nt":
                os.startfile(self.save_dir)
            elif sys.platform == "darwin":
                subprocess.run(["open", self.save_dir])
            else:
                subprocess.run(["xdg-open", self.save_dir])
        except Exception as e:
            messagebox.showinfo("Folder Penyimpanan", f"Path: {self.save_dir}")

    def reconnect_camera(self):
        """Menghubungkan ulang kamera."""
        self._show_toast("Menghubungkan ulang kamera...")
        self.lbl_hud_status.config(text="Status: Reconnecting...", fg="#ff9f43")
        threading.Thread(target=self._async_reconnect).start()

    def _async_reconnect(self):
        success = self.cam.reconnect()
        if success:
            self.root.after(0, lambda: self._show_toast("Kamera berhasil terhubung kembali!"))
            self.root.after(0, lambda: self.lbl_hud_status.config(text="Status: IDLE", fg="#eccc68"))
        else:
            self.root.after(0, lambda: self._show_toast("Gagal menemukan kamera!"))
            self.root.after(0, lambda: self.lbl_hud_status.config(text="Status: Terputus", fg="#ff4757"))

    def on_close(self):
        """Penutupan aplikasi yang aman."""
        self.im.shutdown()
        self.cam.stop()
        self.root.destroy()
