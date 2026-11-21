<x-layouts.app :title="$title">
    <section class="mx-auto max-w-md">
        <x-ui.panel>
            <img
                src="{{ asset('images/checaflow.png') }}"
                alt="ChecaFlow"
                class="cf-brand-mark-lg"
            >
            <h1 class="cf-title mt-5">Entrar</h1>
            <p class="cf-subtitle">Usa el nickname o el correo que te dio tu empresa.</p>
            <form class="mt-6 space-y-4" method="POST" action="{{ route('login.store') }}">
                @csrf
                <x-ui.field label="Nickname">
                    <input class="cf-input" type="text" name="nickname" value="{{ old('nickname') }}" autocomplete="username" required>
                </x-ui.field>
                <x-ui.field label="Contraseña">
                    <input class="cf-input" type="password" name="password" autocomplete="current-password" required>
                </x-ui.field>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" value="1">
                    Recordar este navegador
                </label>
                <button class="cf-btn cf-btn-primary w-full" type="submit">Entrar</button>
            </form>
        </x-ui.panel>
    </section>
</x-layouts.app>
