// check-ap-page.js — pastikan isi R"rawliteral(...)" di controller/ap_page.h sama dengan ap_page.html.
// Dipakai CI: keluar dengan kode 1 bila berbeda.
const fs = require('fs');
const path = require('path');

const html = fs.readFileSync(path.join(__dirname, 'ap_page.html'), 'utf8').replace(/\r\n/g, '\n');
const header = fs.readFileSync(path.join(__dirname, 'controller', 'ap_page.h'), 'utf8');
const match = header.match(/R"rawliteral\(([\s\S]*)\)rawliteral";/);

if (!match) {
  console.error('GAGAL: literal R"rawliteral(...)" tidak ditemukan di controller/ap_page.h');
  process.exit(1);
}
if (match[1] !== html) {
  console.error('GAGAL: ap_page.html dan controller/ap_page.h berbeda. Jalankan: node sync-ap-page.js');
  process.exit(1);
}
console.log('OK: ap_page.html identik dengan isi controller/ap_page.h');
