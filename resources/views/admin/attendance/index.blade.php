<x-layouts.app :title="$title">
    <div
        class="space-y-5"
        data-attendance-list
        data-list-url="{{ route('admin.attendance.index') }}"
        data-is-platform="{{ $isPlatform ? '1' : '0' }}"
        data-page="1"
    >
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Asistencia del día</h1>
                <p class="cf-subtitle">Tablero vivo de marcas por sucursal.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                @if ($isPlatform)
                    <x-ui.field label="Empresa">
                        <select class="cf-select" name="company_id" data-list-filter>
                            <option value="">Selecciona</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                @endif
                <x-ui.field label="Fecha">
                    <input class="cf-input" type="date" name="work_date" value="{{ $workDate }}" data-list-filter>
                </x-ui.field>
                <x-ui.field label="Sucursal">
                    <select class="cf-select" name="branch_id" data-list-filter data-branch-filter>
                        <option value="">Todas las sucursales</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id() }}">{{ $branch->name() }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <button class="cf-btn cf-btn-primary" type="button" data-list-apply>Ver</button>
            </div>
        </div>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <article class="cf-stat"><p class="cf-stat-label">Presentes</p><p class="cf-stat-value" data-stat-present>0</p></article>
            <article class="cf-stat"><p class="cf-stat-label">Tarde</p><p class="cf-stat-value" data-stat-late>0</p></article>
            <article class="cf-stat"><p class="cf-stat-label">En comida</p><p class="cf-stat-value" data-stat-lunch>0</p></article>
            <article class="cf-stat"><p class="cf-stat-label">Incompletos</p><p class="cf-stat-value" data-stat-incomplete>0</p></article>
        </section>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            <div data-list-empty class="px-6 py-14 text-center text-sm text-[var(--color-ink-soft)]">Cargando…</div>
            <div class="overflow-x-auto" data-table-wrap hidden>
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Estado</th>
                            <th>Entrada</th>
                            <th>Salida a comer</th>
                            <th>Regreso de comer</th>
                            <th>Salida</th>
                            <th>Tarde</th>
                            <th>Trabajado</th>
                        </tr>
                    </thead>
                    <tbody data-list-rows></tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-[var(--color-line)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between" data-list-pager></div>
        </x-ui.panel>
    </div>
</x-layouts.app>
