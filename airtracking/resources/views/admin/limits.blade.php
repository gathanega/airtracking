@extends('layouts.app')
@section('title', 'Rentang normal · AirTracking')
@section('content')
@include('partials.admin-nav')
<section class="panel">
  <h2>Rentang normal (baku mutu)</h2>
  <p class="note" style="margin-bottom:12px">Dipakai untuk status Normal / Waspada / Di luar rentang di dashboard dan prediksi. Angka awal hanya acuan, sesuaikan dengan baku mutu penelitian.</p>
  <form method="POST" action="{{ route('admin.limits.update') }}">
    @csrf @method('PUT')
    <div class="tw"><table>
      <tr><th>Parameter</th><th>Satuan</th><th>Batas bawah</th><th>Batas atas</th><th>Waspada dekat</th></tr>
      @foreach ($limits as $l)
        <tr>
          <td>{{ $l->name }}</td><td>{{ $l->unit ?: '–' }}</td>
          <td><input type="number" step="any" name="limits[{{ $l->param }}][lo]" value="{{ old('limits.'.$l->param.'.lo', $l->lo) }}" required></td>
          <td><input type="number" step="any" name="limits[{{ $l->param }}][hi]" value="{{ old('limits.'.$l->param.'.hi', $l->hi) }}" required></td>
          <td>{{ ['l' => 'batas bawah', 'h' => 'batas atas', 'lh' => 'kedua batas'][$l->sides] ?? $l->sides }}</td>
        </tr>
      @endforeach
    </table></div>
    @if ($errors->any())<div class="err" role="alert" style="margin-top:8px">{{ $errors->first() }}</div>@endif
    <button class="btn" type="submit" style="margin-top:12px">Simpan</button>
  </form>
</section>
@endsection
