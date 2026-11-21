@props(['label', 'value'])

<article {{ $attributes->class(['cf-stat']) }}>
    <p class="cf-stat-label">{{ $label }}</p>
    <p class="cf-stat-value">{{ $value }}</p>
</article>
