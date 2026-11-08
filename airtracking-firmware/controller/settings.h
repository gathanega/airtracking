// settings.h — konfigurasi alat (NVS), diatur lewat halaman mode AP.
#pragma once
#include <Preferences.h>
#include <esp_system.h>
#include "config.h"

struct Settings {
  String ssid, pass;
  String devId, loc, key;
  String proto = "http";        // http | mqtt
  String server;                // https://domain/api/ingest  atau  host[:port] untuk MQTT
  uint32_t intervalS = DEFAULT_INTERVAL_S;
  String calPh, calEc;          // tanggal kalibrasi terakhir (YYYY-MM-DD), dikirim sebagai cal_ph/cal_ec
  String apPass;                // sandi WiFi mode AP, dibuat sekali per alat (lihat apPassEnsure())
} cfg;

void cfgLoad() {
  Preferences p;
  p.begin("airtrack", true);
  cfg.ssid = p.getString("ssid", "");
  cfg.pass = p.getString("pass", "");
  cfg.devId = p.getString("id", "");
  cfg.loc = p.getString("loc", "");
  cfg.key = p.getString("key", "");
  cfg.proto = p.getString("proto", "http");
  cfg.server = p.getString("server", "");
  cfg.intervalS = p.getUInt("iv", DEFAULT_INTERVAL_S);
  cfg.calPh = p.getString("calph", "");
  cfg.calEc = p.getString("calec", "");
  cfg.apPass = p.getString("appass", "");
  p.end();
  cfg.intervalS = constrain(cfg.intervalS, (uint32_t)MIN_INTERVAL_S, (uint32_t)MAX_INTERVAL_S);
}

void cfgSave() {
  Preferences p;
  p.begin("airtrack", false);
  p.putString("ssid", cfg.ssid);
  p.putString("pass", cfg.pass);
  p.putString("id", cfg.devId);
  p.putString("loc", cfg.loc);
  p.putString("key", cfg.key);
  p.putString("proto", cfg.proto);
  p.putString("server", cfg.server);
  p.putUInt("iv", cfg.intervalS);
  p.putString("calph", cfg.calPh);
  p.putString("calec", cfg.calEc);
  p.putString("appass", cfg.apPass);
  p.end();
}

bool cfgConfigured() {
  return cfg.ssid.length() && cfg.devId.length() && cfg.key.length() && cfg.server.length();
}

// Buat sandi WiFi mode AP unik per alat bila belum ada (MAC + esp_random, bukan nilai tetap di kode).
// Dipanggil sebelum WiFi.softAP(); hasilnya disimpan permanen di NVS supaya sandi tidak berubah tiap boot.
String apPassEnsure() {
  if (cfg.apPass.length() >= 8) return cfg.apPass;
  static const char alphabet[] = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";  // tanpa 0/O/1/I agar mudah dibaca di LCD
  String s;
  for (int i = 0; i < AP_PASS_LEN; i++) s += alphabet[esp_random() % (sizeof(alphabet) - 1)];
  cfg.apPass = s;
  cfgSave();
  Serial.println("[ap] sandi WiFi AP baru dibuat untuk alat ini: " + s + " (simpan, hanya ditampilkan di Serial/LCD)");
  return s;
}
