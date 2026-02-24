<div align="center">
  <img src="public/apple-touch-icon.png" alt="Solara Logo" width="120" />
  <h1>☀️ Solara</h1>
  <p><strong>Aplikasi Dashboard Produktivitas & Manajemen Waktu Modern Berbasis Web</strong></p>
  
  [![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
  [![Filament](https://img.shields.io/badge/Filament-3.x-F59E0B?style=for-the-badge&logo=laravel)](https://filamentphp.com)
  [![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php)](https://php.net)
  [![PWA](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa)](https://web.dev/progressive-web-apps/)
</div>

---

## ✨ Tentang Solara

**Solara** adalah aplikasi *all-in-one* yang dirancang untuk membantu Anda mengatur keseharian dengan lebih produktif, terstruktur, dan seimbang. Menyatukan kalender akademik, pelacakan keuangan, tugas, hingga pengingat jadwal ibadah di satu tempat—dengan antarmuka mode gelap (Dark Mode) yang elegan dan modern.

## 🚀 Fitur Utama

- **🕌 Jadwal Ibadah Dinamis:** Auto-deteksi jadwal sholat & waktu Imsak berdasarkan pencarian satelit (Integrasi *Open-Meteo* & *Aladhan API*).
- **⛅ Panel Cuaca Realtime:** Pantau suhu & kondisi cuaca sebelum memulai hari Anda.
- **📚 Akademik (Jadwal Kelas & Tugas):** Manajemen sinkronisasi pengingat deadine tugas & jam kuliah dengan rapi.
- **✅ Task & Habit Tracker:** Sistem Todo (Board Kanban/List) dan pembentukan kebiasaan baru yang disiplin.
- **💰 Manajemen Keuangan:** Lacak arus masuk, keluar, dan target saldo tabungan bulanan.
- **🎯 Goals & Milestones:** Pecah target besar menjadi langkah-langkah kecil untuk menjaga persistensi.
- **📱 PWA (Progressive Web App):** Bisa langsung di-*Install* sebagai aplikasi native di HP Android, iOS, maupun Laptop (Windows/Mac) tanpa perlu masuk Google Play Store.
- **🔔 Notifikasi Multi-Channel:** Terima alarm & notifikasi otomatis (jadwal sholat/deadline tugas) langsung ke **aplikasi Telegram** Anda, maupun Web-Push Notification.
- **🌍 Multi-Bahasa:** Pilih antarmuka dalam bahasa Indonesia atau Inggris kapan saja dari pengaturan profil.

## 🛠️ Tech Stack

- **Framework Backend:** Laravel 12
- **Admin Panel / UI:** Filament v3 (TALL Stack: TailwindCSS, Alpine.js, Laravel, Livewire)
- **Database:** MariaDB / MySQL
- **Assets / Bundler:** Vite & Node.js
- **Integrasi Eksternal:** Telegram Bot API

---

## ⚙️ Cara Instalasi (Local Development)

Ikuti langkah-langkah ini untuk menjalankan Solara di komputer lokal / localhost Anda.

### Persyaratan Sistem:
- PHP 8.4+
- Composer
- Node.js (v18+) & NPM
- Database (MySQL / MariaDB / SQLite)

### Langkah Pemasangan:

1. **Clone repositori ini:**
   ```bash
   git clone https://github.com/syhrxul/Solara.git
   cd Solara
   ```

2. **Install dependensi PHP & Javascript:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Siapkan konfigurasi `.env`:**
   ```bash
   cp .env.example .env
   ```
   > Buka file `.env`, lalu atur nama koneksi database Anda (seperti `DB_DATABASE`, `DB_USERNAME`, dll).

4. **Generate Key Aplikasi:**
   ```bash
   php artisan key:generate
   ```

5. **Jalankan Migrasi Database:**
   ```bash
   php artisan migrate
   ```

6. **Buat User Akun Pertama Anda:**
   ```bash
   php artisan make:filament-user
   ```

7. **Tautkan Storage:**
   ```bash
   php artisan storage:link
   ```

8. **Nyalakan Server:**
   ```bash
   php artisan serve
   ```
   *Atau jika menggunakan Laravel Herd/Valet, akses langsung URL lokal Anda (cth: `http://solara.test/app`).*

---

## 📡 Notifikasi Telegram (Opsional)

Jika Anda ingin Solara mengirimi Anda pesan (bot), masukkan token bot API Anda pada file `.env`:
```env
TELEGRAM_BOT_TOKEN="123456789:ABCDefghIJKlmnOPQRstuvwxyz"
```
Anda bisa mendapatkan ID Chat Anda dengan masuk ke pengaturan *Dashboard Solara > Telegram Settings* setelah melakukan verifikasi token bot Anda melalui Telegram App.

---

## 📜 Lisensi

Aplikasi ini dikembangkan untuk penggunaan pribadi dan edukasi. Anda bebas memodifikasi '*source-code*' nya sesuai kebutuhan Anda. Open-sourced under the [MIT license](https://opensource.org/licenses/MIT).

<p align="center">Made with ❤️ for Better Productivity</p>
