{{-- Header admin dirender oleh layouts/app (partials/site-header). Di sini hanya pesan sukses. --}}
@if (session('ok'))<div class="flash" role="status">{{ session('ok') }}</div>@endif
