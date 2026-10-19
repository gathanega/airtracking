@extends('layouts.app')
@section('title', 'Kelola alat · AirTracking')
@section('content')
@include('partials.admin-nav')

@if (session('credential'))
  <div class="keybox" role="alert">
    <b>Credential alat {{ session('credential.code') }}:</b> <code id="newkey">{{ session('credential.key') }}</code>
    <button class="btn s ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('newkey').textContent)">Salin</button>
    <div class="note">Catat sekarang dan masukkan ke alat lewat halaman AP. Credential tidak bisa dilihat lagi; bila hilang, gunakan “Ganti credential”.</div>
  </div>
@endif

<section class="panel">
  <h2 style="margin-bottom:10px">Registrasi alat baru</h2>
  <form method="POST" action="{{ route('admin.devices.store') }}">
    @csrf
    <div class="f">
      <label>ID alat<input name="code" value="{{ old('code') }}" placeholder="AIR-01" required></label>
      <label>Nama<input name="name" value="{{ old('name') }}" placeholder="Kolam Utama" required></label>
      <label>Kode lokasi<input name="location_code" value="{{ old('location_code') }}" placeholder="LOK-01" required></label>
      <label>Latitude (opsional)<input name="latitude" value="{{ old('latitude') }}" inputmode="decimal" placeholder="-7.9800"></label>
      <label>Longitude (opsional)<input name="longitude" value="{{ old('longitude') }}" inputmode="decimal" placeholder="112.6300"></label>
      <label>Credential (kosongkan untuk dibuat acak)<input name="credential" minlength="8" autocomplete="off"></label>
    </div>
    @if ($errors->any())<div class="err" role="alert">{{ $errors->first() }}</div>@endif
    <button class="btn" type="submit">Tambah alat</button>
  </form>
</section>

<section class="panel">
  <h2 style="margin-bottom:10px">Alat terdaftar ({{ $devices->count() }})</h2>
  <div class="tw"><table>
    <tr><th>ID</th><th>Nama</th><th>Lokasi</th><th>Koordinat</th><th>Aktif</th><th>Credential</th><th>Terakhir kirim</th><th></th></tr>
    @forelse ($devices as $d)
      <tr>
        <td class="mono">{{ $d->code }}</td>
        <td><input form="u{{ $d->id }}" name="name" value="{{ $d->name }}" aria-label="Nama {{ $d->code }}"></td>
        <td><input form="u{{ $d->id }}" name="location_code" value="{{ $d->location_code }}" aria-label="Lokasi {{ $d->code }}"></td>
        <td class="mono">@if ($d->latitude !== null)<a href="{{ route('admin.locations', ['alat' => $d->code]) }}">{{ number_format($d->latitude, 5) }}, {{ number_format($d->longitude, 5) }}</a>@else<a href="{{ route('admin.locations', ['alat' => $d->code]) }}">Tandai</a>@endif</td>
        <td><label><input form="u{{ $d->id }}" type="checkbox" name="is_active" value="1" @checked($d->is_active)>Ya</label></td>
        <td class="mono">{{ $d->credential ? '••••'.$d->credential->key_hint : '–' }}</td>
        <td>{{ $d->last_seen_at ? $d->last_seen_at->diffForHumans() : 'belum pernah' }}</td>
        <td style="white-space:nowrap">
          <form id="u{{ $d->id }}" class="inline" method="POST" action="{{ route('admin.devices.update', $d) }}">@csrf @method('PUT')<button class="btn s" type="submit">Simpan</button></form>
          <form class="inline" method="POST" action="{{ route('admin.devices.rotate', $d) }}" onsubmit="return confirm('Ganti credential {{ $d->code }}? Alat harus disetel ulang dengan credential baru.')">@csrf<button class="btn s ghost" type="submit">Ganti credential</button></form>
          <form class="inline" method="POST" action="{{ route('admin.devices.destroy', $d) }}" onsubmit="return confirm('Hapus {{ $d->code }} beserta seluruh riwayat datanya?')">@csrf @method('DELETE')<button class="btn s d" type="submit">Hapus</button></form>
        </td>
      </tr>
    @empty
      <tr><td colspan="8" style="text-align:center;color:var(--ink2)">Belum ada alat.</td></tr>
    @endforelse
  </table></div>
</section>
@endsection
