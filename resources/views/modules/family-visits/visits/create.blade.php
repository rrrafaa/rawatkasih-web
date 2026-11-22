@extends('modules.family-visits.layout', ['pageTitle' => 'Ajukan Kunjungan', 'pageDescription' => 'Rencanakan kunjungan keluarga. Jadwal akan ditinjau oleh admin panti.', 'breadcrumb' => 'Kunjungan'])

@section('portal-content')
    @include('modules.family-visits.partials.resident')

    <div class="fv-form-columns">
        <form class="fv-card fv-form" method="post" action="{{ route('family.visits.store') }}" data-visit-form>
            @csrf
            <input type="hidden" name="resident_id" value="{{ $resident->id }}">
            <input type="hidden" name="submission_key" value="{{ old('submission_key', $submissionKey) }}">
            @if ($errors->any())
                <div class="fv-alert fv-alert-error" role="alert">Pengajuan belum tersimpan. Periksa isian yang ditandai.</div>
            @endif

            <fieldset>
                <legend>Tanggal dan waktu</legend>
                <div>
                    <label class="adm-form-label" for="visit_date">Tanggal kunjungan</label>
                    <input class="adm-form-control" id="visit_date" name="visit_date" type="date"
                           min="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required value="{{ old('visit_date') }}"
                           @error('visit_date') aria-invalid="true" aria-describedby="visit_date-error" @enderror>
                    @error('visit_date') <p class="adm-form-error" id="visit_date-error">{{ $message }}</p> @enderror
                </div>
                <div class="fv-time-grid">
                    <div>
                        <label class="adm-form-label" for="start_time">Waktu mulai (WIB)</label>
                        <input class="adm-form-control" id="start_time" name="start_time" type="time"
                               required value="{{ old('start_time') }}"
                               @error('start_time') aria-invalid="true" aria-describedby="start_time-error" @enderror>
                        @error('start_time') <p class="adm-form-error" id="start_time-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="adm-form-label" for="end_time">Waktu selesai (WIB)</label>
                        <input class="adm-form-control" id="end_time" name="end_time" type="time"
                               required value="{{ old('end_time') }}" aria-describedby="visit-time-hint"
                               @error('end_time') aria-invalid="true" @enderror>
                        <p class="@error('end_time') adm-form-error @else fv-form-hint @enderror"
                           id="visit-time-hint" aria-live="polite">@error('end_time'){{ $message }}@else Waktu selesai harus setelah waktu mulai pada hari yang sama. @enderror</p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Data pengunjung</legend>
                <div>
                    <label class="adm-form-label" for="visitor_count">Jumlah pengunjung</label>
                    <input class="adm-form-control" id="visitor_count" name="visitor_count" type="number"
                           min="1" max="20" required value="{{ old('visitor_count', 1) }}"
                           @error('visitor_count') aria-invalid="true" aria-describedby="visitor_count-error" @enderror>
                    @error('visitor_count') <p class="adm-form-error" id="visitor_count-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="adm-form-label" for="primary_visitor_name">Nama pengunjung utama</label>
                    <input class="adm-form-control" id="primary_visitor_name" name="primary_visitor_name"
                           maxlength="120" autocomplete="name" required value="{{ old('primary_visitor_name', $user->name()) }}"
                           @error('primary_visitor_name') aria-invalid="true" aria-describedby="primary_visitor_name-error" @enderror>
                    @error('primary_visitor_name') <p class="adm-form-error" id="primary_visitor_name-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="adm-form-label" for="note">Catatan tambahan (opsional)</label>
                    <textarea class="adm-form-control" id="note" name="note" maxlength="1000" rows="3"
                              @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ old('note') }}</textarea>
                    @error('note') <p class="adm-form-error" id="note-error">{{ $message }}</p> @enderror
                </div>
            </fieldset>
            @foreach (['resident_id', 'submission_key'] as $hidden)
                @error($hidden) <p class="adm-form-error" role="alert">{{ $message }}</p> @enderror
            @endforeach
            <div class="fv-actions fv-form-actions">
                <a class="adm-secondary-button" href="{{ route('family.visits.index', ['resident' => $resident->id]) }}">Batal</a>
                <button class="adm-primary-button" type="submit">Simpan pengajuan</button>
            </div>
        </form>
        <aside class="fv-card fv-form" aria-labelledby="summary-title">
            <h2 id="summary-title">Ringkasan pengajuan</h2>
            <dl class="fv-account-facts">
                <div><dt>Lansia</dt><dd>{{ $resident->full_name }}</dd></div>
                <div><dt>Tanggal</dt><dd data-summary-date>Pilih tanggal kunjungan</dd></div>
                <div><dt>Waktu</dt><dd data-summary-time>Waktu belum dipilih</dd></div>
                <div><dt>Pengunjung</dt><dd><span data-summary-visitors>{{ old('visitor_count', 1) }} pengunjung</span> · <span data-summary-name>{{ old('primary_visitor_name', $user->name()) }}</span></dd></div>
            </dl>
            <p>Jadwal berlaku setelah disetujui admin.</p>
        </aside>
    </div>
@endsection
