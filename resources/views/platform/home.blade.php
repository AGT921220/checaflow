<x-layouts.app :title="$title">
    <div class="space-y-6">
        <x-ui.panel>
            <h1 class="cf-title">Plataforma</h1>
            <p class="cf-subtitle">
                Estás en la cuenta {{ $accountName }}@if ($accountHandle) · {{ $accountHandle }}@endif. No perteneces a una empresa y no ves la asistencia de ninguna.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.stat label="Empresas registradas" :value="$companyCount" />
                <x-ui.stat label="Empleados activos" :value="$activeEmployees" />
                <x-ui.stat label="Marcas de asistencia" :value="$punchCount" />
                <article class="cf-stat">
                    <p class="cf-stat-label">Empresas por plan</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($companiesByPlan as $planStat)
                            <li>{{ $planStat['segment'] }}: {{ $planStat['count'] }}</li>
                        @endforeach
                    </ul>
                </article>
            </div>
        </x-ui.panel>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
            <x-ui.panel class="overflow-x-auto p-0 sm:p-0">
                <div class="border-b border-[var(--color-line)] px-6 py-4">
                    <h2 class="font-[family-name:var(--font-display)] text-lg font-semibold">Registros</h2>
                </div>
                @if ($rows === [])
                    <p class="px-6 py-8 text-sm text-[var(--color-ink-soft)]">Todavía no hay empresas.</p>
                @endif
                @if ($rows !== [])
                    <table class="cf-table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Plan</th>
                                <th>Empleados</th>
                                <th>Marcas</th>
                                <th>Última marca</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="font-semibold">{{ $row['name'] }}</td>
                                    <td>{{ $row['plan'] }}</td>
                                    <td>{{ $row['employees'] }} / {{ $row['maxEmployees'] }}</td>
                                    <td>{{ $row['punches'] }}</td>
                                    <td>
                                        @if ($row['lastPunch'] === null)
                                            Sin marcas
                                        @endif
                                        @if ($row['lastPunch'] !== null)
                                            {{ $row['lastPunch'] }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-ui.panel>

            <x-ui.panel>
                <h2 class="font-[family-name:var(--font-display)] text-xl font-semibold">Nueva empresa</h2>
                <form class="mt-4 space-y-3" method="POST" action="{{ route('platform.companies.store') }}">
                    @csrf
                    <x-ui.field label="Nombre de la empresa">
                        <input class="cf-input" name="company_name" value="{{ old('company_name') }}" required>
                    </x-ui.field>
                    <x-ui.field label="Plan">
                        <select class="cf-select" name="plan_id" required>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id() }}" @selected((string) old('plan_id') === (string) $plan->id())>
                                    {{ $plan->segment() }} · ${{ $plan->pricePerUserMonthly() }} MXN/usuario/mes
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field label="Máximo de empleados">
                        <input class="cf-input" name="max_employees" type="number" min="1" value="{{ old('max_employees', 9) }}" required>
                    </x-ui.field>
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.field label="Nombre">
                            <input class="cf-input" name="first_name" value="{{ old('first_name') }}" required>
                        </x-ui.field>
                        <x-ui.field label="Apellidos">
                            <input class="cf-input" name="last_name" value="{{ old('last_name') }}" required>
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Nickname">
                        <input class="cf-input" name="nickname" value="{{ old('nickname') }}" required autocapitalize="none" spellcheck="false">
                    </x-ui.field>
                    <x-ui.field label="Correo (opcional)">
                        <input class="cf-input" type="email" name="email" value="{{ old('email') }}">
                    </x-ui.field>
                    <x-ui.field label="Teléfono">
                        <input class="cf-input" name="phone" value="{{ old('phone') }}">
                    </x-ui.field>
                    <x-ui.field label="Contraseña">
                        <input class="cf-input" type="password" name="password" required>
                    </x-ui.field>
                    <button class="cf-btn cf-btn-primary w-full" type="submit">Crear empresa</button>
                </form>
            </x-ui.panel>
        </div>
    </div>
</x-layouts.app>
