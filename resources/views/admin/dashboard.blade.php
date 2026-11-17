@extends('layouts.role')

@section('workspace')
    <a class="rk-back" href="{{ route('admin.visits.index') }}">Tinjau pengajuan kunjungan →</a>
    <section class="rk-empty-workspace" aria-label="Area kerja admin"></section>
@endsection
