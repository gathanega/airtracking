# AirTracking - Pemantauan Kualitas Air (struktur sama seperti AgriFlow)

```
firmware/    (belum dibuat) ESP32 sensor node + controller: pH, EC/TDS, DO, suhu, kekeruhan
server/      FastAPI: validasi ID alat + credential, terima data (MQTT / HTTP), simpan ke Supabase
supabase/    schema.sql: profiles (user/admin), devices, device_keys, water_logs + RLS
web/         dashboard.html + config.js (putih-biru, login, grafik, CRUD alat untuk admin)
```

Alur: alat -> MQTT `airtracking/<ID_ALAT>/data` (atau POST `/api/ingest`) -> server -> Supabase -> dashboard (login Supabase).

Format JSON alat: `{"ts","loc","id","key","ph","ec","tds","do","temp","turb"}`

## Setup
1. **Supabase**: jalankan `supabase/schema.sql`. Buat user di Authentication > Users, lalu
   `update profiles set role='admin' where id='UUID_USER';`
2. **Server** (VPS Ubuntu):
   ```
   cd server && python3 -m venv venv && . venv/bin/activate
   pip install -r requirements.txt
   cp config.example.yaml config.yaml   # isi Supabase url, service_key, MQTT
   uvicorn app:app --host 0.0.0.0 --port 8000
   ```
   Produksi: `airtracking.service` (systemd). Cek: `http://IP_SERVER:8000/api/health`
3. **Dashboard**: isi `web/config.js` (URL + anon key), lalu buka `dashboard.html` atau host statis.
4. **Registrasi alat**: login sebagai admin > Kelola alat > isi ID alat, nama, kode lokasi, credential.

## Catatan
- Alat portable dan hanya kirim data saat tombol kirim ditekan, jadi dashboard menampilkan "terakhir kirim", bukan online/offline.
- Rentang normal di `dashboard.html` (objek `P`) hanya acuan awal; sesuaikan dengan baku mutu proyek.
- Jangan commit `config.yaml` (berisi service_key).
