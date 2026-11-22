@extends('layouts.caregiver')

@section('title', 'Daftar Insiden — RawatKasih')

@section('workspace')
    {{-- Header halaman --}}
    <section class="rk-care-page rk-stitch-page">
        <header class="rk-today-header">
            <div class="rk-today-title">
                <h1>Daftar Insiden</h1>
                <time datetime="{{ now('Asia/Jakarta')->toDateString() }}">{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F') }}</time>
            </div>
        </header>

        @if (session('status'))
            <div class="rk-care-alert" role="status">{{ session('status') }}</div>
        @endif

        {{-- Banner Peringatan SOP Darurat --}}
        <div class="rk-care-alert" role="status" aria-live="polite">
            <strong>Peringatan:</strong> Halaman ini bukan panggilan darurat otomatis. Jika situasi darurat, <strong>caregiver harus langsung melakukan tindakan sesuai SOP panti dan menghubungi petugas/tenaga medis terkini secara langsung.</div>

        {{-- Statistik --}}
        <section class="rk-care-section" style="margin-bottom: 24px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                <div class="rk-care-stat-card" style="background: #fff8e1; border-radius: 12px; padding: 16px; text-align: center; border-left: 4px solid #926018;">
                    <h3 style="font-size: 14px; color: #68776f; margin: 0 0 8px 0;">Terbuka</h3>
                    <span class="rk-stitch-status rk-result-open" style="font-size: 24px; font-weight: 600;">{{ count(array_filter($incidents, fn($i) => ($i['status'] ?? 'open') === 'open')) }}</span>
                </div>
                <div class="rk-care-stat-card" style="background: #e5fcf0; border-radius: 12px; padding: 16px; text-align: center; border-left: 4px solid #005338;">
                    <h3 style="font-size: 14px; color: #68776f; margin: 0 0 8px 0;">Sedang ditindaklanjuti</h3>
                    <span class="rk-stitch-status rk-result-in_progress" style="font-size: 24px; font-weight: 600;">{{ count(array_filter($incidents, fn($i) => ($i['status'] ?? 'open') === 'in_progress')) }}</span>
                </div>
                <div class="rk-care-stat-card" style="background: #e8f5e9; border-radius: 12px; padding: 16px; text-align: center; border-left: 4px solid #247850;">
                    <h3 style="font-size: 14px; color: #68776f; margin: 0 0 8px 0;">Ditutup</h3>
                    <span class="rk-stitch-status rk-result-closed" style="font-size: 24px; font-weight: 600;">{{ count(array_filter($incidents, fn($i) => ($i['status'] ?? 'open') === 'closed')) }}</span>
                </div>
            </div>
        </section>

        {{-- Aksi cepat --}}
        <div style="display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
            <a href="{{ route('incidents.create') }}" class="rk-care-btn rk-care-btn-primary">
                + Laporkan Insiden
            </a>
        </div>

        {{-- Daftar Insiden --}}
        @if(count($incidents) > 0)
            <div class="rk-care-stitch-task-list" aria-label="Daftar insiden yang dilaporkan">
                @foreach ($incidents as $incident)
                    @php
                        // Pastikan incident adalah array
                        $incident = is_object($incident) ? get_object_vars($incident) : (is_array($incident) ? $incident : []);
                        $occurredAt = $incident['occurred_at'] ?? now('Asia/Jakarta')->toIso8601String();
                        $residentName = $incident['resident'] ?? ($incident['resident_id'] ?? 'Lansia');
                        $description = $incident['description'] ?? 'Tidak ada kronologi';
                        $status = $incident['status'] ?? 'open';
                        $category = $incident['category'] ?? 'medical';
                    @endphp
                    <article class="rk-stitch-task-row" data-incident-row>
                        <div class="rk-stitch-task-main">
                            <div class="rk-stitch-task-time">
                                <strong>{{ \Carbon\Carbon::parse($occurredAt)->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</strong>
                            </div>

                            <div class="rk-stitch-task-information">
                                <h3>{{ $residentName }}</h3>
                                <p>Kategori: {{ $statusLabels[$category] ?? $category }}</p>
                                <p class="rk-task-information p">{{ Str::limit($description, 120) }}</p>

                                <span class="rk-stitch-task-meta">
                                    Status:
                                    <span class="rk-stitch-status rk-result-{{ $status }}">
                                        {{ $statusLabels[$status] ?? $status }}</span>
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('incidents.show', $incident['id'] ?? '') }}" class="rk-care-view-link" aria-label="Lihat detail #{{ $incident['id'] ?? '' }}">
                            Lihat detail
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rk-care-empty" style="margin-top: 24px;">
                <h2>Belum ada insiden yang dilaporkan</h2>
                <p>Gunakan tombol "Laporkan Insiden" untuk melayangkan insiden pertama Anda.</p>
            </div>
        @endif
    </section>
@endsection

@push('styles')
<style>
.rk-care-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.rk-care-stat-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.rk-care-statistics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}
.rk-care-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 18px;
    border: none;
    border-radius: 10px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}
.rk-care-btn-primary {
    background: #005338;
    color: white !important;
}
.rk-care-btn-primary:hover {
    background: #003e2a;
}
.rk-care-view-link {
    min-height: 44px;
    padding: 0 12px;
    color: #005338;
    font-weight: 600;
    text-decoration: none;
}
.rk-care-view-link:hover {
    text-decoration: underline;
}
.rk-result-open {
    background: #fff1e2;
    color: #926018;
}
.rk-result-in_progress,
.rk-result-handling {
    background: #e5fcf0;
    color: #005338;
}
.rk-result-closed {
    background: #e8f5e9;
    color: #247850;
}
@media (max-width: 760px) {
    .rk-care-alert {
        padding: 10px 12px;
        font-size: 13px;
    }
}
</style>
