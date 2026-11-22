@extends('modules.family-visits.admin.layout')

@section('visit-content')
    <a class="adm-visit-link" href="{{ route('admin.visits.index') }}">← Kembali ke daftar pengajuan</a>
    <header class="adm-page-heading">
        <div>
            <h1 class="adm-page-title">Detail Pengajuan Kunjungan</h1>
            <p>{{ $visit->resident?->full_name ?? 'Lansia' }}</p>
        </div>
        <x-visit-status :status="$visit->status"/>
    </header>

    @php
        $start = $visit->requested_at->timezone('Asia/Jakarta')->locale('id');
        $end = $visit->requested_end_at?->timezone('Asia/Jakarta');
        $expired = $start->lessThanOrEqualTo(now());
    @endphp
    <div class="adm-visit-columns">
        <section class="adm-visit-card" aria-labelledby="admin-visit-details">
            <h2 id="admin-visit-details">Rincian kunjungan</h2>
            <dl class="adm-visit-facts">
                <div>
                    <dt>Nama pengaju</dt>
                    <dd class="fv-requester-avatar">
                        <img class="fv-account-photo" src="{{ route('admin.visits.avatar', $visit->id) }}" alt="" width="36" height="36">
                        {{ $visit->requester?->full_name ?? 'Keluarga' }}
                    </dd>
                </div>
                <div><dt>Tanggal</dt><dd>{{ $start->translatedFormat('l, d F Y') }}</dd></div>
                <div><dt>Waktu</dt><dd>{{ $start->format('H.i') }}{{ $end ? '–'.$end->format('H.i') : '' }} WIB</dd></div>
                <div><dt>Jumlah pengunjung</dt><dd>{{ $visit->visitor_count }} orang</dd></div>
                <div><dt>Pengunjung utama</dt><dd>{{ $visit->primary_visitor_name ?: ($visit->requester?->full_name ?? 'Keluarga') }}</dd></div>
                <div><dt>Catatan keluarga</dt><dd>{{ $visit->note ?: 'Tidak ada catatan tambahan.' }}</dd></div>
            </dl>
        </section>

        <section class="adm-visit-card" aria-labelledby="admin-visit-decision">
            <h2 id="admin-visit-decision">Keputusan admin</h2>
            @can('review', $visit)
                @if ($expired)
                    <p class="adm-visit-warning">Jadwal sudah lewat. Pengajuan ini hanya bisa ditolak.</p>
                @endif
                @error('visit')
                    <p class="adm-form-error" role="alert">{{ $message }}</p>
                @enderror
                <form method="post" action="{{ route('admin.visits.update', $visit->id) }}"
                      class="adm-visit-decision" data-review-form>
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="adm-form-label" for="decision">Keputusan</label>
                        <select id="decision" name="decision" class="adm-form-control fv-select" required
                                @error('decision') aria-invalid="true" aria-describedby="decision-error" @enderror>
                            <option value="">Pilih keputusan</option>
                            <option value="approved" @disabled($expired) @selected(!$expired && old('decision') === 'approved')>Setujui</option>
                            <option value="rejected" @selected(old('decision') === 'rejected')>Tolak</option>
                        </select>
                        @error('decision') <p class="adm-form-error" id="decision-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="adm-form-label" for="review_note">Catatan keputusan</label>
                        <textarea id="review_note" name="review_note" class="adm-form-control" rows="4" maxlength="1000"
                                  aria-describedby="review-note-help{{ $errors->has('review_note') ? ' review-note-error' : '' }}"
                                  @error('review_note') aria-invalid="true" @enderror>{{ old('review_note') }}</textarea>
                        <small id="review-note-help" class="adm-visit-muted">Opsional saat menyetujui. Alasan wajib diisi saat menolak.</small>
                        @error('review_note') <p class="adm-form-error" id="review-note-error">{{ $message }}</p> @enderror
                    </div>
                    <div><button type="submit" class="adm-primary-button">Simpan keputusan</button></div>
                </form>
                <dialog class="adm-visit-dialog" id="review-confirmation" aria-labelledby="review-confirmation-title"
                        aria-describedby="review-confirmation-description">
                    <form method="dialog">
                        <span class="adm-visit-dialog-icon" aria-hidden="true">!</span>
                        <button type="submit" value="cancel" class="adm-visit-dialog-close" aria-label="Tutup dialog">×</button>
                        <h2 id="review-confirmation-title">Konfirmasi keputusan</h2>
                        <p id="review-confirmation-description">Keputusan yang sudah disimpan tidak dapat diubah.</p>
                        <div class="adm-visit-dialog-actions">
                            <button type="submit" value="cancel" class="adm-secondary-button" autofocus>Batal</button>
                            <button type="submit" value="confirm" class="adm-visit-dialog-confirm">Simpan keputusan</button>
                        </div>
                    </form>
                </dialog>
            @else
                @if ($visit->reviewed_at)
                    <dl class="adm-visit-facts">
                        <div><dt>Ditinjau oleh</dt><dd>{{ $visit->reviewer?->full_name ?? $visit->reviewed_by ?? 'Admin' }}</dd></div>
                        <div><dt>Waktu keputusan</dt><dd>{{ $visit->reviewed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') }} WIB</dd></div>
                        <div><dt>Catatan keputusan</dt><dd>{{ $visit->review_note ?: 'Tidak ada catatan.' }}</dd></div>
                    </dl>
                @else
                    <p>Pengajuan berstatus {{ strtolower($visit->status->label()) }}.</p>
                    @if ($visit->review_note)
                        <p>{{ $visit->review_note }}</p>
                    @endif
                @endif
            @endcan
        </section>
    </div>
@endsection
