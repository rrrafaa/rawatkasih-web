
@extends('layouts.rawatkasih')

@section('title', 'Hari Ini — RawatKasih')

@section('content')
    <div class="rk-care-shell">

        {{-- Sidebar --}}
        <aside class="rk-care-sidebar">
            <div class="rk-care-sidebar-top">

                {{-- Brand --}}
                <div class="rk-care-brand">
                    <x-rk-brand />
                </div>

                {{-- Navigation --}}
                <nav class="rk-care-navigation" aria-label="Navigasi caregiver">

                    <a
                        href="{{ route('caregiver.dashboard') }}"
                        class="rk-care-nav-link is-active"
                        aria-current="page"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                            <path d="M16 3v4M8 3v4M3 11h18"></path>
                        </svg>
                        <span>Hari Ini</span>
                    </a>

                    <span class="rk-care-nav-link rk-care-nav-link--disabled"
                        aria-disabled="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Lansia Saya</span>
                    </span>

                    <span class="rk-care-nav-link rk-care-nav-link--disabled"
                        aria-disabled="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"></path>
                        </svg>
                        <span>Catatan</span>
                    </span>

                    <span class="rk-care-nav-link rk-care-nav-link--disabled"
                        aria-disabled="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8Z"></path>
                        </svg>
                        <span>Kabar Keluarga</span>
                    </span>

                    <a
                        href="{{ route('incidents.index') }}"
                        @class(['rk-care-nav-link', 'is-active' => request()->routeIs('incidents.*')])
                        @if (request()->routeIs('incidents.*')) aria-current="page" @endif
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M12 9v4"></path>
                            <path d="M12 17h.01"></path>
                            <path d="m10.3 3.86-8.1 14A2 2 0 0 0 3.93 21h16.14a2 2 0 0 0 1.73-3.14l-8.1-14a2 2 0 0 0-3.4 0Z"></path>
                        </svg>
                        <span>Insiden</span>
                    </a>

                </nav>
            </div>

            {{-- Caregiver profile and logout --}}
            <div class="rk-care-sidebar-profile">

                <div class="rk-care-profile-information">
                    <div class="rk-care-profile-avatar">
                        {{ mb_strtoupper(mb_substr($user->name(), 0, 1)) }}
                    </div>

                    <div class="rk-care-profile-text">
                        <p>{{ $user->name() }}</p>
                        <span>{{ $user->role()->label() }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="rk-care-logout"
                        aria-label="Keluar"
                        title="Keluar"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <path d="m16 17 5-5-5-5"></path>
                            <path d="M21 12H9"></path>
                        </svg>
                    </button>
                </form>

            </div>
        </aside>

        {{-- Main content --}}
        <main id="main" class="rk-care-main">
            @yield('workspace')
        </main>

    </div>
@endsection