@props(['name', 'size' => 20])
<img src="{{ asset('icons/tabler/'.$name.'.svg') }}" width="{{ $size }}" height="{{ $size }}" alt="" aria-hidden="true" {{ $attributes->class(['lp-icon']) }}>
