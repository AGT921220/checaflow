@props(['label', 'name' => null])

<label {{ $attributes->class(['cf-field']) }}>
    {{ $label }}
    {{ $slot }}
</label>
