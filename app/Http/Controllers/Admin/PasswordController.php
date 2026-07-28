<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'password.changed',
            'subject_type' => $request->user()->getMorphClass(),
            'subject_id' => $request->user()->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Tu contraseña fue actualizada.');
    }
}
