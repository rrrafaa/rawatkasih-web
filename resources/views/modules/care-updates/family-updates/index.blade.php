@extends('layouts.caregiver')

@section('title', 'Kabar Keluarga — RawatKasih')

@section('page-styles')
<style>
    .rk-updates-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    @media (min-width: 960px) {
        .rk-updates-container {
            grid-template-columns: 400px 1fr;
            align-items: start;
        }
    }

    .rk-update-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .rk-update-form-card {
        position: sticky;
        top: 24px;
    }

    .rk-update-form-card h2 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 6px;
    }

    .rk-update-form-card p {
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

    .rk-field-select,
    .rk-field-textarea,
    .rk-field-file {
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

    .rk-field-select:focus,
    .rk-field-textarea:focus,
    .rk-field-file:focus {
        outline: none;
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .rk-field-select[aria-invalid="true"],
    .rk-field-textarea[aria-invalid="true"],
    .rk-field-file[aria-invalid="true"] {
        border-color: #ef4444;
    }

    .rk-field-error {
        margin-top: 4px;
        font-size: 0.8125rem;
        color: #dc2626;
    }

    .rk-consent-banner {
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 0.8125rem;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .rk-consent-banner.is-active {
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .rk-consent-banner.is-inactive {
        background-color: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }

    .rk-btn-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 20px;
    }

    .rk-btn-draft {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 14px;
        min-height: 44px;
        background-color: #f3f4f6;
        color: #374151;
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .rk-btn-draft:hover {
        background-color: #e5e7eb;
        color: #111827;
    }

    .rk-btn-publish {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 14px;
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

    .rk-btn-publish:hover {
        background-color: #0f766e;
    }

    .rk-filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
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
</style>
@endsection

@section('workspace')
    @php
        $todayLabel = \Carbon\Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('l, d F Y');
    @endphp

    <section class="rk-care-page rk-stitch-page">

        <header class="rk-today-header">
            <div class="rk-today-title">
                <h1>Kabar Keluarga</h1>
                <time datetime="{{ $date }}">{{ $todayLabel }}</time>
            </div>
        </header>

        @include('modules.family-visits.partials.notification')

        @if (empty($residents))
            <div class="rk-care-empty" style="margin-top: 24px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; color: #9ca3af; margin: 0 auto 12px;" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                <h3>Tidak Ada Lansia yang Ditugaskan</h3>
                <p>Saat ini Anda belum memiliki lansia dalam periode penugasan aktif.</p>
            </div>
        @else
            <div class="rk-updates-container">

                {{-- Kolom Kiri: Form Buat Kabar --}}
                <div class="rk-update-card rk-update-form-card">
                    <h2>Buat Kabar Keluarga</h2>
                    <p>Cerita ringan & foto aktivitas yang akan dibagikan kepada keluarga lansia terverifikasi.</p>

                    <form method="POST" action="{{ route('care-updates.family-updates.store') }}" enctype="multipart/form-data">
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
                                onchange="updateConsentInfo(this)"
                                @error('resident_id') aria-invalid="true" @enderror
                            >
                                <option value="">-- Pilih Lansia Ditugaskan --</option>
                                @foreach ($residents as $res)
                                    <option
                                        value="{{ $res['id'] }}"
                                        data-photo-consent="{{ $res['photo_consent'] ? '1' : '0' }}"
                                        data-visibility-consent="{{ $res['visibility_consent'] ? '1' : '0' }}"
                                        {{ (old('resident_id', $selectedResidentId) === $res['id']) ? 'selected' : '' }}
                                    >
                                        {{ $res['full_name'] }} (Kamar {{ $res['room'] ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('resident_id')
                                <div class="rk-field-error">{{ $message }}</div>
                            @enderror

                            {{-- Consent Status Indicator --}}
                            <div id="consent-status-container" style="display: none; margin-top: 8px;">
                                <div id="visibility-warning" class="rk-consent-banner is-inactive" style="display: none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; flex-shrink: 0;" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span><strong>Belum ada izin visibilitas keluarga:</strong> Kabar hanya dapat disimpan sebagai draf, belum dapat diterbitkan langsung ke keluarga.</span>
                                </div>
                                <div id="consent-active" class="rk-consent-banner is-active" style="display: none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; flex-shrink: 0;" aria-hidden="true">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span>Izin foto aktif: Foto dapat diunggah dan dibagikan ke keluarga.</span>
                                </div>
                                <div id="consent-inactive" class="rk-consent-banner is-inactive" style="display: none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; flex-shrink: 0;" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>Belum ada izin foto: Hanya teks yang dapat dibagikan (tanpa foto).</span>
                                </div>
                            </div>
                        </div>

                        {{-- Ringkasan Kabar --}}
                        <div class="rk-field-group">
                            <label for="summary" class="rk-field-label">
                                Ringkasan Kabar <span class="required">*</span>
                            </label>
                            <textarea
                                id="summary"
                                name="summary"
                                rows="4"
                                class="rk-field-textarea"
                                placeholder="Contoh: Pagi ini Eyang bersemangat sekali mengikuti senam pagi bersama teman-temannya di taman panti..."
                                required
                                minlength="5"
                                maxlength="2000"
                                @error('summary') aria-invalid="true" @enderror
                            >{{ old('summary') }}</textarea>
                            @error('summary')
                                <div class="rk-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Unggah Foto Wajib --}}
                        <div class="rk-field-group">
                            <label for="photo" class="rk-field-label">
                                Foto Dokumentasi (Wajib)
                            </label>
                            <input
                                type="file"
                                id="photo"
                                name="photo"
                                accept="image/jpeg,image/png,image/webp"
                                class="rk-field-file" required
                                @error('photo') aria-invalid="true" @enderror
                            >
                            <p style="font-size: 0.75rem; color: #6b7280; margin-top: 4px;">
                                Format: JPEG, PNG, atau WebP. Maksimal 3 MB. Disimpan di storage privat.
                            </p>
                            @error('photo')
                                <div class="rk-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tombol Aksi: Draft atau Langsung Terbitkan --}}
                        <div class="rk-btn-group">
                            <button type="submit" name="action" value="draft" class="rk-btn-draft">
                                Simpan Draft
                            </button>
                            <button type="submit" id="btn-publish" name="action" value="publish" class="rk-btn-publish">
                                Terbitkan Kabar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Kolom Kanan: Timeline Kabar Menggunakan Komponen Reusable --}}
                <div class="rk-update-list-wrap">

                    {{-- Filter Status & Lansia --}}
                    <div class="rk-filter-bar">
                        <div class="rk-filter-pills" role="navigation" aria-label="Filter kabar per status">
                            <a
                                href="{{ route('care-updates.family-updates.index', array_filter(['resident' => $selectedResidentId])) }}"
                                class="rk-filter-pill {{ $statusFilter === null ? 'is-active' : '' }}"
                            >
                                Semua Status
                            </a>
                            <a
                                href="{{ route('care-updates.family-updates.index', array_filter(['resident' => $selectedResidentId, 'status' => 'published'])) }}"
                                class="rk-filter-pill {{ $statusFilter === 'published' ? 'is-active' : '' }}"
                            >
                                Terbit
                            </a>
                            <a
                                href="{{ route('care-updates.family-updates.index', array_filter(['resident' => $selectedResidentId, 'status' => 'draft'])) }}"
                                class="rk-filter-pill {{ $statusFilter === 'draft' ? 'is-active' : '' }}"
                            >
                                Draft
                            </a>
                        </div>
                    </div>

                    {{-- Reusable Component --}}
                    <x-care-updates.timeline
                        :updates="$updates"
                        empty-message="Belum ada kabar keluarga yang dibuat untuk kategori ini."
                    />

                    {{-- Pagination --}}
                    @if ($page > 1 || $hasMore)
                        <nav class="rk-stitch-filters" style="margin-top: 20px; justify-content: center;" aria-label="Navigasi halaman kabar">
                            @if ($page > 1)
                                <a
                                    href="{{ route('care-updates.family-updates.index', array_filter(['resident' => $selectedResidentId, 'status' => $statusFilter, 'page' => $page - 1])) }}"
                                    class="rk-stitch-filter"
                                >
                                    &larr; Halaman Sebelumnya
                                </a>
                            @endif
                            @if ($hasMore)
                                <a
                                    href="{{ route('care-updates.family-updates.index', array_filter(['resident' => $selectedResidentId, 'status' => $statusFilter, 'page' => $page + 1])) }}"
                                    class="rk-stitch-filter"
                                >
                                    Halaman Selanjutnya &rarr;
                                </a>
                            @endif
                        </nav>
                    @endif

                </div>

            </div>
        @endif

    </section>

    <script>
        function updateConsentInfo(select) {
            const container = document.getElementById('consent-status-container');
            const visibilityWarning = document.getElementById('visibility-warning');
            const activeBanner = document.getElementById('consent-active');
            const inactiveBanner = document.getElementById('consent-inactive');
            const btnPublish = document.getElementById('btn-publish');

            if (!select.value) {
                container.style.display = 'none';
                if (btnPublish) {
                    btnPublish.disabled = false;
                    btnPublish.style.opacity = '1';
                    btnPublish.title = '';
                }
                return;
            }

            container.style.display = 'block';
            const option = select.options[select.selectedIndex];
            const hasPhoto = option.getAttribute('data-photo-consent') === '1';
            const hasVisibility = option.getAttribute('data-visibility-consent') === '1';

            if (!hasVisibility) {
                visibilityWarning.style.display = 'flex';
                activeBanner.style.display = 'none';
                inactiveBanner.style.display = 'none';
                if (btnPublish) {
                    btnPublish.disabled = true;
                    btnPublish.style.opacity = '0.5';
                    btnPublish.title = 'Lansia ini belum memiliki persetujuan visibilitas keluarga.';
                }
            } else {
                visibilityWarning.style.display = 'none';
                if (btnPublish) {
                    btnPublish.disabled = false;
                    btnPublish.style.opacity = '1';
                    btnPublish.title = '';
                }

                if (hasPhoto) {
                    activeBanner.style.display = 'flex';
                    inactiveBanner.style.display = 'none';
                } else {
                    activeBanner.style.display = 'none';
                    inactiveBanner.style.display = 'flex';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('resident_id');
            if (select && select.value) {
                updateConsentInfo(select);
            }
        });
    </script>
@endsection
