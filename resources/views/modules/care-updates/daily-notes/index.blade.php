@extends('layouts.caregiver')

@section('title', 'Catatan Harian — RawatKasih')

@section('page-styles')
<style>
    .rk-notes-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    @media (min-width: 960px) {
        .rk-notes-container {
            grid-template-columns: 380px 1fr;
            align-items: start;
        }
    }

    .rk-note-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .rk-note-form-card {
        position: sticky;
        top: 24px;
    }

    .rk-note-form-card h2 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 6px;
    }

    .rk-note-form-card p {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 20px;
    }

    .rk-field-group {
        margin-bottom: 16px;
    }

    .rk-field-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .rk-field-label span.required {
        color: #dc2626;
    }

    .rk-field-input,
    .rk-field-select,
    .rk-field-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.875rem;
        color: #111827;
        background-color: #ffffff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        box-sizing: border-box;
    }

    .rk-field-input:focus,
    .rk-field-select:focus,
    .rk-field-textarea:focus {
        outline: none;
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .rk-field-input[aria-invalid="true"],
    .rk-field-select[aria-invalid="true"],
    .rk-field-textarea[aria-invalid="true"] {
        border-color: #ef4444;
    }

    .rk-field-error {
        margin-top: 4px;
        font-size: 0.8125rem;
        color: #dc2626;
    }

    .rk-btn-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 12px 16px;
        min-height: 44px;
        background-color: #0d9488;
        color: #ffffff;
        font-size: 0.875rem;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .rk-btn-submit:hover {
        background-color: #0f766e;
    }

    .rk-filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .rk-filter-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .rk-filter-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        min-height: 36px;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #4b5563;
        background-color: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 9999px;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .rk-filter-pill:hover {
        background-color: #e5e7eb;
        color: #111827;
    }

    .rk-filter-pill.is-active {
        background-color: #0d9488;
        border-color: #0d9488;
        color: #ffffff;
    }

    .rk-note-item {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 16px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .rk-note-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
        gap: 12px;
    }

    .rk-note-item-resident {
        font-weight: 600;
        color: #111827;
        font-size: 1rem;
    }

    .rk-note-item-room {
        font-size: 0.8125rem;
        color: #6b7280;
        margin-left: 6px;
    }

    .rk-note-item-time {
        font-size: 0.8125rem;
        color: #6b7280;
        white-space: nowrap;
    }

    .rk-note-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .rk-badge-mood {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .rk-badge-meal {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .rk-note-item-content {
        font-size: 0.9375rem;
        line-height: 1.5;
        color: #1f2937;
        white-space: pre-line;
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        padding: 12px 14px;
        border-radius: 8px;
    }

    .rk-note-item-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .rk-note-empty {
        background: #ffffff;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        padding: 48px 24px;
        text-align: center;
    }

    .rk-note-empty h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }

    .rk-note-empty p {
        font-size: 0.875rem;
        color: #6b7280;
    }
</style>
@endsection

@section('workspace')
    @php
        $todayLabel = \Carbon\Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('l, d F Y');
    @endphp

    <section class="rk-care-page rk-stitch-page">

        {{-- Header halaman --}}
        <header class="rk-today-header">
            <div class="rk-today-title">
                <h1>Catatan Harian Internal</h1>
                <time datetime="{{ $date }}">{{ $todayLabel }}</time>
            </div>
        </header>

        @if (session('status'))
            <div class="rk-care-alert" role="status" style="margin-top: 16px;">
                {{ session('status') }}
            </div>
        @endif

        @if (empty($residents))
            <div class="rk-care-empty" style="margin-top: 24px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; color: #9ca3af; margin: 0 auto 12px;" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                <h3>Tidak Ada Lansia yang Ditugaskan</h3>
                <p>Saat ini Anda belum memiliki lansia dalam periode penugasan aktif. Silakan hubungi admin panti.</p>
            </div>
        @else
            <div class="rk-notes-container">

                {{-- Kolom Kiri: Form Catatan Harian (Append-only) --}}
                <div class="rk-note-card rk-note-form-card">
                    <h2>Tambah Catatan Internal</h2>
                    <p>Catatan khusus internal staf/perawat, tidak dapat diakses atau dilihat oleh keluarga.</p>

                    <form method="POST" action="{{ route('care-updates.daily-notes.store') }}">
                        @csrf

                        {{-- Input Lansia --}}
                        <div class="rk-field-group">
                            <label for="resident_id" class="rk-field-label">
                                Lansia <span class="required">*</span>
                            </label>
                            <select
                                id="resident_id"
                                name="resident_id"
                                class="rk-field-select"
                                required
                                @error('resident_id') aria-invalid="true" @enderror
                            >
                                <option value="">-- Pilih Lansia Ditugaskan --</option>
                                @foreach ($residents as $res)
                                    <option
                                        value="{{ $res['id'] }}"
                                        {{ (old('resident_id', $selectedResidentId) === $res['id']) ? 'selected' : '' }}
                                    >
                                        {{ $res['full_name'] }} (Kamar {{ $res['room'] ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('resident_id')
                                <div class="rk-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Input Mood --}}
                        <div class="rk-field-group">
                            <label for="mood" class="rk-field-label">
                                Mood / Suasana Hati
                            </label>
                            <input
                                type="text"
                                id="mood"
                                name="mood"
                                value="{{ old('mood') }}"
                                class="rk-field-input"
                                placeholder="Contoh: Ceria, Tenang, Gelisah"
                                maxlength="50"
                                @error('mood') aria-invalid="true" @enderror
                            >
                            @error('mood')
                                <div class="rk-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Input Catatan Makan --}}
                        <div class="rk-field-group">
                            <label for="meal_note" class="rk-field-label">
                                Catatan Makan / Asupan
                            </label>
                            <input
                                type="text"
                                id="meal_note"
                                name="meal_note"
                                value="{{ old('meal_note') }}"
                                class="rk-field-input"
                                placeholder="Contoh: Habis 1 porsi bubur, minum 500ml air"
                                maxlength="500"
                                @error('meal_note') aria-invalid="true" @enderror
                            >
                            @error('meal_note')
                                <div class="rk-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Input Catatan Internal --}}
                        <div class="rk-field-group">
                            <label for="internal_note" class="rk-field-label">
                                Catatan Observasi Perawatan <span class="required">*</span>
                            </label>
                            <textarea
                                id="internal_note"
                                name="internal_note"
                                rows="4"
                                class="rk-field-textarea"
                                placeholder="Tuliskan perkembangan kondisi umum fisik, mobilitas, atau hal penting lainnya..."
                                required
                                minlength="3"
                                maxlength="2000"
                                @error('internal_note') aria-invalid="true" @enderror
                            >{{ old('internal_note') }}</textarea>
                            @error('internal_note')
                                <div class="rk-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="rk-btn-submit">
                            Simpan Catatan Harian
                        </button>
                    </form>
                </div>

                {{-- Kolom Kanan: Daftar Riwayat Catatan Internal --}}
                <div class="rk-note-list-wrap">

                    {{-- Filter per Lansia --}}
                    <div class="rk-filter-bar">
                        <div class="rk-filter-pills" role="navigation" aria-label="Filter catatan per lansia">
                            <a
                                href="{{ route('care-updates.daily-notes.index') }}"
                                class="rk-filter-pill {{ $selectedResidentId === null ? 'is-active' : '' }}"
                            >
                                Semua Lansia
                            </a>
                            @foreach ($residents as $res)
                                <a
                                    href="{{ route('care-updates.daily-notes.index', ['resident' => $res['id']]) }}"
                                    class="rk-filter-pill {{ $selectedResidentId === $res['id'] ? 'is-active' : '' }}"
                                >
                                    {{ $res['full_name'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- List Riwayat --}}
                    @if (empty($notes))
                        <div class="rk-note-empty">
                            <h3>Belum Ada Catatan Harian</h3>
                            <p>Belum ada catatan internal yang dibuat untuk lansia yang dipilih.</p>
                        </div>
                    @else
                        @foreach ($notes as $note)
                            @php
                                $recordedAt = filled($note['recorded_at'] ?? null)
                                    ? \Carbon\Carbon::parse($note['recorded_at'])->locale('id')
                                    : null;
                                $timeDisplay = $recordedAt
                                    ? $recordedAt->isoFormat('D MMMM Y, HH:mm') . ' WIB'
                                    : '-';
                            @endphp

                            <article class="rk-note-item">
                                <header class="rk-note-item-header">
                                    <div>
                                        <span class="rk-note-item-resident">{{ $note['resident_name'] }}</span>
                                        <span class="rk-note-item-room">Kamar {{ $note['resident_room'] }}</span>
                                    </div>
                                    <time class="rk-note-item-time" datetime="{{ $note['recorded_at'] ?? '' }}">
                                        {{ $timeDisplay }}
                                    </time>
                                </header>

                                @if (filled($note['mood'] ?? null) || filled($note['meal_note'] ?? null))
                                    <div class="rk-note-badges">
                                        @if (filled($note['mood'] ?? null))
                                            <span class="rk-badge-mood">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                                                </svg>
                                                Mood: {{ $note['mood'] }}
                                            </span>
                                        @endif

                                        @if (filled($note['meal_note'] ?? null))
                                            <span class="rk-badge-meal">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;" aria-hidden="true">
                                                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                                    <line x1="6" y1="1" x2="6" y2="4"></line>
                                                    <line x1="10" y1="1" x2="10" y2="4"></line>
                                                    <line x1="14" y1="1" x2="14" y2="4"></line>
                                                </svg>
                                                Makan: {{ $note['meal_note'] }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <div class="rk-note-item-content">
                                    {{ $note['internal_note'] }}
                                </div>

                                <footer class="rk-note-item-footer">
                                    <span>Pencatat: {{ $note['caregiver_name'] }}</span>
                                    <span>Internal Staf</span>
                                </footer>
                            </article>
                        @endforeach

                        {{-- Navigasi Halaman jika ada --}}
                        @if ($page > 1 || $hasMore)
                            <nav class="rk-stitch-filters" style="margin-top: 20px; justify-content: center;" aria-label="Navigasi halaman catatan">
                                @if ($page > 1)
                                    <a
                                        href="{{ route('care-updates.daily-notes.index', array_filter(['resident' => $selectedResidentId, 'page' => $page - 1])) }}"
                                        class="rk-stitch-filter"
                                    >
                                        &larr; Halaman Sebelumnya
                                    </a>
                                @endif
                                @if ($hasMore)
                                    <a
                                        href="{{ route('care-updates.daily-notes.index', array_filter(['resident' => $selectedResidentId, 'page' => $page + 1])) }}"
                                        class="rk-stitch-filter"
                                    >
                                        Halaman Selanjutnya &rarr;
                                    </a>
                                @endif
                            </nav>
                        @endif
                    @endif

                </div>

            </div>
        @endif

    </section>
@endsection
