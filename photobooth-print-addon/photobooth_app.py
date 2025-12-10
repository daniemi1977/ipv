#!/usr/bin/env python3
"""
PhotoBooth Printer App - Complete Photo Booth with Integrated Printing
A simple photo booth application with direct printing after capture
"""

import os
import sys
import cv2
import json
import time
import subprocess
import threading
from datetime import datetime
from pathlib import Path
import tkinter as tk
from tkinter import ttk, messagebox, simpledialog
from PIL import Image, ImageTk

class PhotoBoothConfig:
    """Configuration manager"""

    DEFAULT_CONFIG = {
        'printer_name': None,
        'copies': 1,
        'paper_size': 'Letter',
        'orientation': 'portrait',
        'color_mode': 'color',
        'save_path': '~/Pictures/PhotoBooth Prints',
        'auto_print': True,
        'countdown_seconds': 3,
        'camera_index': 0,
        'photo_width': 1280,
        'photo_height': 720
    }

    def __init__(self, config_path='~/.photobooth-app-config.json'):
        self.config_path = os.path.expanduser(config_path)
        self.config = self.load()

    def load(self):
        if os.path.exists(self.config_path):
            try:
                with open(self.config_path, 'r') as f:
                    config = json.load(f)
                    return {**self.DEFAULT_CONFIG, **config}
            except:
                return self.DEFAULT_CONFIG.copy()
        return self.DEFAULT_CONFIG.copy()

    def save(self):
        try:
            with open(self.config_path, 'w') as f:
                json.dump(self.config, f, indent=4)
        except Exception as e:
            print(f"Error saving config: {e}")

    def get(self, key, default=None):
        return self.config.get(key, default)

    def set(self, key, value):
        self.config[key] = value
        self.save()


class PrinterManager:
    """Manages printing operations"""

    @staticmethod
    def get_printers():
        try:
            result = subprocess.run(['lpstat', '-p', '-d'], capture_output=True, text=True)
            printers = []
            default = None

            for line in result.stdout.split('\n'):
                if line.startswith('printer'):
                    printers.append(line.split()[1])
                elif line.startswith('system default destination:'):
                    default = line.split(':')[1].strip()

            return printers, default
        except:
            return [], None

    @staticmethod
    def print_photo(photo_path, config):
        """Print a photo with configuration"""
        try:
            cmd = ['lp']

            printer = config.get('printer_name')
            if printer:
                cmd.extend(['-d', printer])

            copies = config.get('copies', 1)
            cmd.extend(['-n', str(copies)])

            options = []

            orientation = config.get('orientation', 'portrait')
            if orientation == 'landscape':
                options.append('orientation-requested=4')
            else:
                options.append('orientation-requested=3')

            if config.get('color_mode', 'color') == 'monochrome':
                options.append('ColorModel=Gray')

            options.append('fit-to-page')
            options.append(f'media={config.get("paper_size", "Letter")}')

            if options:
                cmd.extend(['-o', ','.join(options)])

            cmd.append(photo_path)

            result = subprocess.run(cmd, capture_output=True, text=True)
            return result.returncode == 0
        except Exception as e:
            print(f"Print error: {e}")
            return False


class PhotoBoothApp:
    """Main Photo Booth Application"""

    def __init__(self):
        self.config = PhotoBoothConfig()
        self.printer = PrinterManager()

        # Create save directory
        self.save_path = os.path.expanduser(self.config.get('save_path'))
        os.makedirs(self.save_path, exist_ok=True)

        # Camera
        self.camera = None
        self.camera_running = False

        # UI
        self.root = tk.Tk()
        self.root.title("📸 PhotoBooth Printer")
        self.root.geometry("1000x700")
        self.root.configure(bg='#2c3e50')

        # Variables
        self.countdown_running = False
        self.last_photo = None

        self.setup_ui()
        self.start_camera()

    def setup_ui(self):
        """Setup user interface"""

        # Title
        title_frame = tk.Frame(self.root, bg='#34495e', height=60)
        title_frame.pack(fill=tk.X)
        title_frame.pack_propagate(False)

        title_label = tk.Label(
            title_frame,
            text="📸 PhotoBooth Printer",
            font=('Arial', 24, 'bold'),
            bg='#34495e',
            fg='white'
        )
        title_label.pack(pady=15)

        # Main content
        content_frame = tk.Frame(self.root, bg='#2c3e50')
        content_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        # Camera preview (left side)
        preview_frame = tk.Frame(content_frame, bg='#34495e', relief=tk.RIDGE, bd=3)
        preview_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=(0, 10))

        self.preview_label = tk.Label(preview_frame, bg='black')
        self.preview_label.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)

        # Countdown overlay
        self.countdown_label = tk.Label(
            preview_frame,
            text="",
            font=('Arial', 72, 'bold'),
            bg='black',
            fg='#e74c3c'
        )

        # Control panel (right side)
        control_frame = tk.Frame(content_frame, bg='#34495e', width=280)
        control_frame.pack(side=tk.RIGHT, fill=tk.Y)
        control_frame.pack_propagate(False)

        # Buttons
        button_style = {
            'font': ('Arial', 14, 'bold'),
            'relief': tk.RAISED,
            'bd': 3,
            'cursor': 'hand2'
        }

        # Capture button
        self.capture_btn = tk.Button(
            control_frame,
            text="📷 Scatta Foto",
            command=self.capture_photo,
            bg='#3498db',
            fg='white',
            height=2,
            **button_style
        )
        self.capture_btn.pack(fill=tk.X, padx=10, pady=(20, 10))

        # Capture and Print button
        self.capture_print_btn = tk.Button(
            control_frame,
            text="📸 Scatta e Stampa",
            command=self.capture_and_print,
            bg='#2ecc71',
            fg='white',
            height=2,
            **button_style
        )
        self.capture_print_btn.pack(fill=tk.X, padx=10, pady=10)

        # Print last button
        self.print_last_btn = tk.Button(
            control_frame,
            text="🖨️ Stampa Ultima",
            command=self.print_last_photo,
            bg='#9b59b6',
            fg='white',
            **button_style
        )
        self.print_last_btn.pack(fill=tk.X, padx=10, pady=10)
        self.print_last_btn.config(state=tk.DISABLED)

        # Separator
        separator = ttk.Separator(control_frame, orient='horizontal')
        separator.pack(fill=tk.X, padx=10, pady=20)

        # Settings button
        settings_btn = tk.Button(
            control_frame,
            text="⚙️ Impostazioni",
            command=self.open_settings,
            bg='#95a5a6',
            fg='white',
            **button_style
        )
        settings_btn.pack(fill=tk.X, padx=10, pady=10)

        # Info panel
        info_frame = tk.LabelFrame(
            control_frame,
            text="Info",
            bg='#34495e',
            fg='white',
            font=('Arial', 10, 'bold')
        )
        info_frame.pack(fill=tk.X, padx=10, pady=20)

        printer = self.config.get('printer_name') or 'Default'
        auto_print = "✓ SI" if self.config.get('auto_print') else "✗ NO"

        info_text = f"""
Stampante: {printer}
Copie: {self.config.get('copies', 1)}
Auto-stampa: {auto_print}

Foto salvate in:
{self.save_path}
        """

        info_label = tk.Label(
            info_frame,
            text=info_text.strip(),
            bg='#34495e',
            fg='white',
            justify=tk.LEFT,
            font=('Arial', 9)
        )
        info_label.pack(padx=10, pady=10)

        # Status bar
        self.status_var = tk.StringVar(value="✓ Pronto")
        status_bar = tk.Label(
            self.root,
            textvariable=self.status_var,
            bg='#34495e',
            fg='white',
            font=('Arial', 10),
            anchor=tk.W,
            relief=tk.SUNKEN,
            bd=1
        )
        status_bar.pack(side=tk.BOTTOM, fill=tk.X)

    def start_camera(self):
        """Start camera capture"""
        camera_index = self.config.get('camera_index', 0)
        self.camera = cv2.VideoCapture(camera_index)

        if not self.camera.isOpened():
            messagebox.showerror(
                "Errore Camera",
                f"Impossibile aprire la camera {camera_index}.\n"
                "Controlla che sia collegata e non in uso."
            )
            return

        # Set resolution
        width = self.config.get('photo_width', 1280)
        height = self.config.get('photo_height', 720)
        self.camera.set(cv2.CAP_PROP_FRAME_WIDTH, width)
        self.camera.set(cv2.CAP_PROP_FRAME_HEIGHT, height)

        self.camera_running = True
        self.update_preview()

    def update_preview(self):
        """Update camera preview"""
        if not self.camera_running or not self.camera.isOpened():
            return

        ret, frame = self.camera.read()
        if ret:
            # Flip horizontally (mirror effect)
            frame = cv2.flip(frame, 1)

            # Convert BGR to RGB
            frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

            # Convert to PIL Image
            img = Image.fromarray(frame_rgb)

            # Resize to fit preview
            preview_width = self.preview_label.winfo_width()
            preview_height = self.preview_label.winfo_height()

            if preview_width > 1 and preview_height > 1:
                img.thumbnail((preview_width, preview_height), Image.Resampling.LANCZOS)

            # Convert to PhotoImage
            photo = ImageTk.PhotoImage(image=img)

            # Update label
            self.preview_label.configure(image=photo)
            self.preview_label.image = photo

        # Schedule next update
        if self.camera_running:
            self.root.after(30, self.update_preview)

    def capture_photo(self, auto_print=False):
        """Capture photo with countdown"""
        if self.countdown_running:
            return

        countdown_seconds = self.config.get('countdown_seconds', 3)

        if countdown_seconds > 0:
            self.run_countdown(countdown_seconds, auto_print)
        else:
            self.take_photo(auto_print)

    def run_countdown(self, seconds, auto_print):
        """Run countdown before capture"""
        self.countdown_running = True
        self.countdown_label.place(relx=0.5, rely=0.5, anchor=tk.CENTER)

        def countdown(count):
            if count > 0:
                self.countdown_label.config(text=str(count))
                self.root.after(1000, lambda: countdown(count - 1))
            else:
                self.countdown_label.config(text="📸")
                self.root.after(200, lambda: self.finish_countdown(auto_print))

        countdown(seconds)

    def finish_countdown(self, auto_print):
        """Finish countdown and take photo"""
        self.countdown_label.place_forget()
        self.countdown_running = False
        self.take_photo(auto_print)

    def take_photo(self, auto_print=False):
        """Take the actual photo"""
        if not self.camera.isOpened():
            messagebox.showerror("Errore", "Camera non disponibile")
            return

        # Capture frame
        ret, frame = self.camera.read()
        if not ret:
            messagebox.showerror("Errore", "Impossibile catturare foto")
            return

        # Flip horizontally
        frame = cv2.flip(frame, 1)

        # Generate filename
        timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
        filename = f"Photo_{timestamp}.jpg"
        filepath = os.path.join(self.save_path, filename)

        # Save photo
        cv2.imwrite(filepath, frame)
        self.last_photo = filepath

        # Enable print last button
        self.print_last_btn.config(state=tk.NORMAL)

        # Status update
        self.status_var.set(f"✓ Foto salvata: {filename}")

        # Flash effect
        self.preview_label.config(bg='white')
        self.root.after(100, lambda: self.preview_label.config(bg='black'))

        # Auto print if requested
        if auto_print or self.config.get('auto_print'):
            self.root.after(500, lambda: self.print_photo(filepath))

    def capture_and_print(self):
        """Capture photo and print immediately"""
        self.capture_photo(auto_print=True)

    def print_photo(self, filepath):
        """Print a photo"""
        self.status_var.set("🖨️ Stampa in corso...")

        # Print in background thread
        def print_thread():
            success = self.printer.print_photo(filepath, self.config.config)

            # Update UI in main thread
            self.root.after(0, lambda: self.print_completed(success, filepath))

        threading.Thread(target=print_thread, daemon=True).start()

    def print_completed(self, success, filepath):
        """Called when printing completes"""
        if success:
            self.status_var.set("✓ Foto stampata con successo!")
            messagebox.showinfo("Stampa", "Foto inviata alla stampante!")
        else:
            self.status_var.set("✗ Errore di stampa")
            messagebox.showerror("Errore", "Impossibile stampare la foto.\nControlla la stampante.")

    def print_last_photo(self):
        """Print the last captured photo"""
        if self.last_photo and os.path.exists(self.last_photo):
            self.print_photo(self.last_photo)
        else:
            messagebox.showwarning("Attenzione", "Nessuna foto da stampare")

    def open_settings(self):
        """Open settings dialog"""
        settings_window = tk.Toplevel(self.root)
        settings_window.title("⚙️ Impostazioni")
        settings_window.geometry("500x600")
        settings_window.configure(bg='#ecf0f1')
        settings_window.transient(self.root)
        settings_window.grab_set()

        # Title
        title = tk.Label(
            settings_window,
            text="Impostazioni PhotoBooth",
            font=('Arial', 16, 'bold'),
            bg='#ecf0f1'
        )
        title.pack(pady=20)

        # Settings frame
        frame = tk.Frame(settings_window, bg='#ecf0f1')
        frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=10)

        # Printer selection
        tk.Label(frame, text="Stampante:", bg='#ecf0f1', font=('Arial', 11, 'bold')).grid(
            row=0, column=0, sticky=tk.W, pady=5
        )

        printers, default = self.printer.get_printers()
        printer_var = tk.StringVar(value=self.config.get('printer_name') or default or 'Default')

        printer_combo = ttk.Combobox(
            frame,
            textvariable=printer_var,
            values=['Default'] + printers,
            state='readonly',
            width=30
        )
        printer_combo.grid(row=0, column=1, pady=5, padx=10)

        # Copies
        tk.Label(frame, text="Copie:", bg='#ecf0f1', font=('Arial', 11, 'bold')).grid(
            row=1, column=0, sticky=tk.W, pady=5
        )

        copies_var = tk.IntVar(value=self.config.get('copies', 1))
        copies_spin = tk.Spinbox(frame, from_=1, to=10, textvariable=copies_var, width=30)
        copies_spin.grid(row=1, column=1, pady=5, padx=10)

        # Paper size
        tk.Label(frame, text="Formato Carta:", bg='#ecf0f1', font=('Arial', 11, 'bold')).grid(
            row=2, column=0, sticky=tk.W, pady=5
        )

        paper_var = tk.StringVar(value=self.config.get('paper_size', 'Letter'))
        paper_combo = ttk.Combobox(
            frame,
            textvariable=paper_var,
            values=['Letter', 'A4', '4x6', '5x7', '8x10'],
            state='readonly',
            width=30
        )
        paper_combo.grid(row=2, column=1, pady=5, padx=10)

        # Orientation
        tk.Label(frame, text="Orientamento:", bg='#ecf0f1', font=('Arial', 11, 'bold')).grid(
            row=3, column=0, sticky=tk.W, pady=5
        )

        orientation_var = tk.StringVar(value=self.config.get('orientation', 'portrait'))
        orientation_combo = ttk.Combobox(
            frame,
            textvariable=orientation_var,
            values=['portrait', 'landscape'],
            state='readonly',
            width=30
        )
        orientation_combo.grid(row=3, column=1, pady=5, padx=10)

        # Color mode
        tk.Label(frame, text="Colore:", bg='#ecf0f1', font=('Arial', 11, 'bold')).grid(
            row=4, column=0, sticky=tk.W, pady=5
        )

        color_var = tk.StringVar(value=self.config.get('color_mode', 'color'))
        color_combo = ttk.Combobox(
            frame,
            textvariable=color_var,
            values=['color', 'monochrome'],
            state='readonly',
            width=30
        )
        color_combo.grid(row=4, column=1, pady=5, padx=10)

        # Auto print
        auto_print_var = tk.BooleanVar(value=self.config.get('auto_print', True))
        auto_print_check = tk.Checkbutton(
            frame,
            text="Stampa automatica dopo lo scatto",
            variable=auto_print_var,
            bg='#ecf0f1',
            font=('Arial', 11)
        )
        auto_print_check.grid(row=5, column=0, columnspan=2, sticky=tk.W, pady=10)

        # Countdown
        tk.Label(frame, text="Countdown (secondi):", bg='#ecf0f1', font=('Arial', 11, 'bold')).grid(
            row=6, column=0, sticky=tk.W, pady=5
        )

        countdown_var = tk.IntVar(value=self.config.get('countdown_seconds', 3))
        countdown_spin = tk.Spinbox(frame, from_=0, to=10, textvariable=countdown_var, width=30)
        countdown_spin.grid(row=6, column=1, pady=5, padx=10)

        # Save button
        def save_settings():
            printer_name = printer_var.get()
            if printer_name == 'Default':
                printer_name = None

            self.config.set('printer_name', printer_name)
            self.config.set('copies', copies_var.get())
            self.config.set('paper_size', paper_var.get())
            self.config.set('orientation', orientation_var.get())
            self.config.set('color_mode', color_var.get())
            self.config.set('auto_print', auto_print_var.get())
            self.config.set('countdown_seconds', countdown_var.get())

            messagebox.showinfo("Impostazioni", "Impostazioni salvate con successo!")
            settings_window.destroy()

            # Refresh UI
            self.setup_ui()

        save_btn = tk.Button(
            settings_window,
            text="💾 Salva Impostazioni",
            command=save_settings,
            bg='#2ecc71',
            fg='white',
            font=('Arial', 12, 'bold'),
            cursor='hand2',
            relief=tk.RAISED,
            bd=3
        )
        save_btn.pack(pady=20)

    def run(self):
        """Run the application"""
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)
        self.root.mainloop()

    def on_closing(self):
        """Handle window closing"""
        self.camera_running = False
        if self.camera:
            self.camera.release()
        self.root.destroy()


def main():
    """Main entry point"""

    # Check dependencies
    try:
        import cv2
        import PIL
    except ImportError:
        print("❌ Dipendenze mancanti!")
        print("\nInstalla con:")
        print("  pip3 install opencv-python pillow")
        sys.exit(1)

    # Run app
    app = PhotoBoothApp()
    app.run()


if __name__ == '__main__':
    main()
