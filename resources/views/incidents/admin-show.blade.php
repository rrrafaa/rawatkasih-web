@extends('layouts.caregiver')

@section('title', 'Detail Insiden — RawatKasih')

@section('workspace')
    {{-- Header halaman --}}
    <section class="rk-care-page rk-stitch-page">
        <header class="rk-today-header">
            <div class="rk-today-title">
                <h1>Detail Insiden</h1>
                <time datetime="{{ now('Asia/Jakarta')->toDateString() }}">{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F') }}</time>
            </div>
        </header>

        @if (session('status'))
            <div class="rk-care-alert" role="status">{{ session('status') }}</div>
        @endif

        {{-- Banner Peringatan SOP Darurat --}}
        <div class="rk-care-alert" role="status" aria-live="polite">
            <strong>Peringatan:</strong> Halaman ini bukan panggilan darurat otomatis. Jika situasi darurat, <strong>admin harus langsung melakukan tindakan sesuai SOP panti dan menghubungi petugas/tenaga medis terkini secara langsung.</div>

        {{-- Card Detail Insiden --}}
        <section class="rk-care-card" aria-labelledby="incident-details-heading">
            <h2 id="incident-details-heading">Rincian Insiden</h2>

            {{-- Pastikan incident data exists --}}
            @if(empty($incident))
                <div class="rk-care-alert rk-care-alert-error" role="alert">
                    Data insiden tidak ditemukan.
                </div>
                <a href="{{ route('admin.incidents.index') }}" class="rk-care-btn rk-care-btn-secondary" style="text-decoration: none; display: inline-block;">
                    ← Kembali ke Daftar
                </a>
                @return
            @endif

            @php
                $residentName = $incident['resident'] ?? ($incident['resident_id'] ?? 'Lansia');
                $category = $incident['category'] ?? 'medical';
                $occurredAt = $incident['occurred_at'] ?? now('Asia/Jakarta')->toIso8601String();
                $description = $incident['description'] ?? 'Tidak ada kronologi';
                $status = $incident['status'] ?? 'open';
                $initialAction = $incident['initial_action'] ?? null;
                $followUp = $incident['follow_up'] ?? null;
                $closedBy = $incident['closed_by'] ?? null;
                $closedAt = $incident['closed_at'] ?? null;
                $reporterName = $incident['reporter_name'] ?? null;
            @endphp

            <dl class="rk-care-facts" style="display: grid; gap: 12px; margin-bottom: 24px;">
                <div>
                    <dt style="color: #68776f; font-size: 12px;">Lansia</dt>
                    <dd style="font-weight: 600; color: #1b2420;">{{ $residentName }}</dd>
                </div>

                <div>
                    <dt style="color: #68776f; font-size: 12px;">Kategori</dt>
                    <dd style="font-weight: 600;">
                        <span class="rk-stitch-status rk-result-{{ $category }}">
                            {{ $statusLabels[$category] ?? $category }}</span>
                    </dd>
                </div>

                <div>
                    <dt style="color: #68776f; font-size: 12px;">Waktu Kejadian</dt>
                    <dd style="font-weight: 600;">
                        {{ \Carbon\Carbon::parse($occurredAt)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y H.i') }} WIB
                    </dd>
                </div>

                <div>
                    <dt style="color: #68776f; font-size: 12px;">Dilaporkan oleh</dt>
                    <dd style="font-weight: 600;">{{ $reporterName ?? '-' }}</dd>
                </div>

                <div>
                    <dt style="color: #68776f; font-size: 12px;">Status</dt>
                    <dd style="font-weight: 600;">
                        <span class="rk-stitch-status rk-result-{{ $status }}">
                            {{ $statusLabels[$status] ?? $status }}</span>
                    </dd>
                </div>
            </dl>

            <div style="margin-top: 16px;">
                <h3 style="font-size: 16px; color: #1b2420; margin: 0 0 8px;">Kronologi</h3>
                <p style="color: #555; font-size: 14px; line-height: 1.6; background: #f7f8f7; padding: 16px; border-radius: 10px;">
                    {{ $description }}
                </p>
            </div>

            @if($initialAction)
                <div style="margin-top: 16px;">
                    <h3 style="font-size: 16px; color: #1b2420; margin: 0 0 8px;">Tindakan Awal</h3>
                    <p style="color: #555; font-size: 14px; line-height: 1.6; background: #fff8ef; padding: 16px; border-radius: 10px; border-left: 4px solid #a66b1e;">
                        {{ $initialAction }}
                    </p>
                </div>
            @endif

            @if($followUp)
                <div style="margin-top: 16px;">
                    <h3 style="font-size: 16px; color: #1b2420; margin: 0 0 8px;">Tindak Lanjut</h3>
                    <p style="color: #555; font-size: 14px; line-height: 1.6;">
                        {{ $followUp }}
                    </p>
                </div>
            @endif

            @if($closedBy && $closedAt)
                <div style="margin-top: 16px;">
                    <h3 style="font-size: 16px; color: #1b2420; margin: 0 0 8px;">Yang Menutup</h3>
                    <p style="color: #555; font-size: 14px; line-height: 1.6;">
                        {{ $closedBy }} pada {{ \Carbon\Carbon::parse($closedAt)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y H.i') }} WIB
                    </p>
                </div>
            @endif

            @if(!empty($auditLogs))
                <section style="margin-top: 24px;" aria-labelledby="audit-log-heading">
                    <h2 id="audit-log-heading">Riwayat Perubahan (Audit Log)</h2>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach ($auditLogs as $log)
                            <div style="background: #f8faf8; padding: 12px; border-radius: 8px; border-left: 4px solid #005338;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong style="font-size: 14px; color: #1b2420;">{{ $log['action'] ?? 'Tidak diketahui' }}</strong>
                                    <span style="font-size: 12px; color: #68776f;">{{ \Carbon\Carbon::parse($log['occurred_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y H.i') }} WIB</span>
                                </div>
                                <p style="font-size: 13px; color: #555; margin: 0;">
                                    Diubah oleh: {{ $log['actor_name'] ?? 'Admin' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Form Ganti Status --}}
            <form method="POST" action="{{ route('admin.incidents.update', $incident['id'] ?? '') }}" style="margin-top: 24px;">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" id="status-input">

                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <label for="status-admin" style="font-size: 14px; font-weight: 500; color: #1b2420;">Ubah Status:</label>
                    <select
                        id="status-admin"
                        name="status"
                        style="width: auto; min-height: 40px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                        onchange="document.getElementById('status-input').value = this.value; this.form.submit();"
                    >
                        <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Terbuka</option>
                        <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>Sedang ditindaklanjuti</option>
                        <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Ditutup</option>
                    </select>
                </div>
            </form>
        </section>

        {{-- Action Links --}}
        <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
            <a href="{{ route('admin.incidents.index') }}" class="rk-care-btn rk-care-btn-secondary" style="text-decoration: none;">
                ← Kembali ke Daftar
            </a>
        </div>
    </section>
@endsection

@push('styles')
<style>
.rk-care-card {
    padding: 24px;
    background: white;
    border-radius: 12px;
    border: 1px solid #dce7e0;
}
.rk-care-facts dt {
    font-size: 12px;
    color: #68776f;
}
.rk-care-facts dd {
    margin: 0;
    color: #1b2420;
    font-size: 14px;
    line-height: 1.4;
}
.rk-stitch-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.rk-result-open {
    background: #fff1e2;
    color: #926018;
}
.rk-result-in_progress,
.rk-result-handling {
    background: #e5fcf0;
    color: #005338;
}
.rk-result-closed {
    background: #e8f5e9;
    color: #247850;
}
.rk-care-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 18px;
    border: none;
    border-radius: 10px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}
.rk-care-btn-primary {
    background: #005338;
    color: white;
}
.rk-care-btn-primary:hover {
    background: #003e2a;
}
.rk-care-btn-secondary {
    background: white;
    color: #68776f;
    border: 1px solid #dce7e0;
}
.rk-care-btn-secondary:hover {
    background: #f7f8f7;
}
@media (max-width: 760px) {
    .rk-care-alert {
        padding: 10px 12px;
        font-size: 13px;
    }
    .rk-care-card {
        padding: 20px 16px;
    }
}
</style>@endpush