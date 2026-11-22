@extends('layouts.admin')

@section('title', 'Manajemen Kunjungan — RawatKasih')

@section('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/family-visits.css') }}?v={{ filemtime(public_path('css/family-visits.css')) }}">
    <script src="{{ asset('js/family-visits.js') }}" defer></script>
@endsection

@section('admin-content')
    <div class="adm-visits">
        @if (session('status'))
            <div class="fv-alert fv-alert-success" role="status">{{ session('status') }}</div>
        @endif
        @yield('visit-content')
    </div>
@endsection
