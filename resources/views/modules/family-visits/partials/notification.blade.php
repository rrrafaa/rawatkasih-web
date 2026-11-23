@if (session('status'))
    <div class="rk-corner-notice fv-alert fv-alert-success" role="status">
        <span>{{ session('status') }}</span>
        <button type="button" data-dismiss-notice aria-label="Tutup notifikasi">×</button>
    </div>
@elseif ($errors->any())
    <div class="rk-corner-notice fv-alert fv-alert-error" role="alert">
        <span>{{ $errors->first() }}</span>
        <button type="button" data-dismiss-notice aria-label="Tutup notifikasi">×</button>
    </div>
@endif
