@extends('layouts.caregiver')

@section('title', 'Laporkan Insiden — RawatKasih')

@section('workspace')
    {{-- Header halaman --}}
    <section class="rk-care-page rk-stitch-page">
        <header class="rk-today-header">
            <div class="rk-today-title">
                <h1>Laporkan Insiden</h1>
                <time datetime="{{ now('Asia/Jakarta')->toDateString() }}">{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F') }}</time>
            </div>
        </header>

        {{-- Banner Peringatan SOP Darurat --}}
        <div class="rk-care-alert" role="status" aria-live="polite">
            <strong>Peringatan:</strong> Halaman ini bukan panggilan darurat otomatis. Jika situasi darurat, <strong>caregiver harus langsung melakukan tindakan sesuai SOP panti dan menghubungi petugas/tenaga medis terkini secara langsung.</div>

        @if (session('status'))
            <div class="rk-care-alert" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rk-care-alert" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Form Pelaporan Insiden --}}
        <section class="rk-care-card" aria-labelledby="incident-form-heading">
            <h2 id="incident-form-heading">Detil Insiden</h2>

            <form method="POST" action="{{ route('incidents.store') }}" class="rk-care-form" x-data="incidentForm()">
                @csrf

                {{-- Lansia --}}
                <div>
                    <label class="rk-care-form-label" for="resident_id">
                        Lansia yang terlibat
                        <span class="rk-care-required" aria-label="wajib diisi">*</span>
                    </label>
                    <select
                        class="rk-care-form-control"
                        id="resident_id"
                        name="resident_id"
                        required
                        aria-describedby="resident-error"
                        @error('resident_id') aria-invalid="true" @enderror
                    >
                        <option value="">Pilih lansia</option>
                        @foreach ($residents as $resident)
                            @php
                                $residentId = is_object($resident) ? $resident->id : ($resident['id'] ?? '');
                                $residentName = is_object($resident) ? $resident->full_name : ($resident['full_name'] ?? '');
                            @endphp
                            <option value="{{ $residentId }}" @selected(old('resident_id') == $residentId ?? false)>
                                {{ $residentName }}
                            </option>
                        @endforeach
                    </select>
                    @error('resident_id')
                        <p class="rk-care-error" id="resident-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="rk-care-form-label" for="category">
                        Kategori insiden
                        <span class="rk-care-required" aria-label="wajib diisi">*</span>
                    </label>
                    <select
                        class="rk-care-form-control"
                        id="category"
                        name="category"
                        required
                        aria-describedby="category-error"
                        @error('category') aria-invalid="true" @enderror
                    >
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}" @selected(old('category') == $category->value ?? false)>
                                {{ $category->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="rk-care-error" id="category-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Waktu Kejadian --}}
                <div>
                    <label class="rk-care-form-label" for="occurred_at">
                        Waktu kejadian
                        <span class="rk-care-required" aria-label="wajib diisi">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        class="rk-care-form-control"
                        id="occurred_at"
                        name="occurred_at"
                        required
                        value="{{ old('occurred_at') }}"
                        aria-describedby="occurred-at-error"
                        @error('occurred_at') aria-invalid="true" @enderror
                    >
                    @error('occurred_at')
                        <p class="rk-care-error" id="occurred-at-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kronologi Singkat --}}
                <div>
                    <label class="rk-care-form-label" for="description">
                        Kronologi singkat
                        <span class="rk-care-required" aria-label="wajib diisi">*</span>
                    </label>
                    <textarea
                        class="rk-care-form-control"
                        id="description"
                        name="description"
                        rows="4"
                        maxlength="2000"
                        placeholder="Jelaskan kejadian dengan singkat namun jelas..."
                        aria-describedby="description-error"
                        @error('description') aria-invalid="true" @enderror
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="rk-care-error" id="description-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tindakan Awal --}}
                <div>
                    <label class="rk-care-form-label" for="initial_action">
                        Tindakan awal yang sudah dilakukan
                    </label>
                    <textarea
                        class="rk-care-form-control"
                        id="initial_action"
                        name="initial_action"
                        rows="3"
                        maxlength="500"
                        placeholder="Sebutkan apa yang sudah Anda lakukan..."
                        aria-describedby="initial-action-error"
                        @error('initial_action') aria-invalid="true" @enderror
                    >{{ old('initial_action') }}</textarea>
                    @error('initial_action')
                        <p class="rk-care-error" id="initial-action-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="rk-care-actions">
                    <a href="{{ route('caregiver.dashboard') }}" class="rk-care-btn rk-care-btn-secondary">
                        Batal
                    </a>
                    <button type="submit" class="rk-care-btn rk-care-btn-primary">
                        Kirim Laporan
                    </button>
                </div>
            </form>
        </section>

        {{-- Riwayat Insiden Caregiver --}}
        @if(isset($incidentHistory) && count($incidentHistory) > 0)
            <section class="rk-care-section-heading">
                <h2>Riwayat laporan Anda</h2>
            </section>

            <div class="rk-care-stitch-task-list" aria-label="Riwayat insiden yang sudah dilaporkan">
                @foreach ($incidentHistory as $incident)
                    <article class="rk-stitch-task-row" data-incident-row>
                        <div class="rk-stitch-task-main">
                            <div class="rk-stitch-task-time">
                                <strong>{{ $incident->occurred_at->format('d/m/Y H:i') }}</strong>
                            </div>

                            <div class="rk-stitch-task-information">
                                <h3>{{ $incident->resident?->full_name ?? 'Lansia' }}</h3>
                                <p>Kategori: {{ $incident->category->label() ?? $incident->category }}</p>
                                <p class="rk-task-information p">{{ Str::limit($incident->description, 100) }}</p>

                                <span class="rk-stitch-task-meta">
                                    Status:
                                    <span class="rk-stitch-status rk-result-{{ $incident->status->value ?? 'open' }}">
                                        {{ $statusLabels[$incident->status->value ?? 'open'] ?? $incident->status->label() ?? 'Terbuka' }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('incidents.show', $incident->id) }}" class="rk-care-view-link" aria-label="Lihat detail #{{ $incident->id }}">
                            Lihat detail
                        </a>
                    </article>
                @endforeach
            </div>

            @if ($incidentHistory->hasPages())
                <nav class="rk-care-view-tabs" aria-label="Navigasi halaman riwayat">
                    @if ($incidentHistory->currentPage() > 1)
                        <a href="{{ $incidentHistory->previousPageUrl() }}" class="rk-care-view-tab">Sebelumnya</a>
                    @endif
                    <span class="rk-care-view-tab">Halaman {{ $incidentHistory->currentPage() }}</span>
                    @if ($incidentHistory->hasMorePages())
                        <a href="{{ $incidentHistory->nextPageUrl() }}" class="rk-care-view-tab">Berikutnya</a>
                    @endif
                </nav>
            @endif
        @endif
    </section>

    {{-- Dialog konfirmasi --}}
    <dialog class="rk-care-dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-desc" hidden>
        <form method="dialog" class="rk-care-dialog-form">
            <button type="button" class="rk-care-dialog-close" aria-label="Tutup dialog">&times;</button>
            <h2 id="confirm-dialog-title">Konfirmasi Pengiriman</h2>
            <p id="confirm-dialog-desc">Apakah Anda sudah yakin dengan laporan ini?</p>
            <div class="rk-care-dialog-actions">
                <button type="button" class="rk-care-dialog-cancel" data-confirm-cancel>Batal</button>
                <button type="submit" class="rk-care-dialog-save">Kirim</button>
            </div>
        </form>
    </dialog>
@endsection

@push('styles')
<style>
.rk-care-form-label {
    display: block;
    margin-bottom: 8px;
    color: #1b2420;
    font-size: 13px;
    font-weight: 700;
}
.rk-care-form-control {
    display: block;
    width: 100%;
    min-height: 44px;
    padding: 12px 14px;
    border: 1px solid #dce7e0;
    border-radius: 10px;
    background: white;
    color: #1b2420;
    font-size: 14px;
}
.rk-care-required {
    color: #b54c4b;
    font-weight: bold;
}
.rk-care-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
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
.rk-care-btn-secondary {
    background: white;
    color: #68776f;
    border: 1px solid #dce7e0;
}
.rk-care-btn-secondary:hover {
    background: #f7f8f7;
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
.rk-result-medical {
    background: #fee2e2;
    color: #991b1b;
}
.rk-result-security {
    background: #fee2e2;
    color: #991b1b;
}
.rk-result-maintenance {
    background: #dbeafe;
    color: #1e40af;
}
.rk-result-other {
    background: #f3f4f6;
    color: #4b5563;
}
</style>