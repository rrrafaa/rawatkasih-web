@extends('modules.family-visits.layout', ['pageTitle' => 'Kunjungan keluarga', 'pageDescription' => 'Lihat perkembangan pengajuan dan rencana waktu bersama.', 'breadcrumb' => 'Kunjungan'])
@section('portal-content')
@include('modules.family-visits.partials.resident')
<section class="fv-request-card {{ $visit->status === \App\Enums\VisitStatus::Approved ? 'fv-mint' : 'fv-peach' }}">
    <x-visit-status :status="$visit->status"/>
    <h2>@switch($visit->status) @case(\App\Enums\VisitStatus::Pending) Pengajuanmu sudah diterima, {{ explode(' ', $user->name())[0] }}. @break @case(\App\Enums\VisitStatus::Approved) Waktu bersama keluarga telah disetujui. @break @case(\App\Enums\VisitStatus::Rejected) Pengajuan kunjungan belum dapat disetujui. @break @default Pengajuan kunjungan telah dibatalkan. @endswitch</h2>
    <p>{{ $visit->status === \App\Enums\VisitStatus::Pending ? 'Admin akan meninjau permintaan dan memberikan keputusan.' : 'Status ini tersimpan pada permintaan kunjungan Anda.' }}</p>
    <h3>{{ $visit->requested_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</h3>
    <p>Usulan {{ $visit->requested_at->timezone('Asia/Jakarta')->format('H.i') }}{{ $visit->requested_end_at ? '–'.$visit->requested_end_at->timezone('Asia/Jakarta')->format('H.i') : '' }} WIB · {{ $visit->visitor_count }} pengunjung<br>{{ $resident->full_name }} · Pengunjung utama: {{ $visit->primary_visitor_name ?: $user->name() }}</p>
    @if($visit->note)<p>Catatan keluarga: {{ $visit->note }}</p>@endif
    <ol class="fv-progress"><li class="is-complete">✓ Pengajuan diterima</li><li @class(['is-complete' => $visit->status !== \App\Enums\VisitStatus::Pending])>Ditinjau admin</li><li>Keputusan jadwal</li></ol>
    @if($visit->review_note)<div class="fv-review-note"><strong>Catatan admin</strong><p>{{ $visit->review_note }}</p></div>@endif
    @if($visit->reviewed_at)<small>Keputusan dicatat {{ $visit->reviewed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') }} WIB.</small>@endif
</section>
<div class="fv-actions"><a class="fv-btn" href="{{ route('family.visits.create', ['resident' => $resident->id]) }}">Ajukan jadwal baru</a>@can('cancel', $visit)<form method="post" action="{{ route('family.visits.cancel', $visit->id) }}" data-confirm="Batalkan pengajuan kunjungan ini?">@csrf @method('PATCH')<button class="fv-btn fv-btn-danger" type="submit">Batalkan pengajuan</button></form>@endcan<a class="fv-btn fv-btn-secondary" href="{{ route('family.visits.index', ['resident' => $resident->id]) }}">Riwayat pengajuan</a></div>
@include('modules.family-visits.partials.guidance')
@endsection
