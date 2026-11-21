@php
    $createOpen = $openModal === 'create';
@endphp

<x-layouts.app :title="$title">
    <div class="space-y-5">
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Empleados</h1>
                <p class="cf-subtitle">Directorio del personal, edición y alta.</p>
            </div>
            <button class="cf-btn cf-btn-primary" type="button" data-modal-open="employee-create">
                Nuevo empleado
            </button>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            @if ($employees === [])
                <div class="px-6 py-14 text-center">
                    <p class="font-[family-name:var(--font-display)] text-xl font-semibold">Sin empleados todavía</p>
                    <p class="mt-2 text-sm text-[var(--color-ink-soft)]">Crea el primero para asignar sucursal y horario.</p>
                    <button class="cf-btn cf-btn-primary mt-5" type="button" data-modal-open="employee-create">Nuevo empleado</button>
                </div>
            @endif

            @if ($employees !== [])
                <div class="hidden border-b border-[var(--color-line)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--color-ink-soft)] sm:grid sm:grid-cols-[minmax(0,1.4fr)_7rem_minmax(0,1fr)_minmax(0,1fr)_6rem_6.5rem] sm:gap-3">
                    <span>Persona</span>
                    <span>Código</span>
                    <span>Sucursal</span>
                    <span>Horario</span>
                    <span>Estado</span>
                    <span class="text-right">Acciones</span>
                </div>

                <ul class="divide-y divide-[var(--color-line)]">
                    @foreach ($employees as $employee)
                        @php
                            $account = $accounts[$employee->userId()] ?? null;
                            $photo = $account ? $photoUrl($account['photoPath']) : null;
                            $name = $account['name'] ?? $employee->employeeCode();
                            $nickname = $account['nickname'] ?? '—';
                            $editId = 'employee-edit-'.$employee->id();
                        @endphp
                        <li class="cf-data-row sm:grid sm:grid-cols-[minmax(0,1.4fr)_7rem_minmax(0,1fr)_minmax(0,1fr)_6rem_6.5rem] sm:items-center sm:gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="" class="cf-avatar">
                                @endif
                                @if (! $photo)
                                    <div class="cf-avatar-fallback">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $name }}</p>
                                    <p class="truncate text-sm text-[var(--color-ink-soft)]">{{ $nickname }} · {{ $employee->positionTitle() ?? 'Sin puesto' }}</p>
                                </div>
                            </div>
                            <p class="font-mono text-sm tabular-nums text-[var(--color-ink-soft)] sm:text-[var(--color-ink)]">
                                <span class="sm:hidden font-sans text-[var(--color-ink-soft)]">Código · </span>{{ $employee->employeeCode() }}
                            </p>
                            <p class="truncate text-sm text-[var(--color-ink-soft)]">
                                <span class="sm:hidden">Sucursal · </span>{{ $branchNames[$employee->branchId()] ?? '—' }}
                            </p>
                            <p class="truncate text-sm text-[var(--color-ink-soft)]">
                                <span class="sm:hidden">Horario · </span>{{ $scheduleNames[$employee->scheduleId()] ?? '—' }}
                            </p>
                            <div>
                                @if ($employee->isActive())
                                    <span class="cf-badge cf-badge-ok">Activo</span>
                                @endif
                                @if (! $employee->isActive())
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

    @foreach ($employees as $employee)
        @php
            $account = $accounts[$employee->userId()] ?? null;
            $name = $account['name'] ?? $employee->employeeCode();
            $editId = 'employee-edit-'.$employee->id();
            $editOpen = $openModal === 'edit:'.$employee->id();
            $isEditOld = old('_form') === 'edit:'.$employee->id();
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
                        <h2 id="{{ $editId }}-title" class="font-[family-name:var(--font-display)] text-xl font-semibold">Editar empleado</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]">{{ $name }} · {{ $employee->employeeCode() }}</p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close aria-label="Cerrar">Cerrar</button>
                </div>
                <form method="POST" action="{{ route('admin.employees.update', $employee->id()) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="edit:{{ $employee->id() }}">
                    <div class="cf-modal-body space-y-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Nombre">
                                <input class="cf-input" name="name" value="{{ $isEditOld ? old('name') : ($account['name'] ?? '') }}" required>
                            </x-ui.field>
                            <x-ui.field label="Nickname">
                                <input class="cf-input" name="nickname" value="{{ $isEditOld ? old('nickname') : ($account['nickname'] ?? '') }}" required autocapitalize="none">
                            </x-ui.field>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Correo (opcional)">
                                <input class="cf-input" type="email" name="email" value="{{ $isEditOld ? old('email') : ($account['email'] ?? '') }}">
                            </x-ui.field>
                            <x-ui.field label="Teléfono">
                                <input class="cf-input" name="phone" value="{{ $isEditOld ? old('phone') : ($account['phone'] ?? '') }}">
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Foto">
                            <input class="cf-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                        </x-ui.field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Código de empleado">
                                <input class="cf-input" name="employee_code" value="{{ $isEditOld ? old('employee_code') : $employee->employeeCode() }}" required>
                            </x-ui.field>
                            <x-ui.field label="Puesto">
                                <input class="cf-input" name="position_title" value="{{ $isEditOld ? old('position_title') : $employee->positionTitle() }}">
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Fecha de ingreso">
                            <input class="cf-input" type="date" name="hire_date" value="{{ $isEditOld ? old('hire_date') : $employee->hireDate()?->format('Y-m-d') }}">
                        </x-ui.field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Sucursal">
                                <select class="cf-select" name="branch_id" required>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id() }}" @selected((string) ($isEditOld ? old('branch_id') : $employee->branchId()) === (string) $branch->id())>{{ $branch->name() }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Horario">
                                <select class="cf-select" name="schedule_id" required>
                                    @foreach ($schedules as $schedule)
                                        <option value="{{ $schedule->id() }}" @selected((string) ($isEditOld ? old('schedule_id') : $employee->scheduleId()) === (string) $schedule->id())>{{ $schedule->name() }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked(($isEditOld ? old('is_active') : ($employee->isActive() ? '1' : '0')) === '1')>
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
        data-modal="employee-create"
        @if ($createOpen) data-modal-auto-open="1" @endif
        hidden
    >
        <div class="cf-modal-backdrop" data-modal-close></div>
        <div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-create-title">
            <div class="cf-modal-head">
                <div>
                    <h2 id="employee-create-title" class="font-[family-name:var(--font-display)] text-xl font-semibold">Nuevo empleado</h2>
                    <p class="mt-1 text-sm text-[var(--color-ink-soft)]">Nickname, código, sucursal y horario.</p>
                </div>
                <button class="cf-btn cf-btn-ghost" type="button" data-modal-close aria-label="Cerrar">Cerrar</button>
            </div>
            <form method="POST" action="{{ route('admin.employees.store') }}">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="cf-modal-body space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Nombre">
                            <input class="cf-input" name="name" value="{{ $createOpen ? old('name') : '' }}" required>
                        </x-ui.field>
                        <x-ui.field label="Nickname">
                            <input class="cf-input" name="nickname" value="{{ $createOpen ? old('nickname') : '' }}" required autocapitalize="none" spellcheck="false">
                        </x-ui.field>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Correo (opcional)">
                            <input class="cf-input" type="email" name="email" value="{{ $createOpen ? old('email') : '' }}">
                        </x-ui.field>
                        <x-ui.field label="Teléfono">
                            <input class="cf-input" name="phone" value="{{ $createOpen ? old('phone') : '' }}" inputmode="tel">
                        </x-ui.field>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Contraseña">
                            <input class="cf-input" type="password" name="password" required>
                        </x-ui.field>
                        <x-ui.field label="Confirmar contraseña">
                            <input class="cf-input" type="password" name="password_confirmation" required>
                        </x-ui.field>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Código de empleado">
                            <input class="cf-input" name="employee_code" value="{{ $createOpen ? old('employee_code') : '' }}" required>
                        </x-ui.field>
                        <x-ui.field label="Puesto">
                            <input class="cf-input" name="position_title" value="{{ $createOpen ? old('position_title') : '' }}">
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Fecha de ingreso">
                        <input class="cf-input" type="date" name="hire_date" value="{{ $createOpen ? old('hire_date') : '' }}">
                    </x-ui.field>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Sucursal">
                            <select class="cf-select" name="branch_id" required>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id() }}" @selected($createOpen && (string) old('branch_id') === (string) $branch->id())>{{ $branch->name() }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.field label="Horario">
                            <select class="cf-select" name="schedule_id" required>
                                @foreach ($schedules as $schedule)
                                    <option value="{{ $schedule->id() }}" @selected($createOpen && (string) old('schedule_id') === (string) $schedule->id())>{{ $schedule->name() }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    </div>
                </div>
                <div class="cf-modal-foot">
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close>Cancelar</button>
                    <button class="cf-btn cf-btn-primary" type="submit">Crear empleado</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
