@if (session('status'))
    <div class="fv-alert fv-alert-success" role="status">{{ session('status') }}</div>
@elseif ($errors->any())
    <div class="fv-alert fv-alert-error" role="alert">{{ $errors->first() }}</div>
@endif
