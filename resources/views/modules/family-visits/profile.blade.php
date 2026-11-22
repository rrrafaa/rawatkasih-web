@extends('modules.family-visits.layout', ['pageTitle' => 'Profil Akun', 'pageDescription' => 'Kelola informasi kontak dan lihat hubungan keluarga yang terverifikasi.', 'breadcrumb' => 'Profil Akun'])

@section('portal-content')
    <section class="fv-card fv-profile-overview">
        <span class="fv-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name(), 0, 1)) }}</span>
        <div>
            <h2>{{ $user->name() }}</h2>
            <p>Anggota keluarga</p>
        </div>
    </section>

    <div class="fv-profile-grid">
        <form class="fv-card fv-form" method="post"
              action="{{ route('family.profile.update', $resident ? ['resident' => $resident->id] : []) }}">
            @csrf
            @method('PATCH')
            <h2>Informasi akun</h2>
            <div>
                <label class="adm-form-label" for="full_name">Nama lengkap</label>
                <input class="adm-form-control" id="full_name" name="full_name" maxlength="120" autocomplete="name" required
                       value="{{ old('full_name', $account['full_name'] ?? $user->name()) }}"
                       @error('full_name') aria-invalid="true" aria-describedby="full-name-error" @enderror>
                @error('full_name') <p class="adm-form-error" id="full-name-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="adm-form-label" for="phone">Nomor telepon (opsional)</label>
                <input class="adm-form-control" id="phone" name="phone" type="tel" maxlength="32" autocomplete="tel"
                       value="{{ old('phone', $account['phone'] ?? '') }}"
                       @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                @error('phone') <p class="adm-form-error" id="phone-error">{{ $message }}</p> @enderror
            </div>
            <dl class="fv-account-facts">
                <div><dt>Email login</dt><dd>{{ $account['email'] ?? $user->email }}</dd></div>
                <div><dt>Peran</dt><dd>Keluarga</dd></div>
            </dl>
            <div class="fv-actions">
                <button class="adm-primary-button" type="submit">Simpan perubahan</button>
            </div>
        </form>

        <section class="fv-card fv-relationship">
            <h2>Hubungan keluarga</h2>
            @if ($resident)
                <dl class="fv-account-facts">
                    <div><dt>Lansia</dt><dd>{{ $resident->full_name }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $resident->room ?: 'Kamar belum diisi' }} · {{ config('family_visits.facility_name') }}</dd></div>
                    <div><dt>Hubungan</dt><dd>{{ $resident->familyLink->relationship }}</dd></div>
                </dl>
                <span class="fv-badge fv-badge-success">Terverifikasi</span>
            @else
                <p>Akun ini sedang menunggu verifikasi hubungan keluarga dari admin panti.</p>
            @endif
        </section>
    </div>
@endsection
