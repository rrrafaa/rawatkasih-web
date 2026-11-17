@props(['status'])
<span class="fv-badge fv-badge-{{ $status->value }}">{{ $status->label() }}</span>
