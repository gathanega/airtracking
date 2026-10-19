/* Peta lokasi publik. Titik diambil dari koordinat alat (/api/public/devices: lat, lng). */
(function () {
  var A = AT, $ = function (id) { return document.getElementById(id); };
  var HOME = (window.AIR_CONFIG && AIR_CONFIG.HOME) || '/';
  var map, group, markers = {}, devs = [], first = true;

  function state(d) { return d.last ? A.worst(d.status) : 'off'; }
  var LABEL = { ok: 'Normal', warn: 'Waspada', crit: 'Perlu perhatian', off: 'Belum ada data' };
  var hasXY = function (d) { return typeof d.lat === 'number' && typeof d.lng === 'number'; };

  function vals(d) {
    return '<div class="pv">' + A.K.map(function (k) {
      var s = d.status && d.status[k] && d.status[k] !== 'ok' ? d.status[k] : '';
      return '<div class="' + s + '"><small>' + A.esc(A.LIM[k].name) + '</small><b>' + A.fmt(k, d.last[k]) + '</b></div>';
    }).join('') + '</div>';
  }

  function popup(d) {
    return '<div class="pop"><b>' + A.esc(d.name) + '</b>' +
      '<div class="small muted">' + A.esc(d.id) + ' · ' + A.esc(d.loc_code) + ' · ' + A.ago(d.last_seen) + '</div>' +
      (d.last ? vals(d) : '<div class="note">Alat belum mengirim data.</div>') +
      '<div class="coord">' + d.lat.toFixed(5) + ', ' + d.lng.toFixed(5) + '</div>' +
      '<a href="' + A.esc(HOME) + '#' + encodeURIComponent(d.id) + '">Lihat detail &rarr;</a></div>';
  }

  function tiles() {
    var on = devs.filter(hasXY).length, warn = devs.filter(function (d) { return state(d) === 'crit'; }).length;
    $('tiles').innerHTML =
      '<div class="card tile"><span>Titik di peta</span><b>' + on + '</b></div>' +
      '<div class="card tile"><span>Belum ditandai</span><b>' + (devs.length - on) + '</b></div>' +
      '<div class="card tile' + (warn ? ' w' : '') + '"><span>Perlu perhatian</span><b>' + warn + '</b></div>';
  }

  function list() {
    var on = devs.filter(hasXY), off = devs.filter(function (d) { return !hasXY(d); });
    $('list').innerHTML = on.map(function (d) {
      var s = state(d);
      return '<button class="card dev loc-item" type="button" data-id="' + A.esc(d.id) + '">' +
        '<div class="ch"><span>' + A.esc(d.name) + '</span><span class="pill ' + (s === 'off' ? 'off' : s) + '">' + LABEL[s] + '</span></div>' +
        '<div class="loc">' + A.esc(d.id) + ' · ' + A.esc(d.loc_code) + ' · ' + A.ago(d.last_seen) + '</div>' +
        '<div class="coord">' + d.lat.toFixed(5) + ', ' + d.lng.toFixed(5) + '</div></button>';
    }).join('') || '<div class="empty">Belum ada alat dengan koordinat. Admin dapat menandai titik di menu “Titik lokasi”.</div>';

    $('list').querySelectorAll('button.loc-item').forEach(function (b) {
      b.onclick = function () {
        var m = markers[b.dataset.id]; if (!m) return;
        map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 15), { duration: .6 }); m.openPopup();
        $('peta').scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      };
    });

    $('nocoord').innerHTML = off.length
      ? '<p class="note">Belum punya koordinat: ' + off.map(function (d) { return '<code>' + A.esc(d.id) + '</code>'; }).join(' ') + '</p>' : '';
  }

  function draw() {
    var seen = {};
    devs.filter(hasXY).forEach(function (d) {
      seen[d.id] = 1;
      var m = markers[d.id], ll = [d.lat, d.lng], icon = PETA.pin(state(d));
      if (!m) {
        m = markers[d.id] = L.marker(ll, { icon: icon, title: d.name, alt: d.name }).addTo(group);
        m.bindPopup('', { maxWidth: 300 });
      } else { m.setLatLng(ll); m.setIcon(icon); }
      m.getPopup().setContent(popup(d));
    });
    Object.keys(markers).forEach(function (id) { if (!seen[id]) { group.removeLayer(markers[id]); delete markers[id]; } });

    if (first) {
      first = false;
      var pts = devs.filter(hasXY).map(function (d) { return [d.lat, d.lng]; });
      if (pts.length === 1) map.setView(pts[0], 15);
      else if (pts.length > 1) map.fitBounds(pts, { padding: [50, 50], maxZoom: 16 });
    }
  }

  function load() {
    return A.api('/api/public/devices').then(function (r) {
      $('err').textContent = ''; devs = r; tiles(); list(); draw();
    }, function (e) { $('err').textContent = 'Gagal memuat data alat: ' + e.message; });
  }

  map = PETA.create('peta');
  group = L.layerGroup().addTo(map);
  $('reset').onclick = function () { map.flyTo(PETA.MALANG, PETA.ZOOM, { duration: .6 }); };
  load();
  setInterval(load, 60000);
})();
