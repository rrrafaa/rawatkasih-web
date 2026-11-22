@extends('layouts.admin')

@section('title', 'Dashboard Admin — RawatKasih')

@section('admin-content')
    <header class="adm-page-heading">
        <div>
            <h1 class="adm-page-title">Dashboard Admin</h1>
            <p>Selamat datang, {{ $user->name() }}! Ringkasan aktivitas hari ini.</p>
        </div>
    </header>

    @if (session('status'))
        <div class="rk-care-alert" role="status">{{ session('status') }}</div>
    @endif

    {{-- Statistik Utama --}}
    <section class="adm-section" style="margin-bottom: 24px;">
        <h2 class="adm-section-title">Ringkasan Hari Ini</h2>

        <div class="adm-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
            {{-- Tugas Harian --}}
            <div class="adm-stat-card" style="background: #f8faf8; border-radius: 12px; padding: 20px; border-left: 4px solid #005338;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 14px; color: #68776f; margin: 0;">Tugas Harian</h3>
                    <span class="rk-stitch-status rk-result-open" style="font-size: 12px;">{{ $stats['tasks']['total'] }} tugas</span>
                </div>
                <div style="display: flex; gap: 16px; font-size: 13px; color: #555;">
                    <span><strong>{{ $stats['tasks']['completed'] }}</strong> selesai</span>
                    <span><strong>{{ $stats['tasks']['pending'] }}</strong> tertunda</span>
                </div>
            </div>

            {{-- Insiden --}}
            <div class="adm-stat-card" style="background: #fff8ef; border-radius: 12px; padding: 20px; border-left: 4px solid #a66b1e;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 14px; color: #68776f; margin: 0;">Insiden</h3>
                    <span class="rk-stitch-status rk-result-open" style="font-size: 12px;">{{ $stats['incidents']['open'] }} terbuka</span>
                </div>
                <div style="display: flex; gap: 16px; font-size: 13px; color: #555;">
                    <span><strong>{{ $stats['incidents']['total'] }}</strong> total</span>
                    <span><strong>{{ $stats['incidents']['closed'] }}</strong> ditutup</span>
                </div>
            </div>

            {{-- Kunjungan Keluarga --}}
            <div class="adm-stat-card" style="background: #e5fcf0; border-radius: 12px; padding: 20px; border-left: 4px solid #005338;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 14px; color: #68776f; margin: 0;">Kunjungan Keluarga</h3>
                    <span class="rk-stitch-status rk-result-open" style="font-size: 12px;">{{ $stats['visits']['pending'] }} tertunda</span>
                </div>
                <div style="display: flex; gap: 16px; font-size: 13px; color: #555;">
                    <span><strong>{{ $stats['visits']['total'] }}</strong> permintaan</span>
                    <span><strong>{{ $stats['visits']['approved'] }}</strong> disetujui</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Tugas Per Hari --}}
    <section class="adm-section" style="margin-bottom: 24px;">
        <h2 class="adm-section-title">Tugas untuk Hari Ini</h2>

        <div style="margin-top: 16px;">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th scope="col">Judul</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Waktu</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
    @forelse($stats['tasks']['todayList'] ?? [] as $task)
        <tr>
            <td>{{ $task['title'] ?? '-' }}</td>
            <td>{{ $task['category'] ?? '-' }}</td>
            <td>{{ $task['time'] ?? '-' }}</td>
            <td>{{ $task['status'] ?? '-' }}</td>
        </tr>
    @empty
        <tr>
            <td class="adm-table-empty" colspan="4">Tidak ada tugas yang terencana untuk hari ini.</td>
        </tr>
    @endforelse
</tbody>
            </table>
        </div>
    </section>

    {{-- Aksi Cepat --}}
    <section class="adm-section" style="margin-top: 24px;">
        <h2 class="adm-section-title">Tindakan Cepat</h2>

        <div class="adm-actions">
            <a href="{{ route('admin.incidents.index') }}" class="adm-primary-button">
                Lihat Semua Insiden
            </a>
            <a href="{{ route('admin.visits.index') }}" class="adm-secondary-button facm">
                Kelola Kunjungan Keluarga
            </a>
            <a href="{{ route('care-tasks.templates.index') }}" class="adm-secondary-button facm">
                Kelola Template Tugas
            </a>
        </div>
    </section>
@endsection

@push('admin-page-styles')
<style>
.adm-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #1b2420;
    margin: 0 0 12px 0;
}
.adm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.adm-stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.adm-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.adm-primary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    background: #005338;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
}
.adm-primary-button:hover {
    background: #003e2a;
}
.adm-secondary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    background: white;
    color: #68776f;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
}
.adm-secondary-button:hover {
    background: #f7f8f7;
}
.facm{
padding: 10px 20px !important;
}
</style>
@endpush