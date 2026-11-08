// sync-ap-page.js — regenerasi controller/ap_page.h dari ap_page.html (sumber tunggal).
// Jalankan: node sync-ap-page.js
// Dipakai juga oleh CI untuk memeriksa ap_page.html dan ap_page.h tidak menyimpang (lihat README bagian CI).
const fs = require('fs');
const path = require('path');

const htmlPath = path.join(__dirname, 'ap_page.html');
const hPath = path.join(__dirname, 'controller', 'ap_page.h');

const html = fs.readFileSync(htmlPath, 'utf8').replace(/\r\n/g, '\n');

const header = `// ap_page.h — halaman mode AP (192.168.4.1): SATU HTML untuk Controller + Sensor node. Tema putih-biru sama dengan web AirTracking.
// DIHASILKAN dari ../ap_page.html (sumber utama; bisa dibuka di browser, tambahkan ?demo untuk data contoh).
// JANGAN EDIT LANGSUNG: jalankan \`node sync-ap-page.js\` dari airtracking-firmware/ setelah mengubah ap_page.html.
// Tanpa file/font eksternal (AP tidak punya internet): font Outfit dipakai bila terpasang, selain itu sistem.
#pragma once
#include <pgmspace.h>

static const char AP_PAGE[] PROGMEM = R"rawliteral(`;

const footer = `)rawliteral";\n`;

fs.writeFileSync(hPath, header + html + footer);
console.log('ap_page.h diperbarui dari ap_page.html (' + html.length + ' byte HTML).');
