@extends('layouts.admin')

@section('title', 'Detail Insiden — RawatKasih')

@section('admin-content')
    <a class="adm-visit-link" href="{{ route('incidents.admin.index') }}">← Kembali ke daftar</a>

    <header class="adm-page-heading">
        <div>
            <h1 class="adm-page-title">Detail Insiden</h1>
            <p>Insin ID: #{{ $incident['id'] ?? '-' }}</p>
        </div>
    </header>

    @if (session('status'))
        <div class="rk-care-alert" role="status">{{ session('status') }}</div>
    @endif

    {{-- Card Detail Insiden --}}
    <section class="adm-visit-card" aria-labelledby="incident-details-heading">
        <h2 id="incident-details-heading">Rincian Insiden</h2>

        <dl class="adm-visit-facts" style="display: grid; gap: 14px; width: 100%; margin: 0;">
            <div>
                <dt>Lansia</dt>
                <dd>{{ $incident['resident'] ?? 'Lansia' }}</dd>
            </div>

            <div>
                <dt>Kategori</dt>
                <dd>
                    <span class="rk-stitch-status rk-result-{{ $incident['category'] ?? 'medical' }}">
                        {{ $statusLabels[$incident['category'] ?? 'medical'] ?? $incident['category'] ?? 'Medis' }}
                    </span>
                </dd>
            </div>

            <div>
                <dt>Waktu Kejadian</dt>
                <dd>
                    {{ \Carbon\Carbon::parse($incident['occurred_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y H.i') }} WIB
                </dd>
            </div>

            <div>
                <dt>Dilaporkan oleh</dt>
                <dd>{{ $incident['reporter_name'] ?? $incident['reporter_id'] ?? 'Tidak diketahui' }}</dd>
            </div>

            <div>
                <dt>Status</dt>
                <dd>
                    <span class="rk-stitch-status rk-result-{{ $incident['status'] ?? 'open' }}">
                        {{ $statusLabels[$incident['status'] ?? 'open'] ?? 'Terbuka' }}
                    </span>
                </dd>
            </div>
        </dl>

        @if(!empty($incident['description']))
            <div style="margin-top: 20px;">
                <h3 style="font-size: 16px; color: #203c30; margin: 0 0 8px;">Kronologi</h3>
                <p style="color: #555; font-size: 14px; line-height: 1.6; background: #f7f8f7; padding: 16px; border-radius: 10px;">
                    {{ $incident['description'] }}
                </p>
            </div>
        @endif

        @if(!empty($incident['initial_action']))
            <div style="margin-top: 16px;">
                <h3 style="font-size: 16px; color: #203c30; margin: 0 0 8px;">Tindakan Awal</h3>
                <p style="color: #555; font-size: 14px; line-height: 1.6; background: #fff8ef; padding: 16px; border-radius: 10px; border-left: 4px solid #a66b1e;">
                    {{ $incident['initial_action'] }}
                </p>
            </div>
        @endif

        @if(!empty($incident['follow_up']))
            <div style="margin-top: 16px;">
                <h3 style="font-size: 16px; color: #203c30; margin: 0 0 8px;">Tindak Lanjut</h3>
                <p style="color: #555; font-size: 14px; line-height: 1.6;">
                    {{ $incident['follow_up'] }}
                </p>
            </div>
        @endif

        @if(!empty($incident['closed_by']))
            <div style="margin-top: 16px;">
                <h3 style="font-size: 16px; color: #203c30; margin: 0 0 8px;">Yang Menutup</h3>
                <p style="color: #555; font-size: 14px; line-height: 1.6;">
                    {{ $incident['closed_by'] }} pada {{ \Carbon\Carbon::parse($incident['closed_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y H.i') }} WIB
                </p>
            </div>
        @endif
    </section>

    {{-- Audit Log --}}
    @if (!empty($auditLogs))
        <section class="adm-visit-card" style="margin-top: 24px;" aria-labelledby="audit-log-heading">
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
    @endif@endsection