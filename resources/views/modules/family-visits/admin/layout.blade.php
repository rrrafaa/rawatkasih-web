@extends('layouts.admin')

@section('title', 'Manajemen Kunjungan — RawatKasih')

@section('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/family-visits.css') }}?v={{ filemtime(public_path('css/family-visits.css')) }}">
    <script src="{{ asset('js/family-visits.js') }}?v={{ filemtime(public_path('js/family-visits.js')) }}" defer></script>
@endsection

@section('admin-content')
    <div class="adm-visits">
        @include('modules.family-visits.partials.notification')
        @yield('visit-content')
        @include('modules.family-visits.partials.confirmation')
    </div>
@endsection
