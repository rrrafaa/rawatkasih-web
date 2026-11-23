
@extends('layouts.rawatkasih')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('css/admin-care-tasks.css') }}?v={{ filemtime(public_path('css/admin-care-tasks.css')) }}">
    @yield('admin-page-styles')
@endsection

@section('content')
<div class="adm-page">
    <div class="adm-layout">

        <aside class="adm-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="adm-sidebar-brand">
                <img src="{{ asset('images/rawatkasih-mark.svg') }}" alt="">
                <span>RawatKasih</span>
            </a>

            <nav class="adm-nav" aria-label="Navigasi admin">

                <a href="{{ route('admin.dashboard') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                   @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    <svg class="w-5 h-5 stroke-current text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('care-tasks.templates.index') }}"
                   class="adm-nav-link {{ request()->routeIs('care-tasks.templates.*') ? 'is-active' : '' }}"
                   @if (request()->routeIs('care-tasks.templates.*')) aria-current="page" @endif>
                    <svg class="w-5 h-5 stroke-current text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span>Tugas</span>
                </a>

                <a href="{{ route('admin.incidents.index') }}" class="adm-nav-link">
                    <svg class="w-5 h-5 stroke-current text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span>Insiden</span>
                </a>

                <a href="{{ route('admin.visits.index') }}"
                   @class(['adm-nav-link', 'is-active' => request()->routeIs('admin.visits.*')])
                   @if (request()->routeIs('admin.visits.*')) aria-current="page" @endif>
                    <svg class="w-5 h-5 stroke-current text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Kunjungan</span>
                </a>

            </nav>

            <div class="adm-sidebar-profile">
                <div class="adm-avatar">
                    {{ strtoupper(substr($user->name(), 0, 1)) }}
                </div>

                <div class="adm-profile-info">
                    <p class="adm-profile-name">{{ $user->name() }}</p>
                    <p class="adm-profile-role">{{ $user->role()->label() }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="adm-sidebar-logout" aria-label="Keluar">
                        ↪
                    </button>
                </form>
            </div>
        </aside>

        <main id="main" class="adm-main">
            <div class="adm-content">
                @yield('admin-content')
            </div>
        </main>

    </div>
</div>
@yield('admin-overlay')
@endsection