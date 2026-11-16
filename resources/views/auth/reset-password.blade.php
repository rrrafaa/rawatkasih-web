@extends('layouts.rawatkasih')
@section('title', 'Atur kata sandi — RawatKasih')
@section('content')
<main id="main" class="rk-recovery"><x-rk-brand /><div class="rk-card">
    <h1>Atur kata sandi baru</h1><p class="rk-description">Gunakan minimal 12 karakter. Tautan pemulihan hanya dapat digunakan sekali.</p>
    @if ($errors->has('token_hash'))<p class="rk-error" role="alert">{{ $errors->first('token_hash') }}</p>@endif
    @if ($tokenHash === '' && ! session()->has('supabase_recovery'))<p class="rk-error" role="alert">Tautan pemulihan belum tersedia. Buka tautan dari email Anda.</p>@endif
    <form class="rk-form" method="post" action="{{ route('password.update') }}">@csrf
        <input type="hidden" name="token_hash" value="{{ $tokenHash }}">
        <div><label for="password">Kata sandi baru</label><input id="password" name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        @error('password')<p id="password-error" class="rk-error" role="alert">{{ $message }}</p>@enderror</div>
        <div><label for="password_confirmation">Ulangi kata sandi baru</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></div>
        <button type="submit" class="rk-button">Simpan kata sandi</button>
    </form><a class="rk-back" href="{{ route('password.request') }}">Minta tautan pemulihan baru</a>
</div></main>
@endsection
