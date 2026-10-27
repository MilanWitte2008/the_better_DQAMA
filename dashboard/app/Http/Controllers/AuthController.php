<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        $storedPassword = (string) ($user?->wachtwoord ?? '');
        $passwordMatches = $user !== null && (
            password_verify($data['password'], $storedPassword)
            || hash_equals($storedPassword, $data['password'])
        );

        if (! $passwordMatches) {
            return back()->withErrors(['email' => 'Inloggegevens zijn onjuist.'])->onlyInput('email');
        }

        if (hash_equals($storedPassword, $data['password'])) {
            $user->wachtwoord = Hash::make($data['password']);
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
