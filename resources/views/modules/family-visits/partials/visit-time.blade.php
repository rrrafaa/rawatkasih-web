<div>
    <label class="adm-form-label" for="{{ $field }}">{{ $label }}</label>
    <select class="adm-form-control fv-select" id="{{ $field }}" name="{{ $field }}" required
            aria-describedby="{{ $field }}-error"
            @error($field) aria-invalid="true" @enderror>
        <option value="">Pilih jam</option>
        @foreach ($slots as $slot)
            <option value="{{ $slot }}" @selected(old($field) === $slot)>{{ str_replace(':', '.', $slot) }}</option>
        @endforeach
    </select>
    <p class="adm-form-error" id="{{ $field }}-error" aria-live="polite">{{ $errors->first($field) }}</p>
</div>
