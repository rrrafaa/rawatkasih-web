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
                    <label class="adm-form-label" id="visit-date-label" for="visit_date">Tanggal kunjungan</label>
                    <div class="fv-date-picker">
                        <input class="adm-form-control" id="visit_date" name="visit_date" type="date"
                               min="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required value="{{ old('visit_date') }}"
                               aria-describedby="visit_date-error" @error('visit_date') aria-invalid="true" @enderror>
                        <button type="button" class="adm-form-control fv-date-trigger" hidden
                                id="visit-date-trigger" aria-labelledby="visit-date-label visit-date-value"
                                aria-describedby="visit_date-error" aria-expanded="false" aria-controls="visit-calendar">
                            <span id="visit-date-value">Pilih tanggal</span><span class="fv-chevron" aria-hidden="true"></span>
                        </button>
                        <div class="fv-calendar" id="visit-calendar" role="group" aria-label="Kalender kunjungan" hidden>
                            <div class="fv-calendar-header">
                                <button type="button" data-calendar-month="-1" aria-label="Bulan sebelumnya">‹</button>
                                <strong data-calendar-title aria-live="polite"></strong>
                                <button type="button" data-calendar-month="1" aria-label="Bulan berikutnya">›</button>
                            </div>
                            <div class="fv-calendar-grid" aria-hidden="true">
                                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                                    <span>{{ $day }}</span>
                                @endforeach
                            </div>
                            <div class="fv-calendar-grid" data-calendar-days></div>
                        </div>
                    </div>
                    <p class="adm-form-error" id="visit_date-error" aria-live="polite">{{ $errors->first('visit_date') }}</p>
                </div>
                <div class="fv-time-grid">
                    @include('modules.family-visits.partials.visit-time', ['field' => 'start_time', 'label' => 'Waktu mulai (WIB)', 'slots' => array_slice($visitSlots, 0, -1)])
                    @include('modules.family-visits.partials.visit-time', ['field' => 'end_time', 'label' => 'Waktu selesai (WIB)', 'slots' => array_slice($visitSlots, 1)])
                </div>
                <p class="fv-form-hint">Jam kunjungan 08.00–19.00 WIB. Pilih waktu selesai setelah waktu mulai.</p>
            </fieldset>

            <fieldset>
                <legend>Data pengunjung</legend>
                <div>
                    <label class="adm-form-label" for="visitor_count">Jumlah pengunjung</label>
                    <input class="adm-form-control" id="visitor_count" name="visitor_count" type="number"
                           min="1" max="20" required value="{{ old('visitor_count', 1) }}"
                           aria-describedby="visitor_count-error" @error('visitor_count') aria-invalid="true" @enderror>
                    <p class="adm-form-error" id="visitor_count-error" aria-live="polite">{{ $errors->first('visitor_count') }}</p>
                </div>
                <div>
                    <label class="adm-form-label" for="primary_visitor_name">Nama pengunjung utama</label>
                    <input class="adm-form-control" id="primary_visitor_name" name="primary_visitor_name"
                           maxlength="120" autocomplete="name" required value="{{ old('primary_visitor_name', $user->name()) }}"
                           aria-describedby="primary_visitor_name-error" @error('primary_visitor_name') aria-invalid="true" @enderror>
                    <p class="adm-form-error" id="primary_visitor_name-error" aria-live="polite">{{ $errors->first('primary_visitor_name') }}</p>
                </div>
                <div>
                    <label class="adm-form-label" for="note">Catatan tambahan (opsional)</label>
                    <textarea class="adm-form-control" id="note" name="note" maxlength="1000" rows="3"
                              aria-describedby="note-error" @error('note') aria-invalid="true" @enderror>{{ old('note') }}</textarea>
                    <p class="adm-form-error" id="note-error" aria-live="polite">{{ $errors->first('note') }}</p>
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
