@extends('layouts.admin')
@section('title', 'Kelola Foto Resident — RawatKasih')
@section('content')
<main id="main" class="adm-main" style="max-width:1000px;margin:auto;padding:32px">
    <h1>Kelola foto keluarga dan lansia</h1>
    <p>Semua foto disimpan di Supabase Storage privat, maksimal 3 MiB (JPEG, PNG, WebP). Foto kabar hanya dapat dilihat keluarga jika kabar telah dipublikasikan dan izin foto aktif.</p>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert">{{ $errors->first() }}</div>@endif
    <section class="adm-visit-card" style="padding:24px;margin-top:24px">
        <h2>Unggah foto profil lansia</h2>
        <form method="POST" action="{{ route('admin.family-media.resident') }}" enctype="multipart/form-data">
            @csrf
            <label for="resident-photo-id">Resident</label>
            <select id="resident-photo-id" name="resident_id" required>
                @foreach($residents as $resident)
                    <option value="{{ $resident['id'] }}">{{ $resident['full_name'] }}</option>
                @endforeach
            </select>
            <label for="resident-photo">Foto</label>
            <input type="file" id="resident-photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
            <button type="submit" class="adm-primary-button">Simpan foto lansia</button>
        </form>
    </section>
    <section class="adm-visit-card" style="padding:24px;margin-top:24px">
        <h2>Unggah dokumentasi Kabar Harian</h2>
        <form method="POST" action="{{ route('admin.family-media.update') }}" enctype="multipart/form-data">
            @csrf
            <label for="update-photo-id">Kabar</label>
            <select id="update-photo-id" name="update_id" required>
                @foreach($updates as $update)
                    <option value="{{ $update['id'] }}">{{ ucfirst($update['status']) }} — {{ Str::limit($update['summary'], 100) }}</option>
                @endforeach
            </select>
            <label for="update-photo">Foto</label>
            <input type="file" id="update-photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
            <button type="submit" class="adm-primary-button">Simpan foto kabar</button>
        </form>
    </section>
</main>
@endsection
