@extends('modules.family-visits.admin.layout')

@section('visit-content')
    <header class="adm-page-heading">
        <div>
            <h1 class="adm-page-title">Manajemen Kunjungan</h1>
            <p>Tinjau pengajuan keluarga dan lihat riwayat keputusan kunjungan.</p>
        </div>
    </header>

    <section aria-label="Ringkasan status" class="adm-visit-counts">
        @foreach (\App\Enums\VisitStatus::cases() as $status)
            <a href="{{ route('admin.visits.index', ['status' => $status->value]) }}" class="adm-visit-card"
               @if (request('status') === $status->value) aria-current="true" @endif>
                <x-visit-status :status="$status"/>
                <strong>{{ $counts[$status->value] }}</strong>
            </a>
        @endforeach
    </section>

    <section aria-labelledby="visit-queue">
        <div class="adm-toolbar adm-visit-toolbar">
            <h2 id="visit-queue">Daftar pengajuan</h2>
            <form method="get" action="{{ route('admin.visits.index') }}" class="adm-visit-filter">
                <label for="admin-status" class="adm-form-label">Status pengajuan</label>
                <select id="admin-status" name="status" class="adm-filter fv-select">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\VisitStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button class="adm-secondary-button" type="submit">Tampilkan</button>
            </form>
        </div>

        <div class="adm-table-wrap" tabindex="0" role="region" aria-labelledby="visit-queue">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th scope="col">Lansia</th>
                        <th scope="col">Nama pengaju</th>
                        <th scope="col">Jadwal (WIB)</th>
                        <th scope="col">Pengunjung</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $item)
                        @php
                            $start = $item->requested_at->timezone('Asia/Jakarta')->locale('id');
                            $end = $item->requested_end_at?->timezone('Asia/Jakarta');
                        @endphp
                        <tr>
                            <td class="adm-elder-name">{{ $item->resident?->full_name ?? 'Lansia' }}</td>
                            <td>{{ $item->requester?->full_name ?? 'Keluarga' }}</td>
                            <td>
                                <time datetime="{{ $start->toIso8601String() }}">{{ $start->translatedFormat('d M Y') }}</time>
                                <small class="adm-visit-muted">{{ $start->format('H.i') }}{{ $end ? '–'.$end->format('H.i') : '' }}</small>
                            </td>
                            <td>{{ $item->visitor_count }} orang</td>
                            <td><x-visit-status :status="$item->status"/></td>
                            <td><a class="adm-visit-link" href="{{ route('admin.visits.show', $item->id) }}">Lihat detail</a></td>
                        </tr>
                    @empty
                        <tr><td class="adm-visit-empty" colspan="6">Belum ada pengajuan untuk status ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($visits->hasPages())
            <nav class="adm-pagination" aria-label="Halaman daftar kunjungan">
                <span>Halaman {{ $visits->currentPage() }} dari {{ $visits->lastPage() }}</span>
                <div class="adm-pagination-pages">
                    @if ($visits->previousPageUrl())
                        <a class="adm-page-number" href="{{ $visits->previousPageUrl() }}" aria-label="Sebelumnya">‹</a>
                    @endif
                    <span class="adm-page-number is-active" aria-current="page">{{ $visits->currentPage() }}</span>
                    @if ($visits->nextPageUrl())
                        <a class="adm-page-number" href="{{ $visits->nextPageUrl() }}" aria-label="Berikutnya">›</a>
                    @endif
                </div>
            </nav>
        @endif
    </section>
@endsection
