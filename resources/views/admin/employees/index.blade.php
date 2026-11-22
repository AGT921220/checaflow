@php
    $createOpen = $openModal === 'create';
    $editOpen = is_string($openModal) && str_starts_with((string) $openModal, 'edit:');
@endphp

<x-layouts.app :title="$title">
    <div
        class="space-y-5"
        data-employees-list
        data-list-url="{{ route('admin.employees.index') }}"
        data-can-manage="{{ $canManage ? '1' : '0' }}"
        data-is-platform="{{ $isPlatform ? '1' : '0' }}"
        data-page="1"
    >
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Empleados</h1>
                <p class="cf-subtitle">
                    @if ($isPlatform)
                        Directorio de todas las empresas.
                    @endif
                    @if (! $isPlatform)
                        Directorio del personal, edición y alta.
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
                    <button class="cf-btn cf-btn-primary" type="button" data-modal-open="employee-create">
                        Nuevo empleado
                    </button>
                @endif
            </div>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            <div class="border-b border-[var(--color-line)] px-4 py-3" data-list-empty hidden>
                <p class="py-10 text-center text-sm text-[var(--color-ink-soft)]">Sin empleados.</p>
            </div>

            <div
                class="hidden border-b border-[var(--color-line)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--color-ink-soft)] sm:grid {{ $isPlatform ? 'sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_7rem_minmax(0,1fr)_minmax(0,1fr)_6rem_6.5rem]' : 'sm:grid-cols-[minmax(0,1.4fr)_7rem_minmax(0,1fr)_minmax(0,1fr)_6rem_6.5rem]' }} sm:gap-3"
                data-list-header
                hidden
            >
                <span>Persona</span>
                @if ($isPlatform)
                    <span>Empresa</span>
                @endif
                <span>Código</span>
                <span>Sucursal</span>
                <span>Horario</span>
                <span>Estado</span>
                <span class="text-right">Acciones</span>
            </div>

            <ul class="divide-y divide-[var(--color-line)]" data-list-rows></ul>

            <div class="flex flex-col gap-3 border-t border-[var(--color-line)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between" data-list-pager></div>
        </x-ui.panel>
    </div>

    @if ($canManage)
        <div class="cf-modal" data-modal="employee-edit" @if ($editOpen) data-modal-auto-open="1" @endif hidden>
            <div class="cf-modal-backdrop" data-modal-close></div>
            <div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-edit-title">
                <div class="cf-modal-head">
                    <div>
                        <h2 id="employee-edit-title" class="font-[family-name:var(--font-display)] text-xl font-semibold">Editar empleado</h2>
                        <p class="mt-1 text-sm text-[var(--color-ink-soft)]" data-employee-edit-title></p>
                    </div>
                    <button class="cf-btn cf-btn-ghost" type="button" data-modal-close aria-label="Cerrar">Cerrar</button>
                </div>
                <form method="POST" action="{{ route('admin.employees.update', 0) }}" enctype="multipart/form-data" data-employee-edit-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="{{ $editOpen ? $openModal : 'edit:0' }}">
                    <div class="cf-modal-body space-y-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Nombre">
                                <input class="cf-input" name="name" value="{{ $editOpen ? old('name') : '' }}" required>
                            </x-ui.field>
                            <x-ui.field label="Nickname">
                                <input class="cf-input" name="nickname" value="{{ $editOpen ? old('nickname') : '' }}" required autocapitalize="none">
                            </x-ui.field>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Correo (opcional)">
                                <input class="cf-input" type="email" name="email" value="{{ $editOpen ? old('email') : '' }}">
                            </x-ui.field>
                            <x-ui.field label="Teléfono">
                                <input class="cf-input" name="phone" value="{{ $editOpen ? old('phone') : '' }}">
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Foto">
                            <input class="cf-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                        </x-ui.field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Código de empleado">
                                <input class="cf-input" name="employee_code" value="{{ $editOpen ? old('employee_code') : '' }}" required>
                            </x-ui.field>
                            <x-ui.field label="Puesto">
                                <input class="cf-input" name="position_title" value="{{ $editOpen ? old('position_title') : '' }}">
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Fecha de ingreso">
                            <input class="cf-input" type="date" name="hire_date" value="{{ $editOpen ? old('hire_date') : '' }}">
                        </x-ui.field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-ui.field label="Sucursal">
                                <select class="cf-select" name="branch_id" required>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id() }}" @selected($editOpen && (string) old('branch_id') === (string) $branch->id())>{{ $branch->name() }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Horario">
                                <select class="cf-select" name="schedule_id" required>
                                    @foreach ($schedules as $schedule)
                                        <option value="{{ $schedule->id() }}" @selected($editOpen && (string) old('schedule_id') === (string) $schedule->id())>{{ $schedule->name() }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($editOpen && old('is_active') === '1')>
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

        <div class="cf-modal" data-modal="employee-create" @if ($createOpen) data-modal-auto-open="1" @endif hidden>
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
    @endif
</x-layouts.app>
