@extends('layouts.rawatkasih')
@section('title', ($pageTitle ?? 'Ruang keluarga').' — RawatKasih')
@section('page-styles')
    <link rel="stylesheet" href="{{ asset('css/admin-care-tasks.css') }}?v={{ filemtime(public_path('css/admin-care-tasks.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/family-visits.css') }}?v={{ filemtime(public_path('css/family-visits.css')) }}">
@endsection
@section('content')
<script src="{{ asset('js/family-visits.js') }}" defer></script>
<div class="fv-shell">
    <aside class="adm-sidebar fv-sidebar" aria-label="Navigasi ruang keluarga">
        <a class="adm-sidebar-brand" href="{{ route('family.dashboard') }}">
            <img src="{{ asset('images/rawatkasih-mark.svg') }}" alt="" width="48" height="51">
            <span>RawatKasih</span>
        </a>

        <nav class="adm-nav" aria-label="Menu keluarga">
            @php
                $menu = [
                    'home' => ['Beranda', 'family.dashboard', 'M3 10l9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z'],
                    'news' => ['Kabar Harian', 'family.news', 'M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm2 5h8m-8 4h8m-8 4h6'],
                    'visits' => ['Kunjungan', 'family.visits.index', 'M8 3v4m8-4v4M4 10h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z'],
                    'profile' => ['Profil Akun', 'family.profile', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-7 9v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1z'],
                ];
            @endphp
            @foreach ($menu as $key => [$label, $route, $iconPath])
                <a href="{{ route($route, $resident ? ['resident' => $resident->id] : []) }}"
                   @class(['adm-nav-link', 'is-active' => $activeNav === $key])
                   @if ($activeNav === $key) aria-current="page" @endif>
                    <svg class="adm-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $iconPath }}"></path>
                    </svg>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <div class="adm-sidebar-profile">
            <span class="adm-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name(), 0, 1)) }}</span>
            <div class="adm-profile-info">
                <p class="adm-profile-name">{{ $user->name() }}</p>
                <p class="adm-profile-role">{{ $resident ? 'Keluarga terverifikasi' : 'Menunggu verifikasi' }}</p>
            </div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="adm-sidebar-logout" aria-label="Keluar akun" title="Keluar akun">↪</button>
            </form>
        </div>
    </aside>
    <main id="main" class="fv-main">
        <div class="fv-context"><p>{{ config('family_visits.facility_name') }} <span>/</span> {{ $breadcrumb ?? 'Beranda' }}</p><span class="fv-badge {{ $resident ? 'fv-badge-success' : 'fv-badge-pending' }}">{{ $resident ? 'Terverifikasi' : 'Menunggu verifikasi' }}</span></div>
        <header class="fv-page-heading"><h1 class="adm-page-title">{{ $pageTitle }}</h1><p>{{ $pageDescription }}</p></header>
        @if (session('status'))<div class="fv-alert fv-alert-success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->has('visit'))<div class="fv-alert fv-alert-error" role="alert">{{ $errors->first('visit') }}</div>@endif
        @if (count($residents) > 1)<form method="get" action="{{ route('family.dashboard') }}" class="adm-toolbar fv-filter-bar"><div class="fv-filter-field"><label class="adm-form-label" for="resident-selector">Orang tersayang</label><select class="adm-filter fv-select" id="resident-selector" name="resident">@foreach ($residents as $linked)<option value="{{ $linked->id }}" @selected($resident?->id === $linked->id)>{{ $linked->full_name }}</option>@endforeach</select></div><button class="adm-secondary-button" type="submit">Tampilkan</button></form>@endif
        @yield('portal-content')
        <p class="fv-footer">Privasi Eyang terjaga. Hanya kabar terpublikasi yang ditampilkan kepada keluarga terverifikasi.</p>
    </main>
</div>
@endsection
