@php
    $notifications = collect($notifications ?? []);
@endphp

<div id="notificationPanel" class="notification-panel" aria-hidden="true">
    <div class="notification-header">
        <h3>Notifications</h3>
        <button id="closeNotification" data-notification-close type="button" aria-label="Close notifications">&times;</button>
    </div>

    <div class="notification-body">
        @forelse($notifications as $notification)
            @php
                $type = $notification['type'] ?? 'default';
            @endphp

            <a class="notification-item notification-item--{{ $type }}" href="{{ $notification['url'] ?? '#' }}">
                <strong>
                    @switch($type)
                        @case('customer')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                            @break

                        @case('n27')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6" /><path d="M8 13h8" /><path d="M8 17h6" /></svg>
                            @break

                        @case('payment')
                        @case('subscription')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18" /><path d="M7 15h4" /></svg>
                            @break

                        @case('download')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="M7 10l5 5 5-5" /><path d="M5 21h14" /></svg>
                            @break

                        @default
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" /></svg>
                    @endswitch

                    {{ $notification['title'] ?? 'Notification' }}
                </strong>

                <p>{{ $notification['message'] ?? '' }}</p>
                <span>{{ $notification['time'] ?? '' }}</span>
            </a>
        @empty
            <div class="notification-empty">
                <strong>{{ $emptyTitle ?? 'No notifications' }}</strong>
                <p>{{ $emptyMessage ?? 'New activity will appear here.' }}</p>
            </div>
        @endforelse
    </div>
</div>
