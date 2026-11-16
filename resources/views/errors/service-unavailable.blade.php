@extends('layouts.rawatkasih')
@section('title', 'Layanan belum tersedia — RawatKasih')
@section('content')
<main id="main" class="rk-recovery"><x-rk-brand /><div class="rk-card"><h1>Belum dapat terhubung</h1><p class="rk-description">Layanan belum dapat dihubungi. Silakan coba kembali. Tindakan Anda belum dikonfirmasi berhasil.</p><a class="rk-back" href="{{ route('login') }}">Kembali ke halaman masuk</a></div></main>
@endsection
