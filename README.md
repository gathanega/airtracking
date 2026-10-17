# AirTracking

Sistem pemantauan kualitas air berbasis IoT. Alat portable berbasis ESP32 mengukur **pH, EC, TDS, DO (oksigen terlarut), suhu, dan kekeruhan**, lalu mengirim datanya ke server lewat HTTP. Server menyimpan data, menilai statusnya terhadap rentang normal (baku mutu), dan menampilkannya di dashboard web bertema putih-biru.

Backend dan dashboard berada dalam **satu proyek Laravel**: web publik tanpa login, panel admin dengan login, API JSON, dan penerima data dari alat.

## Fitur

- **Dashboard publik** tanpa login: ringkasan, kartu per alat, kartu parameter dengan sparkline, grafik tren 7/14/30 hari, prediksi kualitas air, riwayat, dan unduh CSV.
- **Peta lokasi** (Leaflet + OpenStreetMap): titik alat berwarna sesuai status.
- **Panel admin** (login): registrasi alat, ganti credential, hapus alat, tandai titik di peta, panduan setup WiFi alat, dan ubah rentang normal.
- **Penerima data** `POST /api/ingest`: memvalidasi ID alat dan credential (hash SHA-256) sebelum menyimpan, mendukung kiriman banyak data dari SD card dalam satu paket.
- **Status per parameter**: Normal, Waspada, Di luar rentang. Alat dengan parameter di luar rentang ditandai **Perlu perhatian**.
- **API JSON publik** (hanya baca) untuk semua data yang tampil di dashboard.

Panduan lengkap setiap halaman ada di [`docs/TAMPILAN.md`](docs/TAMPILAN.md).

## Screenshot

![Dashboard](docs/screenshots/dashboard.png)

| Detail alat | Grafik dan prediksi |
|---|---|
| ![Detail alat](docs/screenshots/detail-alat.png) | ![Grafik dan prediksi](docs/screenshots/grafik-prediksi.png) |

| Kelola alat (admin) | Tampilan HP |
|---|---|
| ![Kelola alat](docs/screenshots/admin-alat.png) | ![Tampilan HP](docs/screenshots/mobile.png) |

## Arsitektur

```
ESP32 (sensor node + controller)
        |  HTTP POST /api/ingest   (JSON: id, key, nilai sensor)
        v
Laravel  --(validasi ID alat + credential)-->  Database (SQLite / MySQL / PostgreSQL)
   |
   |-- /                 dashboard publik   --> membaca /api/public/*
   |-- /lokasi           peta lokasi        --> membaca /api/public/devices
   |-- /admin/*          panel admin (login, peran admin)
   '-- /api/public/*     API JSON publik (hanya baca)
```

## Struktur folder

```
app/
  Console/Commands/MakeAdmin.php        perintah airtracking:make-admin
  Http/Controllers/Api/                 IngestController (alat -> server), PublicController (baca)
  Http/Controllers/Admin/               Auth, Device, Limit, Wifi
  Http/Middleware/                      EnsureAdmin, ForceJson
  Models/                               Device, DeviceCredential, Measurement, Prediction, ParameterLimit, User
  Services/                             MeasurementIngestor (simpan data), Quality (status + prediksi)
database/
  migrations/                           skema tabel
  seeders/                              ParameterLimitSeeder (wajib), DemoSeeder (opsional)
  database.sqlite                       (hanya di zip) database contoh
public/css, public/js                   tampilan dan skrip dashboard/peta (tanpa proses build)
resources/views/                        layouts, partials, dashboard, lokasi, admin/*
routes/                                 web.php, api.php
tests/                                  tes otomatis
docs/                                   skema-database.md, TAMPILAN.md, screenshots/
```

Skema database lengkap (tabel, relasi, pemetaan nama JSON ke kolom) ada di [`docs/skema-database.md`](docs/skema-database.md).

## Kebutuhan

- PHP **8.3 atau lebih baru** (Laravel 13), ekstensi umum Laravel (mbstring, openssl, pdo, tokenizer, xml, ctype, json) dan driver database yang dipakai (mis. `pdo_sqlite`)
- [Composer](https://getcomposer.org)
- Database: SQLite (bawaan), MySQL/MariaDB, atau PostgreSQL
- Akses internet di browser untuk halaman peta (Leaflet dan tile OpenStreetMap dimuat dari CDN) dan font Outfit dari Google Fonts

Node.js/npm **tidak wajib**: halaman memakai berkas statis di `public/css` dan `public/js`, tanpa Vite.

## Instalasi

### A. Dari repositori

```bash
git clone https://github.com/gathanega/airtracking.git
cd airtracking

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite        # hanya bila memakai SQLite
php artisan migrate --seed            # tabel + rentang normal awal (wajib)
php artisan airtracking:make-admin admin@contoh.com --name="Admin"   # diminta kata sandi, minimal 8 karakter
php artisan db:seed --class=DemoSeeder                               # opsional: 4 alat + data contoh

php artisan serve                     # http://127.0.0.1:8000
```

> `migrate --seed` menjalankan `ParameterLimitSeeder`. Tanpa seeder ini tabel rentang normal kosong, sehingga status parameter tidak muncul. Bila memakai `composer setup`, jalankan juga `php artisan db:seed`.

### B. Dari `airtracking.zip`

Zip sudah berisi `vendor/`, `.env`, dan `database/database.sqlite` yang terisi data contoh (4 alat, 360 data pengukuran, dan 1 akun pengguna), jadi cukup:

```bash
unzip airtracking.zip && cd airtracking
php artisan serve
```

Bila muncul galat provider atau cache, jalankan `php artisan optimize:clear`. Bila `vendor/` tidak ikut atau tidak cocok dengan PHP Anda, jalankan `composer install`.

Jangan membagikan zip ini ke pihak lain tanpa menghapus `.env` (berisi `APP_KEY`) dan `database/database.sqlite` (berisi akun dan hash credential).

### Pilihan database (`.env`)

```env
# SQLite (bawaan)
DB_CONNECTION=sqlite

# MySQL / MariaDB
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=airtracking
DB_USERNAME=...
DB_PASSWORD=...

# PostgreSQL: DB_CONNECTION=pgsql dan DB_PORT=5432
```

Waktu disimpan dalam **UTC** (`config/app.php`), dan dashboard menampilkannya sesuai zona waktu browser. Sesi, cache, dan antrean memakai driver `database` (tabelnya ikut dibuat oleh migrasi).

### Uji coba cepat

Setelah `DemoSeeder`, buka `http://127.0.0.1:8000` untuk dashboard dan `http://127.0.0.1:8000/admin/login` untuk panel admin. Kirim satu data contoh (credential demo: `demo-credential-01` sampai `demo-credential-04`):

```bash
curl -X POST http://127.0.0.1:8000/api/ingest -H "Content-Type: application/json" \
  -d '{"id":"AIR-01","key":"demo-credential-01","loc":"LOK-01","ph":7.1,"ec":420,"tds":210,"do":6.8,"temp":27.5,"turb":3.2}'
# {"ok":true,"saved":1}
```

Kartu alat di dashboard diperbarui otomatis setiap 15 detik.

## Halaman

| Alamat | Akses | Isi |
|---|---|---|
| `/` | Publik | Ringkasan, daftar alat, detail alat, grafik tren, prediksi, riwayat, rentang normal, daftar API |
| `/lokasi` | Publik | Peta lokasi alat, titik berwarna sesuai status |
| `/admin/login` | Publik (form) | Login admin. Dibatasi 5 percobaan gagal per 5 menit per kombinasi email dan IP |
| `/admin/devices` | Admin | Registrasi, ubah, nonaktifkan, ganti credential, hapus alat |
| `/admin/lokasi` | Admin | Tandai titik alat di peta (klik, geser, atau ketik koordinat) |
| `/admin/wifi` | Admin | Panduan mode AP dan mode Internet, pembuat konfigurasi JSON |
| `/admin/limits` | Admin | Ubah rentang normal (baku mutu) |
| `/up` | Publik | Pemeriksaan kesehatan bawaan Laravel |

Halaman `/admin/*` dilindungi middleware `EnsureAdmin`. Akun biasa (bukan admin) ditolak.

## Format data dari alat

`POST /api/ingest` dengan JSON:

```json
{
  "id": "AIR-01",
  "key": "credential-alat",
  "loc": "LOK-01",
  "ts": "2026-10-17T08:30:00Z",
  "ph": 7.1,
  "ec": 420,
  "tds": 210,
  "do": 6.8,
  "temp": 27.5,
  "turb": 3.2
}
```

| Field | Keterangan | Batas yang diterima |
|---|---|---|
| `id` | ID alat (harus terdaftar dan aktif) | teks, maks 32 |
| `key` | Credential alat | teks, maks 128 |
| `loc` | Kode lokasi (opsional; bila kosong memakai kode lokasi alat) | teks, maks 32 |
| `ts` | Waktu dari RTC alat (opsional; bila kosong memakai waktu server) | tanggal ISO 8601 |
| `ph` | pH | 0 sampai 14 |
| `ec` | EC (µS/cm) | 0 sampai 100000 |
| `tds` | TDS (ppm) | 0 sampai 100000 |
| `do` | DO (mg/L) | 0 sampai 60 |
| `temp` | Suhu (°C) | -5 sampai 100 |
| `turb` | Kekeruhan (NTU) | 0 sampai 10000 |

Field status opsional: `fw` (versi firmware), `bat` (0 sampai 100), `cal_ph` dan `cal_ec` (tanggal kalibrasi), `sd_pending` (jumlah data di SD yang belum terkirim).

**Banyak data dari SD card** dalam satu paket: ganti nilai sensor dengan `"records": [{"ts": "...", "ph": 7.1, ...}]` (maksimal 500 data).

Balasan dan perilaku:

| Kode | Arti |
|---|---|
| `200` | `{"ok":true,"saved":N}` |
| `401` | ID alat atau credential salah, alat belum diregistrasi, atau alat dinonaktifkan |
| `422` | Nilai di luar batas fisik (mis. pH lebih dari 14) |
| `429` | Lebih dari 120 permintaan per menit per IP |

- Kirim ulang data dengan waktu yang sama **tidak menggandakan** data (unik per alat dan `measured_at`).
- Waktu `ts` yang lebih dari 1 hari di masa depan (jam alat salah) diganti dengan waktu server.
- Bacaan terakhir alat hanya maju: paket SD lama tidak menimpa bacaan yang lebih baru.

## API JSON publik

Hanya baca, tanpa login, 60 permintaan per menit per IP. Credential alat tidak pernah ikut.

| Metode | Alamat | Fungsi |
|---|---|---|
| GET | `/api/public/limits` | Rentang normal per parameter |
| GET | `/api/public/devices` | Semua alat aktif + bacaan terakhir + status per parameter |
| GET | `/api/public/devices/{id}` | Satu alat |
| GET | `/api/public/devices/{id}/history?days=7&limit=500` | Riwayat urut waktu naik (`days` 1 sampai 365, `limit` maksimal 1000) |
| GET | `/api/public/devices/{id}/history.csv?days=30` | Riwayat CSV (maksimal 10.000 baris) |
| GET | `/api/public/devices/{id}/predict` | Prediksi terbaru: `aman`, `waspada`, atau `tercemar` (`409` bila alat belum mengirim data) |

## Rentang normal dan status

Rentang awal (diubah admin di `/admin/limits`; hanya acuan, sesuaikan dengan baku mutu yang dipakai):

| Parameter | Satuan | Batas bawah | Batas atas | Waspada dekat |
|---|---|---|---|---|
| pH | – | 6,5 | 8,5 | kedua batas |
| EC | µS/cm | 0 | 1500 | batas atas |
| TDS | ppm | 0 | 500 | batas atas |
| DO | mg/L | 5 | 14 | batas bawah |
| Suhu | °C | 20 | 32 | kedua batas |
| Kekeruhan | NTU | 0 | 5 | batas atas |

- **Normal**: nilai di dalam rentang.
- **Waspada**: nilai di dalam rentang tetapi berjarak kurang dari 10% lebar rentang dari batas yang berlaku.
- **Di luar rentang**: nilai kurang dari batas bawah atau lebih dari batas atas. Satu parameter saja sudah menandai alat sebagai **Perlu perhatian**.

**Prediksi** saat ini berupa aturan sederhana (`rule-v1`) yang menjumlahkan penalti status parameter, bukan model machine learning. Kolom `cod_mg_l` dan `tss_mg_l` sudah disiapkan untuk model berikutnya.

## Perangkat keras alat (rencana)

Alat portable dengan dua ESP32 DevKit 38-pin (controller dan sensor node). Mode internet hanya aktif saat data akan dikirim.

| Bagian | Komponen |
|---|---|
| Sensor | pH analog (probe BNC), TDS/EC DFRobot, kekeruhan DFRobot, DS18B20, DO fluoresensi SN-3002-LDO |
| ADC dan konverter | ADS1115 (16-bit), MAX485 (RS485 ke TTL) |
| Controller | Modul microSD, RTC DS3231, LCD 16x2 I2C, push button (ukur, kirim, prediksi), saklar mode konfigurasi |
| Daya | Li-ion 18650, TP4056, buck 3,3 V, LDO rendah noise, boost, proteksi (fuse, MOSFET) |

Setup WiFi alat dilakukan lewat halaman AP milik alat (`192.168.4.1`), bukan dari web ini. Halaman `/admin/wifi` hanya memberi panduan dan membuat konfigurasi JSON (tanpa kata sandi WiFi dan credential).

## Tes

```bash
php artisan test
```

Tes memakai SQLite di memori dan mencakup: penyimpanan data, penolakan credential salah atau alat nonaktif, batas fisik, data ganda, paket SD, kerahasiaan credential di API publik, akses admin, peta lokasi, dan menu header.

## Produksi (VPS Ubuntu)

1. Arahkan root nginx/Apache ke folder `public/` dan pakai PHP-FPM.
2. Pasang **HTTPS** (mis. Let's Encrypt). Alat mengirim credential, jadi jangan lewat HTTP polos di internet.
3. Di `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain, dan database produksi.
4. `composer install --no-dev --optimize-autoloader`, lalu `php artisan migrate --force --seed`.
5. `php artisan config:cache && php artisan route:cache`.
6. Buat admin dengan `php artisan airtracking:make-admin`. **Jangan** menjalankan `DemoSeeder` di produksi (credential demo diketahui umum).

Bila alat di jaringan lokal perlu mengakses server dari laptop saat uji coba, jalankan `php artisan serve --host=0.0.0.0` dan pakai IP laptop (bukan `localhost`) di alat.

## Keamanan

- Credential alat disimpan sebagai **hash SHA-256** dan hanya tampil sekali (saat dibuat atau diganti). Bila dikosongkan, credential dibuat acak 24 karakter.
- API publik tidak pernah mengembalikan credential. Semua halaman admin dilindungi `EnsureAdmin` di sisi server.
- Form admin memakai CSRF Laravel. Rute `/api/*` tanpa sesi.
- Pembandingan credential memakai `hash_equals`, dan hash tetap dibandingkan walau ID alat tidak ada, supaya waktu respons tidak membocorkan ID yang valid.
- `.env` sudah ada di `.gitignore`. Jangan commit `.env` atau `database/database.sqlite`.

## Pemecahan masalah

| Gejala | Penyebab umum |
|---|---|
| `No application encryption key has been specified` | Jalankan `php artisan key:generate`. |
| Status parameter kosong, tabel rentang normal kosong | Seeder belum dijalankan: `php artisan db:seed --class=ParameterLimitSeeder`. |
| `401` saat kirim data | ID atau credential salah, alat belum diregistrasi, atau alat dinonaktifkan di Kelola alat. |
| `422` saat kirim data | Ada nilai di luar batas fisik (lihat tabel format data). |
| Tidak bisa masuk admin | Akun belum berperan admin: `php artisan airtracking:make-admin email_anda`. Setelah 5 kali gagal, tunggu beberapa menit. |
| Dashboard menampilkan "Data contoh, server belum terhubung" | Browser tidak dapat menjangkau `/api/public/*`. Pastikan server berjalan dan alamatnya benar. |
| Peta kosong atau abu-abu | Browser perlu internet untuk Leaflet (unpkg) dan tile OpenStreetMap. |
| `Address already in use` | Pakai port lain: `php artisan serve --port=8001`. |
| ESP32 tidak bisa terhubung | Pakai IP server (bukan `localhost`), jalankan dengan `--host=0.0.0.0`, dan buka port di firewall. |
| Galat provider atau cache setelah ekstrak zip | `php artisan optimize:clear`, atau `composer install`. |

Menghentikan server: **Ctrl+C** di terminal tempat `php artisan serve` berjalan.

## Belum ada / rencana

- [ ] **MQTT**: penerimanya belum dibuat. `MeasurementIngestor` sudah siap dipakai (`ingest($device, $payload, 'mqtt')`); tinggal membuat perintah artisan pelanggan MQTT (mis. dengan `php-mqtt/client`) atau memakai mode HTTP di firmware. Topik yang direncanakan: `airtracking/<ID_ALAT>/data`.
- [ ] **Firmware ESP32**: baca sensor, mode AP untuk konfigurasi WiFi, mode internet, kirim data. Folder `firmware/` belum ada di proyek ini.
- [ ] Penyimpanan cadangan di microSD saat tidak ada koneksi (sisi server sudah mendukung paket `records`).
- [ ] Prediksi dengan machine learning (COD/TSS) menggantikan aturan `rule-v1`.
- [ ] Perintah dari server ke alat (konfigurasi jarak jauh).
- [ ] Sesuaikan rentang normal dengan baku mutu yang dipakai penelitian.
- [ ] Samakan antarmuka sensor DO (dokumen rancangan menyebut RS232, daftar komponen RS485).
- [ ] Data dari proyek versi Supabase tidak ikut dipindahkan; credential lama berupa teks asli, jadi buat ulang lewat panel admin.

## Kredit

Struktur awal proyek terinspirasi dari AgriFlow/GreenFlow.
