@php
    $createOpen = $openModal === 'create';
    $editOpen = is_string($openModal) && str_starts_with((string) $openModal, 'edit:');
@endphp

<x-layouts.app :title="$title">
    <div
        class="space-y-5"
        data-branches-list
        data-list-url="{{ route('admin.branches.index') }}"
        data-can-manage="{{ $canManage ? '1' : '0' }}"
        data-is-platform="{{ $isPlatform ? '1' : '0' }}"
        data-page="1"
    >
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Sucursales</h1>
                <p class="cf-subtitle">
                    @if ($isPlatform)
                        Geocercas de todas las empresas.
                    @endif
                    @if (! $isPlatform)
                        Directorio de geocercas, edición y alta.
                    @endif
                </p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                @if ($isPlatform)
                    <x-ui.field label="Empresa">
                        <select class="cf-select" name="company_id" data-list-filter>
                            <option value="">Todas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                @endif
                @if ($canManage)
                    <button class="cf-btn cf-btn-primary" type="button" data-modal-open="branch-create">Nueva sucursal</button>
                @endif
            </div>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            <div data-list-empty class="px-4 py-10 text-center text-sm text-[var(--color-ink-soft)]" hidden>Sin sucursales.</div>
            <div class="hidden border-b border-[var(--color-line)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--color-ink-soft)] sm:grid {{ $isPlatform ? 'sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1.2fr)_7rem_7rem_6.5rem]' : 'sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)_7rem_7rem_6.5rem]' }} sm:gap-3" data-list-header hidden>
                <span>Sucursal</span>
                @if ($isPlatform)<span>Empresa</span>@endif
                <span>Dirección</span>
                <span>Geocerca</span>
                <span>Estado</span>
                <span class="text-right">Acciones</span>
            </div>
            <ul class="divide-y divide-[var(--color-line)]" data-list-rows></ul>
            <div class="flex flex-col gap-3 border-t border-[var(--color-line)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between" data-list-pager></div>
        </x-ui.panel>
    </div>

    @if ($canManage)
        <div class="cf-modal" data-modal="branch-edit" @if ($editOpen) data-modal-auto-open="1" @endif hidden>
            <div class="cf-modal-backdrop" data-modal-close></div>
            <div class="cf-modal-dialog" role="dialog" aria-modal="true">
                <div class="cf-modal-head">
                    <div>
                        <h2 class="font-[family-name:var(--font-display)] text-xl font-semibold">Editar sucursal</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]" data-branch-edit-title></p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cerrar</button>
                </div>
                <form method="POST" action="#" data-branch-edit-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="{{ $editOpen ? $openModal : 'edit:0' }}">
                    <div class="cf-modal-body space-y-3">
                        <x-ui.field label="Nombre"><input class="cf-input" name="name" value="{{ $editOpen ? old('name') : '' }}" required></x-ui.field>
                        <x-ui.field label="Dirección"><input class="cf-input" name="address" value="{{ $editOpen ? old('address') : '' }}"></x-ui.field>
                        <div data-branch-map data-maps-key="{{ $mapsApiKey }}">
                            <p class="cf-location text-sm" data-branch-map-notice hidden></p>
                            <div class="cf-map" data-branch-map-canvas></div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Latitud"><input class="cf-input" name="latitude" value="{{ $editOpen ? old('latitude') : '' }}" inputmode="decimal"></x-ui.field>
                            <x-ui.field label="Longitud"><input class="cf-input" name="longitude" value="{{ $editOpen ? old('longitude') : '' }}" inputmode="decimal"></x-ui.field>
                        </div>
                        <x-ui.field label="Radio en metros"><input class="cf-input" name="geofence_radius_meters" value="{{ $editOpen ? old('geofence_radius_meters') : '' }}" inputmode="numeric"></x-ui.field>
                        <input type="hidden" name="require_geofence" value="0">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_geofence" value="1" @checked($editOpen && old('require_geofence') === '1')> Exigir geocerca</label>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($editOpen && old('is_active') === '1')> Activa</label>
                    </div>
                    <div class="cf-modal-foot">
                        <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                        <button class="cf-btn cf-btn-primary" type="submit">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="cf-modal" data-modal="branch-create" @if ($createOpen) data-modal-auto-open="1" @endif hidden>
            <div class="cf-modal-backdrop" data-modal-close></div>
            <div class="cf-modal-dialog" role="dialog" aria-modal="true">
                <div class="cf-modal-head">
                    <div>
                        <h2 class="font-[family-name:var(--font-display)] text-xl font-semibold">Nueva sucursal</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]">Nombre, ubicación y geocerca.</p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cerrar</button>
                </div>
                <form method="POST" action="{{ route('admin.branches.store') }}">
                    @csrf
                    <input type="hidden" name="_form" value="create">
                    <div class="cf-modal-body space-y-3">
                        <x-ui.field label="Nombre"><input class="cf-input" name="name" value="{{ $createOpen ? old('name') : '' }}" required></x-ui.field>
                        <x-ui.field label="Dirección"><input class="cf-input" name="address" value="{{ $createOpen ? old('address') : '' }}" autocomplete="street-address"></x-ui.field>
                        <div data-branch-map data-maps-key="{{ $mapsApiKey }}">
                            <p class="cf-location text-sm" data-branch-map-notice hidden></p>
                            <div class="cf-map" data-branch-map-canvas></div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Latitud"><input class="cf-input" name="latitude" value="{{ $createOpen ? old('latitude') : '' }}" inputmode="decimal"></x-ui.field>
                            <x-ui.field label="Longitud"><input class="cf-input" name="longitude" value="{{ $createOpen ? old('longitude') : '' }}" inputmode="decimal"></x-ui.field>
                        </div>
                        <x-ui.field label="Radio en metros"><input class="cf-input" name="geofence_radius_meters" value="{{ $createOpen ? old('geofence_radius_meters') : '' }}" inputmode="numeric"></x-ui.field>
                        <input type="hidden" name="require_geofence" value="0">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_geofence" value="1" @checked($createOpen && old('require_geofence') === '1')> Exigir geocerca</label>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(($createOpen ? old('is_active', '1') : '1') === '1')> Activa</label>
                    </div>
                    <div class="cf-modal-foot">
                        <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                        <button class="cf-btn cf-btn-primary" type="submit">Crear sucursal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-layouts.app>
