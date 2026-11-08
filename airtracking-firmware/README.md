# AirTracking — Firmware ESP32 (controller + sensor node)

Firmware Arduino untuk alat portable AirTracking: **dua board DOIT ESP32 DevKit V1 (30 pin)** (controller dan sensor node),
mengirim data ke server AirTracking (Laravel) dengan format JSON yang sama persis dengan `POST /api/ingest`.
Pin mengikuti sheet **Pinout (usulan)** di `Komponen_Spesifik_Airtracking.xlsx` dan sudah dicek terhadap header 30 pin
DOIT DevKit V1 (lihat tabel pinout di bawah).

> **Status:** kedua sketch **berhasil dikompilasi dengan toolchain ESP32 asli** (`arduino-cli` + core `esp32:esp32` 3.3.12,
> board DOIT ESP32 DEVKIT V1), tetapi **belum diunggah ke hardware dan belum diuji di lapangan**. Hasil ukuran flash/RAM
> ada di bagian *Uji kompilasi tanpa hardware*; checklist bench ada di bawah.

## Isi folder

```
ap_page.html    SUMBER halaman mode AP (1 file HTML). Buka langsung di browser + ?demo untuk melihat tampilan tanpa alat.
controller/     ESP32 #1: jadwal, rail daya, SD, RTC, LCD, WiFi, kirim ke server, MODE AP
  controller.ino  config.h  settings.h  hw.h  state.h  node_link.h  storage.h  net.h  lcd_ui.h  ap_mode.h  ap_page.h  protocol.h
sensor_node/    ESP32 #2: ADS1115 (pH, EC, kekeruhan), DS18B20, DO RS485 (fase 2), kalibrasi
  sensor_node.ino  config.h  protocol.h
```

Buka `controller/controller.ino` dan `sensor_node/sensor_node.ino` sebagai dua sketch terpisah di Arduino IDE.
Di **Tools → Board** pilih **DOIT ESP32 DEVKIT V1** (atau *ESP32 Dev Module* dengan catatan pinout di bawah), core
*esp32 by Espressif* 2.0.x atau 3.x. `protocol.h` sama di kedua folder.

## Board: DOIT ESP32 DevKit V1 (30 pin)

Kedua sketch memakai board yang sama. Tabel di bawah adalah pin yang benar-benar dipakai firmware dan sudah
dicocokkan dengan label pada header 30 pin DOIT DevKit V1.

**Controller (ESP32 #1)**

| Fungsi | GPIO | Keterangan board DOIT V1 |
|---|---|---|
| I2C SDA (DS3231 + LCD) | 21 | tersedia |
| I2C SCL | 22 | tersedia |
| microSD CS / SCK / MISO / MOSI | 5 / 18 / 19 / 23 | VSPI; GPIO5 strapping (harus HIGH saat boot) |
| UART ke sensor node TX / RX | 17 / 16 | tersedia |
| WAKE ke sensor node | 25 | tersedia |
| EN rail 5V / 12V | 26 / 27 | tersedia |
| Tombol UKUR / LCD | 32 / 33 | tersedia |
| Sensor baterai (ADC) | 34 | **input-only**, tanpa pull-up internal |
| Switch mode AP | 13 | bukan strapping; pull-down 10k ke GND |

**Sensor node (ESP32 #2)**

| Fungsi | GPIO | Keterangan board DOIT V1 |
|---|---|---|
| I2C SDA / SCL (ADS1115) | 21 / 22 | tersedia |
| DS18B20 OneWire | 4 | perlu pull-up 4.7k ke 3V3 |
| UART ke controller RX / TX | 26 / 27 | tersedia |
| WAKE dari controller | 33 | tersedia |
| RS485 RX / TX / DE (fase 2) | 16 / 17 / 19 | tersedia |

Yang **tidak** dipakai: GPIO 6–11 (flash internal) dan GPIO 0/1/2/3/12/15 (strapping/UART0). Board 30 pin tidak
memiliki GPIO 9/10, jadi jangan menambahkan periferal baru ke pin tersebut. Jika memakai board 38 pin, pinout ini
tetap valid karena semua GPIO yang dipakai ada di kedua varian.

Catatan strapping: **GPIO5 (CS microSD)** harus HIGH saat boot. Modul microSD umumnya sudah memasang pull-up pada CS;
bila alat gagal boot/masuk mode download, tambahkan pull-up 10 kΩ dari CS ke 3V3. **GPIO12** (strapping, harus LOW saat
boot) tidak dipakai.

**Library** (Library Manager):

| Sketch | Library |
|---|---|
| controller | `RTClib` (Adafruit), `LiquidCrystal_I2C` (Frank de Brabander), `PubSubClient` (hanya bila `USE_MQTT 1`) |
| sensor_node | `Adafruit ADS1X15`, `OneWire`, `DallasTemperature` |

WiFi, WebServer, DNSServer, HTTPClient, SD, Preferences sudah bawaan core ESP32. Tidak memakai ArduinoJson.

**Saat flashing via USB:** lepas catu eksternal ke pin 3V3 (catatan BOM no. 1).

## Cara kerja

```
timer / tombol ──► CONTROLLER (bangun)
                    1. EN 5V (GPIO26) ON  [+ EN 12V (GPIO27) bila DO]
                    2. pulsa WAKE (GPIO25) ──► SENSOR NODE bangun (ext0, GPIO33)
                    3. UART 115200: {"c":"measure"} ──► node warm-up ±15 dtk, baca sensor
                       node ──► {"ok":1,"ph":..,"ec":..,"tds":..,"do":null,"temp":..,"turb":..}
                    4. node tidur, rail OFF
                    5. data ditulis ke microSD (/pending.ndjson) + arsip /data.csv
                    6. WiFi → (NTP bila jam belum benar) → POST /api/ingest (antrean SD dikirim per 20 data)
                    7. deep sleep sampai jadwal berikutnya (jam DS3231, tidak bergeser oleh tombol)
```

- Waktu dikirim sebagai **UTC** (`ts`, format `2026-10-19T02:30:00Z`) dari RTC DS3231. Bila RTC belum benar, alat sinkron NTP; bila
  tetap gagal, `ts` dikosongkan dan server memakai waktunya sendiri.
- Bila WiFi/server putus, data **tetap di microSD** dan ikut terkirim pada siklus berikutnya (`records`, field `sd_pending` terisi).
  Tanpa microSD, data hanya dikirim langsung (tidak ada buffer).
- Data yang ditolak server dengan 422 (nilai di luar batas fisik) dipindah ke `/rejected.ndjson` agar tidak menyumbat antrean.
  Jawaban 401 (credential salah) membiarkan data tetap di SD.

## Format data (sama dengan web)

```json
{"id":"AIR-01","key":"<credential>","loc":"LOK-01","fw":"1.0.0","bat":87,"cal_ph":"2026-10-19","cal_ec":"2026-10-19","sd_pending":0,
 "ts":"2026-10-19T02:30:00Z","ph":7.12,"ec":420.4,"tds":210.2,"do":null,"temp":27.50,"turb":3.2}
```

Banyak data (antrean SD): sama, tetapi nilai sensor diganti `"records":[{"ts":..,"ph":..}, ...]` (maks 20 per paket).
`do` bernilai `null` selama fase 1 (belum ada sensor DO). Dokumen perencanaan menyebut protokol **MQTT**; sudah tersedia
(`proto = mqtt`, topik `airtracking/<ID>/data`, username = ID alat, password = credential), tetapi **penerima MQTT di server belum dibuat**
(lihat README web), jadi pakai **HTTP** dulu.

## Mode AP (setup): satu halaman untuk dua ESP32

**Dibuka dengan SWITCH AP** (slide switch panel-mount, GPIO13 controller):

```
3V3 ──[ switch ]──┬── GPIO13
                  └── 10 kΩ ── GND        ON = HIGH = mode AP, OFF = mode Internet
```

- **Switch ON** (alat tidur pun akan bangun): controller memancarkan WiFi **`AirTrack-<ID>`**, menyalakan rail 5V, dan **menahan sensor node tetap bangun**.
  LCD (bila rail 5V siap) menampilkan `MODE AP`. Selama switch ON alat tidak mengukur terjadwal dan tidak mengirim data.
- **Switch OFF**: AP ditutup, node ditidurkan, lalu controller **langsung ukur + kirim** ke server (cek “Sinkron terakhir” di dashboard), kemudian tidur seperti biasa.
- Bila switch dibiarkan ON dan halaman tidak dipakai **30 menit** (`AP_SWITCH_IDLE_MS`), AP ditutup sendiri untuk menghemat baterai. Untuk membuka lagi: OFF-kan lalu ON-kan switch.
- Tanpa switch pun, alat yang **belum dikonfigurasi** tetap masuk AP, dan AP otomatis terbuka 5 menit setelah WiFi gagal 3 siklus berturut-turut.
  Cadangan lama (tombol UKUR ditahan 3 detik) bisa dihidupkan dengan `USE_AP_BTN_HOLD 1` di `config.h`.

**Satu HTML, dua ESP32.** Sensor node tidak punya WiFi; halaman dilayani controller, dan perintah ke node diteruskan lewat kabel UART yang sudah ada:

| Panel di halaman | ESP32 | Endpoint controller |
|---|---|---|
| Status Controller (ID, jam, baterai, SD, antrean) | #1 controller | `GET /api/status` |
| Status Sensor node (firmware, ADS1115, nilai kalibrasi) | #2 node, via UART | `GET /api/node` |
| Pembacaan langsung (segar tiap 3 dtk), kedua ESP dalam 1 panggilan | #1 + #2 | `GET /api/live` |
| Pengaturan WiFi/ID/server/interval | #1 | `GET/POST /api/config`, `GET /api/scan` |
| Ukur penuh & kalibrasi pH/EC/kekeruhan | #2 via #1 | `POST /api/measure`, `POST /api/cal` |

Langkah setup:

1. **Daftarkan alat dulu di web:** `/admin/devices` → isi ID alat, nama, kode lokasi → catat **credential** (hanya tampil sekali).
2. Geser **switch AP ke ON**. Sambungkan HP ke WiFi **`AirTrack-<ID>`**: sandi dibuat otomatis per alat (acak, 10 karakter) saat AP pertama dibuka, dan ditampilkan di **LCD** alat serta di **Serial monitor** (`[ap] sandi WiFi: ...`). Sandi disimpan di NVS sehingga tidak berubah di boot berikutnya; tidak ada lagi sandi bawaan yang sama untuk semua alat.
3. Halaman terbuka sendiri (captive portal); bila tidak, buka `http://192.168.4.1`. Kedua kartu (Controller dan Sensor node) harus **Online**.
4. **Pindai WiFi** → pilih WiFi lokasi → isi sandi. Isi ID alat, kode lokasi, credential, alamat server (mis. `https://domain-anda/api/ingest`), interval.
   Atau tempel JSON dari `/admin/wifi` lalu **Isi form dari konfigurasi**. Tekan **Simpan pengaturan** (alat tidak restart).
5. Kalibrasi sambil memantau **Pembacaan langsung** (lihat bagian Kalibrasi).
6. Geser **switch AP ke OFF**. Alat masuk mode Internet dan mengirim data.

WiFi dan credential disimpan di NVS alat; halaman tidak pernah menampilkannya kembali.
AP tidak punya internet, jadi font Outfit hanya dipakai bila terpasang di HP (selain itu font sistem).
Halaman diubah di `ap_page.html` (sumber tunggal), lalu regenerasi header dengan `node sync-ap-page.js` dari folder
`airtracking-firmware/` (jangan edit `controller/ap_page.h` langsung).

## Tombol di lapangan

| Tombol | Aksi |
|---|---|
| SWITCH AP (GPIO13) | ON = mode AP (satu halaman untuk controller + sensor node) · OFF = mode Internet |
| UKUR (GPIO32) | tekan = ukur & kirim sekarang |
| LCD (GPIO33) | tekan = LCD menyala 30 detik (rail 5V ON); tekan lagi = halaman berikut (nilai · turbidity/DO · baterai/SD/WiFi · jam WIB). Saat LCD menyala, tombol UKUR = ukur dengan layar aktif |

## Kalibrasi (lewat halaman AP)

Pantau panel **Pembacaan langsung** (mV mentah ikut tampil) sampai stabil sebelum menekan tombol kalibrasi.

1. **pH 2 titik:** bilas probe, celup ke buffer **7.00**, tunggu ±1 menit, tekan *pH 7.00*; lalu buffer **4.00**, tekan *pH 4.00*.
   Kompensasi suhu (Nernst) memakai DS18B20. Sebelum dikalibrasi dipakai nilai bawaan DFRobot (1500 mV = pH 7, 2032 mV = pH 4).
2. **EC:** celup ke larutan **1413 µS/cm**, tekan *EC 1413*. Menyimpan faktor K (wajib; tanpa kalibrasi EC belum akurat). TDS = EC × 0,5 (`TDS_FACTOR`, ubah 0,5–0,7).
3. **Kekeruhan:** celup ke aquades, tekan *Kekeruhan 0*. Hasil NTU **indikatif** (rumus SEN0189).
4. Tanggal kalibrasi pH/EC otomatis ikut terkirim sebagai `cal_ph` / `cal_ec` (butuh jam valid).

Nilai kalibrasi disimpan di NVS **sensor node**; tanggalnya di controller.

## Fase 2: sensor DO (SN-3002-LDO, RS485)

Set `USE_DO 1` di **kedua** `config.h`. Controller akan menyalakan rail 12V dan node membaca Modbus-RTU lewat RS485 (GPIO16/17, DE/RE GPIO19).
**Verifikasi dulu di datasheet** `DO_MB_ADDR`, `DO_MB_BAUD`, `DO_MB_REG`, `DO_MB_IDX`, `DO_MB_SCALE` — nilai di `config.h` hanya tebakan umum untuk sensor sejenis.

## Mengambil ROOT_CA (untuk HTTPS)

Isi `ROOT_CA` di `controller/config.h` dengan **root CA** yang menandatangani sertifikat server Anda, bukan sertifikat
server itu sendiri. Contoh bila server memakai Let's Encrypt:

```bash
# 1) Ambil rantai sertifikat server
openssl s_client -connect domain-anda:443 -servername domain-anda -showcerts </dev/null

# 2) Simpan sertifikat root (yang paling akhir pada rantai) sebagai PEM, lalu tempel ke ROOT_CA:
#    "-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----\n"
```

Alternatif yang lebih praktis: pakai root CA bawaan core ESP32 (`WiFiClientSecure::setCACertBundle`) atau
*certificate pinning* (bandingkan fingerprint sertifikat server). Jangan menaruh **private key** di firmware.
Untuk produksi jangan set `ALLOW_INSECURE_TLS 1`.

## Uji kompilasi tanpa hardware

Diuji pada 2026-11-08 dengan `arduino-cli 1.5.1`, core **esp32:esp32 3.3.12**, board
`esp32:esp32:esp32doit-devkit-v1` (DOIT ESP32 DEVKIT V1):

| Sketch | Flash terpakai | RAM global | Hasil |
|---|---|---|---|
| `controller` | 1.216.492 / 1.310.720 byte (**92%**) | 51.784 / 327.680 byte (15%) | **OK** |
| `sensor_node` | 337.140 / 1.310.720 byte (25%) | 23.836 / 327.680 byte (7%) | **OK** |

Tidak ada error atau warning dari kode proyek. Warning yang muncul hanya dari library pihak ketiga
(`LiquidCrystal I2C` memakai konstanta `B00000001` yang deprecated, `OneWire` punya `#undef` dengan token tambahan,
dan `driver/gpio.h` dari core ESP32).

> **Perhatian:** `controller` sudah memakai **92% flash** pada skema partisi bawaan. Sebelum menambah fitur besar
> (OTA, downlink, dll.) pilih skema partisi yang lebih besar atau set `USE_MQTT 0` bila MQTT tidak dipakai.

Perintah yang dipakai (jalankan dari folder `airtracking-firmware/`):

```bash
arduino-cli core install esp32:esp32
arduino-cli lib install "RTClib" "LiquidCrystal I2C" "PubSubClient" "OneWire" "DallasTemperature" "Adafruit ADS1X15"

arduino-cli compile --fqbn esp32:esp32:esp32doit-devkit-v1 controller
arduino-cli compile --fqbn esp32:esp32:esp32doit-devkit-v1 sensor_node

node check-ap-page.js    # ap_page.html harus identik dengan controller/ap_page.h
```

JavaScript halaman AP (satu blok `<script>`, ±8,9 KB) juga lolos `node --check`.

`controller/protocol.h` dan `sensor_node/protocol.h` juga sudah dicek identik (SHA-256 sama). Varian **`USE_DO 1`**
(jalur sensor DO fase 2) untuk kedua sketch juga berhasil dikompilasi: controller 1.216.488 byte (92%), sensor node
338.792 byte (25%).

## Checklist uji bench (wajib dijalankan di hardware)

Kompilasi sudah lolos, tetapi firmware belum dijalankan di hardware. Setelah perubahan keamanan, jalankan minimal:

- [x] Build kedua sketch untuk board **DOIT ESP32 DEVKIT V1** tanpa error (dilakukan di host, lihat bagian di atas).
- [ ] **P0-02 TLS:** dengan `ROOT_CA` kosong + `ALLOW_INSECURE_TLS 0`, alat menolak kirim ke `https://...`; Serial
      menampilkan `[tls] DITOLAK` dan halaman AP menampilkan **pengiriman diblokir**. Isi `ROOT_CA` → pengiriman sukses.
- [ ] **P0-02 bench:** set `ALLOW_INSECURE_TLS 1` → pengiriman jalan tetapi Serial + halaman AP menampilkan peringatan
      **TIDAK AMAN (mode bench)**. Kembalikan ke 0 setelah uji.
- [ ] **P0-03 sandi AP:** dua alat berbeda menghasilkan sandi AP berbeda (cek Serial/LCD). Sandi tetap sama setelah reboot.
- [ ] **P0-03 token:** `curl -X POST http://192.168.4.1/api/reboot` tanpa header `X-Air-Token` → `401`; dengan token dari
      halaman AP → `200`. Halaman AP tetap bisa menyimpan konfigurasi dan kalibrasi.
- [ ] **P0-03 batas klien:** klien ke-3 tidak bisa tersambung ke AP.
- [ ] **UART/pinout:** dengan board DOIT V1, tombol UKUR (32), LCD (33), switch AP (13), VBAT (34), dan microSD (5/18/19/23)
      berfungsi; tidak ada pin yang bentrok dengan flash.

## Hal yang perlu diuji / diputuskan

- **Uji pertama (bench):** (1) node saja: buka Serial Monitor, ketik `{"c":"ping"}` + Enter lalu `{"c":"measure"}`; (2) controller + node tanpa WiFi: cek `[cycle] data:` di Serial;
  (3) mode AP (switch ON → kedua kartu Online, pembacaan langsung bergerak; switch OFF → alat ukur+kirim); (4) kirim ke server lokal (`php artisan serve --host=0.0.0.0`, alamat `http://IP-laptop:8000/api/ingest`).
- **Interval minimum 60 detik** (web admin mengizinkan 10) karena sensor perlu ±45 detik aktif; default 900 detik (15 menit, sama dengan sheet Anggaran Daya).
- **Switch AP:** tambahan hardware baru (belum ada di BOM/Pinout): 1 slide switch + resistor pull-down 10 kΩ di GPIO13 controller. Tombol ukur tidak lagi dipakai untuk masuk AP.
- **Mode AP lebih boros daya:** WiFi AP, rail 5V, dan sensor node menyala terus selama switch ON. Jangan dibiarkan ON di lapangan.
- **DO:** dokumen perencanaan menyebut RS232, daftar komponen RS485 — firmware mengikuti RS485.
- **HTTPS/TLS:** bawaan sekarang **https://** bila alamat server tidak menulis skema. Isi `ROOT_CA` (PEM root CA server)
  untuk produksi. Bila `ROOT_CA` kosong dan `ALLOW_INSECURE_TLS 0` (bawaan), alat **menolak mengirim** dan menampilkan
  peringatan di Serial + halaman AP. `ALLOW_INSECURE_TLS 1` hanya untuk uji bench di jaringan tepercaya (tertulis jelas
  di Serial dan halaman AP). Cara mengambil root CA ada di bagian *Mengambil ROOT_CA*.
- **Sandi mode AP unik per alat:** dibuat acak 10 karakter saat AP pertama dibuka, disimpan di NVS, ditampilkan di LCD
  dan Serial. Tidak ada lagi sandi bawaan yang sama untuk semua alat. Semua endpoint POST (`/api/config`, `/api/cal`,
  `/api/measure`, `/api/reboot`) mewajibkan header `X-Air-Token` yang hanya diberikan ke halaman AP yang dimuat.
  AP dibatasi maksimal 2 klien.
- Credential dan sandi WiFi tersimpan polos di NVS (tidak terenkripsi); aktifkan flash encryption bila alat bisa jatuh ke tangan lain.
- Rumus EC (DFR0300), pH, dan kekeruhan memakai konstanta DFRobot; akurasi final tergantung kalibrasi dan kondisi probe.
- Persentase baterai dari kurva Li-ion kasar; sesuaikan `VBAT_SCALE` dengan multimeter.

## Pemecahan masalah

| Gejala | Penyebab umum |
|---|---|
| `Sensor node tidak merespons` | Kabel TX/RX harus silang (17→26, 16→27), GND bersama, WAKE GPIO25→GPIO33, catu 3V3 node hidup |
| `ADS1115 tidak terdeteksi` | Rail 5V/LDO analog belum menyala, alamat I2C bukan 0x48 (ADDR ke GND), SDA/SCL tertukar |
| `401` | ID alat/credential tidak cocok dengan yang diregistrasi (ganti credential di `/admin/devices`, isi ulang lewat AP) |
| `422` | Nilai di luar batas fisik (data pindah ke `/rejected.ndjson`) |
| Jam tidak benar | Baterai CR2032 DS3231 habis / modul ZS-042 — alat otomatis sinkron NTP bila ada WiFi |
| Alat bangun terus | Tombol ditekan terus atau pull-down 10 kΩ tidak terpasang pada GPIO32/33 |
| LCD kosong | Rail 5V mati, level shifter belum terpasang, atau alamat bukan 0x27/0x3F |
