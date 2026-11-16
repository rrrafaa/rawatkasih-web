@extends('layouts.rawatkasih')
@section('title', 'Masuk — RawatKasih')
@section('content')
<div class="rk-auth">
    <aside class="rk-story" aria-label="Tentang RawatKasih">
        <div class="rk-login-logo"><img src="{{ asset('images/rawatkasih-mark.svg') }}" alt="Logo RawatKasih" width="72.609" height="77.066"></div>
        <h1>RawatKasih</h1>
        <p>Ruang perawatan yang hangat, aman dan terhubung.</p>
    </aside>
    <main id="main" class="rk-login-main">
        <div class="rk-mobile-brand"><x-rk-brand /></div>
        <div class="rk-form-wrap">
            <h2>Masuk ke akun</h2>
            <p class="rk-description">Gunakan email dan kata sandi terdaftar.</p>
            @if (session('status'))<div class="rk-notice" role="status">{{ session('status') }}</div>@endif
            <form method="post" action="{{ route('login.store') }}" class="rk-form">
                @csrf
                <div><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@example.test" autocomplete="username" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<p id="email-error" class="rk-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div><label for="password">Kata sandi</label>
                    <div class="rk-password"><input id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false">Lihat</button></div>
                    @error('password')<p id="password-error" class="rk-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <button class="rk-button" type="submit">Masuk</button>
            </form>
            <a class="rk-reset-link" href="{{ route('password.request') }}">Lupa kata sandi? <span aria-hidden="true">→</span> Reset via email terverifikasi</a>
        </div>
    </main>
</div>
@endsection
