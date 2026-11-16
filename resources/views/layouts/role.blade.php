@extends('layouts.rawatkasih')
@section('title', $user->role()->label().' — RawatKasih')
@section('content')
<header class="rk-dashboard-header"><x-rk-brand /><div class="rk-account"><span class="rk-role">{{ $user->role()->label() }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="rk-logout" type="submit">Keluar</button></form></div></header>
<main id="main" class="rk-dashboard"><span class="rk-eyebrow">{{ $user->role()->label() }}</span><h1>Selamat datang, {{ $user->name() }}</h1><p class="rk-description">Anda masuk sebagai {{ $user->role()->label() }}.</p>@yield('workspace')</main>
@endsection
