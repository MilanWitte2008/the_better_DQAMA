<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        // Zoek de gebruiker op met het opgegeven e-mailadres.
        $user = User::where('email', $data['email'])->first();

        // Haal het opgeslagen wachtwoord op; gebruik een lege tekst als de gebruiker niet bestaat.
        $storedPassword = (string) ($user?->wachtwoord ?? '');
        // Accepteer een veilig gehasht wachtwoord, of een oud wachtwoord dat nog onversleuteld is opgeslagen.
        $passwordMatches = $user !== null && (
            password_verify($data['password'], $storedPassword)
            || hash_equals($storedPassword, $data['password'])
        );

        if (! $passwordMatches || strtolower((string) $user?->status) !== 'actief') {
            // Geef een foutmelding en vul het e-mailadres alvast opnieuw in.
            return back()
                ->with('login_error', 'Controleer je e-mailadres en wachtwoord en probeer het opnieuw.')
                ->onlyInput('email');
        }

        // Meld de gevonden gebruiker aan na het controleren van het wachtwoord.
        Auth::login($user);

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
