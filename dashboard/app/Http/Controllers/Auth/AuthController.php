<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Controleer of het e-mailadres en wachtwoord zijn ingevuld en geldig zijn.
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Laravel zoekt de gebruiker en controleert het wachtwoord.
        if (! Auth::attempt($data)) {
            // Geef een foutmelding en vul het e-mailadres alvast opnieuw in.
            return back()->withErrors(['email' => 'Inloggegevens zijn onjuist.'])->onlyInput('email');
        }

        // Vernieuw de sessie nadat de gebruiker is ingelogd.
        $request->session()->regenerate();

        // Stuur door naar de pagina die de gebruiker wilde openen, of anders naar het dashboard.
        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        // Meld de gebruiker af.
        Auth::logout();
        // Maak de huidige sessie ongeldig en vernieuw het CSRF-token.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Stuur de gebruiker terug naar de startpagina.
        return redirect('/');
    }
}
