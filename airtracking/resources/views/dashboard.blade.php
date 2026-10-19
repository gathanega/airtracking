@extends('layouts.app')
@section('title', 'AirTracking · Dashboard kualitas air')
@section('content')
<div>
  <h1>Dashboard kualitas air</h1>
  <div class="sub">Terbuka untuk umum, tanpa login</div>
</div>
<span class="badge-ex" id="demo" hidden style="align-self:flex-start">Data contoh, server belum terhubung</span>

<section class="tiles" id="tiles" aria-label="Ringkasan"></section>
<h2>Daftar alat</h2>
<section class="grid" id="devs"></section>
<div id="detail" style="display:flex;flex-direction:column;gap:16px"></div>
<section class="panel" id="limpanel"></section>
<section class="panel" id="apipanel"></section>
@endsection

@push('scripts')
<script>window.AIR_CONFIG = { API_BASE: "" };</script>
<script src="{{ asset('js/airtracking.js') }}"></script>
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
