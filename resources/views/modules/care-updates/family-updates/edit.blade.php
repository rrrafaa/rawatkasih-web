@extends('layouts.caregiver')

@section('title', 'Edit Draf Kabar Keluarga — RawatKasih')

@section('page-styles')
<style>
    .rk-edit-container {
        max-width: 640px;
        margin: 24px auto 0;
    }

    .rk-update-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .rk-field-group {
        margin-bottom: 18px;
    }

    .rk-field-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .rk-field-textarea,
    .rk-field-file {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.875rem;
        color: #111827;
        background-color: #ffffff;
        box-sizing: border-box;
    }

    .rk-field-textarea:focus,
    .rk-field-file:focus {
        outline: none;
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
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
        display: flex;
        gap: 12px;
        margin-top: 24px;
        justify-content: flex-end;
    }

    .rk-btn-cancel {
        display: inline-flex;
        align-items: center;
        padding: 10px 18px;
        min-height: 42px;
        background-color: #f3f4f6;
        color: #374151;
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        text-decoration: none;
    }

    .rk-btn-draft {
        display: inline-flex;
        align-items: center;
        padding: 10px 18px;
        min-height: 42px;
        background-color: #ffffff;
        color: #374151;
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        cursor: pointer;
    }

    .rk-btn-publish {
        display: inline-flex;
        align-items: center;
        padding: 10px 18px;
        min-height: 42px;
        background-color: #0d9488;
        color: #ffffff;
        font-size: 0.875rem;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
</style>
@endsection

@section('workspace')
    <section class="rk-care-page rk-stitch-page">
        <header class="rk-today-header">
            <div class="rk-today-title">
                <h1>Edit Draf Kabar Keluarga</h1>
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 4px;">
                    Untuk lansia: <strong>{{ $residentName }}</strong> (Kamar {{ $residentRoom }})
                </p>
            </div>
        </header>

        <div class="rk-edit-container">
            <div class="rk-update-card">
                <form method="POST" action="{{ route('care-updates.family-updates.update', ['updateId' => $update['id']]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    {{-- Informasi Consent --}}
                    @if (! $hasVisibilityConsent)
                        <div class="rk-consent-banner is-inactive" style="margin-bottom: 18px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; flex-shrink: 0;" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span><strong>Belum ada izin visibilitas keluarga:</strong> Kabar hanya dapat disimpan sebagai draf, belum dapat diterbitkan langsung.</span>
                        </div>
                    @endif

                    {{-- Ringkasan Kabar --}}
                    <div class="rk-field-group">
                        <label for="summary" class="rk-field-label">
                            Ringkasan Kabar <span style="color: #dc2626;">*</span>
                        </label>
                        <textarea
                            id="summary"
                            name="summary"
                            rows="5"
                            class="rk-field-textarea"
                            required
                            minlength="5"
                            maxlength="2000"
                            @error('summary') aria-invalid="true" @enderror
                        >{{ old('summary', $update['summary'] ?? '') }}</textarea>
                        @error('summary')
                            <div class="rk-field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Foto yang sudah ada jika ada --}}
                    @if ($photoUrl)
                        <div class="rk-field-group" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px;">
                            <span class="rk-field-label">Foto Saat Ini</span>
                            <div style="max-width: 200px; max-height: 140px; overflow: hidden; border-radius: 6px; margin: 8px 0;">
                                <img src="{{ $photoUrl }}" alt="Foto draf" style="width: 100%; height: auto; object-fit: cover;">
                            </div>

                        </div>
                    @endif

                    {{-- Ganti / Unggah Foto Baru --}}
                    <div class="rk-field-group">
                        <label for="photo" class="rk-field-label">
                            {{ $photoUrl ? 'Ganti Foto Dokumentasi (Opsional)' : 'Tambah Foto Dokumentasi (Wajib)' }}
                        </label>
                        <input
                            type="file"
                            id="photo"
                            name="photo"
                            accept="image/jpeg,image/png,image/webp"
                            class="rk-field-file"
                            @required(! $photoUrl)
                            @error('photo') aria-invalid="true" @enderror
                        >
                        <p style="font-size: 0.75rem; color: #6b7280; margin-top: 4px;">
                            Format: JPEG, PNG, atau WebP. Maksimal 3 MB.
                        </p>
                        @error('photo')
                            <div class="rk-field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="rk-btn-group">
                        <a href="{{ route('care-updates.family-updates.index', ['resident' => $update['resident_id']]) }}" class="rk-btn-cancel">
                            Batal
                        </a>
                        <button type="submit" name="action" value="draft" class="rk-btn-draft">
                            Simpan Perubahan Draf
                        </button>
                        <button
                            type="submit"
                            name="action"
                            value="publish"
                            class="rk-btn-publish"
                            @if (! $hasVisibilityConsent)
                                disabled
                                style="opacity: 0.5; cursor: not-allowed;"
                                title="Lansia belum memiliki izin visibilitas keluarga."
                            @endif
                        >
                            Terbitkan Kabar
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </section>
@endsection
