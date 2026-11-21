@extends('layouts.caregiver')

@section('title', 'Hari Ini — RawatKasih')

@section('workspace')
    @php
        $todayLabel = \Carbon\Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('l, d F');

        $nowJakarta = now('Asia/Jakarta');

        $viewMode = $viewMode ?? 'tasks';
    @endphp

    <section class="rk-care-page rk-stitch-page">

        {{-- Header halaman --}}
        <header class="rk-today-header">
            <div class="rk-today-title">
                <h1>{{ $viewMode === 'history' ? 'Riwayat Perawatan' : 'Hari Ini' }}</h1>
                <time datetime="{{ $date }}">{{ $todayLabel }}</time>
            </div>

            <nav class="rk-care-view-tabs" aria-label="Navigasi tampilan caregiver">
                <a
                    href="{{ route('caregiver.dashboard') }}"
                    class="rk-care-view-tab {{ $viewMode === 'tasks' ? 'is-active' : '' }}"
                    @if ($viewMode === 'tasks') aria-current="page" @endif
                >
                    Tugas Hari Ini
                </a>

                <a
                    href="{{ route('caregiver.dashboard', ['view' => 'history']) }}"
                    class="rk-care-view-tab {{ $viewMode === 'history' ? 'is-active' : '' }}"
                    @if ($viewMode === 'history') aria-current="page" @endif
                >
                    Riwayat
                </a>
            </nav>
        </header>

        @if (session('status'))
            <div class="rk-care-alert" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{-- =====================================================
             MODE RIWAYAT
             ===================================================== --}}
        @if ($viewMode === 'history')

            @php
                $historyRows = $historyRows ?? [];
                $historyPage = $historyPage ?? 1;
                $hasMoreHistory = $hasMoreHistory ?? false;

                $historyStatusLabels = [
                    'pending' => 'Belum',
                    'completed' => 'Selesai',
                    'postponed' => 'Ditunda',
                    'missed' => 'Terlewat',
                ];
            @endphp

            @if (count($historyRows) > 0)

                <section
                    class="rk-stitch-task-list"
                    aria-label="Riwayat perubahan status tugas perawatan"
                >

                    @foreach ($historyRows as $historyRow)
                        @php
                            $oldStatus = $historyRow['old_status'] ?? null;
                            $newStatus = $historyRow['new_status'] ?? 'pending';

                            $oldStatusLabel = $oldStatus !== null
                                ? ($historyStatusLabels[$oldStatus] ?? $oldStatus)
                                : 'Status awal';

                            $newStatusLabel = $historyStatusLabels[$newStatus]
                                ?? $newStatus;

                            $scheduledTime = substr(
                                $historyRow['scheduled_time'] ?? '--:--',
                                0,
                                5
                            );

                            $taskDateLabel = filled($historyRow['task_date'] ?? null)
                                ? \Carbon\Carbon::parse($historyRow['task_date'])
                                    ->locale('id')
                                    ->translatedFormat('d F Y')
                                : '-';

                            $changedAtLabel = filled($historyRow['changed_at'] ?? null)
                                ? \Carbon\Carbon::parse($historyRow['changed_at'])
                                    ->timezone('Asia/Jakarta')
                                    ->format('d/m/Y H:i')
                                : '-';
                        @endphp

                        <article class="rk-stitch-task-row">

                            {{-- Waktu dan informasi perubahan --}}
                            <div class="rk-stitch-task-main">

                                <div class="rk-stitch-task-time">
                                    <strong>{{ $scheduledTime }}</strong>

                                    <span class="rk-stitch-task-meta">
                                        {{ $taskDateLabel }}
                                    </span>
                                </div>

                                <div class="rk-stitch-task-information">
                                    <h3>{{ $historyRow['task_title'] }}</h3>

                                    <p>
                                        {{ $historyRow['resident_name'] }}
                                    </p>

                                    <p>
                                        {{ $oldStatusLabel }}
                                        &rarr;
                                        {{ $newStatusLabel }}
                                    </p>

                                    @if (filled($historyRow['reason'] ?? null))
                                        <p>
                                            Alasan: {{ $historyRow['reason'] }}
                                        </p>
                                    @endif

                                    <span class="rk-stitch-task-meta">
                                        Diubah oleh
                                        {{ $historyRow['changed_by_name'] ?? 'Tidak diketahui' }}
                                        pada {{ $changedAtLabel }}
                                    </span>
                                </div>

                            </div>

                            {{-- Status terbaru --}}
                            <div class="rk-stitch-task-actions">
                                <span class="rk-stitch-status rk-result-{{ $newStatus }}">
                                    {{ $newStatusLabel }}
                                </span>
                            </div>

                        </article>
                    @endforeach

                </section>

                {{-- Pagination riwayat --}}
                @if ($historyPage > 1 || $hasMoreHistory)
                    <nav
                        class="rk-care-view-tabs"
                        aria-label="Navigasi halaman riwayat"
                    >

                        @if ($historyPage > 1)
                            <a
                                href="{{ route('caregiver.dashboard', [
                                    'view' => 'history',
                                    'page' => $historyPage - 1,
                                ]) }}"
                                class="rk-care-view-tab"
                            >
                                Sebelumnya
                            </a>
                        @endif

                        <span class="rk-care-view-tab">
                            Halaman {{ $historyPage }}
                        </span>

                        @if ($hasMoreHistory)
                            <a
                                href="{{ route('caregiver.dashboard', [
                                    'view' => 'history',
                                    'page' => $historyPage + 1,
                                ]) }}"
                                class="rk-care-view-tab"
                            >
                                Berikutnya
                            </a>
                        @endif

                    </nav>
                @endif

            @else

                <div class="rk-care-empty rk-stitch-empty-residents">
                    <h2>Belum ada riwayat perubahan</h2>
                    <p>
                        Riwayat perubahan status tugas perawatan akan muncul di sini.
                    </p>
                </div>

            @endif

        {{-- =====================================================
             MODE TUGAS HARI INI
             ===================================================== --}}
        @elseif (count($residents) > 0)

            {{-- Tab Lansia --}}
            <nav class="rk-resident-tabs" aria-label="Pilih lansia">
                @foreach ($residents as $resident)
                    <button
                        type="button"
                        class="rk-resident-tab {{ $loop->first ? 'is-active' : '' }}"
                        data-resident-tab="{{ $resident['id'] }}"
                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                    >
                        <span>{{ $resident['full_name'] }}</span>

                        <span class="rk-resident-tab-count">
                            {{ $resident['task_summary']['total'] ?? 0 }}
                        </span>
                    </button>
                @endforeach
            </nav>

            {{-- Panel setiap lansia --}}
            @foreach ($residents as $resident)
                @php
                    $summary = $resident['task_summary'];

                    $total = (int) $summary['total'];
                    $completed = (int) $summary['completed'];
                    $pending = (int) $summary['pending'];
                    $postponed = (int) $summary['postponed'];
                    $missed = (int) $summary['missed'];

                    $percentage = (float) $summary['completion_percentage'];

                    $percentageLabel = rtrim(
                        rtrim(number_format($percentage, 1, '.', ''), '0'),
                        '.'
                    );

                    $residentTasks = collect($resident['tasks']);

                    $nextPendingTask = $residentTasks
                        ->filter(function ($task) use ($date, $nowJakarta) {
                            if (($task['status'] ?? 'pending') !== 'pending') {
                                return false;
                            }

                            $scheduledAt = \Carbon\Carbon::parse(
                                $date.' '.($task['scheduled_time'] ?? '00:00:00'),
                                'Asia/Jakarta'
                            );

                            return $scheduledAt->greaterThanOrEqualTo($nowJakarta);
                        })
                        ->sortBy('scheduled_time')
                        ->first();

                    $statusLabels = [
                        'pending' => 'Belum',
                        'completed' => 'Selesai',
                        'postponed' => 'Ditunda',
                        'missed' => 'Terlewat',
                    ];
                @endphp

                <section
                    class="rk-resident-panel"
                    data-resident-panel="{{ $resident['id'] }}"
                    @if (!$loop->first) hidden @endif
                >

                    {{-- Ringkasan progres --}}
                    <section class="rk-stitch-summary">
                        <div class="rk-stitch-summary-header">
                            <div class="rk-stitch-progress-label">
                                <strong>{{ $completed }} dari {{ $total }} selesai</strong>
                                <span>({{ $percentageLabel }}%)</span>
                            </div>

                            <div class="rk-stitch-filters" aria-label="Filter status tugas">
                                <button
                                    type="button"
                                    class="rk-stitch-filter rk-filter-completed"
                                    data-task-filter="completed"
                                    aria-pressed="false"
                                >
                                    <span class="rk-filter-dot"></span>
                                    Selesai {{ $completed }}
                                </button>

                                <button
                                    type="button"
                                    class="rk-stitch-filter rk-filter-pending"
                                    data-task-filter="pending"
                                    aria-pressed="false"
                                >
                                    <span class="rk-filter-dot"></span>
                                    Belum {{ $pending }}
                                </button>

                                <button
                                    type="button"
                                    class="rk-stitch-filter rk-filter-postponed"
                                    data-task-filter="postponed"
                                    aria-pressed="false"
                                >
                                    <span class="rk-filter-dot"></span>
                                    Ditunda {{ $postponed }}
                                </button>

                                <button
                                    type="button"
                                    class="rk-stitch-filter rk-filter-missed"
                                    data-task-filter="missed"
                                    aria-pressed="false"
                                >
                                    <span class="rk-filter-dot"></span>
                                    Terlewat {{ $missed }}
                                </button>
                            </div>
                        </div>

                        <div
                            class="rk-stitch-progress-track"
                            role="progressbar"
                            aria-label="Progres penyelesaian tugas"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="{{ $percentage }}"
                        >
                            <div
                                class="rk-stitch-progress-value"
                                style="width: {{ min(100, max(0, $percentage)) }}%"
                            ></div>
                        </div>
                    </section>

                    {{-- Daftar tugas --}}
                    <section
                        class="rk-stitch-task-list"
                        aria-label="Daftar tugas perawatan"
                    >

                        @forelse ($residentTasks as $task)
                            @php
                                $status = $task['status'] ?? 'pending';

                                $scheduledTime = substr(
                                    $task['scheduled_time'] ?? '--:--',
                                    0,
                                    5
                                );

                                $scheduledAt = \Carbon\Carbon::parse(
                                    $date.' '.($task['scheduled_time'] ?? '00:00:00'),
                                    'Asia/Jakarta'
                                );

                                $isLate = $status === 'pending'
                                    && $scheduledAt->lessThan($nowJakarta);

                                $isNext = $status === 'pending'
                                    && ($nextPendingTask['id'] ?? null) === $task['id'];

                                $occurrenceUrl = route(
                                    'care-tasks.occurrences.status',
                                    ['occurrenceId' => $task['id']]
                                );
                            @endphp

                            <article
                                class="rk-stitch-task-row rk-task-status-{{ $status }}"
                                data-task-row
                                data-task-status="{{ $status }}"
                            >

                                {{-- Waktu dan informasi tugas --}}
                                <div class="rk-stitch-task-main">

                                    <div class="rk-stitch-task-time">
                                        <strong>{{ $scheduledTime }}</strong>

                                        @if ($isLate)
                                            <span class="rk-task-time-label rk-task-late">
                                                Terlambat
                                            </span>
                                        @elseif ($isNext)
                                            <span class="rk-task-time-label rk-task-next">
                                                Berikutnya
                                            </span>
                                        @endif
                                    </div>

                                    <div class="rk-stitch-task-information">
                                        <h3>{{ $task['title'] }}</h3>

                                        @if (filled($task['instructions'] ?? null))
                                            <p>— {{ $task['instructions'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Status dan tindakan --}}
                                <div class="rk-stitch-task-actions">

                                    @if ($status === 'completed')

                                        <div class="rk-stitch-task-result">
                                            <span class="rk-stitch-status rk-result-completed">
                                                <svg viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.5"
                                                    stroke-linecap="round" stroke-linejoin="round"
                                                    aria-hidden="true">
                                                    <path d="m5 13 4 4L19 7"></path>
                                                </svg>
                                                Selesai
                                            </span>

                                            @if (filled($task['completed_at'] ?? null))
                                                <span class="rk-stitch-task-meta">
                                                    {{ \Carbon\Carbon::parse($task['completed_at'])->timezone('Asia/Jakarta')->format('H:i') }}

                                                    @if (filled($task['completed_by_name'] ?? null))
                                                        oleh {{ $task['completed_by_name'] }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>

                                        <button
                                            type="button"
                                            class="rk-stitch-edit-button"
                                            data-care-open-dialog
                                            data-dialog-mode="edit"
                                            data-target-status="{{ $status }}"
                                            data-update-url="{{ $occurrenceUrl }}"
                                            data-task-title="{{ $task['title'] }}"
                                            data-resident-name="{{ $resident['full_name'] }}"
                                            data-current-reason="{{ $task['reason'] ?? '' }}"
                                        >
                                            Ubah
                                        </button>

                                    @elseif ($status === 'postponed' || $status === 'missed')

                                        <div class="rk-stitch-task-result">
                                            <span class="rk-stitch-status rk-result-{{ $status }}">
                                                {{ $statusLabels[$status] }}
                                            </span>

                                            @if (filled($task['reason'] ?? null))
                                                <span class="rk-stitch-task-meta">
                                                    {{ $task['reason'] }}
                                                </span>
                                            @endif
                                        </div>

                                        <button
                                            type="button"
                                            class="rk-stitch-edit-button"
                                            data-care-open-dialog
                                            data-dialog-mode="edit"
                                            data-target-status="{{ $status }}"
                                            data-update-url="{{ $occurrenceUrl }}"
                                            data-task-title="{{ $task['title'] }}"
                                            data-resident-name="{{ $resident['full_name'] }}"
                                            data-current-reason="{{ $task['reason'] ?? '' }}"
                                        >
                                            Ubah
                                        </button>

                                    @else

                                        <form
                                            method="POST"
                                            action="{{ $occurrenceUrl }}"
                                            class="rk-stitch-complete-form"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="completed"
                                            >

                                            <button
                                                type="submit"
                                                class="rk-stitch-action-button rk-action-complete"
                                            >
                                                Selesai
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            class="rk-stitch-action-button rk-action-postpone"
                                            data-care-open-dialog
                                            data-dialog-mode="status"
                                            data-target-status="postponed"
                                            data-update-url="{{ $occurrenceUrl }}"
                                            data-task-title="{{ $task['title'] }}"
                                            data-resident-name="{{ $resident['full_name'] }}"
                                        >
                                            Tunda
                                        </button>

                                        <button
                                            type="button"
                                            class="rk-stitch-action-button rk-action-missed"
                                            data-care-open-dialog
                                            data-dialog-mode="status"
                                            data-target-status="missed"
                                            data-update-url="{{ $occurrenceUrl }}"
                                            data-task-title="{{ $task['title'] }}"
                                            data-resident-name="{{ $resident['full_name'] }}"
                                        >
                                            Lewat
                                        </button>

                                    @endif

                                </div>
                            </article>

                        @empty
                            <div class="rk-stitch-empty">
                                <p>Belum ada tugas perawatan untuk lansia ini hari ini.</p>
                            </div>
                        @endforelse

                        <p class="rk-stitch-filter-empty" hidden>
                            Tidak ada tugas dengan status tersebut.
                        </p>

                    </section>
                </section>
            @endforeach

        @else

            <div class="rk-care-empty rk-stitch-empty-residents">
                <h2>Belum ada penugasan lansia</h2>
                <p>
                    Belum ada lansia yang ditugaskan kepada akun caregiver Anda
                    untuk tanggal ini.
                </p>
            </div>

        @endif

    </section>

    {{-- Dialog alasan dan koreksi status: hanya untuk mode Tugas Hari Ini --}}
    @if ($viewMode === 'tasks')
        <div
            class="rk-care-dialog-overlay"
            id="care-task-dialog"
            hidden
        >
            <section
                class="rk-care-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="care-task-dialog-title"
            >

                <header class="rk-care-dialog-header">
                    <div>
                        <h2 id="care-task-dialog-title">Kenapa ditunda?</h2>
                        <p id="care-task-dialog-context"></p>
                    </div>

                    <button
                        type="button"
                        class="rk-care-dialog-close"
                        data-care-close-dialog
                        aria-label="Tutup dialog"
                    >
                        &times;
                    </button>
                </header>

                <form
                    method="POST"
                    action=""
                    id="care-task-update-form"
                >
                    @csrf
                    @method('PATCH')

                    <div
                        class="rk-care-dialog-status-field"
                        id="care-task-dialog-status-field"
                        hidden
                    >
                        <label for="care-task-dialog-status">Status baru</label>

                        <select
                            id="care-task-dialog-status"
                            disabled
                        >
                            <option value="completed">Selesai</option>
                            <option value="postponed">Ditunda</option>
                            <option value="missed">Terlewat</option>
                        </select>
                    </div>

                    <input
                        type="hidden"
                        name="status"
                        id="care-task-dialog-status-hidden"
                        value="postponed"
                    >

                    <div
                        class="rk-care-dialog-reasons"
                        id="care-task-dialog-reasons"
                    >
                        <p class="rk-care-dialog-label">Pilih alasan</p>

                        <div class="rk-care-reason-options">

                            <button
                                type="button"
                                data-care-reason="Sedang tidur"
                            >
                                Sedang tidur
                            </button>

                            <button
                                type="button"
                                data-care-reason="Kurang sehat"
                            >
                                Kurang sehat
                            </button>

                            <button
                                type="button"
                                data-care-reason="Menolak"
                            >
                                Menolak
                            </button>

                            <button
                                type="button"
                                data-care-reason-other
                            >
                                Lainnya
                            </button>

                        </div>
                    </div>

                    <div
                        class="rk-care-dialog-textarea"
                        id="care-task-dialog-textarea"
                    >
                        <label for="care-task-dialog-reason">Alasan</label>

                        <textarea
                            name="reason"
                            id="care-task-dialog-reason"
                            rows="3"
                            maxlength="2000"
                            placeholder="Tuliskan alasan penundaan di sini..."
                        ></textarea>

                        <p
                            class="rk-care-dialog-error"
                            id="care-task-dialog-error"
                            hidden
                        >
                            Alasan wajib diisi untuk tugas yang ditunda atau terlewat.
                        </p>
                    </div>

                    <footer class="rk-care-dialog-footer">
                        <button
                            type="button"
                            class="rk-care-dialog-cancel"
                            data-care-close-dialog
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="rk-care-dialog-save"
                        >
                            Simpan
                        </button>
                    </footer>

                </form>
            </section>
        </div>
    @endif
@endsection