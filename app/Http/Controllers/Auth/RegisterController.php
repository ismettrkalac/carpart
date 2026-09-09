<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // User::$casts includes 'password' => 'hashed', which hashes this
        // for us — don't Hash::make() it here too, or it'd double-hash.
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.profile')->with('status', 'Welcome! Your account has been created.');
    }
}
