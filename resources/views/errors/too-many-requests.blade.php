@extends('layouts.rawatkasih')
@section('title', 'Terlalu banyak permintaan — RawatKasih')
@section('content')
<main id="main" class="rk-recovery">
    <x-rk-brand />
    <div class="rk-card">
        <h1>Terlalu banyak percobaan</h1>
        <p class="rk-description">Demi keamanan akun, permintaan sementara dibatasi. Silakan coba kembali setelah satu menit.</p>
        <a class="rk-back" href="{{ route('login') }}">← Kembali ke halaman masuk</a>
    </div>
</main>
@endsection
