<x-layouts.app :title="$title">
    <div class="space-y-5">
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Reportes</h1>
                <p class="cf-subtitle">Resumen por empleado en el rango elegido.</p>
            </div>
            <form class="flex flex-col gap-2 sm:flex-row sm:items-end" method="GET" action="{{ route('admin.reports.index') }}">
                <x-ui.field label="Desde">
                    <input class="cf-input" type="date" name="from_date" value="{{ $fromDate }}">
                </x-ui.field>
                <x-ui.field label="Hasta">
                    <input class="cf-input" type="date" name="to_date" value="{{ $toDate }}">
                </x-ui.field>
                <x-ui.field label="Sucursal">
                    <select class="cf-select" name="branch_id">
                        <option value="">Todas</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id() }}" @selected($branchId === $branch->id())>{{ $branch->name() }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <button class="cf-btn cf-btn-primary" type="submit">Consultar</button>
            </form>
        </div>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            @if ($rows === [])
                <div class="px-6 py-14 text-center">
                    <p class="font-[family-name:var(--font-display)] text-xl font-semibold">Sin datos</p>
                    <p class="mt-2 text-sm text-[var(--color-ink-soft)]">No hay empleados activos para este filtro.</p>
                </div>
            @endif

            @if ($rows !== [])
                <div class="overflow-x-auto">
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
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $row['name'] }}</div>
                                        <div class="text-[var(--color-ink-soft)]">{{ $row['code'] }}</div>
                                    </td>
                                    <td>{{ $row['presentDays'] }}</td>
                                    <td>{{ $row['lateDays'] }}</td>
                                    <td>{{ $row['absentDays'] }}</td>
                                    <td>{{ $row['workedMinutes'] }}</td>
                                    <td>{{ $row['lateMinutes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>
    </div>
</x-layouts.app>
