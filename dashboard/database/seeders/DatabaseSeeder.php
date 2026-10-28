<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // Maakt voorbeeldrollen en testgebruikers aan voor lokaal gebruik.
    public function run(): void
    {
        // Zorg dat de rollen bestaan en onthoud hun database-ID's.
        // Interne rolcodes worden gekoppeld aan de namen die in de database staan.
        $roles = [
            'student' => ['Student', 'Volgt een opleiding'],
            'docent' => ['Coach', 'Begeleidt studenten'],
            'beheerder' => ['Admin', 'Beheert de applicatie'],
            'teamleider' => ['Teammanager', 'Bekijkt teamoverzichten en voortgang'],
        ];

        $roleIds = [];

        foreach ($roles as $key => [$name, $description]) {
            $roleId = DB::table('rol')->where('naam', $name)->value('idrol');

            if (! $roleId) {
                $roleId = DB::table('rol')->insertGetId([
                    'naam' => $name,
                    'beschrijving' => $description,
                ]);
            }

            $roleIds[$key] = $roleId;
        }

        // Gebruik voor alle bestaande en demo-accounts hetzelfde testwachtwoord.
        $password = Hash::make('test123');
        DB::table('lid')->update(['wachtwoord' => $password]);

        $accounts = [
            ['voornaam' => 'Emma', 'achternaam' => 'Jansen', 'email' => 'emma@example.com', 'rol_idrol' => $roleIds['student']],
            ['voornaam' => 'Sophie', 'achternaam' => 'Visser', 'email' => 'sophie@example.com', 'rol_idrol' => $roleIds['docent']],
            ['voornaam' => 'Iris', 'achternaam' => 'Beheer', 'email' => 'iris@example.com', 'rol_idrol' => $roleIds['beheerder']],
            ['voornaam' => 'Milan', 'achternaam' => 'Manager', 'email' => 'milan@example.com', 'rol_idrol' => $roleIds['teamleider']],
        ];

        foreach ($accounts as $account) {
            DB::table('lid')->updateOrInsert(
                ['email' => $account['email']],
                $account + [
                    'wachtwoord' => $password,
                    'status' => 'actief',
                    'aangemaakt_datum' => now(),
                    'school_idschool' => null,
                ],
            );

            $memberId = DB::table('lid')->where('email', $account['email'])->value('idlid');
            DB::table('rol_has_lid')->insertOrIgnore([
                'rol_idrol' => $account['rol_idrol'],
                'lid_idlid' => $memberId,
            ]);
        }

        // Vul de rollen-koppeltabel ook aan voor bestaande accounts, zoals extra studenten.
        foreach (DB::table('lid')->whereNotNull('rol_idrol')->get(['idlid', 'rol_idrol']) as $member) {
            DB::table('rol_has_lid')->insertOrIgnore([
                'rol_idrol' => $member->rol_idrol,
                'lid_idlid' => $member->idlid,
            ]);
        }
    }
}
