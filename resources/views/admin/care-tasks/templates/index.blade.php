
@extends('layouts.admin')

@section('title', 'Template Tugas — RawatKasih')

@section('admin-content')

    {{-- JUDUL HALAMAN --}}
    <div class="adm-page-heading">
        <h1 class="adm-page-title">Template Tugas</h1>

        <button type="button" class="adm-primary-button" id="open-template-drawer">
            <span>+</span>
            <span>Tambah template</span>
        </button>
    </div>

    {{-- ALERT --}}
    <div class="adm-alert">
        <div class="adm-alert-content">
            <span class="adm-alert-icon">ⓘ</span>
            <span>3 lansia belum punya rutinitas</span>
        </div>

        <a href="#">Lihat</a>
    </div>

    {{-- FILTER --}}
    <div class="adm-toolbar">
        <select class="adm-filter" aria-label="Filter lansia">
            <option>Semua lansia</option>
            <option>Ibu Sri</option>
            <option>Bapak Hartono</option>
            <option>Ibu Kartini</option>
        </select>

        <div class="adm-tabs">
            <button type="button" class="adm-tab is-active">
                Aktif (24)
            </button>

            <button type="button" class="adm-tab">
                Nonaktif (4)
            </button>
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
                        $resident = collect($residents)->firstWhere('id', $template['resident_id']);
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
                    @endphp

                    <tr>
                        <td>
                            <span class="adm-elder-name">
                                {{ $resident['full_name'] ?? 'Lansia tidak ditemukan' }}
                            </span>
                        </td>

                        <td>
                            <span class="adm-task-name">{{ $template['title'] }}</span>
                        </td>

                        <td>
                            {{ $template['scheduled_time']
                                ? substr($template['scheduled_time'], 0, 5)
                                : '—' }}
                        </td>

                        <td>{{ $repeat ?: '—' }}</td>

                        <td>
                            <span class="adm-status {{ $template['is_active'] ? '' : 'is-inactive' }}">
                                ● {{ $template['is_active'] ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>

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
                                    >⋯</button>

                                    <div class="adm-action-dropdown" data-action-menu hidden>
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
                                        >Edit template</button>

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

                                            <div class="adm-status-submenu" data-status-menu hidden>
                                                <form method="POST" action="{{ route('care-tasks.templates.status', $template['id']) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="adm-dropdown-item">
                                                        Aktif
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('care-tasks.templates.status', $template['id']) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="adm-dropdown-item">
                                                        Nonaktif
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada template tugas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="adm-pagination">
        <span>Menampilkan 1–7 dari 28 template</span>

        <div class="adm-pagination-pages">
            <button type="button" class="adm-page-number">‹</button>
            <button type="button" class="adm-page-number is-active">1</button>
            <button type="button" class="adm-page-number">2</button>
            <button type="button" class="adm-page-number">3</button>
            <button type="button" class="adm-page-number">4</button>
            <button type="button" class="adm-page-number">›</button>
        </div>
    </div>

@endsection

{{-- PANEL TAMBAH TEMPLATE --}}
@section('admin-overlay')
    <div class="adm-overlay" id="template-drawer" @if (!$errors->any()) hidden @endif>
        <section class="adm-drawer" role="dialog" aria-modal="true" aria-labelledby="template-drawer-title">
            <div class="adm-drawer-header">
                <h2 class="adm-drawer-title" id="template-drawer-title">Tambah template</h2>
                <button type="button" class="adm-drawer-close" data-close-drawer aria-label="Tutup">&times;</button>
            </div>

            <form
                id="template-form"
                method="POST"
                action="{{ route('care-tasks.templates.store') }}"
                data-store-action="{{ route('care-tasks.templates.store') }}"
            >
                @csrf

                <div class="adm-drawer-body">
                    @if ($errors->any())
                        <div class="adm-form-error" role="alert">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if (session('status'))
                        <p role="status">{{ session('status') }}</p>
                    @endif

                    <div class="adm-form-group">
                        @if ($errors->any())
                            <div class="adm-form-error" role="alert">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        @if (session('status'))
                            <p role="status">{{ session('status') }}</p>
                        @endif

                        <label class="adm-form-label" for="elder">
                            Lansia <span class="adm-required">*</span>
                        </label>

                        <select class="adm-form-control" id="elder" name="resident_id" required>
                            <option value="">Pilih lansia</option>
                            @foreach ($residents as $resident)
                                <option value="{{ $resident['id'] }}">
                                    {{ $resident['full_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label" for="task-name">
                            Nama tugas <span class="adm-required">*</span>
                        </label>
                        <input
                            class="adm-form-control"
                            id="task-name"
                            name="title"
                            type="text"
                            placeholder="Contoh: Minum obat pagi"
                            required
                        >
                    </div>

                    <div class="adm-form-group">
                        <span class="adm-form-label">
                            Kategori <span class="adm-required">*</span>
                        </span>

                        <div class="adm-category-options">
                            <button type="button" class="adm-choice" data-category="Makan">Makan</button>
                            <button type="button" class="adm-choice" data-category="Mandi">Mandi</button>
                            <button type="button" class="adm-choice" data-category="Aktivitas">Aktivitas</button>
                            <button type="button" class="adm-choice" data-category="Obat">Obat</button>
                        </div>

                        <input type="hidden" name="category" id="task-category">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label" for="task-time">
                            Jam <span class="adm-required">*</span>
                        </label>
                        <input
                            class="adm-form-control"
                            id="task-time"
                            name="scheduled_time"
                            type="time"
                            value="07:30"
                            required
                        >
                    </div>

                    <div class="adm-form-group">
                        <span class="adm-form-label">
                            Ulangi <span class="adm-required">*</span>
                        </span>

                        <div class="adm-repeat-options">
                            <button type="button" class="adm-choice is-selected" data-repeat="daily">
                                Setiap hari
                            </button>
                            <button type="button" class="adm-choice" data-repeat="weekly">
                                Pilih hari
                            </button>
                        </div>

                        <input type="hidden" name="frequency" id="task-repeat" value="daily">

                        <div class="adm-day-options" id="task-days" hidden style="margin-top: 12px;">
                            <button type="button" class="adm-choice" data-day="1">Sen</button>
                            <button type="button" class="adm-choice" data-day="2">Sel</button>
                            <button type="button" class="adm-choice" data-day="3">Rab</button>
                            <button type="button" class="adm-choice" data-day="4">Kam</button>
                            <button type="button" class="adm-choice" data-day="5">Jum</button>
                            <button type="button" class="adm-choice" data-day="6">Sab</button>
                            <button type="button" class="adm-choice" data-day="0">Min</button>
                        </div>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label" for="task-instructions">Instruksi</label>
                        <textarea
                            class="adm-form-control"
                            id="task-instructions"
                            name="instructions"
                            rows="4"
                            placeholder="Tambahkan instruksi khusus jika diperlukan"
                        ></textarea>
                    </div>
                </div>

                <div class="adm-drawer-footer">
                    <button type="button" class="adm-secondary-button" data-close-drawer>
                        Batal
                    </button>
                    <button type="submit" class="adm-primary-button" id="template-submit-button">
                        Simpan data
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection