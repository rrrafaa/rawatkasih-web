<section class="fv-resident-context" aria-label="Lansia terkait">
    @if ($showDemoPortrait ?? false)
        <img class="fv-resident-photo"
             src="{{ route('family.demo-media', ['asset' => 'sulastri', 'resident' => $resident->id], absolute: false) }}"
             alt="Foto ilustrasi Ibu Sulastri" width="56" height="56">
    @else
        <span class="fv-avatar" aria-hidden="true">{{ mb_substr($resident->full_name, 0, 1) }}</span>
    @endif
    <div>
        <h2>{{ $resident->full_name }}</h2>
        <p>{{ $resident->room ?: 'Kamar belum diisi' }} · {{ config('family_visits.facility_name') }} · Relasi terverifikasi</p>
    </div>
</section>
