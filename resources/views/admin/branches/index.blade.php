@php
    $createOpen = $openModal === 'create';
@endphp

<x-layouts.app :title="$title">
    <div class="space-y-5">
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Sucursales</h1>
                <p class="cf-subtitle">Directorio de geocercas, edición y alta.</p>
            </div>
            <button class="cf-btn cf-btn-primary" type="button" data-modal-open="branch-create">
                Nueva sucursal
            </button>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            @if ($branches === [])
                <div class="px-6 py-14 text-center">
                    <p class="font-[family-name:var(--font-display)] text-xl font-semibold">Sin sucursales todavía</p>
                    <p class="mt-2 text-sm text-[var(--color-ink-soft)]">Crea la primera para ubicar a tu equipo.</p>
                    <button class="cf-btn cf-btn-primary mt-5" type="button" data-modal-open="branch-create">Nueva sucursal</button>
                </div>
            @endif

            @if ($branches !== [])
                <div class="hidden border-b border-[var(--color-line)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--color-ink-soft)] sm:grid sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)_7rem_7rem_6.5rem] sm:gap-3">
                    <span>Sucursal</span>
                    <span>Dirección</span>
                    <span>Geocerca</span>
                    <span>Estado</span>
                    <span class="text-right">Acciones</span>
                </div>

                <ul class="divide-y divide-[var(--color-line)]">
                    @foreach ($branches as $branch)
                        @php
                            $editId = 'branch-edit-'.$branch->id();
                            $radius = $branch->geofenceRadiusMeters();
                        @endphp
                        <li class="cf-data-row sm:grid sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)_7rem_7rem_6.5rem] sm:items-center sm:gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $branch->name() }}</p>
                                <p class="truncate text-sm text-[var(--color-ink-soft)] sm:hidden">{{ $branch->address() ?? 'Sin dirección' }}</p>
                            </div>
                            <p class="hidden truncate text-sm text-[var(--color-ink-soft)] sm:block">{{ $branch->address() ?? '—' }}</p>
                            <p class="text-sm text-[var(--color-ink-soft)]">
                                <span class="sm:hidden">Geocerca · </span>
                                @if ($branch->requiresGeofence())
                                    {{ $radius }} m
                                @endif
                                @if (! $branch->requiresGeofence())
                                    No
                                @endif
                            </p>
                            <div>
                                @if ($branch->isActive())
                                    <span class="cf-badge cf-badge-ok">Activa</span>
                                @endif
                                @if (! $branch->isActive())
                                    <span class="cf-badge cf-badge-muted">Inactiva</span>
                                @endif
                            </div>
                            <div class="flex sm:justify-end">
                                <button class="cf-btn cf-btn-ghost" type="button" data-modal-open="{{ $editId }}">Editar</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    </div>

    @foreach ($branches as $branch)
        @php
            $location = $branch->location();
            $editId = 'branch-edit-'.$branch->id();
            $editOpen = $openModal === 'edit:'.$branch->id();
            $isEditOld = old('_form') === 'edit:'.$branch->id();
        @endphp
        <div
            class="cf-modal"
            data-modal="{{ $editId }}"
            @if ($editOpen) data-modal-auto-open="1" @endif
            hidden
        >
            <div class="cf-modal-backdrop" data-modal-close></div>
            <div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $editId }}-title">
                <div class="cf-modal-head">
                    <div>
                        <h2 id="{{ $editId }}-title" class="font-[family-name:var(--font-display)] text-xl font-semibold">Editar sucursal</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]">{{ $branch->name() }}</p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close aria-label="Cerrar">Cerrar</button>
                </div>
                <form method="POST" action="{{ route('admin.branches.update', $branch->id()) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="edit:{{ $branch->id() }}">
                    <div class="cf-modal-body space-y-3">
                        <x-ui.field label="Nombre">
                            <input class="cf-input" name="name" value="{{ $isEditOld ? old('name') : $branch->name() }}" required>
                        </x-ui.field>
                        <x-ui.field label="Dirección">
                            <input class="cf-input" name="address" value="{{ $isEditOld ? old('address') : $branch->address() }}">
                        </x-ui.field>
                        <div data-branch-map data-maps-key="{{ $mapsApiKey }}">
                            <p class="cf-location text-sm" data-branch-map-notice hidden></p>
                            <div class="cf-map" data-branch-map-canvas></div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Latitud">
                                <input class="cf-input" name="latitude" value="{{ $isEditOld ? old('latitude') : $location?->latitude }}" inputmode="decimal">
                            </x-ui.field>
                            <x-ui.field label="Longitud">
                                <input class="cf-input" name="longitude" value="{{ $isEditOld ? old('longitude') : $location?->longitude }}" inputmode="decimal">
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Radio en metros">
                            <input class="cf-input" name="geofence_radius_meters" value="{{ $isEditOld ? old('geofence_radius_meters') : $branch->geofenceRadiusMeters() }}" inputmode="numeric">
                        </x-ui.field>
                        <input type="hidden" name="require_geofence" value="0">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="require_geofence" value="1" @checked(($isEditOld ? old('require_geofence') : ($branch->requiresGeofence() ? '1' : '0')) === '1')>
                            Exigir geocerca
                        </label>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked(($isEditOld ? old('is_active') : ($branch->isActive() ? '1' : '0')) === '1')>
                            Activa
                        </label>
                    </div>
                    <div class="cf-modal-foot">
                        <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                        <button class="cf-btn cf-btn-primary" type="submit">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div
        class="cf-modal"
        data-modal="branch-create"
        @if ($createOpen) data-modal-auto-open="1" @endif
        hidden
    >
        <div class="cf-modal-backdrop" data-modal-close></div>
        <div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="branch-create-title">
            <div class="cf-modal-head">
                <div>
                    <h2 id="branch-create-title" class="font-[family-name:var(--font-display)] text-xl font-semibold">Nueva sucursal</h2>
                    <p class="mt-1 text-sm text-[var(--color-ink-soft)]">Nombre, ubicación y geocerca.</p>
                </div>
                <button class="cf-btn cf-btn-ghost" type="button" data-modal-close aria-label="Cerrar">Cerrar</button>
            </div>
            <form method="POST" action="{{ route('admin.branches.store') }}">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="cf-modal-body space-y-3">
                    <x-ui.field label="Nombre">
                        <input class="cf-input" name="name" value="{{ $createOpen ? old('name') : '' }}" required>
                    </x-ui.field>
                    <x-ui.field label="Dirección">
                        <input class="cf-input" name="address" value="{{ $createOpen ? old('address') : '' }}" autocomplete="street-address">
                    </x-ui.field>
                    <div data-branch-map data-maps-key="{{ $mapsApiKey }}">
                        <p class="cf-location text-sm" data-branch-map-notice hidden></p>
                        <div class="cf-map" data-branch-map-canvas></div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Latitud">
                            <input class="cf-input" name="latitude" value="{{ $createOpen ? old('latitude') : '' }}" inputmode="decimal">
                        </x-ui.field>
                        <x-ui.field label="Longitud">
                            <input class="cf-input" name="longitude" value="{{ $createOpen ? old('longitude') : '' }}" inputmode="decimal">
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Radio en metros">
                        <input class="cf-input" name="geofence_radius_meters" value="{{ $createOpen ? old('geofence_radius_meters') : '' }}" inputmode="numeric">
                    </x-ui.field>
                    <input type="hidden" name="require_geofence" value="0">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="require_geofence" value="1" @checked($createOpen && old('require_geofence') === '1')>
                        Exigir geocerca
                    </label>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" @checked(($createOpen ? old('is_active', '1') : '1') === '1')>
                        Activa
                    </label>
                </div>
                <div class="cf-modal-foot">
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                    <button class="cf-btn cf-btn-primary" type="submit">Crear sucursal</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
