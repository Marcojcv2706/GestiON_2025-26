@props(['label', 'state' => null])

<span class="status-pill{{ $state ? ' status-pill-'.$state : '' }}" @if($state) role="status" @endif>
    <span aria-hidden="true"></span>{{ $label }}
</span>
