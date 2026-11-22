@extends('modules.family-visits.layout', ['pageTitle' => 'Kunjungan Keluarga', 'pageDescription' => 'Ajukan jadwal dan pantau keputusan untuk setiap kunjungan.', 'breadcrumb' => 'Kunjungan'])

@section('portal-content')
    @if (!$resident)
        @include('modules.family-visits.partials.unlinked')
    @else
        @include('modules.family-visits.partials.resident')
        <section class="fv-section" aria-labelledby="visit-history">
            <div class="fv-list-heading">
                <h2 id="visit-history">Riwayat pengajuan</h2>
                <a class="adm-primary-button" href="{{ route('family.visits.create', ['resident' => $resident->id]) }}">+ Ajukan kunjungan</a>
            </div>
            <form method="get" action="{{ route('family.visits.index') }}" class="adm-toolbar fv-filter-bar">
                <input type="hidden" name="resident" value="{{ $resident->id }}">
                <div class="fv-filter-field">
                    <label class="adm-form-label" for="visit-status">Status pengajuan</label>
                    <select class="adm-filter" name="status" id="visit-status">
                        <option value="">Semua status</option>
                        @foreach (\App\Enums\VisitStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="adm-secondary-button" type="submit">Tampilkan</button>
            </form>
            <div class="adm-table-wrap" tabindex="0" role="region" aria-labelledby="visit-history">
                <table class="adm-table fv-visit-table">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Waktu (WIB)</th>
                            <th scope="col">Pengunjung</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visits as $item)
                            <tr>
                                <td class="adm-elder-name">{{ $item->requested_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y') }}</td>
                                <td>{{ $item->requested_at->timezone('Asia/Jakarta')->format('H.i') }}{{ $item->requested_end_at ? '–'.$item->requested_end_at->timezone('Asia/Jakarta')->format('H.i') : '' }}</td>
                                <td>{{ $item->visitor_count }} orang</td>
                                <td><x-visit-status :status="$item->status"/></td>
                                <td><a class="fv-table-link" href="{{ route('family.visits.show', $item->id) }}">Lihat detail</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fv-table-empty" colspan="5">{{ request('status') ? 'Belum ada pengajuan dengan status ini.' : 'Belum ada pengajuan kunjungan.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('modules.family-visits.partials.pagination', ['paginator' => $visits])
        </section>
        @include('modules.family-visits.partials.guidance')
    @endif
@endsection
