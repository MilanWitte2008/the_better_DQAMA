@php
    $notificationsAvailable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
    $dashboardNotifications = $notificationsAvailable
        ? auth()->user()->notifications()->latest()->limit(5)->get()
        : collect();
    $unreadNotificationCount = $notificationsAvailable
        ? auth()->user()->unreadNotifications()->count()
        : 0;
@endphp
<div class="notification-wrapper">
    <button class="notification" type="button" aria-label="Meldingen"
        aria-expanded="false" aria-controls="notification-panel"
        data-notification-toggle>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
            <path d="M10 21h4" />
        </svg>
        @if ($unreadNotificationCount > 0)
            <span class="notification-count" aria-label="{{ $unreadNotificationCount }} ongelezen">{{ $unreadNotificationCount }}</span>
        @endif
    </button>
    <section class="notification-panel" id="notification-panel" aria-label="Meldingen"
        data-notification-panel aria-live="polite" hidden>
        <div class="notification-panel-heading">
            <span class="notification-panel-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                    <path d="M10 21h4" />
                </svg>
            </span>
            <h2>Meldingen</h2>
        </div>
        @if (!$notificationsAvailable)
            <p class="notification-empty">Meldingen zijn niet beschikbaar. De database moet eerst worden bijgewerkt.</p>
        @elseif ($dashboardNotifications->isEmpty())
            <p class="notification-empty">Je bent helemaal bij. Er zijn geen nieuwe meldingen.</p>
        @else
            <ul class="notification-list">
                @foreach ($dashboardNotifications as $notification)
                    <li class="{{ $notification->read_at ? '' : 'notification-unread' }}">
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button class="notification-link" type="submit">
                            <strong>{{ $notification->data['title'] ?? 'Nieuwe melding' }}</strong>
                            <span>{{ $notification->data['message'] ?? '' }}</span>
                            <time>{{ $notification->created_at->diffForHumans() }}</time>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
