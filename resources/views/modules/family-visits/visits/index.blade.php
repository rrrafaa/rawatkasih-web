@extends('modules.family-visits.layout', ['pageTitle' => $visits?->total() ? 'Kunjungan keluarga' : 'Waktu bersama, lebih bermakna', 'pageDescription' => 'Rencanakan kunjungan dan lihat perkembangan pengajuan keluarga.', 'breadcrumb' => 'Kunjungan'])
@section('portal-content')
@if (!$resident) @include('modules.family-visits.partials.unlinked')
@else
    @include('modules.family-visits.partials.resident')
    @if($visits->total() === 0 && !request('status'))
        <section class="fv-empty"><h2>Momen hangat berikutnya<br>dimulai dari sini.</h2><p>Belum ada pengajuan kunjungan. Pilih tanggal dan waktu yang nyaman untuk keluarga; admin akan meninjau permintaan Anda.</p><a class="fv-btn" href="{{ route('family.visits.create', ['resident' => $resident->id]) }}">Ajukan kunjungan</a></section>
    @else
        <div class="fv-list-heading"><h2>Pengajuan dan riwayat</h2><a class="fv-btn" href="{{ route('family.visits.create', ['resident' => $resident->id]) }}">Ajukan jadwal baru</a></div>
        <form method="get" class="fv-news-filter"><input type="hidden" name="resident" value="{{ $resident->id }}"><label for="visit-status">Status pengajuan</label><select name="status" id="visit-status"><option value="">Semua status</option>@foreach(\App\Enums\VisitStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select><button class="fv-btn fv-btn-secondary" type="submit">Tampilkan</button></form>
        <section class="fv-card"><h2>Riwayat permintaan kunjungan</h2>@forelse($visits as $item)<a class="fv-visit-row" href="{{ route('family.visits.show', $item->id) }}"><div><strong>{{ $item->requested_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</strong><p>{{ $item->requested_at->timezone('Asia/Jakarta')->format('H.i') }} WIB · {{ $item->visitor_count }} pengunjung</p>@if($item->review_note)<small>Catatan admin: {{ $item->review_note }}</small>@endif</div><x-visit-status :status="$item->status"/></a>@empty<p>Belum ada pengajuan dengan status ini.</p>@endforelse
        @include('modules.family-visits.partials.pagination', ['paginator' => $visits])</section>
    @endif
    @include('modules.family-visits.partials.guidance')
@endif
@endsection
