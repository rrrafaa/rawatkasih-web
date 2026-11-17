@extends('modules.family-visits.layout', ['pageTitle' => 'Cerita hari-hari Eyang', 'pageDescription' => 'Ikuti aktivitas dan momen kebersamaan yang dibagikan panti.', 'breadcrumb' => 'Kabar Harian'])
@section('portal-content')
@if (!$resident) @include('modules.family-visits.partials.unlinked')
@else
    @include('modules.family-visits.partials.resident')
    @include('modules.family-visits.partials.demo-notice')
    <form method="get" class="fv-news-filter"><input type="hidden" name="resident" value="{{ $resident->id }}"><div><label for="news-date">Tanggal kabar</label><input type="date" name="date" id="news-date" value="{{ request('date') }}"></div><div><label for="news-category">Kategori</label><select name="category" id="news-category">@foreach(['semua' => 'Semua kabar', 'aktivitas' => 'Aktivitas', 'kebersamaan' => 'Kebersamaan'] as $value => $label)<option value="{{ $value }}" @selected(request('category', 'semua') === $value)>{{ $label }}</option>@endforeach</select></div><button class="fv-btn" type="submit">Tampilkan kabar</button><a class="fv-btn fv-btn-secondary" href="{{ route('family.news', ['resident' => $resident->id]) }}">Reset filter</a></form>
    @forelse($stories as $story)
        <article class="fv-card fv-story-row">
            @if($story['media'])<img src="{{ $story['is_demo'] ? route('family.demo-media', ['asset' => $story['media'], 'resident' => $resident->id]) : $story['media'] }}" alt="{{ $story['is_demo'] ? 'Ilustrasi kabar contoh: ' : 'Dokumentasi kabar: ' }}{{ $story['title'] }}">@endif
            <div><p class="fv-eyebrow">{{ \Carbon\CarbonImmutable::parse($story['published_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('H.i').' WIB · '.\Carbon\CarbonImmutable::parse($story['published_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y') }}</p><h2>{{ $story['title'] }}</h2><p>{{ $story['summary'] }}</p><small>{{ $story['is_demo'] ? 'Contoh penerbit' : 'Penerbit' }}: {{ $story['author'] }}</small></div>
        </article>
    @empty<section class="fv-card"><h2>Belum ada kabar untuk pilihan ini</h2><p>Coba tanggal atau kategori lain.</p></section>@endforelse
@endif
@endsection
