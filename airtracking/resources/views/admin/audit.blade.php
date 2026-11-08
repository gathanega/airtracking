@extends('layouts.app')
@section('title', 'Audit admin · AirTracking')
@section('content')
@include('partials.admin-nav')

<section class="panel">
  <div class="head">
    <div>
      <h2>Audit admin</h2>
      <p class="note" style="margin:4px 0 0">100 aksi terbaru: login, logout, dan perubahan data oleh admin. Credential, kata sandi, dan token tidak pernah dicatat.</p>
    </div>
    <a class="btn s ghost" href="{{ route('admin.devices') }}">Kelola alat</a>
  </div>

  <div class="tw" style="margin-top:12px"><table class="l">
    <tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Subjek</th><th>IP</th><th>Ringkasan</th></tr>
    @forelse ($logs as $log)
      <tr>
        <td class="mono">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '–' }}</td>
        <td>{{ $log->user?->email ?? '–' }}</td>
        <td class="mono">{{ $log->action }}</td>
        <td class="mono">{{ $log->subject_type ? class_basename($log->subject_type).'#'.$log->subject_id : '–' }}</td>
        <td class="mono">{{ $log->ip ?: '–' }}</td>
        <td class="mono" style="white-space:pre-wrap">{{ $log->meta ? json_encode($log->meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '–' }}</td>
      </tr>
    @empty
      <tr><td colspan="6" style="text-align:center;color:var(--ink2)">Belum ada catatan audit.</td></tr>
    @endforelse
  </table></div>
</section>
@endsection
