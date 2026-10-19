@extends('layouts.app')
@section('title', 'Setup WiFi alat · AirTracking')
@section('content')
@include('partials.admin-nav')

<section class="panel">
  <h2>Dua mode jaringan alat</h2>
  <p class="note" style="margin-bottom:12px">WiFi alat tidak diatur dari web ini, melainkan lewat halaman yang dibuka alat sendiri saat berada di mode AP. Halaman ini memberi panduan dan menyiapkan konfigurasi.</p>
  <div class="cols">
    <div class="panel" style="background:var(--s2)">
      <h3>Mode AP (setup)</h3>
      <ul class="c">
        <li>Alat memancarkan WiFi sendiri: <code>AirTrack-&lt;ID&gt;</code></li>
        <li>Halaman setup di <code>192.168.4.1</code> (terbuka otomatis di HP)</li>
        <li>Isi: SSID dan kata sandi WiFi, ID alat, credential, alamat server, protokol, interval kirim</li>
        <li>Aktif saat tombol konfigurasi ditahan 3 detik, alat belum pernah disetel, atau WiFi gagal tersambung</li>
        <li>Tidak ada pengiriman data ke server</li>
      </ul>
    </div>
    <div class="panel" style="background:var(--s2)">
      <h3>Mode Internet (kerja normal)</h3>
      <ul class="c">
        <li>Alat tersambung ke WiFi lokasi (atau hotspot HP)</li>
        <li>Kirim JSON lewat HTTP <code>POST /api/ingest</code> atau MQTT <code>airtracking/&lt;ID&gt;/data</code></li>
        <li>Waktu dari NTP; bila belum sinkron, server memakai waktunya sendiri</li>
        <li>Bila 20 detik gagal tersambung, alat kembali ke mode AP</li>
      </ul>
    </div>
  </div>
</section>

<section class="panel">
  <h2 style="margin-bottom:10px">Langkah setup di lapangan</h2>
  <ol class="st">
    <li>Tahan tombol konfigurasi alat 3 detik sampai WiFi <code>AirTrack-…</code> muncul di HP.</li>
    <li>Sambungkan HP ke WiFi tersebut (kata sandi AP ada di firmware; ganti sebelum alat dipasang).</li>
    <li>Halaman setup terbuka sendiri. Bila tidak, buka <code>http://192.168.4.1</code>.</li>
    <li>Tekan Pindai, pilih WiFi lokasi, isi kata sandinya.</li>
    <li>Isi ID alat, credential, dan alamat server (atau tempel konfigurasi dari kotak di bawah), lalu Simpan.</li>
    <li>Alat restart ke mode Internet. Di dashboard publik, “Sinkron terakhir” harus berubah ke “baru saja”.</li>
  </ol>
</section>

<section class="panel">
  <h2 style="margin-bottom:10px">Siapkan konfigurasi alat</h2>
  <div class="f">
    <label>Alat
      <select id="w_dev"><option value="">Pilih alat</option>
        @foreach ($devices as $d)<option value="{{ $d->code }}" data-loc="{{ $d->location_code }}">{{ $d->code }} · {{ $d->name }}</option>@endforeach
      </select></label>
    <label>Alamat server<input id="w_host" value="{{ url('/api/ingest') }}" placeholder="https://domain-anda/api/ingest atau host MQTT"></label>
    <label>Protokol<select id="w_pro"><option value="http">HTTP</option><option value="mqtt">MQTT</option></select></label>
    <label>Interval kirim (detik)<input id="w_iv" type="number" min="10" value="300"></label>
  </div>
  <div class="err" id="w_err" role="alert"></div>
  <button class="btn" id="w_go" type="button">Buat konfigurasi</button>
  <div id="w_out" hidden style="margin-top:14px">
    <div class="head"><h3>Hasil</h3><button class="btn s ghost" id="w_copy" type="button">Salin</button></div>
    <pre id="w_json"></pre>
  </div>
  <p class="note">Konfigurasi ini tidak memuat kata sandi WiFi maupun credential. Keduanya diketik langsung di halaman AP alat supaya tidak tersimpan atau terkirim lewat web.</p>
</section>

<script>
document.getElementById('w_go').onclick = function () {
  var $ = function (i) { return document.getElementById(i); }, sel = $('w_dev'), opt = sel.options[sel.selectedIndex];
  var host = $('w_host').value.trim(), pro = $('w_pro').value, iv = Math.max(10, parseInt($('w_iv').value, 10) || 300);
  if (!sel.value) { $('w_err').textContent = 'Pilih alat terlebih dahulu'; $('w_out').hidden = true; return; }
  if (!host) { $('w_err').textContent = 'Isi alamat server'; $('w_out').hidden = true; return; }
  $('w_err').textContent = '';
  var o = { device_id: sel.value, loc: opt.getAttribute('data-loc'), proto: pro, server: host, interval_s: iv };
  if (pro === 'mqtt') o.topic = 'airtracking/' + sel.value + '/data';
  $('w_json').textContent = JSON.stringify(o, null, 2); $('w_out').hidden = false;
};
document.getElementById('w_copy').onclick = function () {
  var b = this, t = document.getElementById('w_json').textContent;
  navigator.clipboard.writeText(t).then(function () { var o = b.textContent; b.textContent = 'Tersalin'; setTimeout(function () { b.textContent = o; }, 1300); });
};
</script>
@endsection
