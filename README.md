# AirTracking

Sistem pemantauan kualitas air berbasis IoT. Alat portable berbasis ESP32 mengukur **pH, EC, TDS, DO (oksigen terlarut), suhu, dan kekeruhan**, lalu mengirim datanya lewat HTTP ke server. Backend dan dashboard dibuat dalam satu proyek **Laravel**: **web publik** (tanpa login), **panel admin** (login), **API JSON**, dan penerima data dari alat. Skema database ada di [`docs/skema-database.md`](docs/skema-database.md).

Ini **overlay**: berkas yang ditimpakan ke proyek Laravel baru (Laravel 12 atau 13; Laravel 13 butuh PHP 8.3 atau lebih baru).

## Fitur

- Dashboard grafis: ringkasan alat, kartu parameter + sparkline, gauge, grafik tren 7/14/30 hari, dan riwayat pengukuran.
- Peta lokasi alat (Leaflet + OpenStreetMap) dengan titik berwarna sesuai status.
- Panel admin untuk registrasi alat (CRUD): ID alat, nama, kode lokasi, credential, dan koordinat.
- Server memvalidasi ID alat dan credential sebelum menyimpan data.
- Data dikirim lewat **HTTP** (`POST /api/ingest`). Penerima MQTT belum dibuat (lihat bagian *Belum ada*).
- Penanda **Perlu perhatian** bila ada parameter di luar rentang normal; rentang normal bisa diubah admin.
- Prediksi kualitas air (`aman` / `waspada` / `tercemar`), saat ini berbasis aturan sederhana.
- Credential alat disimpan sebagai hash dan tidak pernah ditampilkan oleh API publik.

## Arsitektur

```
ESP32 (sensor node + controller)
        |  HTTP POST /api/ingest   (validasi ID alat + credential)
        v
Laravel (web publik, panel admin, API JSON)  -->  Database (MySQL / MariaDB / PostgreSQL)
        ^
        |  baca data (API publik, tanpa login)
Dashboard web (halaman publik)
```

## Struktur folder

```
app/         controller, model, middleware, service (MeasurementIngestor, Quality)
database/    migrasi dan seeder (rentang normal awal, data demo)
docs/        skema database
public/      aset CSS dan JS dashboard
resources/   tampilan (Blade), CSS, JS
routes/      web.php, api.php
tests/       tes otomatis
firmware/    (rencana) firmware ESP32 sensor node dan controller
```

## Pasang

```bash
composer create-project laravel/laravel airtracking
cd airtracking
php artisan install:api          # mengaktifkan routes/api.php (ikut memasang Sanctum; tidak dipakai di sini)
```

Salin seluruh isi folder overlay ini ke dalam `airtracking/` dan **setujui penimpaan** (`routes/web.php`, `routes/api.php`, `app/Models/User.php`). Lalu atur database di `.env`:

```env
# MySQL / MariaDB
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=airtracking
DB_USERNAME=...
DB_PASSWORD=...
# atau PostgreSQL: DB_CONNECTION=pgsql, DB_PORT=5432
APP_TIMEZONE=UTC                  # biarkan UTC; waktu ditampilkan sesuai zona browser
```

```bash
php artisan migrate --seed                      # tabel + rentang normal awal
php artisan airtracking:make-admin admin@contoh.com --name="Admin"   # minta kata sandi
php artisan db:seed --class=DemoSeeder          # opsional: 4 alat + 90 data contoh (titik di sekitar Kota Malang)
php artisan serve                               # http://127.0.0.1:8000
php artisan test                                # tes otomatis
```

Produksi (VPS Ubuntu): arahkan root nginx/Apache ke `public/`, pasang HTTPS (alat mengirim credential), jalankan `php artisan config:cache route:cache`. Sebagai service, jalankan lewat PHP-FPM + nginx (atau Apache) dan pantau dengan systemd bila memakai worker antrean.

## Halaman

| Alamat | Akses | Isi |
|---|---|---|
| `/` | Publik | Ringkasan, daftar alat, kartu parameter + sparkline, grafik tren 7/14/30 hari, prediksi, riwayat, daftar API |
| `/lokasi` | Publik | Peta lokasi alat (Leaflet + OpenStreetMap), titik berwarna sesuai status |
| `/admin/login` | Publik (form) | Login admin, dibatasi 5 percobaan gagal per 5 menit per email dan IP |
| `/admin/devices` | Admin | Registrasi, ubah, nonaktifkan, ganti credential, hapus alat |
| `/admin/lokasi` | Admin | Tandai titik alat di peta (klik, geser, atau ketik koordinat) |
| `/admin/wifi` | Admin | Panduan mode AP dan mode Internet, pembuat konfigurasi JSON |
| `/admin/limits` | Admin | Ubah rentang normal (baku mutu) |

Bila server tidak terjangkau, halaman publik memakai data contoh dan menampilkan penanda.

## Format data JSON

```json
{
  "ts": "2026-10-17T08:30:00Z",
  "loc": "LOK-01",
  "id": "AIR-01",
  "key": "credential-alat",
  "ph": 7.1,
  "ec": 420,
  "tds": 210,
  "do": 6.8,
  "temp": 27.5,
  "turb": 3.2
}
```

| Field | Keterangan |
|---|---|
| `ts` | Timestamp dari RTC alat (opsional; bila kosong, memakai waktu server) |
| `loc` | Kode lokasi |
| `id` | ID alat (harus sudah diregistrasi) |
| `key` | Credential alat |
| `ph`, `ec`, `tds`, `do`, `temp`, `turb` | Nilai sensor: pH, EC (µS/cm), TDS (ppm), DO (mg/L), suhu (°C), kekeruhan (NTU) |

## API JSON

Publik (hanya baca, 60 permintaan per menit per IP):

| Metode | Alamat | Fungsi |
|---|---|---|
| GET | `/api/public/limits` | Rentang normal |
| GET | `/api/public/devices` | Semua alat aktif + bacaan terakhir + status per parameter |
| GET | `/api/public/devices/{id}` | Satu alat |
| GET | `/api/public/devices/{id}/history?days=7&limit=500` | Riwayat, urut waktu naik (`days` 1–365, `limit` ≤ 1000) |
| GET | `/api/public/devices/{id}/history.csv?days=30` | Riwayat CSV |
| GET | `/api/public/devices/{id}/predict` | Prediksi terbaru (`aman` / `waspada` / `tercemar`) |

Alat → server (`POST /api/ingest`, 120 per menit per IP):

```json
{"id":"AIR-01","key":"<credential>","loc":"LOK-01","ts":"2026-10-17T08:30:00Z",
 "ph":7.1,"ec":420,"tds":210,"do":6.8,"temp":27.5,"turb":3.2}
```

Banyak data dari SD card dalam satu paket: ganti nilai sensor dengan `"records":[{"ts":"...","ph":7.1,...}]` (maks 500). Field status opsional: `fw`, `bat` (0–100), `cal_ph`, `cal_ec` (tanggal), `sd_pending`. Balasan: `{"ok":true,"saved":N}`; `401` bila ID atau credential salah; `422` bila nilai di luar batas fisik (mis. pH > 14).

```bash
curl -X POST http://127.0.0.1:8000/api/ingest -H "Content-Type: application/json" \
  -d '{"id":"AIR-01","key":"demo-credential-01","loc":"LOK-01","ph":7.1,"ec":420,"tds":210,"do":6.8,"temp":27.5,"turb":3.2}'
```

Kartu alat di dashboard terisi setelah data masuk. Grafik riwayat muncul setelah ada minimal 2 data.

## Hardware (rencana)

Alat portable dengan dua ESP32 DevKit 38-pin (controller dan sensor node). Mode IoT hanya aktif saat data akan dikirim ke server.

| Bagian | Komponen |
|---|---|
| Sensor | pH analog (probe BNC), TDS/EC DFRobot, kekeruhan DFRobot, DS18B20, DO fluoresensi SN-3002-LDO |
| ADC dan konverter | ADS1115 (16-bit), MAX485 (RS485 ke TTL) |
| Controller | Modul microSD, RTC DS3231, LCD 16x2 I2C, push button (ukur, kirim, prediksi), saklar mode konfigurasi |
| Daya | Li-ion 18650, TP4056, buck 3.3 V, LDO rendah noise, boost, proteksi (fuse, MOSFET) |

Daftar lengkap ada di berkas komponen proyek.

## Pemecahan masalah

| Gejala | Penyebab umum |
|---|---|
| `Address already in use` | Port 8000 dipakai program lain. Hentikan prosesnya (`sudo ss -ltnp \| grep :8000`) atau pakai `php artisan serve --port=8001`. |
| `401` saat kirim data | ID alat atau credential tidak cocok dengan yang diregistrasi. |
| `422` saat kirim data | Nilai sensor di luar batas fisik (mis. pH > 14). |
| Menu admin tidak muncul / ditolak | Akun belum admin. Buat dengan `php artisan airtracking:make-admin`. |
| Login gagal terus | Dibatasi 5 percobaan gagal per 5 menit per email dan IP. Tunggu lalu coba lagi. |
| Error koneksi database | Periksa `DB_*` di `.env`, lalu jalankan `php artisan config:clear`. |
| ESP32 tidak bisa terhubung | Pakai IP laptop/server (bukan `localhost`), jalankan `php artisan serve --host=0.0.0.0`, dan buka port 8000 di firewall. |

## Keamanan

- Credential alat disimpan sebagai hash SHA-256 dan hanya tampil sekali (saat dibuat atau diganti). Credential dibuat acak 24 karakter bila kolomnya dikosongkan.
- API publik tidak pernah mengembalikan credential. Semua halaman admin dilindungi `EnsureAdmin` di sisi server.
- Form admin memakai CSRF Laravel; API `/api/*` tanpa sesi.
- Jangan commit berkas `.env` (berisi kredensial database dan `APP_KEY`).
- Pasang HTTPS di produksi. Firmware dengan `ROOT_CA` kosong tidak memverifikasi sertifikat server.

## Belum ada / perlu keputusan

- **MQTT:** penerimanya belum dibuat. `MeasurementIngestor` sudah siap dipakai (`ingest($device, $payload, 'mqtt')`); tinggal buat perintah artisan pelanggan MQTT (mis. dengan paket `php-mqtt/client`) atau gunakan mode HTTP di firmware.
- **Prediksi:** `Quality::predict()` masih aturan sederhana (placeholder). Model COD/TSS menyusul; kolom `cod_mg_l` dan `tss_mg_l` sudah disiapkan.
- **Perintah dari server ke alat** (konfigurasi jarak jauh) belum ada. "Dua arah" diartikan dua sisi akses (publik dan admin).
- **Pindah data dari Supabase** tidak termasuk (credential lama berupa teks asli, tidak bisa dipakai apa adanya; buat ulang lewat panel admin).
- Rentang normal awal hanyalah acuan; sesuaikan di `/admin/limits` dengan baku mutu yang dipakai.

## Rencana pengembangan

- [ ] Firmware ESP32: baca sensor, mode AP untuk konfigurasi WiFi, mode internet, kirim via HTTP/MQTT
- [ ] Penyimpanan cadangan di microSD saat tidak ada koneksi
- [ ] Prediksi kualitas air dengan machine learning
- [ ] Penerima MQTT
- [ ] Samakan antarmuka sensor DO (dokumen rancangan menyebut RS232, daftar komponen RS485)

## Kredit

Struktur proyek terinspirasi dari AgriFlow/GreenFlow.
