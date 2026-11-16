@extends('layouts.rawatkasih')
@section('title', 'Lupa kata sandi — RawatKasih')
@section('content')
<main id="main" class="rk-recovery rk-forgot"><div class="rk-card">
    <h1>Atur ulang kata sandi</h1><p class="rk-description">Masukkan email terdaftar untuk menerima tautan reset.</p>
    @if (session('status'))<div class="rk-notice" role="status">{{ session('status') }}</div>@endif
    <form class="rk-form" method="post" action="{{ route('password.email') }}">@csrf
        <div><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @error('email')<p id="email-error" class="rk-error" role="alert">{{ $message }}</p>@enderror</div>
        <button type="submit" class="rk-button">Kirim tautan reset</button>
    </form><a class="rk-back" href="{{ route('login') }}">← Kembali ke halaman masuk</a>
</div></main>
@endsection
