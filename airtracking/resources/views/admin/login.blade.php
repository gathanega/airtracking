@extends('layouts.app')
@section('title', 'Masuk admin · AirTracking')
@section('content')
<div class="login panel">
  <h1>Air<b>Tracking</b></h1>
  <p class="sub" style="margin:4px 0 16px">Masuk khusus admin untuk mengelola alat. Dashboard publik tidak memerlukan login.</p>
  <form method="POST" action="{{ route('admin.login.post') }}">
    @csrf
    <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
    <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label>
    @error('email')<div class="err" role="alert">{{ $message }}</div>@enderror
    <button class="btn" type="submit" style="width:100%">Masuk</button>
  </form>
  <p class="note"><a href="{{ route('home') }}">‹ Kembali ke dashboard publik</a></p>
</div>
@endsection
