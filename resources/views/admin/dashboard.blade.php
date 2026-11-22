
@extends('layouts.admin')

@section('title', 'Dashboard Admin — RawatKasih')

@section('admin-content')
    <div class="adm-page-heading">
        <h1 class="adm-page-title">Dashboard</h1>
    </div>

    <a class="rk-back" href="{{ route('admin.visits.index') }}">
        Tinjau pengajuan kunjungan →
    </a>

    <section class="adm-dashboard-empty" aria-label="Area kerja admin">
        <p>Anda belum membuat apa-apa.</p>
    </section>
@endsection