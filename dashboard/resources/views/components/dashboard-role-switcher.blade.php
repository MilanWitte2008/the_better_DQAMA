@php
    $dashboardRoleLabels = [
        'student' => 'Student',
        'docent' => 'Docent',
        'coach' => 'Coach',
        'beheerder' => 'Applicatiebeheerder',
        'teamleider' => 'Teamleider',
    ];
    $availableDashboardRoles = auth()->user()?->dashboardRoleKeys() ?? [];
@endphp
@if(count($availableDashboardRoles) > 1)
    <details class="dashboard-role-switcher">
        <summary>Wissel omgeving</summary>
        <nav aria-label="Andere rollen">
            @foreach($availableDashboardRoles as $dashboardRole)
                <a href="{{ route('dashboard', ['dashboard_role' => $dashboardRole]) }}" @if(($roleKey ?? null) === $dashboardRole) aria-current="page" @endif>
                    {{ $dashboardRoleLabels[$dashboardRole] ?? ucfirst($dashboardRole) }}
                </a>
            @endforeach
        </nav>
    </details>
@endif
