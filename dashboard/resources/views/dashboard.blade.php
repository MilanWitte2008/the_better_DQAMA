{{-- Overzichtspagina. --}}
@extends('components.layout-dashboard')

{{-- Paginatitel. --}}
@section('title', 'Dashboard')

@section('content')
{{-- Welkomstbericht en afmelden. --}}
<main class="mx-auto mt-20 max-w-xl rounded bg-white p-6 shadow">
    <h1 class="text-2xl font-bold">Welkom, {{ auth()->user()->voornaam }} {{ auth()->user()->achternaam }}</h1>
    <p>Je bent ingelogd.</p>

    <form method="POST" action="/logout" class="mt-4">
        {{-- Beveiligingstoken. --}}
        @csrf
        <button class="rounded bg-indigo-600 px-4 py-2 text-white">Uitloggen</button>
    </form>
</main>
@endsection
