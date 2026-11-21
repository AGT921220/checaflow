@props(['title'])

@php
    $role = auth()->user()?->role;
    $isAdmin = in_array($role, ['company_admin', 'hr', 'super_admin'], true);
    $isPlatform = $role === 'super_admin';
    $canOwnPhoto = in_array($role, ['company_admin', 'hr', 'employee'], true);
    $routeName = request()->route()?->getName();
    $photoPath = auth()->user()?->photo_path;
    $photoUrl = $photoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($photoPath) : null;
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · ChecaFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="sticky top-0 z-20 border-b border-[var(--color-line)]/80 bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a
                href="{{ auth()->check() ? route(app(\App\Http\Auth\HomeRoute::class)->nameFor(auth()->user()->role)) : route('login') }}"
                class="cf-brand"
            >
                <img
                    src="{{ asset('images/checaflow-logo.png') }}"
                    alt="ChecaFlow"
                    class="cf-brand-mark"
                >
                <span class="sr-only">ChecaFlow</span>
            </a>
            @auth
                <nav class="flex flex-wrap items-center gap-1">
                    @if ($isAdmin)
                        <a class="cf-nav-link" href="{{ route('attendance.page') }}" @if ($routeName === 'attendance.page') aria-current="page" @endif>Asistencia</a>
                        <a class="cf-nav-link" href="{{ route('reports.page') }}" @if ($routeName === 'reports.page') aria-current="page" @endif>Reportes</a>
                        <a class="cf-nav-link" href="{{ route('branches.page') }}" @if ($routeName === 'branches.page') aria-current="page" @endif>Sucursales</a>
                        <a class="cf-nav-link" href="{{ route('schedules.page') }}" @if ($routeName === 'schedules.page') aria-current="page" @endif>Horarios</a>
                        <a class="cf-nav-link" href="{{ route('employees.page') }}" @if ($routeName === 'employees.page') aria-current="page" @endif>Empleados</a>
                    @endif
                    @if ($isPlatform)
                        <a class="cf-nav-link" href="{{ route('platform.home') }}" @if ($routeName === 'platform.home') aria-current="page" @endif>Plataforma</a>
                    @endif
                    @if ($canOwnPhoto)
                        <details class="relative">
                            <summary class="cf-nav-link flex cursor-pointer list-none items-center gap-2">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="" class="h-7 w-7 rounded-full object-cover">
                                @endif
                                Foto
                            </summary>
                            <form class="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-[var(--color-line)] bg-white p-3 shadow-lg" method="POST" action="{{ route('profile.photo.store') }}" enctype="multipart/form-data">
                                @csrf
                                <label class="block text-sm font-medium">Cambiar mi foto
                                    <input class="cf-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                </label>
                                <button class="cf-btn cf-btn-primary mt-3 w-full" type="submit">Subir</button>
                            </form>
                        </details>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="cf-btn cf-btn-ghost ml-1" type="submit">Salir</button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>
    <main class="cf-shell">
        @if ($errors->any())
            <div class="cf-alert-error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif
        @if (session('status'))
            <div class="cf-alert-ok" role="status">
                {{ session('status') }}
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
