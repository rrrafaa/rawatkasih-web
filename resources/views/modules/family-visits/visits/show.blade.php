@extends('modules.family-visits.layout', ['pageTitle' => 'Kunjungan keluarga', 'pageDescription' => 'Lihat rincian dan perkembangan pengajuan kunjungan.', 'breadcrumb' => 'Kunjungan'])

@section('portal-content')
    @include('modules.family-visits.partials.resident')

    @php
        $start = $visit->requested_at->timezone('Asia/Jakarta')->locale('id');
        $end = $visit->requested_end_at?->timezone('Asia/Jakarta');
        $requesterName = $visit->requester?->full_name ?? $user->name();
        [$process, $description] = match ($visit->status) {
            \App\Enums\VisitStatus::Pending => ['Menunggu tinjauan admin', 'Admin akan meninjau pengajuan dan memberikan keputusan.'],
            \App\Enums\VisitStatus::Approved => ['Jadwal dikonfirmasi', 'Keluarga dapat datang pada waktu kunjungan yang tercantum.'],
            \App\Enums\VisitStatus::Rejected => ['Peninjauan selesai', 'Admin telah meninjau dan menolak pengajuan ini.'],
            \App\Enums\VisitStatus::Cancelled => ['Pengajuan ditutup', 'Pengajuan ini telah dibatalkan.'],
        };
    @endphp

    <section class="fv-card fv-request-card" aria-labelledby="visit-date-heading">
        <h2 id="visit-date-heading">{{ $start->translatedFormat('l, d F Y') }}</h2>
        <dl class="fv-visit-facts">
            <div>
                <dt>Waktu</dt>
                <dd>{{ $start->format('H.i') }}{{ $end ? '–'.$end->format('H.i') : '' }} WIB</dd>
            </div>
            <div>
                <dt>Jumlah Pengunjung</dt>
                <dd>{{ $visit->visitor_count }} orang</dd>
            </div>
            <div>
                <dt>Nama pengaju</dt>
                <dd>{{ $requesterName }}</dd>
            </div>
            @if ($visit->primary_visitor_name && $visit->primary_visitor_name !== $requesterName)
                <div>
                    <dt>Pengunjung utama</dt>
                    <dd>{{ $visit->primary_visitor_name }}</dd>
                </div>
            @endif
            @if ($visit->note)
                <div>
                    <dt>Catatan keluarga</dt>
                    <dd>{{ $visit->note }}</dd>
                </div>
            @endif
        </dl>

        <section class="fv-visit-process" aria-labelledby="visit-status-heading">
            <div class="fv-visit-status">
                <h3 id="visit-status-heading">Status</h3>
                <x-visit-status :status="$visit->status"/>
            </div>
            <strong>{{ $process }}</strong>
            <p>{{ $description }}</p>
            @if ($visit->review_note)
                <div class="fv-review-note">
                    <strong>Catatan admin</strong>
                    <p>{{ $visit->review_note }}</p>
                </div>
            @endif
            @if ($visit->reviewed_at)
                <small>Keputusan dicatat {{ $visit->reviewed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') }} WIB.</small>
            @endif
        </section>
    </section>

    <div class="fv-actions">
        <a class="adm-primary-button" href="{{ route('family.visits.create', ['resident' => $resident->id]) }}">Ajukan jadwal baru</a>
        @can('cancel', $visit)
            <form method="post" action="{{ route('family.visits.cancel', $visit->id) }}" data-confirm="Batalkan pengajuan kunjungan ini?">
                @csrf
                @method('PATCH')
                <button class="fv-btn fv-btn-danger" type="submit">Batalkan pengajuan</button>
            </form>
        @endcan
        <a class="adm-secondary-button" href="{{ route('family.visits.index', ['resident' => $resident->id]) }}">Riwayat pengajuan</a>
    </div>
    @include('modules.family-visits.partials.guidance')
@endsection
