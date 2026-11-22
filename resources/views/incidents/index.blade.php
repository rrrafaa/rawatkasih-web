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

        {{-- Filter Status --}}
        <div class="rk-care-section-heading" style="margin-top: 24px;">
            <h2>Insin yang Anda Laporkan</h2>
        </div>

        {{-- Tambah tombol untuk laporan baru --}}
        <div class="rk-care-actions" style="margin-bottom: 16px;">
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

            @if ($incidents->hasPages ?? false)
                <nav class="rk-care-view-tabs" aria-label="Navigasi halaman">
                    @if ($incidents->currentPage() > 1)
                        <a href="{{ $incidents->previousPageUrl() }}" class="rk-care-view-tab">Sebelumnya</a>
                    @endif
                    <span class="rk-care-view-tab">Halaman {{ $incidents->currentPage() }}</span>
                    @if ($incidents->hasMorePages())
                        <a href="{{ $incidents->nextPageUrl() }}" class="rk-care-view-tab">Berikutnya</a>
                    @endif
                </nav>
            @endif
        @else
            <div class="rk-care-empty rk-stitch-empty-residents">
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
    color: white;
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
.rk-care-view-tab {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #68776f;
    background: transparent;
    border: 1px solid #dce7e0;
}
.rk-care-view-tab:hover {
    background: #f7f8f7;
}
@media (max-width: 760px) {
    .rk-care-alert {
        padding: 10px 12px;
        font-size: 13px;
    }
}
</style>
