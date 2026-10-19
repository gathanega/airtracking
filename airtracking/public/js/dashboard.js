/* Dashboard publik AirTracking. Data dari /api/public/*; bila server tidak terjangkau, memakai data contoh. */
(function () {
  const { K, LIM, ST, esc, status, fmt, dt, dd, ago, worst, copy, origin, api, title, DEMO, BASE } = AT;
  const $ = (id) => document.getElementById(id);
  const S = { devs: [], sel: null, par: 'ph', days: 14, hist: [], pred: null, demo: false, cx: null };
  const cur = () => S.devs.find((d) => d.id === S.sel);

  function get(path, demoFn) {
    return api(path).catch((e) => {
      if (!e.net) throw e;
      S.demo = true; $('demo').hidden = false;
      return demoFn();
    });
  }

  /* ---------- ringkasan + daftar alat ---------- */
  function renderTop() {
    const day = Date.now() - 864e5;
    const recent = S.devs.filter((d) => d.last_seen && Date.parse(d.last_seen) > day).length;
    const warn = S.devs.filter((d) => worst(d.status) === 'crit').length;
    $('tiles').innerHTML =
      `<div class="card tile"><span>Alat terdaftar</span><b>${S.devs.length}</b></div>` +
      `<div class="card tile"><span>Kirim data 24 jam terakhir</span><b>${recent}</b></div>` +
      `<div class="card tile${warn ? ' w' : ''}"><span>Perlu perhatian</span><b>${warn}</b></div>`;

    $('devs').innerHTML = S.devs.map((d) => {
      const w = worst(d.status);
      const pill = !d.last ? '<span class="pill off">Belum ada data</span>'
        : w === 'crit' ? '<span class="pill crit">Perlu perhatian</span>' : '<span class="pill ok">Baik</span>';
      const vals = d.last ? '<div class="vals">' + K.map((k) =>
        `<div class="${d.status && d.status[k] !== 'ok' ? d.status[k] || '' : ''}"><small>${esc(LIM[k].name)}</small><b>${fmt(k, d.last[k])}</b></div>`).join('') + '</div>'
        : '<div class="empty">Alat belum mengirim data</div>';
      return `<button class="card dev" data-id="${esc(d.id)}" aria-pressed="${d.id === S.sel}">` +
        `<div class="ch"><span>${esc(d.name)}</span>${pill}</div>` +
        `<div class="loc">${esc(d.id)} · ${esc(d.loc_code)} · ${ago(d.last_seen)}</div>${vals}</button>`;
    }).join('') || '<div class="empty" style="grid-column:1/-1">Belum ada alat terdaftar.</div>';

    $('devs').querySelectorAll('button.dev').forEach((b) => b.onclick = () => select(b.dataset.id));
  }

  function select(id) {
    S.sel = id; S.hist = []; S.pred = null; S.par = 'ph';
    history.replaceState(null, '', '#' + encodeURIComponent(id));
    renderTop(); renderDetail(); loadHist();
  }

  /* ---------- detail alat ---------- */
  function spark(vals) {
    if (vals.length < 2) return '';
    const w = 120, h = 26, mn = Math.min(...vals), mx = Math.max(...vals), s = mx - mn || 1;
    const pts = vals.map((v, i) => [(i / (vals.length - 1) * w).toFixed(1), (h - 2 - (v - mn) / s * (h - 6)).toFixed(1)]);
    const l = pts[pts.length - 1];
    return `<svg viewBox="0 0 ${w} ${h}" width="100%" height="26" aria-hidden="true"><polyline points="${pts.map((p) => p.join(',')).join(' ')}" fill="none" stroke="var(--blue)" stroke-width="1.6" stroke-linejoin="round"/><circle cx="${l[0]}" cy="${l[1]}" r="3" fill="var(--blue)" stroke="#fff" stroke-width="2"/></svg>`;
  }

  function calDate(s) { return s ? new Date(s + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '–'; }

  function renderDetail() {
    const d = cur(), box = $('detail');
    if (!d) { box.innerHTML = ''; return; }
    if (!d.last) { box.innerHTML = `<div class="panel"><h2>${esc(d.name)}</h2><div class="empty">Alat belum mengirim data</div></div>`; return; }

    const bat = d.battery_pct == null ? '–' : d.battery_pct + '%';
    const strip = `<section class="panel strip" aria-label="Status alat">` +
      `<div><div class="lbl">Sinkron terakhir</div><div class="v">${d.last_seen ? dt(d.last_seen) : '–'}</div></div>` +
      `<div><div class="lbl">Lokasi</div><div class="v">${esc(d.loc_code)}</div></div>` +
      `<div><div class="lbl">Baterai</div><div class="v">${bat}</div></div>` +
      `<div><div class="lbl">Kalibrasi pH</div><div class="v">${calDate(d.cal_ph)}</div></div>` +
      `<div><div class="lbl">Kalibrasi EC</div><div class="v">${calDate(d.cal_ec)}</div></div>` +
      `<div><div class="lbl">Belum terkirim (SD)</div><div class="v">${d.sd_pending == null ? '–' : d.sd_pending + ' data'}</div></div></section>`;

    const recent = S.hist.slice(-24);
    const cards = '<section class="pcards" aria-label="Pengukuran terbaru">' + K.map((k) => {
      const p = LIM[k], v = d.last[k], s = status(k, v);
      const rg = p.sides === 'lh' ? `${p.lo}–${p.hi}` : p.sides === 'h' ? `maks ${p.hi}` : `min ${p.lo}`;
      return `<button class="card pc" data-k="${k}" aria-pressed="${k === S.par}">` +
        `<div class="top"><span class="lbl">${esc(p.name)}</span>${s ? `<span class="pill ${s}"><span aria-hidden="true">${ST[s][0]}</span>${ST[s][1]}</span>` : ''}</div>` +
        `<div class="val">${fmt(k, v)}<small>${esc(p.unit)}</small></div>${spark(recent.map((r) => r[k]).filter((x) => x != null))}` +
        `<div class="rng">rentang ${rg}</div></button>`;
    }).join('') + '</section>';

    const chartPanel = `<section class="panel" aria-label="Grafik tren"><div class="head"><h2>Tren ${esc(LIM[S.par].name)}${LIM[S.par].unit ? ' (' + esc(LIM[S.par].unit) + ')' : ''}</h2>` +
      `<div class="seg" role="tablist" aria-label="Rentang waktu">${[7, 14, 30].map((n) => `<button class="tab" role="tab" aria-selected="${n === S.days}" data-d="${n}">${n} hari</button>`).join('')}</div></div>` +
      `<div class="chartbox" id="chartbox">${chartSvg()}<div class="tip" id="tip"></div></div>` +
      `<p class="note">Pita hijau adalah rentang normal. Titik merah di luar rentang. Klik kartu parameter untuk mengganti grafik.</p></section>`;

    const predPanel = `<section class="panel pred" aria-label="Prediksi kualitas air"><div><div class="lbl">Prediksi kualitas air</div>` +
      `<div class="small muted">Dari pengukuran terbaru</div></div><div id="pred">${predHtml()}</div>` +
      `<div class="row"><button class="btn" id="runpred">Jalankan prediksi</button></div>` +
      `<p class="note" style="margin:0">Saat ini aturan sederhana sebagai contoh, bukan model ML. Model COD/TSS menyusul.</p></section>`;

    const rows = S.hist.slice(-8).reverse();
    const table = `<section class="panel"><div class="head"><h2>Riwayat pengukuran</h2>` +
      (S.demo ? '' : `<a class="btn ghost s" href="${BASE}/api/public/devices/${encodeURIComponent(d.id)}/history.csv?days=30">Unduh CSV (30 hari)</a>`) + `</div>` +
      `<div class="tw"><table><tr><th>Waktu</th>${K.map((k) => `<th>${esc(LIM[k].name)}${LIM[k].unit ? ' (' + esc(LIM[k].unit) + ')' : ''}</th>`).join('')}</tr>` +
      (rows.map((r) => `<tr><td class="mono">${dt(r.measured_at)}</td>${K.map((k) => `<td class="mono ${status(k, r[k]) === 'crit' ? 'bad' : ''}">${fmt(k, r[k])}</td>`).join('')}</tr>`).join('') ||
        `<tr><td colspan="7" style="text-align:center;color:var(--ink2)">Memuat riwayat…</td></tr>`) + `</table></div></section>`;

    box.innerHTML = `<h2>${esc(d.name)} <span class="muted small">${esc(d.id)}</span></h2>` + strip + cards +
      `<div class="grid2">${chartPanel}${predPanel}</div>` + table;

    box.querySelectorAll('button.pc').forEach((b) => b.onclick = () => { S.par = b.dataset.k; renderDetail(); });
    box.querySelectorAll('.tab').forEach((b) => b.onclick = () => { S.days = +b.dataset.d; renderDetail(); });
    $('runpred').onclick = runPred;
    bindChart();
  }

  /* ---------- grafik tren ---------- */
  function chartSvg() {
    const k = S.par, p = LIM[k], cut = Date.now() - S.days * 864e5;
    const pts = S.hist.filter((r) => r[k] != null && Date.parse(r.measured_at) >= cut).map((r) => ({ t: Date.parse(r.measured_at), v: +r[k] }));
    S.cx = null;
    if (pts.length < 2) return '<div class="empty">Data riwayat belum cukup untuk grafik (minimal 2 data)</div>';

    const W = 760, H = 300, L = 48, R = 14, T = 14, B = 30, vs = pts.map((x) => x.v), mx = Math.max(...vs);
    let lo = Math.min(...vs, p.lo), hi = Math.max(mx, Math.min(p.hi, mx * 1.15 + 1));
    const pad = (hi - lo) * .08 || 1; lo -= pad; hi += pad; if (p.lo === 0) lo = Math.max(0, lo);
    const x0 = pts[0].t, x1 = pts[pts.length - 1].t || x0 + 1;
    const X = (t) => L + (t - x0) / ((x1 - x0) || 1) * (W - L - R), Y = (v) => T + (1 - (v - lo) / (hi - lo)) * (H - T - B);

    let g = '';
    for (let i = 0; i <= 4; i++) { const v = lo + (hi - lo) * i / 4, y = Y(v); g += `<line x1="${L}" x2="${W - R}" y1="${y}" y2="${y}" stroke="var(--grid)"/><text x="${L - 8}" y="${y + 4}" text-anchor="end" font-size="11" fill="var(--ink3)">${v.toFixed(p.dec > 1 ? 1 : p.dec)}</text>`; }
    const top = Y(Math.min(p.hi, hi)), bot = Y(Math.max(p.lo, lo));
    g = `<rect x="${L}" y="${top}" width="${W - L - R}" height="${Math.max(0, bot - top)}" fill="var(--good2)" opacity=".75"/>` + g;
    for (let i = 0; i <= 4; i++) { const t = x0 + (x1 - x0) * i / 4; g += `<text x="${X(t)}" y="${H - 8}" text-anchor="${i === 0 ? 'start' : i === 4 ? 'end' : 'middle'}" font-size="11" fill="var(--ink3)">${dd(t)}</text>`; }
    g += `<polyline fill="none" stroke="var(--blue)" stroke-width="2" stroke-linejoin="round" points="${pts.map((q) => X(q.t).toFixed(1) + ',' + Y(q.v).toFixed(1)).join(' ')}"/>`;
    pts.forEach((q) => { if (status(k, q.v) === 'crit') g += `<circle cx="${X(q.t)}" cy="${Y(q.v)}" r="3.5" fill="var(--crit)" stroke="#fff" stroke-width="2"/>`; });
    const e = pts[pts.length - 1];
    g += `<circle cx="${X(e.t)}" cy="${Y(e.v)}" r="5" fill="var(--blue)" stroke="#fff" stroke-width="2"/>` +
      `<line id="xh" y1="${T}" y2="${H - B}" stroke="var(--ink3)" stroke-dasharray="3 3" style="display:none"/><circle id="dot" r="5" fill="var(--blue)" stroke="#fff" stroke-width="2" style="display:none"/>`;
    S.cx = { pts, W, H, L, R, x0, x1, X, Y };
    return `<svg class="chart" id="chart" viewBox="0 0 ${W} ${H}" role="img" aria-label="Grafik tren ${esc(p.name)}">${g}</svg>`;
  }

  function bindChart() {
    const c = S.cx, svg = $('chart'); if (!c || !svg) return;
    const tip = $('tip'), xh = svg.querySelector('#xh'), dot = svg.querySelector('#dot'), p = LIM[S.par];
    svg.onpointermove = (ev) => {
      const r = svg.getBoundingClientRect(), t = c.x0 + ((ev.clientX - r.left) / r.width * c.W - c.L) / (c.W - c.L - c.R) * (c.x1 - c.x0);
      let b = c.pts[0]; for (const q of c.pts) if (Math.abs(q.t - t) < Math.abs(b.t - t)) b = q;
      const cx = c.X(b.t), cy = c.Y(b.v), s = status(S.par, b.v);
      xh.setAttribute('x1', cx); xh.setAttribute('x2', cx); xh.style.display = '';
      dot.setAttribute('cx', cx); dot.setAttribute('cy', cy); dot.style.display = '';
      tip.style.display = 'block'; tip.style.left = (cx / c.W * r.width) + 'px'; tip.style.top = (cy / c.H * r.height) + 'px';
      tip.textContent = dt(b.t) + '  ' + b.v.toFixed(p.dec) + ' ' + p.unit + (s && s !== 'ok' ? '  ' + ST[s][1] : '');
    };
    svg.onpointerleave = () => { tip.style.display = 'none'; xh.style.display = 'none'; dot.style.display = 'none'; };
  }

  /* ---------- prediksi ---------- */
  function predHtml() {
    const p = S.pred; if (!p) return '<div class="predclass">–</div>';
    return `<div class="predclass">${esc(title(p.label))}</div><div class="bars">` + p.probs.map((x) =>
      `<div class="bar"><span>${esc(title(x.label))}</span><div class="tr"><div class="fl" style="width:${(x.p * 100).toFixed(0)}%"></div></div><span class="mono">${(x.p * 100).toFixed(0)}%</span></div>`).join('') +
      `</div><div class="small muted" style="margin-top:6px">Berdasarkan data ${p.basis_ts ? dt(p.basis_ts) : '–'} · model ${esc(p.model || '')}</div>`;
  }
  function runPred() {
    const id = S.sel, b = $('runpred'); if (b) { b.disabled = true; b.textContent = 'Memproses…'; }
    get('/api/public/devices/' + encodeURIComponent(id) + '/predict', () => DEMO.predict(id))
      .then((r) => { if (S.sel === id) { S.pred = r; if ($('pred')) $('pred').innerHTML = predHtml(); } })
      .catch(() => { if ($('pred')) $('pred').innerHTML = '<div class="empty">Prediksi belum tersedia</div>'; })
      .then(() => { const b2 = $('runpred'); if (b2) { b2.disabled = false; b2.textContent = 'Jalankan prediksi'; } });
  }

  /* ---------- batas + panel API ---------- */
  function renderStatic() {
    $('limpanel').innerHTML = '<h2 style="margin-bottom:10px">Rentang normal yang dipakai</h2><div class="tw"><table class="l"><tr><th>Parameter</th><th>Rentang</th><th>Satuan</th></tr>' +
      K.map((k) => { const p = LIM[k]; return `<tr><td>${esc(p.name)}</td><td class="mono">${p.sides === 'lh' ? p.lo + ' – ' + p.hi : p.sides === 'h' ? '≤ ' + p.hi : '≥ ' + p.lo}</td><td>${esc(p.unit || '–')}</td></tr>`; }).join('') +
      '</table></div><p class="note">Angka acuan awal; admin dapat menyesuaikannya dengan baku mutu penelitian.</p>';

    const o = origin(), id = (cur() || {}).id || 'AIR-01';
    const eps = [['GET', '/api/public/limits', 'Rentang normal per parameter'], ['GET', '/api/public/devices', 'Daftar alat + bacaan terakhir + status'],
      ['GET', `/api/public/devices/${id}`, 'Satu alat'], ['GET', `/api/public/devices/${id}/history?days=7&limit=500`, 'Riwayat (JSON)'],
      ['GET', `/api/public/devices/${id}/history.csv?days=30`, 'Riwayat (CSV)'], ['GET', `/api/public/devices/${id}/predict`, 'Prediksi kualitas air']];
    const sample = JSON.stringify(cur() || { id: 'AIR-01', name: 'Kolam Utama', loc_code: 'LOK-01', last: { ts: '2026-10-17T08:30:00Z', ph: 7.1, ec: 420, tds: 210, do: 6.8, temp: 27.5, turb: 3.2 } }, null, 2);
    const send = JSON.stringify({ id, key: '<credential-alat>', loc: (cur() || {}).loc_code || 'LOK-01', ph: 7.1, ec: 420, tds: 210, do: 6.8, temp: 27.5, turb: 3.2 }, null, 2);
    $('apipanel').innerHTML = '<div class="head"><h2>API JSON</h2></div><p class="note" style="margin:0 0 10px">Data dapat diambil siapa saja tanpa login (hanya baca). Batas 60 permintaan per menit per alamat IP.</p>' +
      '<div class="tw"><table class="l"><tr><th>Metode</th><th>Alamat</th><th>Fungsi</th></tr>' +
      eps.map((e) => `<tr><td class="mono">${e[0]}</td><td class="mono">${esc(e[1])}</td><td>${e[2]}</td></tr>`).join('') + '</table></div>' +
      `<div class="cols" style="margin-top:14px"><div><div class="head"><h3>Contoh respons satu alat</h3><button class="btn s ghost" data-cp="r">Salin</button></div><pre id="cp_r">${esc(sample)}</pre></div>` +
      `<div><div class="head"><h3>Kirim data dari alat</h3><button class="btn s ghost" data-cp="s">Salin</button></div><pre id="cp_s">POST ${esc(o)}/api/ingest\nContent-Type: application/json\n\n${esc(send)}</pre></div></div>` +
      '<p class="note">Satu paket bisa berisi banyak data dari SD card lewat <span class="mono">"records": [ {"ts": "...", "ph": 7.1, ...} ]</span>.</p>';
    $('apipanel').querySelectorAll('[data-cp]').forEach((b) => b.onclick = () => copy($('cp_' + b.dataset.cp).textContent, b));
  }

  /* ---------- muat data ---------- */
  function loadHist() {
    const id = S.sel; if (!id) return Promise.resolve();
    return get('/api/public/devices/' + encodeURIComponent(id) + '/history?days=30&limit=1000', () => DEMO.history(id))
      .then((r) => { if (S.sel !== id) return; S.hist = r.data || []; renderDetail(); renderStatic(); if (!S.pred) runPred(); });
  }

  function load() {
    return get('/api/public/limits', DEMO.limits).then((l) => { Object.keys(l).forEach((k) => { LIM[k] = l[k]; }); })
      .then(() => get('/api/public/devices', DEMO.devices))
      .then((d) => {
        S.devs = d;
        const h = decodeURIComponent(location.hash.slice(1));
        if (!S.devs.some((x) => x.id === S.sel)) S.sel = S.devs.some((x) => x.id === h) ? h : (S.devs[0] || {}).id || null;
        renderTop(); renderDetail(); renderStatic(); return loadHist();
      });
  }

  setInterval(() => {
    if (S.demo) return;
    api('/api/public/devices').then((d) => {
      const o = cur(); S.devs = d; const n = cur();
      renderTop();
      if (n && (!o || o.last_seen !== n.last_seen)) loadHist();
    }).catch(() => {});
  }, 15000);

  load().catch((e) => { $('devs').innerHTML = `<div class="empty" style="grid-column:1/-1">Gagal memuat data: ${esc(e.message)}</div>`; });
})();
