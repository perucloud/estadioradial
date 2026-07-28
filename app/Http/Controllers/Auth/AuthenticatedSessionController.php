<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MathCaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, MathCaptcha $captcha): View
    {
        return view('auth.login', [
            'captchaEnabled' => (bool) config('admin.captcha.enabled', true),
            'captcha' => config('admin.captcha.enabled', true) ? $captcha->challenge($request) : null,
        ]);
    }

    public function store(Request $request, MathCaptcha $captcha): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'captcha' => config('admin.captcha.enabled', true) ? ['required', 'integer'] : ['nullable'],
        ]);

        if (config('admin.captcha.enabled', true) && ! $captcha->verifyAndConsume($request, $credentials['captcha'])) {
            $captcha->issue($request);

            throw ValidationException::withMessages([
                'captcha' => 'La respuesta de seguridad es incorrecta o venció.',
            ]);
        }

        $email = mb_strtolower($credentials['email']);
        $key = Str::transliterate($email.'|'.$request->ip());
        $maximumAttempts = (int) config('admin.login.max_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $maximumAttempts)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Vuelve a intentarlo en '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user?->locked_until?->isFuture()) {
            RateLimiter::hit($key, (int) config('admin.login.decay_seconds', 60));

            throw ValidationException::withMessages([
                'email' => 'No fue posible iniciar sesión con esos datos.',
            ]);
        }

        $authenticated = Auth::attempt([
            'email' => $email,
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $authenticated) {
            RateLimiter::hit($key, (int) config('admin.login.decay_seconds', 60));

            if ($user) {
                $attempts = $user->failed_login_attempts + 1;
                $user->forceFill([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $attempts >= $maximumAttempts
                        ? now()->addMinutes((int) config('admin.login.lock_minutes', 15))
                        : null,
                ])->save();
            }

            throw ValidationException::withMessages([
                'email' => 'No fue posible iniciar sesión con esos datos.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'La sesión se cerró correctamente.');
    }
}
