@extends('layouts.rawatkasih')
@section('title', 'Pengajuan sudah berubah — RawatKasih')
@section('content')
<main id="main" class="rk-recovery"><x-rk-brand/><div class="rk-card"><h1>Pengajuan sudah berubah</h1><p>{{ $message }}</p><a class="rk-back" href="{{ auth()->user()?->role()->value === 'admin' ? route('admin.visits.index') : route('family.visits.index') }}">Muat ulang daftar kunjungan</a></div></main>
@endsection
