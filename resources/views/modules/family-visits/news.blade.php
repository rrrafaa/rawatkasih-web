@extends('modules.family-visits.layout', ['pageTitle' => 'Kabar Harian', 'pageDescription' => 'Cerita hari-hari Eyang dan aktivitas yang dibagikan panti.', 'breadcrumb' => 'Kabar Harian'])

@section('portal-content')
    @if (!$resident)
        @include('modules.family-visits.partials.unlinked')
    @else
        @include('modules.family-visits.partials.resident', ['showDemoPortrait' => $demoPortrait])
        @include('modules.family-visits.partials.demo-notice')

        <form method="get" action="{{ route('family.news') }}" class="adm-toolbar fv-filter-bar">
            <input type="hidden" name="resident" value="{{ $resident->id }}">
            <div class="fv-filter-field">
                <label class="adm-form-label" for="news-date">Tanggal kabar</label>
                <select class="adm-filter" name="date" id="news-date">
                    <option value="">Semua tanggal</option>
                    @if (request('date') && !isset($dates[request('date')]))
                        <option value="{{ request('date') }}" selected>{{ request('date') }}</option>
                    @endif
                    @foreach ($dates as $date => $label)
                        <option value="{{ $date }}" @selected(request('date') === $date)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fv-filter-field">
                <label class="adm-form-label" for="news-category">Kategori</label>
                <select class="adm-filter" name="category" id="news-category">
                    @foreach (['semua' => 'Semua kategori', 'aktivitas' => 'Aktivitas', 'kebersamaan' => 'Kebersamaan'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('category', 'semua') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fv-actions">
                <button class="adm-primary-button" type="submit">Terapkan filter</button>
                <a class="adm-secondary-button" href="{{ route('family.news', ['resident' => $resident->id]) }}">Reset filter</a>
            </div>
        </form>

        @if ($stories)
            <section class="fv-news-grid" aria-label="Daftar kabar harian">
                @foreach ($stories as $story)
                    <article class="fv-card fv-news-card">
                        @if ($story['media'])
                            <img src="{{ $story['is_demo'] ? route('family.demo-media', ['asset' => $story['media'], 'resident' => $resident->id]) : $story['media'] }}"
                                 alt="{{ ($story['is_demo'] ? 'Ilustrasi: ' : 'Dokumentasi: ').$story['title'] }}" loading="lazy">
                        @endif
                        <div class="fv-news-card-body">
                            <p class="fv-story-meta">{{ ucfirst($story['category']) }} · <time datetime="{{ $story['publishedAt']->toIso8601String() }}">{{ $story['publishedAt']->translatedFormat('d F Y, H.i') }} WIB</time></p>
                            <h2>{{ $story['title'] }}</h2>
                            <details>
                                <summary aria-label="Lihat kabar {{ $story['title'] }}">Lihat kabar</summary>
                                <p>{{ $story['summary'] }}</p>
                                <small>{{ $story['is_demo'] ? 'Contoh penerbit' : 'Penerbit' }}: {{ $story['author'] }}</small>
                            </details>
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <section class="fv-card">
                <h2>Belum ada kabar untuk pilihan ini</h2>
                <p>Coba tanggal atau kategori lain.</p>
            </section>
        @endif
    @endif
@endsection
