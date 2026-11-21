<x-layouts.app :title="$title">
    <section
        class="mx-auto max-w-md space-y-4"
        data-punch-shell
        data-maps-key="{{ $mapsApiKey }}"
        data-require-geofence="{{ $requireGeofence ? '1' : '0' }}"
        data-branch-lat="{{ $branchLatitude }}"
        data-branch-lng="{{ $branchLongitude }}"
        data-branch-radius="{{ $branchRadius }}"
    >
        <x-ui.panel class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--color-ink-soft)]">{{ $timezone }}</p>
            <p class="mt-3 font-[family-name:var(--font-display)] text-5xl font-semibold tabular-nums tracking-tight" data-live-clock data-timezone="{{ $timezone }}">{{ $clock }}</p>
            <p class="mt-4 text-lg font-semibold">{{ $statusLabel }}</p>
            <p class="mt-1 text-sm text-[var(--color-ink-soft)]">Tarde {{ $lateMinutes }} min · Trabajado {{ $workedMinutes }} min</p>

            <div class="cf-map mt-4 hidden" data-punch-map></div>
            <p class="cf-location mt-3 text-sm" data-location-status data-tone="muted">Preparando ubicación…</p>

            @if ($nextPunch === null)
                <p class="mt-6 rounded-2xl bg-[var(--color-paper)] px-4 py-4 text-lg font-semibold">Día completo</p>
            @endif
            @if ($nextPunch !== null)
                <form class="mt-6" method="POST" action="{{ route('attendance.punches.store') }}" data-punch-form>
                    @csrf
                    <input type="hidden" name="punch_type" value="{{ $nextPunch }}">
                    <input type="hidden" name="latitude" value="">
                    <input type="hidden" name="longitude" value="">
                    <button class="cf-btn-punch" type="submit">{{ $actionLabel }}</button>
                    @if ($requireGeofence)
                        <p class="mt-3 text-xs text-[var(--color-ink-soft)]">Esta sucursal exige geocerca: la marca usa tu ubicación actual.</p>
                    @endif
                    @if (! $requireGeofence)
                        <p class="mt-3 text-xs text-[var(--color-ink-soft)]">La ubicación se envía si el navegador lo permite.</p>
                    @endif
                </form>
            @endif
        </x-ui.panel>

        <x-ui.panel>
            <h2 class="font-[family-name:var(--font-display)] text-lg font-semibold">Ubicación</h2>

            <div class="mt-4 space-y-3 text-left">
                <article class="cf-list-row">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-ink-soft)]">Sucursal</p>
                    <p class="mt-1 font-semibold">{{ $branchName }}</p>
                    <p class="mt-1 text-sm text-[var(--color-ink-soft)]">{{ $branchAddress ?? 'Sin dirección registrada' }}</p>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Latitud</dt>
                            <dd class="font-mono tabular-nums" data-branch-lat-label>
                                @if ($branchLatitude !== null)
                                    {{ number_format((float) $branchLatitude, 7, '.', '') }}
                                @endif
                                @if ($branchLatitude === null)
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Longitud</dt>
                            <dd class="font-mono tabular-nums" data-branch-lng-label>
                                @if ($branchLongitude !== null)
                                    {{ number_format((float) $branchLongitude, 7, '.', '') }}
                                @endif
                                @if ($branchLongitude === null)
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Radio</dt>
                            <dd class="font-mono tabular-nums">
                                @if ($branchRadius !== null)
                                    {{ $branchRadius }} m
                                @endif
                                @if ($branchRadius === null)
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Geocerca</dt>
                            <dd>
                                @if ($requireGeofence)
                                    Obligatoria
                                @endif
                                @if (! $requireGeofence)
                                    Opcional
                                @endif
                            </dd>
                        </div>
                    </dl>
                </article>

                <article class="cf-list-row">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-ink-soft)]">Tu dispositivo</p>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Latitud</dt>
                            <dd class="font-mono tabular-nums" data-device-lat>—</dd>
                        </div>
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Longitud</dt>
                            <dd class="font-mono tabular-nums" data-device-lng>—</dd>
                        </div>
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Precisión</dt>
                            <dd class="font-mono tabular-nums" data-device-accuracy>—</dd>
                        </div>
                        <div>
                            <dt class="text-[var(--color-ink-soft)]">Distancia a sucursal</dt>
                            <dd class="font-mono tabular-nums" data-device-distance>—</dd>
                        </div>
                    </dl>
                </article>
            </div>
        </x-ui.panel>

        <x-ui.panel>
            <h2 class="font-[family-name:var(--font-display)] text-lg font-semibold">Hoy</h2>
            @if ($punches === [])
                <p class="mt-3 text-sm text-[var(--color-ink-soft)]">Aún no hay marcas.</p>
            @endif
            <ol class="mt-3 space-y-2">
                @foreach ($punches as $punch)
                    <li class="cf-list-row flex items-center justify-between">
                        <span>{{ $punch['label'] }}</span>
                        <time class="font-mono tabular-nums">{{ $punch['time'] }}</time>
                    </li>
                @endforeach
            </ol>
        </x-ui.panel>

        <x-ui.panel>
            <h2 class="font-[family-name:var(--font-display)] text-lg font-semibold">Días recientes</h2>
            @if ($history === [])
                <p class="mt-3 text-sm text-[var(--color-ink-soft)]">Aún no hay asistencia reciente.</p>
            @endif
            <ul class="mt-3 space-y-2">
                @foreach ($history as $day)
                    <li class="cf-list-row text-sm">
                        <div class="flex items-center justify-between">
                            <span>{{ $day['date'] }}</span>
                            <span>{{ $day['status'] }}</span>
                        </div>
                        <p class="mt-1 text-[var(--color-ink-soft)]">Entrada {{ $day['clockIn'] ?? '—' }} · Salida {{ $day['clockOut'] ?? '—' }}</p>
                    </li>
                @endforeach
            </ul>
        </x-ui.panel>
    </section>
</x-layouts.app>
