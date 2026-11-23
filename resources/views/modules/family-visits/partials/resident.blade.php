<section class="fv-resident-context" aria-label="Lansia terkait">
    @if ($realPortrait ?? false)
        <img class="fv-resident-photo" src="{{ route('family.resident-photo', ['resident' => $resident->id], absolute: false) }}"
             alt="Foto {{ $resident->full_name }}" width="56" height="56">
    @elseif ($demoPortrait ?? false)
        <img class="fv-resident-photo"
             src="{{ route('family.demo-media', ['asset' => 'sulastri', 'resident' => $resident->id], absolute: false) }}"
             alt="Foto ilustrasi {{ $resident->full_name }}" width="56" height="56">
    @else
        <span class="fv-avatar" aria-hidden="true">{{ mb_substr($resident->full_name, 0, 1) }}</span>
    @endif
    <div>
        <h2>{{ $resident->full_name }}</h2>
        <p>{{ $resident->room ?: 'Kamar belum diisi' }} · {{ config('family_visits.facility_name') }} · Relasi terverifikasi</p>
    </div>
</section>
