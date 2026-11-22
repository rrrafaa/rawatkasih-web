@extends('modules.family-visits.layout', ['pageTitle' => 'Selamat datang kembali, '.explode(' ', $user->name())[0], 'pageDescription' => 'Ada cerita hangat dari Eyang untuk keluarga hari ini.', 'breadcrumb' => 'Beranda'])
@section('portal-content')
@if (!$resident) @include('modules.family-visits.partials.unlinked')
@else
    <section class="fv-spotlight"><span class="fv-avatar fv-avatar-spotlight" aria-hidden="true">{{ mb_substr($resident->full_name, 0, 1) }}</span><div class="fv-spotlight-text"><p class="fv-eyebrow">ORANG TERSAYANG</p><h2>{{ $resident->full_name }}</h2><p>{{ $resident->birth_date ? $resident->birth_date->age.' tahun · ' : '' }}{{ $resident->room ?: 'Kamar belum diisi' }}</p><p>Terhubung dengan keluarga terverifikasi</p></div><a class="adm-primary-button" href="{{ route('family.news', ['resident' => $resident->id]) }}">Lihat kabar Eyang</a></section>
    @include('modules.family-visits.partials.demo-notice')
    @php($latest = $stories[0] ?? null)
    @php($hasDemo = count(array_filter($stories, fn ($story) => $story['is_demo'])) > 0)
    @php($latestVisit = $visits?->first())
    <section class="fv-summary-grid" aria-label="Ringkasan keluarga">
        <div class="fv-summary"><p>Kabar terbaru</p><strong>{{ $latest ? \Carbon\CarbonImmutable::parse($latest['published_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M') : 'Belum ada' }}</strong><small>{{ $latest ? ($latest['is_demo'] ? 'Contoh pembaruan dari panti' : 'Pembaruan dari panti') : 'Menunggu kabar panti' }}</small></div>
        <div class="fv-summary"><p>{{ $hasDemo ? 'Kabar contoh' : 'Kabar tersedia' }}</p><strong>{{ count($stories) }} cerita</strong><small>{{ $hasDemo ? 'Data dummy modul kabar' : 'Kabar terpublikasi' }}</small></div>
        <div class="fv-summary"><p>{{ $hasDemo ? 'Foto contoh' : 'Foto kabar' }}</p><strong>{{ count(array_filter($stories, fn ($s) => $s['media'] !== null)) }} momen</strong><small>Ditampilkan dengan izin aktif</small></div>
        <div class="fv-summary"><p>Kunjungan</p><strong>{{ $latestVisit?->status->label() ?? 'Belum ada' }}</strong><small>Status pengajuan terbaru</small></div>
    </section>
    <div class="fv-home-columns">
        <section class="fv-card fv-latest-story">
            @if($latest)
                @if($latest['media'])<img class="fv-latest-photo" src="{{ $latest['is_demo'] ? route('family.demo-media', ['asset' => $latest['media'], 'resident' => $resident->id]) : $latest['media'] }}" alt="{{ $latest['is_demo'] ? 'Ilustrasi kabar contoh: ' : 'Dokumentasi kabar: ' }}{{ $latest['title'] }}">@endif
                <p class="fv-eyebrow">{{ $latest['is_demo'] ? 'KABAR CONTOH' : 'KABAR KELUARGA' }} / {{ \Carbon\CarbonImmutable::parse($latest['published_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M · H.i') }} WIB</p><h2>{{ $latest['title'] }}</h2><p>{{ $latest['summary'] }}</p>
            @else<h2>Menunggu kabar keluarga</h2><p>Kabar yang dibagikan panti akan tersedia di sini.</p>@endif
            <a class="adm-secondary-button" href="{{ route('family.news', ['resident' => $resident->id]) }}">Baca kabar harian</a>
        </section>
        <div class="fv-home-aside"><section class="fv-card"><h2>Waktu bersama keluarga</h2>
            @if($latestVisit)<x-visit-status :status="$latestVisit->status"/><h3>{{ $latestVisit->requested_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y') }}</h3><p>Usulan {{ $latestVisit->requested_at->timezone('Asia/Jakarta')->format('H.i') }}{{ $latestVisit->requested_end_at ? '–'.$latestVisit->requested_end_at->timezone('Asia/Jakarta')->format('H.i') : '' }} WIB<br>{{ $latestVisit->visitor_count }} pengunjung</p><a class="adm-secondary-button" href="{{ route('family.visits.show', $latestVisit->id) }}">Lihat pengajuan</a>
            @else<p>Belum ada pengajuan kunjungan. Rencanakan waktu bersama orang tersayang.</p><a class="adm-secondary-button" href="{{ route('family.visits.create', ['resident' => $resident->id]) }}">Ajukan kunjungan</a>@endif
        </section></div>
    </div>
@endif
@endsection
