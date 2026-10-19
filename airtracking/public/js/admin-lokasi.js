/* Admin: tandai titik alat di peta (klik / geser / ketik koordinat), lalu simpan lewat form. */
(function () {
  var CFG = window.LOKASI || { devices: [], action: '' };
  var $ = function (id) { return document.getElementById(id); };
  if (!$('dsel')) return;

  var map = PETA.create('peta'), others = L.layerGroup().addTo(map), cur = null, marker = null;
  var DEV = CFG.devices, byId = {}; DEV.forEach(function (d) { byId[d.id] = d; });

  $('dsel').innerHTML = DEV.map(function (d) {
    return '<option value="' + d.id + '">' + esc(d.code) + ' · ' + esc(d.name) + (d.lat == null ? ' (belum ditandai)' : '') + '</option>';
  }).join('');
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function num(v) { v = String(v).trim().replace(',', '.'); return v !== '' && isFinite(v) ? Number(v) : null; }
  function valid(lat, lng) { return lat != null && lng != null && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180; }

  function hint() {
    var lat = num($('lat').value), lng = num($('lng').value), ok = valid(lat, lng), h = $('hint');
    $('save').disabled = !ok;
    h.className = 'mapnote';
    if (!ok) { h.textContent = 'Klik peta untuk menaruh titik.'; return; }
    if (!PETA.inArea(lat, lng)) { h.className = 'mapnote warnx'; h.textContent = 'Titik ini di luar area Malang dan sekitarnya. Pastikan sudah benar.'; return; }
    h.textContent = 'Titik siap disimpan.';
  }

  function setPoint(lat, lng, pan) {
    $('lat').value = lat.toFixed(6); $('lng').value = lng.toFixed(6);
    if (!marker) {
      marker = L.marker([lat, lng], { icon: PETA.pin('sel'), draggable: true, title: 'Geser untuk memindah', alt: 'Titik alat' }).addTo(map);
      marker.on('dragend', function () { var p = marker.getLatLng(); setPoint(p.lat, p.lng, false); });
    } else marker.setLatLng([lat, lng]);
    if (pan) map.panTo([lat, lng]);
    hint();
  }

  function drawOthers() {
    others.clearLayers();
    DEV.forEach(function (d) {
      if (d.id === cur || d.lat == null) return;
      var m = L.marker([d.lat, d.lng], { icon: PETA.pin('off', 'dim'), title: d.code + ' · ' + d.name, alt: d.name }).addTo(others);
      m.bindTooltip(esc(d.code) + ' · ' + esc(d.name));
      m.on('click', function () { $('dsel').value = d.id; choose(d.id, true); });
    });
  }

  function choose(id, fly) {
    cur = Number(id); var d = byId[cur];
    $('lform').action = CFG.action.replace('__ID__', encodeURIComponent(cur));
    if (marker) { map.removeLayer(marker); marker = null; }
    $('lat').value = ''; $('lng').value = '';
    if (d.lat != null) { setPoint(d.lat, d.lng, false); if (fly) map.flyTo([d.lat, d.lng], Math.max(map.getZoom(), 15), { duration: .5 }); }
    else { if (fly) map.flyTo(PETA.MALANG, PETA.ZOOM, { duration: .5 }); hint(); }
    drawOthers();
  }

  $('dsel').onchange = function () { choose(this.value, true); };
  map.on('click', function (e) { setPoint(e.latlng.lat, e.latlng.lng, false); });
  ['lat', 'lng'].forEach(function (id) {
    $(id).addEventListener('input', function () {
      var lat = num($('lat').value), lng = num($('lng').value);
      if (valid(lat, lng)) {
        if (!marker) setPoint(lat, lng, true);
        else { marker.setLatLng([lat, lng]); map.panTo([lat, lng]); hint(); }
      } else hint();
    });
  });
  $('reset').onclick = function () { map.flyTo(PETA.MALANG, PETA.ZOOM, { duration: .6 }); };

  // Alat awal: dari ?alat=KODE (setelah simpan atau dari tabel Kelola alat), bila tidak ada pakai yang pertama.
  var q = new URLSearchParams(location.search).get('alat'), start = DEV.filter(function (d) { return d.code === q; })[0] || DEV[0];
  $('dsel').value = start.id; choose(start.id, false);
  if (start.lat != null) map.setView([start.lat, start.lng], 15);
})();
