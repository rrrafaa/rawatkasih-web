@extends('layouts.admin')

@section('title', 'Manajemen Anggota — RawatKasih')

@section('admin-content')
    <div class="space-y-6">
        @php
            $memberRoles = ['family' => 'Keluarga', 'caregiver' => 'Caregiver'];
            $selectedRole = old('role', request()->query('tambah'));
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="adm-page-title">Manajemen Anggota</h1>
            @if ($creationConfigured)
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.members.index', ['tambah' => 'family']) }}#form-anggota" class="adm-primary-button">Tambah Anggota Keluarga</a>
                    <a href="{{ route('admin.members.index', ['tambah' => 'caregiver']) }}#form-anggota" class="adm-primary-button">Tambah Caregiver</a>
                </div>
            @endif
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</p>
        @endif

        @if (! $creationConfigured)
            <p role="status" class="rounded-lg bg-amber-50 p-4">Pembuatan akun belum dikonfigurasi di server.</p>
        @elseif (is_string($selectedRole) && isset($memberRoles[$selectedRole]))
            @php
                $role = $selectedRole;
                $label = $memberRoles[$role];
                $submitted = old('role') === $role;
            @endphp
            <section id="form-anggota" class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6" aria-labelledby="member-form-title">
                <h2 id="member-form-title" class="mb-4 text-lg font-semibold">Tambah {{ $label }}</h2>
                @if ($submitted && $errors->any())
                    <ul role="alert" class="mb-4 list-inside list-disc text-red-700">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                @endif
                <form method="POST" action="{{ route('admin.members.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role }}">
                    <label class="block">
                        Nama lengkap
                        <input class="mt-1 w-full rounded-lg border border-gray-300 p-2" name="full_name" autocomplete="off" value="{{ $submitted ? old('full_name') : '' }}" maxlength="120" required>
                    </label>
                    <label class="block">
                        Email
                        <input class="mt-1 w-full rounded-lg border border-gray-300 p-2" name="email" type="email" autocomplete="off" value="{{ $submitted ? old('email') : '' }}" maxlength="254" required>
                    </label>
                    @if ($role === 'family')
                        <label class="block">
                            Lansia
                            <select class="mt-1 w-full rounded-lg border border-gray-300 bg-white p-2" name="resident_id" required>
                                <option value="">Pilih lansia</option>
                                @foreach ($residents as $resident)
                                    <option value="{{ $resident['id'] }}" @selected($submitted && old('resident_id') === $resident['id'])>{{ $resident['full_name'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            Hubungan keluarga
                            <input class="mt-1 w-full rounded-lg border border-gray-300 p-2" name="relationship" value="{{ $submitted ? old('relationship') : '' }}" maxlength="80" placeholder="Contoh anak atau cucu" required>
                        </label>
                    @endif
                    <label class="block">
                        Kata sandi awal (minimal 12 karakter)
                        <input class="mt-1 w-full rounded-lg border border-gray-300 p-2" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
                    </label>
                    <label class="block">
                        Konfirmasi kata sandi
                        <input class="mt-1 w-full rounded-lg border border-gray-300 p-2" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </label>
                    <div class="flex flex-wrap items-center gap-4">
                        <button class="adm-primary-button" type="submit">Buat akun {{ $label }}</button>
                        <a href="{{ route('admin.members.index') }}" class="font-medium underline">Batal</a>
                    </div>
                </form>
            </section>
        @endif

        <section aria-labelledby="member-list">
            <h2 id="member-list" class="mb-3 text-lg font-semibold">Daftar anggota</h2>
            <div class="adm-table-wrap" tabindex="0" role="region" aria-labelledby="member-list">
                <table class="adm-table">
                    <thead>
                        <tr><th scope="col">Nama</th><th scope="col">Peran</th><th scope="col">Status akun</th><th scope="col">Relasi dan verifikasi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td>
                                    <strong>{{ $member['full_name'] }}</strong>
                                    <div>{{ $member['email'] }}</div>
                                </td>
                                <td>{{ $member['role'] === 'family' ? 'Keluarga' : 'Caregiver' }}</td>
                                <td>{{ $member['is_active'] ? 'Aktif' : 'Nonaktif' }}</td>
                                <td>
                                    @foreach ($links->get($member['id'], []) as $link)
                                        <div class="space-y-1 py-2">
                                            <div>{{ $residents->get($link['resident_id'])['full_name'] ?? 'Lansia' }} · {{ $link['relationship'] }}</div>
                                            @switch($link['status'])
                                                @case('pending')
                                                    <form method="POST" action="{{ route('admin.members.verify', $link['id']) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="adm-primary-button" type="submit">Verifikasi relasi</button>
                                                    </form>
                                                    @break
                                                @case('verified')
                                                    <span class="font-medium text-emerald-800">Terverifikasi</span>
                                                    @break
                                                @default
                                                    <span>{{ ucfirst($link['status']) }}</span>
                                            @endswitch
                                        </div>
                                    @endforeach
                                    @if (! $links->has($member['id']))
                                        <span>{{ $member['role'] === 'family' ? 'Belum ada relasi' : '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada anggota.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
