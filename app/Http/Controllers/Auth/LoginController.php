<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * The demo credentials are only advertised where the demo account actually exists,
     * so a real installation never shows a password hint on its login page.
     */
    public function create(): View
    {
        $hasDemoAccount = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->exists();

        return view('auth.login', ['demoEmail' => $hasDemoAccount ? DemoSeeder::DEMO_EMAIL : null]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->validated(), $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('quotes.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
