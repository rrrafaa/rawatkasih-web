@extends('layouts.admin')

@section('title', 'Audit Log — RawatKasih')

@section('admin-content')
    <header class="adm-page-heading">
        <div>
            <h1 class="adm-page-title">Audit Log Insiden</h1>
            <p>Riwayat perubahan untuk insiden: {{ $incident['resident'] ?? 'Lansia' }}</p>
        </div>
    </header>

    @if (session('status'))
        <div class="rk-care-alert" role="status">{{ session('status') }}</div>
    @endif

    @if(!empty($auditLogs))
        <section class="adm-section">
            <h2>Aktivitas Perubahan</h2>

            <div class="adm-audit-log">
                @foreach ($auditLogs as $log)
                    <div class="adm-audit-entry">
                        <div class="adm-audit-header">
                            <strong class="adm-audit-action">{{ $log['action'] ?? 'Tidak diketahui' }}</strong>
                            <span class="adm-audit-time">{{ \Carbon\Carbon::parse($log['occurred_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y H.i') }} WIB</span>
                        </div>
                        <div class="adm-audit-body">
                            <p class="adm-audit-actor">Diubah oleh: <strong>{{ $log['actor_name'] ?? 'Admin' }}</strong></p>
                            @if(!empty($log['changes']))
                                <div class="adm-audit-changes">
                                    @foreach ($log['changes'] as $field => $change)
                                        <div class="adm-audit-change">
                                            <span class="adm-audit-field">{{ $field }}</span>
                                            <span class="adm-audit-diff">{{ $change['from'] ?? '-' }} → {{ $change['to'] ?? '-' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <section class="adm-section">
            <div class="adm-empty">
                <h3>Tidak ada catatan audit</h3>
                <p>Belum ada perubahan yang dicatat untuk insiden ini.</p>
            </div>
        </section>
    @endif

    <div style="margin-top: 24px;">
        <a href="{{ route('incidents.admin.show', $incident['id'] ?? '') }}" class="adm-secondary-button">
            ← Kembali ke Detail Insiden
        </a>
    </div>
@endsection
@endsection

@push('admin-page-styles')
<style>
.adm-audit-log {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
}
.adm-audit-entry {
    border-left: 4px solid #005338;
    background: white;
    padding: 12px 16px;
    margin-bottom: 12px;
    border-radius: 0 8px 8px 0;
}
.adm-audit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.adm-audit-action {
    color: #1b2420;
    font-size: 14px;
    font-weight: 600;
}
.adm-audit-time {
    color: #6b7280;
    font-size: 12px;
}
.adm-audit-actor {
    color: #555;
    font-size: 13px;
    margin: 0;
}
.adm-audit-changes {
    margin-top: 8px;
}
.adm-audit-change {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 13px;
}
.adm-audit-field {
    color: #6b7280;
}
.adm-audit-diff {
    color: #1b2420;
    font-weight: 500;
}
</style>
@endpush