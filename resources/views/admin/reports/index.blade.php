<x-layouts.app :title="$title">
    <div
        class="space-y-5"
        data-reports-list
        data-list-url="{{ route('admin.reports.index') }}"
        data-is-platform="{{ $isPlatform ? '1' : '0' }}"
        data-page="1"
    >
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Reportes</h1>
                <p class="cf-subtitle">Resumen por empleado en el rango elegido.</p>
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
                <x-ui.field label="Desde">
                    <input class="cf-input" type="date" name="from_date" data-list-filter>
                </x-ui.field>
                <x-ui.field label="Hasta">
                    <input class="cf-input" type="date" name="to_date" data-list-filter>
                </x-ui.field>
                <x-ui.field label="Sucursal">
                    <select class="cf-select" name="branch_id" data-list-filter data-branch-filter>
                        <option value="">Todas</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id() }}">{{ $branch->name() }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <button class="cf-btn cf-btn-primary" type="button" data-list-apply>Consultar</button>
            </div>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            <div data-list-empty class="px-6 py-14 text-center text-sm text-[var(--color-ink-soft)]" hidden>Sin datos.</div>
            <div class="overflow-x-auto" data-table-wrap hidden>
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Presentes</th>
                            <th>Tarde</th>
                            <th>Ausentes</th>
                            <th>Min. trabajados</th>
                            <th>Min. tarde</th>
                        </tr>
                    </thead>
                    <tbody data-list-rows></tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-[var(--color-line)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between" data-list-pager></div>
        </x-ui.panel>
    </div>
</x-layouts.app>
