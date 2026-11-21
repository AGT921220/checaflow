@props(['tight' => false])

<section {{ $attributes->class([$tight ? 'cf-panel-tight' : 'cf-panel']) }}>
    {{ $slot }}
</section>
