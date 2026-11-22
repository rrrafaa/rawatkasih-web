@extends('layouts.admin')

@section('title', 'Manajemen Insiden — RawatKasih')

@section('admin-content')
    <header class="adm-page-heading">
        <div>
            <h1 class="adm-page-title">Manajemen Insiden</h1>
            <p>Tinjau pelaporan insiden dari caregiver dan keluarga.</p>
        </div>
    </header>

    @if (session('status'))
        <div class="rk-care-alert" role="status">{{ session('status') }}</div>
    @endif

    {{-- Filter --}}
    <div class="adm-toolbar adm-visit-toolbar" style="margin-bottom: 20px;">
        <form method="get" action="{{ route('incidents.admin.index') }}" class="adm-visit-filter">
            <label class="adm-form-label" for="admin-status">Status</label>
            <select id="admin-status" name="status" class="adm-filter fv-select">
                <option value="">Semua status</option>
                <option value="open" @selected(request('status') === 'open')>Terbuka</option>
                <option value="handling" @selected(request('status') === 'handling')>Sedang ditindaklanjuti</option>
                <option value="closed" @selected(request('status') === 'closed')>Ditutup</option>
            </select>
            <button class="adm-secondary-button" type="submit">Tampilkan</button>
        </form>
    </div>

    {{-- Table Daftar Insiden --}}
    <div class="adm-table-wrap" tabindex="0" role="region" aria-labelledby="incident-list">
        <table class="adm-table">
            <thead>
                <tr>
                    <th scope="col">Lansia</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Waktu Kejadian</th>
                    <th scope="col">Reporter</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($incidents as $incident)
                    <tr>
                        <td class="adm-elder-name">{{ $incident['resident'] ?? 'Lansia' }}</td>
                        <td>
                            <span class="rk-stitch-status rk-result-{{ $incident['category'] ?? 'medical' }}">
                                {{ $statusLabels[$incident['category'] ?? 'medical'] ?? $incident['category'] ?? 'Medis' }}
                            </span>
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($incident['occurred_at'])->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y H.i') }} WIB
                        </td>
                        <td>{{ $incident['reporter_name'] ?? 'Admin' }}</td>
                        <td>
                            <span class="rk-stitch-status rk-result-{{ $incident['status'] ?? 'open' }}">
                                {{ $statusLabels[$incident['status'] ?? 'open'] ?? 'Terbuka' }}
                            </span>
                        </td>
                        <td>
                            <a class="adm-visit-link" href="{{ route('incidents.admin.show', $incident['id']) }}">
                                Lihat detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="adm-visit-empty" colspan="6">
                            Belum ada insiden yang dilaporkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('admin-page-styles')
<style>
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
.rk-result-handling {
    background: #e5fcf0;
    color: #005338;
}
.rk-result-closed {
    background: #e8f5e9;
    color: #247850;
}
</style>
@endpush