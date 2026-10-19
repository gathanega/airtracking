/* AirTracking - fungsi bersama untuk halaman publik (index.html) dan admin (admin.html) */
(function () {
  var C = window.AIR_CONFIG || {}, BASE = (C.API_BASE || '').replace(/\/$/, '');
  var K = ['ph', 'ec', 'tds', 'do', 'temp', 'turb'];
  // Nilai awal; diganti oleh GET /api/public/limits bila server terjangkau.
  var LIM = {
    ph: { name: 'pH', unit: '', lo: 6.5, hi: 8.5, sides: 'lh', dec: 2 }, ec: { name: 'EC', unit: 'µS/cm', lo: 0, hi: 1500, sides: 'h', dec: 0 },
    tds: { name: 'TDS', unit: 'ppm', lo: 0, hi: 500, sides: 'h', dec: 0 }, do: { name: 'DO', unit: 'mg/L', lo: 5, hi: 14, sides: 'l', dec: 2 },
    temp: { name: 'Suhu', unit: '°C', lo: 20, hi: 32, sides: 'lh', dec: 1 }, turb: { name: 'Kekeruhan', unit: 'NTU', lo: 0, hi: 5, sides: 'h', dec: 1 }
  };
  var ST = { ok: ['✓', 'Normal'], warn: ['!', 'Waspada'], crit: ['▲', 'Di luar rentang'] };

  function title(s) { s = String(s || ''); return s.charAt(0).toUpperCase() + s.slice(1); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function status(k, v) {
    if (v == null) return null;
    var p = LIM[k]; if (v < p.lo || v > p.hi) return 'crit';
    var m = (p.hi - p.lo) * .1;
    if ((p.sides.indexOf('l') >= 0 && v < p.lo + m) || (p.sides.indexOf('h') >= 0 && v > p.hi - m)) return 'warn';
    return 'ok';
  }
  function fmt(k, v) { return v == null ? '–' : Number(v).toFixed(LIM[k].dec); }
  function dt(t) { return new Date(t).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':'); }
  function dd(t) { return new Date(t).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }); }
  function ago(t) {
    if (!t) return 'belum pernah kirim data';
    var m = Math.round((Date.now() - new Date(t)) / 60000);
    return m < 1 ? 'baru saja' : m < 60 ? m + ' menit lalu' : m < 1440 ? Math.round(m / 60) + ' jam lalu' : Math.round(m / 1440) + ' hari lalu';
  }
  function worst(st) { var r = 'ok'; K.forEach(function (k) { if (st && st[k] === 'crit') r = 'crit'; else if (st && st[k] === 'warn' && r !== 'crit') r = 'warn'; }); return r; }
  function copy(text, btn) {
    var done = function () { var o = btn.textContent; btn.textContent = 'Tersalin'; setTimeout(function () { btn.textContent = o; }, 1300); };
    var fb = function () { var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (e) { } t.remove(); };
    try { navigator.clipboard.writeText(text).then(done, fb); } catch (e) { fb(); }
  }
  function origin() { return BASE || (location.protocol.indexOf('http') === 0 ? location.origin : 'https://DOMAIN-ANDA'); }

  function api(path, opt) {
    opt = opt || {};
    var h = { 'Content-Type': 'application/json' };
    if (opt.token) h.Authorization = 'Bearer ' + opt.token;
    return fetch(BASE + path, { method: opt.method || 'GET', headers: h, body: opt.body ? JSON.stringify(opt.body) : undefined }).then(function (r) {
      return r.text().then(function (t) {
        var j; try { j = t ? JSON.parse(t) : {}; } catch (e) { var er = new Error('Server tidak membalas JSON'); er.net = true; throw er; }
        if (!r.ok) { var e2 = new Error(typeof j.detail === 'string' ? j.detail : 'Permintaan gagal (' + r.status + ')'); e2.status = r.status; throw e2; }
        return j;
      });
    }, function () { var e = new Error('Server tidak terjangkau'); e.net = true; throw e; });
  }

  // ---- DATA CONTOH (dipakai hanya bila server tidak terjangkau) ----
  function rng(a) { return function () { a |= 0; a = a + 0x6D2B79F5 | 0; var t = Math.imul(a ^ a >>> 15, 1 | a); t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t; return ((t ^ t >>> 14) >>> 0) / 4294967296; }; }
  function demoHist(seed, shift) {
    var r = rng(seed), end = Date.now() - 20 * 60000, out = [];
    for (var i = 0; i < 90; i++) {
      var ev = (i >= 36 && i <= 44) ? 1 : 0, up = Math.max(0, i - 83), day = Math.sin(i / 3 * Math.PI * 2 / 3), ec = 520 + shift * 90 + 30 * day + (r() - .5) * 40 + ev * 170 + up * 8;
      out.push({ measured_at: new Date(end - (89 - i) * 8 * 3600e3).toISOString(), ph: 7.35 + .12 * day + (r() - .5) * .14 - ev * .45, ec: ec, tds: ec * .5,
        do: 7.1 - .5 * day + (r() - .5) * .3 - ev * 1.9 - up * .34, temp: 27.4 + 1.4 * day + (r() - .5) * .4, turb: 3 + shift + (r() - .5) * 2 + ev * 7 + up * .35 });
    }
    return out;
  }
  var DH = { 'AIR-01': demoHist(2026, 0), 'AIR-02': demoHist(77, 1) };
  var DEMO = {
    limits: function () { return LIM; },
    devices: function () {
      var names = { 'AIR-01': ['Kolam Utama', 'LOK-01'], 'AIR-02': ['Embung Sampel', 'LOK-02'] };
      return Object.keys(DH).map(function (id) {
        var h = DH[id], l = h[h.length - 1], last = { ts: l.measured_at }; K.forEach(function (k) { last[k] = l[k]; });
        var st = {}; K.forEach(function (k) { st[k] = status(k, l[k]); });
        return { id: id, name: names[id][0], loc_code: names[id][1], last: last, last_seen: l.measured_at, status: st };
      });
    },
    history: function (id) { return { id: id, data: DH[id] || [] }; },
    predict: function (id) {
      var d = DEMO.devices().filter(function (x) { return x.id === id; })[0], pen = 0;
      K.forEach(function (k) { var s = d.status[k]; pen += s === 'crit' ? 2 : s === 'warn' ? .8 : 0; });
      var sc = [2.2 - pen * 1.1, .8 + Math.min(pen, 2.2) * .5 - Math.abs(pen - 1.4) * .2, -.6 + pen * .9], ex = sc.map(Math.exp), sm = ex.reduce(function (a, b) { return a + b; }), L = ['aman', 'waspada', 'tercemar'];
      var pr = ex.map(function (x) { return x / sm; });
      return { id: id, label: L[pr.indexOf(Math.max.apply(null, pr))], model: 'contoh (lokal)', probs: pr.map(function (p, i) { return { label: L[i], p: p }; }), basis_ts: d.last.ts };
    }
  };

  window.AT = { title: title, K: K, LIM: LIM, ST: ST, esc: esc, status: status, fmt: fmt, dt: dt, dd: dd, ago: ago, worst: worst, copy: copy, origin: origin, api: api, DEMO: DEMO, BASE: BASE };
})();
