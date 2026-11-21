<x-layouts.app :title="$title">
    <div class="space-y-5">
        <div class="cf-page-head">
            <div>
                <h1 class="cf-title">Asistencia del día</h1>
                <p class="cf-subtitle">Tablero vivo de marcas por sucursal.</p>
            </div>
            <form class="flex flex-col gap-2 sm:flex-row sm:items-end" method="GET" action="{{ route('admin.attendance.index') }}">
                <x-ui.field label="Fecha">
                    <input class="cf-input" type="date" name="work_date" value="{{ $workDate }}">
                </x-ui.field>
                <x-ui.field label="Sucursal">
                    <select class="cf-select" name="branch_id">
                        <option value="">Todas las sucursales</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id() }}" @selected($branchId === $branch->id())>{{ $branch->name() }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <button class="cf-btn cf-btn-primary" type="submit">Ver</button>
            </form>
        </div>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <x-ui.stat label="Presentes" :value="$present" />
            <x-ui.stat label="Tarde" :value="$late" />
            <x-ui.stat label="En comida" :value="$atLunch" />
            <x-ui.stat label="Incompletos" :value="$incomplete" />
        </section>

        <x-ui.panel class="overflow-hidden p-0 sm:p-0">
            @if ($rows === [])
                <div class="px-6 py-14 text-center">
                    <p class="font-[family-name:var(--font-display)] text-xl font-semibold">Sin registros</p>
                    <p class="mt-2 text-sm text-[var(--color-ink-soft)]">No hay empleados activos para este filtro.</p>
                </div>
            @endif

            @if ($rows !== [])
                <div class="overflow-x-auto">
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
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $row['name'] }}</div>
                                        <div class="text-[var(--color-ink-soft)]">{{ $row['code'] }}</div>
                                    </td>
                                    <td>{{ $row['status'] }}</td>
                                    <td class="font-mono tabular-nums">{{ $row['clockIn'] ?? '—' }}</td>
                                    <td class="font-mono tabular-nums">{{ $row['lunchOut'] ?? '—' }}</td>
                                    <td class="font-mono tabular-nums">{{ $row['lunchIn'] ?? '—' }}</td>
                                    <td class="font-mono tabular-nums">{{ $row['clockOut'] ?? '—' }}</td>
                                    <td>{{ $row['lateMinutes'] }}</td>
                                    <td>{{ $row['workedMinutes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>
    </div>
</x-layouts.app>
