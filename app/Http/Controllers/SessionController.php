<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Auth\HomeRoute;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class SessionController extends Controller
{
    public function create(Request $request, HomeRoute $home): View|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            return redirect()->route($home->nameFor((string) $user->role));
        }

        return view('auth.login', ['title' => 'Entrar']);
    }

    public function store(LoginRequest $request, HomeRoute $home): RedirectResponse
    {
        $user = $this->account($request->string('nickname')->toString());

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'nickname' => 'Estas credenciales no coinciden con nuestros registros.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'nickname' => 'Esta cuenta está inactiva.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($home->nameFor((string) $user->role)));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function account(string $nickname): ?User
    {
        $byNickname = User::query()->where('nickname', $nickname)->first();

        if ($byNickname !== null) {
            return $byNickname;
        }

        return User::query()->where('email', $nickname)->first();
    }
}
