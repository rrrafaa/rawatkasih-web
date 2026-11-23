
@extends('layouts.admin')

@section('title', 'Template Tugas — RawatKasih')

@section('admin-content')

    @php
        $templateStatusFilter = $templateStatusFilter ?? 'active';
        $selectedResidentId = $selectedResidentId ?? '';

        $residentOptions = $residentOptions ?? $residents ?? [];

        $activeTemplateCount = $activeTemplateCount ?? 0;
        $inactiveTemplateCount = $inactiveTemplateCount ?? 0;

        $totalFilteredTemplates = $totalFilteredTemplates ?? count($templates);
        $templatePerPage = $templatePerPage ?? 10;
        $templatePage = $templatePage ?? 1;
        $templateTotalPages = $templateTotalPages ?? 1;

        $firstTemplateItem = $totalFilteredTemplates > 0
            ? (($templatePage - 1) * $templatePerPage) + 1
            : 0;

        $lastTemplateItem = min(
            $templatePage * $templatePerPage,
            $totalFilteredTemplates
        );

        $pageWindowStart = max(1, $templatePage - 2);
        $pageWindowEnd = min($templateTotalPages, $templatePage + 2);
    @endphp

    {{-- JUDUL HALAMAN --}}
    <div class="adm-page-heading">
        <h1 class="adm-page-title">Template Tugas</h1>

        <button
            type="button"
            class="adm-primary-button"
            id="open-template-drawer"
        >
            <span>+</span>
            <span>Tambah template</span>
        </button>
    </div>

    {{-- FILTER TEMPLATE --}}
    <div class="adm-toolbar">

        {{-- Filter berdasarkan lansia --}}
        <form
            method="GET"
            action="{{ route('care-tasks.templates.index') }}"
            class="adm-resident-filter"
        >
            <input
                type="hidden"
                name="status"
                value="{{ $templateStatusFilter }}"
            >

            <select
                class="adm-filter"
                name="resident_id"
                aria-label="Filter berdasarkan lansia"
            >
                <option value="">Semua lansia</option>

                @foreach ($residentOptions as $residentOption)
                    <option
                        value="{{ $residentOption['id'] }}"
                        @selected(
                            (string) $selectedResidentId
                            === (string) $residentOption['id']
                        )
                    >
                        {{ $residentOption['full_name'] }}
                        ({{ ($residentOption['status'] ?? '') === 'active'
                            ? 'Aktif'
                            : 'Nonaktif' }})
                    </option>
                @endforeach
            </select>

            <button type="submit" class="adm-filter-apply">
                Terapkan
            </button>
        </form>

        {{-- Filter status template --}}
        <div class="adm-tabs">

            <a
                href="{{ route('care-tasks.templates.index', array_filter([
                    'status' => 'active',
                    'resident_id' => $selectedResidentId ?: null,
                ])) }}"
                class="adm-tab {{ $templateStatusFilter === 'active' ? 'is-active' : '' }}"
                @if ($templateStatusFilter === 'active') aria-current="page" @endif
            >
                Aktif ({{ $activeTemplateCount }})
            </a>

            <a
                href="{{ route('care-tasks.templates.index', array_filter([
                    'status' => 'inactive',
                    'resident_id' => $selectedResidentId ?: null,
                ])) }}"
                class="adm-tab {{ $templateStatusFilter === 'inactive' ? 'is-active' : '' }}"
                @if ($templateStatusFilter === 'inactive') aria-current="page" @endif
            >
                Nonaktif ({{ $inactiveTemplateCount }})
            </a>

        </div>
    </div>

    {{-- TABEL TEMPLATE --}}
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Lansia</th>
                    <th>Tugas</th>
                    <th>Jam</th>
                    <th>Ulang</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($templates as $template)
                    @php
                        // Gunakan seluruh data lansia agar nama tetap terlihat
                        // meskipun lansia sudah berstatus nonaktif.
                        $resident = collect($residentOptions)->firstWhere(
                            'id',
                            $template['resident_id']
                        );

                        $days = [
                            0 => 'Minggu',
                            1 => 'Senin',
                            2 => 'Selasa',
                            3 => 'Rabu',
                            4 => 'Kamis',
                            5 => 'Jumat',
                            6 => 'Sabtu',
                        ];

                        $repeat = $template['frequency'] === 'daily'
                            ? 'Setiap hari'
                            : collect($template['days_of_week'] ?? [])
                                ->map(fn ($day) => $days[$day] ?? null)
                                ->filter()
                                ->implode(', ');

                        $isActiveTemplate = filter_var(
                            $template['is_active'] ?? false,
                            FILTER_VALIDATE_BOOLEAN
                        );
                    @endphp

                    <tr>
                        {{-- LANSIA --}}
                        <td>
                            <span class="adm-elder-name">
                                {{ $resident['full_name'] ?? 'Lansia tidak ditemukan' }}
                            </span>
                        </td>

                        {{-- NAMA TUGAS --}}
                        <td>
                            <span class="adm-task-name">
                                {{ $template['title'] }}
                            </span>
                        </td>

                        {{-- JAM --}}
                        <td>
                            {{ $template['scheduled_time']
                                ? substr($template['scheduled_time'], 0, 5)
                                : '—' }}
                        </td>

                        {{-- FREKUENSI --}}
                        <td>{{ $repeat ?: '—' }}</td>

                        {{-- STATUS --}}
                        <td>
                            <span class="adm-status {{ $isActiveTemplate ? '' : 'is-inactive' }}">
                                ● {{ $isActiveTemplate ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>

                        {{-- AKSI --}}
                        <td>
                            <div class="adm-row-actions">
                                <div class="adm-action-menu">

                                    <button
                                        type="button"
                                        class="adm-row-action"
                                        data-action-menu-toggle
                                        aria-label="Buka menu aksi"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        ⋯
                                    </button>

                                    <div
                                        class="adm-action-dropdown"
                                        data-action-menu
                                        hidden
                                    >

                                        {{-- EDIT TEMPLATE --}}
                                        <button
                                            type="button"
                                            class="adm-dropdown-item"
                                            data-edit-template
                                            data-id="{{ $template['id'] }}"
                                            data-update-url="{{ url('/care-tasks/templates/' . $template['id']) }}"
                                            data-resident="{{ $template['resident_id'] }}"
                                            data-title="{{ $template['title'] }}"
                                            data-category="{{ $template['category'] }}"
                                            data-time="{{ $template['scheduled_time'] ? substr($template['scheduled_time'], 0, 5) : '' }}"
                                            data-instructions="{{ $template['instructions'] ?? '' }}"
                                            data-frequency="{{ $template['frequency'] }}"
                                            data-days="{{ json_encode($template['days_of_week'] ?? []) }}"
                                        >
                                            Edit template
                                        </button>

                                        {{-- UBAH STATUS --}}
                                        <div class="adm-dropdown-submenu">

                                            <button
                                                type="button"
                                                class="adm-dropdown-item"
                                                data-status-menu-toggle
                                                aria-expanded="false"
                                            >
                                                Ubah status
                                                <span aria-hidden="true">›</span>
                                            </button>

                                            <div
                                                class="adm-status-submenu"
                                                data-status-menu
                                                hidden
                                            >

                                                @if ($isActiveTemplate)

                                                    {{-- TEMPLATE AKTIF --}}
                                                    {{-- Harus dikonfirmasi sebelum dinonaktifkan --}}
                                                    <button
                                                        type="button"
                                                        class="adm-dropdown-item"
                                                        data-template-deactivate
                                                        data-template-title="{{ $template['title'] }}"
                                                        data-template-form-id="deactivate-template-{{ $template['id'] }}"
                                                    >
                                                        Nonaktifkan
                                                    </button>

                                                    <form
                                                        id="deactivate-template-{{ $template['id'] }}"
                                                        method="POST"
                                                        action="{{ route('care-tasks.templates.status', $template['id']) }}"
                                                        hidden
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <input
                                                            type="hidden"
                                                            name="is_active"
                                                            value="0"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="resident_id"
                                                            value="{{ $selectedResidentId }}"
                                                        >
                                                    </form>

                                                @else

                                                    {{-- TEMPLATE NONAKTIF --}}
                                                    {{-- Bisa langsung diaktifkan --}}
                                                    <form
                                                        method="POST"
                                                        action="{{ route('care-tasks.templates.status', $template['id']) }}"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <input
                                                            type="hidden"
                                                            name="is_active"
                                                            value="1"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="resident_id"
                                                            value="{{ $selectedResidentId }}"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="adm-dropdown-item"
                                                        >
                                                            Aktifkan
                                                        </button>
                                                    </form>

                                                @endif

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="6">
                            Tidak ada template
                            {{ $templateStatusFilter === 'active' ? 'aktif' : 'nonaktif' }}
                            untuk filter yang dipilih.
                        </td>
                    </tr>

                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION DINAMIS --}}
    <div class="adm-pagination">

        <span>
            Menampilkan {{ $firstTemplateItem }}–{{ $lastTemplateItem }}
            dari {{ $totalFilteredTemplates }} template
        </span>

        @if ($templateTotalPages > 1)

            <div class="adm-pagination-pages">

                {{-- HALAMAN SEBELUMNYA --}}
                @if ($templatePage > 1)
                    <a
                        href="{{ route('care-tasks.templates.index', [
                            'status' => $templateStatusFilter,
                            'resident_id' => $selectedResidentId ?: null,
                            'page' => $templatePage - 1,
                        ]) }}"
                        class="adm-page-number"
                        aria-label="Halaman sebelumnya"
                    >
                        ‹
                    </a>
                @endif

                {{-- HALAMAN PERTAMA --}}
                @if ($pageWindowStart > 1)
                    <a
                        href="{{ route('care-tasks.templates.index', [
                            'status' => $templateStatusFilter,
                            'resident_id' => $selectedResidentId ?: null,
                            'page' => 1,
                        ]) }}"
                        class="adm-page-number"
                    >
                        1
                    </a>

                    @if ($pageWindowStart > 2)
                        <span class="adm-page-number" aria-hidden="true">
                            ...
                        </span>
                    @endif
                @endif

                {{-- NOMOR HALAMAN --}}
                @for ($pageNumber = $pageWindowStart; $pageNumber <= $pageWindowEnd; $pageNumber++)
                    <a
                        href="{{ route('care-tasks.templates.index', [
                            'status' => $templateStatusFilter,
                            'resident_id' => $selectedResidentId ?: null,
                            'page' => $pageNumber,
                        ]) }}"
                        class="adm-page-number {{ $templatePage === $pageNumber ? 'is-active' : '' }}"
                        @if ($templatePage === $pageNumber) aria-current="page" @endif
                    >
                        {{ $pageNumber }}
                    </a>
                @endfor

                {{-- HALAMAN TERAKHIR --}}
                @if ($pageWindowEnd < $templateTotalPages)

                    @if ($pageWindowEnd < $templateTotalPages - 1)
                        <span class="adm-page-number" aria-hidden="true">
                            ...
                        </span>
                    @endif

                    <a
                        href="{{ route('care-tasks.templates.index', [
                            'status' => $templateStatusFilter,
                            'resident_id' => $selectedResidentId ?: null,
                            'page' => $templateTotalPages,
                        ]) }}"
                        class="adm-page-number"
                    >
                        {{ $templateTotalPages }}
                    </a>

                @endif

                {{-- HALAMAN BERIKUTNYA --}}
                @if ($templatePage < $templateTotalPages)
                    <a
                        href="{{ route('care-tasks.templates.index', [
                            'status' => $templateStatusFilter,
                            'resident_id' => $selectedResidentId ?: null,
                            'page' => $templatePage + 1,
                        ]) }}"
                        class="adm-page-number"
                        aria-label="Halaman berikutnya"
                    >
                        ›
                    </a>
                @endif

            </div>

        @endif
    </div>

@endsection


{{-- =========================================================
     DRAWER DAN MODAL ADMIN
     ========================================================= --}}
@section('admin-overlay')

    @php
        $oldDays = collect(old('days_of_week', []))
            ->map(fn ($day) => (string) $day)
            ->all();

        $oldCategory = old('category', '');
        $oldFrequency = old('frequency', 'daily');

        $shouldOpenTemplateDrawer = $errors->any()
            || session('task_feedback.type') === 'error';
    @endphp

    {{-- =====================================================
         DRAWER TAMBAH TEMPLATE
         ===================================================== --}}
    <div
        class="adm-overlay"
        id="template-drawer"
        @if (!$shouldOpenTemplateDrawer) hidden @endif
    >

        <section
            class="adm-drawer"
            role="dialog"
            aria-modal="true"
            aria-labelledby="template-drawer-title"
        >

            {{-- HEADER DRAWER --}}
            <div class="adm-drawer-header">
                <h2
                    class="adm-drawer-title"
                    id="template-drawer-title"
                >
                    Tambah template
                </h2>

                <button
                    type="button"
                    class="adm-drawer-close"
                    data-close-drawer
                    aria-label="Tutup"
                >
                    &times;
                </button>
            </div>

            {{-- FORM TAMBAH TEMPLATE --}}
            <form
                id="template-form"
                method="POST"
                action="{{ route('care-tasks.templates.store') }}"
                data-store-action="{{ route('care-tasks.templates.store') }}"
            >
                @csrf

                <div class="adm-drawer-body">

                    {{-- ERROR VALIDASI SERVER --}}
                    @if ($errors->any())
                        <div class="adm-form-error" role="alert">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    {{-- LANSIA --}}
                    <div class="adm-form-group">
                        <label
                            class="adm-form-label"
                            for="elder"
                        >
                            Lansia <span class="adm-required">*</span>
                        </label>

                        <select
                            class="adm-form-control"
                            id="elder"
                            name="resident_id"
                            required
                        >
                            <option value="">Pilih lansia</option>

                            @foreach ($residents as $resident)
                                <option
                                    value="{{ $resident['id'] }}"
                                    @selected(
                                        (string) old('resident_id')
                                        === (string) $resident['id']
                                    )
                                >
                                    {{ $resident['full_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- NAMA TUGAS --}}
                    <div class="adm-form-group">
                        <label
                            class="adm-form-label"
                            for="task-name"
                        >
                            Nama tugas <span class="adm-required">*</span>
                        </label>

                        <input
                            class="adm-form-control"
                            id="task-name"
                            name="title"
                            type="text"
                            value="{{ old('title') }}"
                            placeholder="Contoh: Minum obat pagi"
                            required
                        >
                    </div>

                    {{-- KATEGORI --}}
                    <div class="adm-form-group">
                        <span class="adm-form-label">
                            Kategori <span class="adm-required">*</span>
                        </span>

                        <div class="adm-category-options">

                            <button
                                type="button"
                                class="adm-choice {{ $oldCategory === 'Makan' ? 'is-selected' : '' }}"
                                data-category="Makan"
                            >
                                Makan
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ $oldCategory === 'Mandi' ? 'is-selected' : '' }}"
                                data-category="Mandi"
                            >
                                Mandi
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ $oldCategory === 'Aktivitas' ? 'is-selected' : '' }}"
                                data-category="Aktivitas"
                            >
                                Aktivitas
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ $oldCategory === 'Obat' ? 'is-selected' : '' }}"
                                data-category="Obat"
                            >
                                Obat
                            </button>

                        </div>

                        <input
                            type="hidden"
                            name="category"
                            id="task-category"
                            value="{{ $oldCategory }}"
                        >
                    </div>

                    {{-- JAM --}}
                    <div class="adm-form-group">
                        <label
                            class="adm-form-label"
                            for="task-time"
                        >
                            Jam <span class="adm-required">*</span>
                        </label>

                        <input
                            class="adm-form-control"
                            id="task-time"
                            name="scheduled_time"
                            type="time"
                            value="{{ old('scheduled_time', '07:30') }}"
                            required
                        >
                    </div>

                    {{-- FREKUENSI --}}
                    <div class="adm-form-group">
                        <span class="adm-form-label">
                            Ulangi <span class="adm-required">*</span>
                        </span>

                        <div class="adm-repeat-options">

                            <button
                                type="button"
                                class="adm-choice {{ $oldFrequency === 'daily' ? 'is-selected' : '' }}"
                                data-repeat="daily"
                            >
                                Setiap hari
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ $oldFrequency === 'weekly' ? 'is-selected' : '' }}"
                                data-repeat="weekly"
                            >
                                Pilih hari
                            </button>

                        </div>

                        <input
                            type="hidden"
                            name="frequency"
                            id="task-repeat"
                            value="{{ $oldFrequency }}"
                        >

                        {{-- PILIH HARI --}}
                        <div
                            class="adm-day-options"
                            id="task-days"
                            @if ($oldFrequency !== 'weekly') hidden @endif
                            style="margin-top: 12px;"
                        >

                            <button
                                type="button"
                                class="adm-choice {{ in_array('1', $oldDays, true) ? 'is-selected' : '' }}"
                                data-day="1"
                            >
                                Sen
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ in_array('2', $oldDays, true) ? 'is-selected' : '' }}"
                                data-day="2"
                            >
                                Sel
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ in_array('3', $oldDays, true) ? 'is-selected' : '' }}"
                                data-day="3"
                            >
                                Rab
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ in_array('4', $oldDays, true) ? 'is-selected' : '' }}"
                                data-day="4"
                            >
                                Kam
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ in_array('5', $oldDays, true) ? 'is-selected' : '' }}"
                                data-day="5"
                            >
                                Jum
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ in_array('6', $oldDays, true) ? 'is-selected' : '' }}"
                                data-day="6"
                            >
                                Sab
                            </button>

                            <button
                                type="button"
                                class="adm-choice {{ in_array('0', $oldDays, true) ? 'is-selected' : '' }}"
                                data-day="0"
                            >
                                Min
                            </button>

                        </div>
                    </div>

                    {{-- INSTRUKSI --}}
                    <div class="adm-form-group">
                        <label
                            class="adm-form-label"
                            for="task-instructions"
                        >
                            Instruksi
                        </label>

                        <textarea
                            class="adm-form-control"
                            id="task-instructions"
                            name="instructions"
                            rows="4"
                            placeholder="Tambahkan instruksi khusus jika diperlukan"
                        >{{ old('instructions') }}</textarea>
                    </div>

                </div>

                {{-- FOOTER DRAWER --}}
                <div class="adm-drawer-footer">

                    <button
                        type="button"
                        class="adm-secondary-button"
                        data-close-drawer
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="adm-primary-button"
                        id="template-submit-button"
                    >
                        Simpan data
                    </button>

                </div>

            </form>
        </section>
    </div>


    {{-- =====================================================
         MODAL KONFIRMASI NONAKTIFKAN TEMPLATE
         ===================================================== --}}
    <dialog
        class="adm-template-confirm-dialog"
        id="template-deactivation-dialog"
        aria-labelledby="template-deactivation-title"
        aria-describedby="template-deactivation-description"
    >
        <form method="dialog">

            <span
                class="adm-template-confirm-icon"
                aria-hidden="true"
            >
                !
            </span>

            <button
                type="submit"
                value="cancel"
                class="adm-template-confirm-close"
                aria-label="Tutup dialog"
            >
                &times;
            </button>

            <h2 id="template-deactivation-title">
                Nonaktifkan template?
            </h2>

            <p id="template-deactivation-description">
                Apakah kamu yakin ingin menonaktifkan template
                <strong id="template-deactivation-name"></strong>?

                Template yang dinonaktifkan tidak lagi digunakan
                untuk membuat jadwal tugas berikutnya.
            </p>

            <div class="adm-template-confirm-actions">

                <button
                    type="submit"
                    value="cancel"
                    class="adm-secondary-button"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    value="confirm"
                    class="adm-template-confirm-button"
                >
                    Ya, nonaktifkan
                </button>

            </div>

        </form>
    </dialog>

    {{-- POPUP HASIL DAN TOAST --}}
    <x-task-feedback />

@endsection