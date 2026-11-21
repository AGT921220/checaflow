@php
    $createOpen = $openModal === 'create';
@endphp

<x-layouts.app :title="$title">
    <div class="space-y-5">
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Horarios</h1>
                <p class="cf-subtitle">Turnos esperados, edición y alta.</p>
            </div>
            <button class="cf-btn cf-btn-primary" type="button" data-modal-open="schedule-create">
                Nuevo horario
            </button>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            @if ($schedules === [])
                <div class="px-6 py-14 text-center">
                    <p class="font-[family-name:var(--font-display)] text-xl font-semibold">Sin horarios todavía</p>
                    <p class="mt-2 text-sm text-[var(--color-ink-soft)]">Crea el primero para asignarlo a empleados.</p>
                    <button class="cf-btn cf-btn-primary mt-5" type="button" data-modal-open="schedule-create">Nuevo horario</button>
                </div>
            @endif

            @if ($schedules !== [])
                <div class="hidden border-b border-[var(--color-line)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--color-ink-soft)] sm:grid sm:grid-cols-[minmax(0,1.2fr)_8rem_minmax(0,1.4fr)_6rem_6.5rem] sm:gap-3">
                    <span>Nombre</span>
                    <span>Jornada</span>
                    <span>Días</span>
                    <span>Estado</span>
                    <span class="text-right">Acciones</span>
                </div>

                <ul class="divide-y divide-[var(--color-line)]">
                    @foreach ($schedules as $schedule)
                        @php
                            $editId = 'schedule-edit-'.$schedule->id();
                            $clockIn = sprintf('%02d:%02d', $schedule->clockIn()->hour(), $schedule->clockIn()->minute());
                            $clockOut = sprintf('%02d:%02d', $schedule->clockOut()->hour(), $schedule->clockOut()->minute());
                            $days = implode(', ', array_map(fn (string $day): string => $weekdayLabels[$day] ?? $day, $schedule->workingDays()->values()));
                        @endphp
                        <li class="cf-data-row sm:grid sm:grid-cols-[minmax(0,1.2fr)_8rem_minmax(0,1.4fr)_6rem_6.5rem] sm:items-center sm:gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $schedule->name() }}</p>
                                <p class="truncate text-sm text-[var(--color-ink-soft)] sm:hidden">{{ $clockIn }} – {{ $clockOut }}</p>
                            </div>
                            <p class="hidden font-mono text-sm tabular-nums sm:block">{{ $clockIn }} – {{ $clockOut }}</p>
                            <p class="truncate text-sm text-[var(--color-ink-soft)]">
                                <span class="sm:hidden">Días · </span>{{ $days }}
                            </p>
                            <div>
                                @if ($schedule->isActive())
                                    <span class="cf-badge cf-badge-ok">Activo</span>
                                @endif
                                @if (! $schedule->isActive())
                                    <span class="cf-badge cf-badge-muted">Inactivo</span>
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

    @foreach ($schedules as $schedule)
        @php
            $editId = 'schedule-edit-'.$schedule->id();
            $editOpen = $openModal === 'edit:'.$schedule->id();
            $isEditOld = old('_form') === 'edit:'.$schedule->id();
            $clockIn = sprintf('%02d:%02d', $schedule->clockIn()->hour(), $schedule->clockIn()->minute());
            $clockOut = sprintf('%02d:%02d', $schedule->clockOut()->hour(), $schedule->clockOut()->minute());
            $selectedDays = $isEditOld ? old('working_days', []) : $schedule->workingDays()->values();
            if (! is_array($selectedDays)) {
                $selectedDays = [];
            }
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
                        <h2 id="{{ $editId }}-title" class="font-[family-name:var(--font-display)] text-xl font-semibold">Editar horario</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]">{{ $schedule->name() }}</p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close aria-label="Cerrar">Cerrar</button>
                </div>
                <form method="POST" action="{{ route('admin.schedules.update', $schedule->id()) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="edit:{{ $schedule->id() }}">
                    <div class="cf-modal-body space-y-3">
                        <x-ui.field label="Nombre">
                            <input class="cf-input" name="name" value="{{ $isEditOld ? old('name') : $schedule->name() }}" required>
                        </x-ui.field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Entrada">
                                <input class="cf-input" type="time" name="clock_in_time" value="{{ $isEditOld ? old('clock_in_time') : $clockIn }}" required>
                            </x-ui.field>
                            <x-ui.field label="Salida">
                                <input class="cf-input" type="time" name="clock_out_time" value="{{ $isEditOld ? old('clock_out_time') : $clockOut }}" required>
                            </x-ui.field>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Tolerancia de retardo (minutos)">
                                <input class="cf-input" name="late_tolerance_minutes" value="{{ $isEditOld ? old('late_tolerance_minutes') : $schedule->lateToleranceMinutes() }}" required>
                            </x-ui.field>
                            <x-ui.field label="Comida permitida (minutos)">
                                <input class="cf-input" name="allowed_lunch_minutes" value="{{ $isEditOld ? old('allowed_lunch_minutes') : $schedule->allowedLunchMinutes() }}" required>
                            </x-ui.field>
                        </div>
                        <fieldset>
                            <legend class="text-sm font-medium">Días laborales</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                @foreach ($weekdays as $day)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="working_days[]" value="{{ $day }}" @checked(in_array($day, $selectedDays, true))>
                                        {{ $weekdayLabels[$day] }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked(($isEditOld ? old('is_active') : ($schedule->isActive() ? '1' : '0')) === '1')>
                            Activo
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
        data-modal="schedule-create"
        @if ($createOpen) data-modal-auto-open="1" @endif
        hidden
    >
        <div class="cf-modal-backdrop" data-modal-close></div>
        <div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="schedule-create-title">
            <div class="cf-modal-head">
                <div>
                    <h2 id="schedule-create-title" class="font-[family-name:var(--font-display)] text-xl font-semibold">Nuevo horario</h2>
                    <p class="mt-1 text-sm text-[var(--color-ink-soft)]">Entrada, salida y días laborales.</p>
                </div>
                <button class="cf-btn cf-btn-ghost" type="button" data-modal-close aria-label="Cerrar">Cerrar</button>
            </div>
            <form method="POST" action="{{ route('admin.schedules.store') }}">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="cf-modal-body space-y-3">
                    <x-ui.field label="Nombre">
                        <input class="cf-input" name="name" value="{{ $createOpen ? old('name') : '' }}" required>
                    </x-ui.field>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Entrada">
                            <input class="cf-input" type="time" name="clock_in_time" value="{{ $createOpen ? old('clock_in_time', '09:00') : '09:00' }}" required>
                        </x-ui.field>
                        <x-ui.field label="Salida">
                            <input class="cf-input" type="time" name="clock_out_time" value="{{ $createOpen ? old('clock_out_time', '18:00') : '18:00' }}" required>
                        </x-ui.field>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Tolerancia de retardo (minutos)">
                            <input class="cf-input" name="late_tolerance_minutes" value="{{ $createOpen ? old('late_tolerance_minutes', 10) : 10 }}" required>
                        </x-ui.field>
                        <x-ui.field label="Comida permitida (minutos)">
                            <input class="cf-input" name="allowed_lunch_minutes" value="{{ $createOpen ? old('allowed_lunch_minutes', 60) : 60 }}" required>
                        </x-ui.field>
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
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" @checked(($createOpen ? old('is_active', '1') : '1') === '1')>
                        Activo
                    </label>
                </div>
                <div class="cf-modal-foot">
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                    <button class="cf-btn cf-btn-primary" type="submit">Crear horario</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
