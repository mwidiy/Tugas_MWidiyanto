"""
Input Manager Module
Menangani event penekanan tombol keyboard (Single Capture, Burst Capture hold-down detection,
dan tombol navigasi) dengan algoritma debouncing anti auto-repeat yang andal untuk Windows dan Linux.
"""

import time
import os
import sys
import threading
import logging

# Jika berjalan di Windows, gunakan Win32 GetAsyncKeyState untuk presisi physical key polling
IS_WINDOWS = (os.name == "nt")
if IS_WINDOWS:
    import ctypes


class InputManager:
    """Mengelola penekanan tombol keyboard dan mendeteksi status penahanan tombol (hold-down)."""

    def __init__(self, single_keys=("space", "c"), burst_key="b"):
        self.single_keys = [k.lower() for k in single_keys]
        self.burst_key = burst_key.lower()

        # Callbacks
        self.on_single_capture = None
        self.on_burst_start = None
        self.on_burst_tick = None
        self.on_burst_stop = None
        self.on_reconnect = None
        self.on_quit = None

        # State tracking
        self.is_bursting = False
        self.burst_count = 0
        self.burst_session_id = ""
        self.last_burst_press_time = 0.0
        self.watchdog_timer = None
        self.polling_thread = None
        self.is_running = True

        # Virtual Key Code Windows untuk tombol burst (default: B = 0x42, SPACE = 0x20)
        self.vk_burst_code = 0x42 if self.burst_key == "b" else 0x20

    def register_callbacks(self, on_single=None, on_burst_start=None, on_burst_tick=None,
                           on_burst_stop=None, on_reconnect=None, on_quit=None):
        """Mendaftarkan fungsi-fungsi callback untuk setiap aksi keyboard."""
        self.on_single_capture = on_single
        self.on_burst_start = on_burst_start
        self.on_burst_tick = on_burst_tick
        self.on_burst_stop = on_burst_stop
        self.on_reconnect = on_reconnect
        self.on_quit = on_quit

    def attach_tk_bindings(self, root):
        """Menghubungkan event handler keyboard ke window Tkinter."""
        root.bind("<KeyPress>", self._on_tk_key_press)
        root.bind("<KeyRelease>", self._on_tk_key_release)

    def _on_tk_key_press(self, event):
        """Menangani event tombol ditekan (KeyPress)."""
        key = event.keysym.lower()

        # 1. Single Capture
        if key in self.single_keys:
            if not self.is_bursting:
                logging.info(f"Tombol Single Capture [{key.upper()}] ditekan.")
                if self.on_single_capture:
                    self.on_single_capture()
            return

        # 2. Burst Capture (Start or Continue Hold)
        if key == self.burst_key:
            now = time.time()
            self.last_burst_press_time = now

            if not self.is_bursting:
                self.is_bursting = True
                self.burst_count = 0
                self.burst_session_id = time.strftime("%Y%m%d_%H%M%S")
                logging.info(f"Mode BURST dimulai (Tombol [{self.burst_key.upper()}] ditahan)...")
                if self.on_burst_start:
                    self.on_burst_start(self.burst_session_id)

                # Jalankan thread pemantauan pelepasan fisik tombol jika di Windows
                if IS_WINDOWS:
                    self._start_windows_key_poller()

            # Reset timer watchdog (pembatalan timeout release)
            if self.watchdog_timer:
                self.watchdog_timer.cancel()
                self.watchdog_timer = None
            return

        # 3. Reconnect
        if key == "r":
            logging.info("Tombol Reconnect [R] ditekan.")
            if self.on_reconnect:
                self.on_reconnect()
            return

        # 4. Quit
        if key in ("escape", "q"):
            logging.info("Tombol Keluar [ESC/Q] ditekan.")
            if self.on_quit:
                self.on_quit()
            return

    def _on_tk_key_release(self, event):
        """
        Menangani event tombol dilepas (KeyRelease).
        Menggunakan sistem debounce watchdog 120ms agar auto-repeat OS tidak mematikan burst prematurely.
        """
        key = event.keysym.lower()
        if key == self.burst_key and self.is_bursting:
            # Di Windows, verifikasi langsung apakah tombol fisik memang sudah dilepas
            if IS_WINDOWS:
                state = ctypes.windll.user32.GetAsyncKeyState(self.vk_burst_code)
                is_physically_down = bool(state & 0x8000)
                if not is_physically_down:
                    self._trigger_burst_stop()
                    return

            # Multiplatform Debounce Watchdog: tunggu 130ms. Jika tidak ada KeyPress baru, berarti tombol dilepas.
            if self.watchdog_timer:
                self.watchdog_timer.cancel()
            self.watchdog_timer = threading.Timer(0.13, self._check_burst_timeout)
            self.watchdog_timer.daemon = True
            self.watchdog_timer.start()

    def _check_burst_timeout(self):
        """Watchdog timer untuk memverifikasi apakah penahanan tombol telah berakhir."""
        elapsed = time.time() - self.last_burst_press_time
        if elapsed >= 0.12 and self.is_bursting:
            self._trigger_burst_stop()

    def _start_windows_key_poller(self):
        """Thread latar belakang untuk memeriksa physical key release secara akurat pada OS Windows."""
        def poll_loop():
            while self.is_bursting and self.is_running:
                state = ctypes.windll.user32.GetAsyncKeyState(self.vk_burst_code)
                is_down = bool(state & 0x8000)
                if not is_down:
                    self._trigger_burst_stop()
                    break
                time.sleep(0.03)

        thread = threading.Thread(target=poll_loop, daemon=True)
        thread.start()

    def _trigger_burst_stop(self):
        """Menghentikan sesi burst capture dan memicu callback on_burst_stop."""
        if not self.is_bursting:
            return
        self.is_bursting = False
        if self.watchdog_timer:
            self.watchdog_timer.cancel()
            self.watchdog_timer = None
        logging.info(f"Mode BURST dihentikan. Total frame berhasil ditangkap: {self.burst_count}")
        if self.on_burst_stop:
            self.on_burst_stop(self.burst_session_id, self.burst_count)

    def shutdown(self):
        """Membersihkan thread dan timer saat aplikasi ditutup."""
        self.is_running = False
        if self.watchdog_timer:
            self.watchdog_timer.cancel()
        self.is_bursting = False
