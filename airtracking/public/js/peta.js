/* AirTracking - dasar peta (Leaflet + OpenStreetMap), dipakai halaman publik dan admin. */
window.PETA = (function () {
  var MALANG = [-7.9797, 112.6304], ZOOM = 12;           // pusat Kota Malang
  var BOUNDS = { lat: [-8.2, -7.7], lng: [112.4, 112.9] }; // Malang dan sekitarnya (untuk peringatan saja)

  function create(id) {
    var m = L.map(id).setView(MALANG, ZOOM);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
    }).addTo(m);
    return m;
  }

  function pin(state, extra) {
    return L.divIcon({
      className: 'pin-wrap',
      html: '<span class="pin ' + state + (extra ? ' ' + extra : '') + '"></span>',
      iconSize: [26, 26], iconAnchor: [13, 13], popupAnchor: [0, -12]
    });
  }

  function inArea(lat, lng) {
    return lat >= BOUNDS.lat[0] && lat <= BOUNDS.lat[1] && lng >= BOUNDS.lng[0] && lng <= BOUNDS.lng[1];
  }

  return { MALANG: MALANG, ZOOM: ZOOM, create: create, pin: pin, inArea: inArea };
})();
