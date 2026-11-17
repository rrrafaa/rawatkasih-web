@extends('layouts.rawatkasih')
@section('title', ($pageTitle ?? 'Ruang keluarga').' — RawatKasih')
@section('content')
<link rel="stylesheet" href="{{ asset('css/family-visits.css') }}?v={{ filemtime(public_path('css/family-visits.css')) }}">
<script src="{{ asset('js/family-visits.js') }}" defer></script>
<div class="fv-shell">
    <aside class="fv-sidebar" aria-label="Navigasi ruang keluarga">
        <a class="fv-brand" href="{{ route('family.dashboard') }}"><img src="{{ asset('images/rawatkasih-mark.svg') }}" alt="" width="32" height="34"><strong>RawatKasih</strong></a>
        <p class="fv-eyebrow fv-sidebar-label">RUANG KELUARGA</p>
        <nav class="fv-nav" aria-label="Menu keluarga">
            @foreach (['home' => ['Beranda', 'family.dashboard'], 'news' => ['Kabar Harian', 'family.news'], 'visits' => ['Kunjungan', 'family.visits.index'], 'profile' => ['Profil Akun', 'family.profile']] as $key => [$label, $route])
                <a href="{{ route($route, $resident ? ['resident' => $resident->id] : []) }}" @if($activeNav === $key) class="is-active" aria-current="page" @endif><span aria-hidden="true">0{{ $loop->iteration }}</span>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="fv-reassurance"><h2>Selalu dekat,<br>meski berjauhan.</h2><p>Cerita kecil Eyang, berarti besar untuk keluarga.</p></div>
        <div class="fv-sidebar-bottom"><div class="fv-account"><span class="fv-avatar fv-avatar-small" aria-hidden="true">{{ mb_substr($user->name(), 0, 1) }}</span><div><strong>{{ $user->name() }}</strong><small>{{ $resident ? 'Keluarga terverifikasi' : 'Menunggu verifikasi' }}</small></div></div>
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="fv-logout">Keluar akun</button></form></div>
    </aside>
    <main id="main" class="fv-main">
        <div class="fv-context"><p>{{ config('family_visits.facility_name') }} <span>/</span> {{ $breadcrumb ?? 'Beranda' }}</p><span class="fv-badge {{ $resident ? 'fv-badge-success' : 'fv-badge-pending' }}">{{ $resident ? 'Terverifikasi' : 'Menunggu verifikasi' }}</span></div>
        <header class="fv-page-heading"><h1>{{ $pageTitle }}</h1><p>{{ $pageDescription }}</p></header>
        @if (session('status'))<div class="fv-alert fv-alert-success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->has('visit'))<div class="fv-alert fv-alert-error" role="alert">{{ $errors->first('visit') }}</div>@endif
        @if (count($residents) > 1)<form method="get" action="{{ route('family.dashboard') }}" class="fv-select-resident"><label for="resident-selector">Orang tersayang</label><select id="resident-selector" name="resident">@foreach ($residents as $linked)<option value="{{ $linked->id }}" @selected($resident?->id === $linked->id)>{{ $linked->full_name }}</option>@endforeach</select><button class="fv-btn fv-btn-secondary" type="submit">Tampilkan</button></form>@endif
        @yield('portal-content')
        <p class="fv-footer">Privasi Eyang terjaga. Hanya kabar terpublikasi yang ditampilkan kepada keluarga terverifikasi.</p>
    </main>
</div>
@endsection
