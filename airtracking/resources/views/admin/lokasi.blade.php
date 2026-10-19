@extends('layouts.app')
@section('title', 'Titik lokasi · AirTracking')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="{{ asset('css/lokasi.css') }}">
@endpush

@section('content')
@include('partials.admin-nav')

<div class="lokgrid">
  <section class="card mapbox" aria-label="Peta penandaan titik">
    <div id="peta"></div>
    <div class="maplegend">
      <span><i class="sel" style="background:var(--blue)"></i>Alat yang sedang diatur</span>
      <span><i class="off"></i>Alat lain</span>
      <button class="btn s ghost" type="button" id="reset" style="margin-left:auto">Pusatkan ke Malang</button>
    </div>
  </section>

  <section class="panel">
    <h2 style="margin-bottom:6px">Tandai titik alat</h2>
    <p class="note" style="margin:0 0 12px">Pilih alat, lalu klik peta atau geser penanda. Koordinat juga bisa diketik. Titik tampil di halaman <a href="{{ route('lokasi') }}">Peta lokasi</a>.</p>

    @if ($devices->isEmpty())
      <div class="empty">Belum ada alat. Daftarkan alat dulu di <a href="{{ route('admin.devices') }}">Kelola alat</a>.</div>
    @else
      <form id="lform" method="POST" action="#">
        @csrf @method('PUT')
        <label>Alat<select id="dsel" aria-label="Pilih alat"></select></label>
        <div class="pickrow">
          <label>Latitude<input id="lat" name="latitude" inputmode="decimal" placeholder="-7.98000" autocomplete="off"></label>
          <label>Longitude<input id="lng" name="longitude" inputmode="decimal" placeholder="112.63000" autocomplete="off"></label>
        </div>
        @if ($errors->any())<div class="err" role="alert">{{ $errors->first() }}</div>@endif
        <div class="mapnote" id="hint" role="status"></div>
        <div class="row" style="margin-top:10px">
          <button class="btn" type="submit" id="save" disabled>Simpan titik</button>
        </div>
      </form>
    @endif
  </section>
</div>
@endsection

@push('scripts')
<script>
  window.LOKASI = {
    devices: @json($devices),
    action: @json(route('admin.devices.location', ['device' => '__ID__'])),
  };
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/peta.js') }}"></script>
<script src="{{ asset('js/admin-lokasi.js') }}"></script>
@endpush
