@php
    $createOpen = $openModal === 'create';
    $editOpen = is_string($openModal) && str_starts_with((string) $openModal, 'edit:');
@endphp

<x-layouts.app :title="$title">
    <div
        class="space-y-5"
        data-schedules-list
        data-list-url="{{ route('admin.schedules.index') }}"
        data-can-manage="{{ $canManage ? '1' : '0' }}"
        data-is-platform="{{ $isPlatform ? '1' : '0' }}"
        data-page="1"
    >
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Horarios</h1>
                <p class="cf-subtitle">
                    @if ($isPlatform)
                        Turnos de todas las empresas.
                    @endif
                    @if (! $isPlatform)
                        Turnos esperados, edición y alta.
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
                    <button class="cf-btn cf-btn-primary" type="button" data-modal-open="schedule-create">Nuevo horario</button>
                @endif
            </div>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            <div data-list-empty class="px-4 py-10 text-center text-sm text-[var(--color-ink-soft)]">Cargando…</div>
            <div class="hidden border-b border-[var(--color-line)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--color-ink-soft)] sm:grid {{ $isPlatform ? 'sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_8rem_minmax(0,1.2fr)_6rem_6.5rem]' : 'sm:grid-cols-[minmax(0,1.2fr)_8rem_minmax(0,1.4fr)_6rem_6.5rem]' }} sm:gap-3" data-list-header hidden>
                <span>Nombre</span>
                @if ($isPlatform)<span>Empresa</span>@endif
                <span>Jornada</span>
                <span>Días</span>
                <span>Estado</span>
                <span class="text-right">Acciones</span>
            </div>
            <ul class="divide-y divide-[var(--color-line)]" data-list-rows></ul>
            <div class="flex flex-col gap-3 border-t border-[var(--color-line)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between" data-list-pager></div>
        </x-ui.panel>
    </div>

    @if ($canManage)
        <div class="cf-modal" data-modal="schedule-edit" @if ($editOpen) data-modal-auto-open="1" @endif hidden>
            <div class="cf-modal-backdrop" data-modal-close></div>
            <div class="cf-modal-dialog" role="dialog" aria-modal="true">
                <div class="cf-modal-head">
                    <div>
                        <h2 class="font-[family-name:var(--font-display)] text-xl font-semibold">Editar horario</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]" data-schedule-edit-title></p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cerrar</button>
                </div>
                <form method="POST" action="#" data-schedule-edit-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="{{ $editOpen ? $openModal : 'edit:0' }}">
                    <div class="cf-modal-body space-y-3">
                        <x-ui.field label="Nombre"><input class="cf-input" name="name" value="{{ $editOpen ? old('name') : '' }}" required></x-ui.field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Entrada"><input class="cf-input" type="time" name="clock_in_time" value="{{ $editOpen ? old('clock_in_time') : '' }}" required></x-ui.field>
                            <x-ui.field label="Salida"><input class="cf-input" type="time" name="clock_out_time" value="{{ $editOpen ? old('clock_out_time') : '' }}" required></x-ui.field>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Tolerancia de retardo (minutos)"><input class="cf-input" name="late_tolerance_minutes" value="{{ $editOpen ? old('late_tolerance_minutes') : '' }}" required></x-ui.field>
                            <x-ui.field label="Comida permitida (minutos)"><input class="cf-input" name="allowed_lunch_minutes" value="{{ $editOpen ? old('allowed_lunch_minutes') : '' }}" required></x-ui.field>
                        </div>
                        <fieldset>
                            <legend class="text-sm font-medium">Días laborales</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-sm" data-schedule-days>
                                @foreach ($weekdays as $day)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="working_days[]" value="{{ $day }}">
                                        {{ $weekdayLabels[$day] }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($editOpen && old('is_active') === '1')> Activo</label>
                    </div>
                    <div class="cf-modal-foot">
                        <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                        <button class="cf-btn cf-btn-primary" type="submit">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="cf-modal" data-modal="schedule-create" @if ($createOpen) data-modal-auto-open="1" @endif hidden>
            <div class="cf-modal-backdrop" data-modal-close></div>
            <div class="cf-modal-dialog" role="dialog" aria-modal="true">
                <div class="cf-modal-head">
                    <div>
                        <h2 class="font-[family-name:var(--font-display)] text-xl font-semibold">Nuevo horario</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]">Entrada, salida y días laborales.</p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cerrar</button>
                </div>
                <form method="POST" action="{{ route('admin.schedules.store') }}">
                    @csrf
                    <input type="hidden" name="_form" value="create">
                    <div class="cf-modal-body space-y-3">
                        <x-ui.field label="Nombre"><input class="cf-input" name="name" value="{{ $createOpen ? old('name') : '' }}" required></x-ui.field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Entrada"><input class="cf-input" type="time" name="clock_in_time" value="{{ $createOpen ? old('clock_in_time', '09:00') : '09:00' }}" required></x-ui.field>
                            <x-ui.field label="Salida"><input class="cf-input" type="time" name="clock_out_time" value="{{ $createOpen ? old('clock_out_time', '18:00') : '18:00' }}" required></x-ui.field>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Tolerancia de retardo (minutos)"><input class="cf-input" name="late_tolerance_minutes" value="{{ $createOpen ? old('late_tolerance_minutes', 10) : 10 }}" required></x-ui.field>
                            <x-ui.field label="Comida permitida (minutos)"><input class="cf-input" name="allowed_lunch_minutes" value="{{ $createOpen ? old('allowed_lunch_minutes', 60) : 60 }}" required></x-ui.field>
                        </div>
                        <fieldset>
                            <legend class="text-sm font-medium">Días laborales</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                @foreach ($weekdays as $day)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="working_days[]" value="{{ $day }}" @checked(in_array($day, $createOpen ? old('working_days', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']) : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], true))>
                                        {{ $weekdayLabels[$day] }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(($createOpen ? old('is_active', '1') : '1') === '1')> Activo</label>
                    </div>
                    <div class="cf-modal-foot">
                        <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                        <button class="cf-btn cf-btn-primary" type="submit">Crear horario</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-layouts.app>
