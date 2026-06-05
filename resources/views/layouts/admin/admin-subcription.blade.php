@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')
        <div class="hall-second">
            @include('components.admin-header')
            @php
                $subscriptions = $subscriptions ?? collect();
                $summary = $subscriptionSummary ?? [
                    'active' => 0,
                    'expiring_soon' => 0,
                    'expired' => 0,
                    'cancelled' => 0,
                    'active_amount' => 0,
                ];
            @endphp

            <section class="admin-subscription">
                @if(session('success'))
                    <div class="subscription-alert subscription-alert--success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="subscription-alert subscription-alert--error">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="subscription-summary">
                    <article>
                        <div class="subscription-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.55 2.91 8.64 7 10 4.09-1.36 7-5.45 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </div>
                        <div class="subscription-summary__body">
                            <span>Active Access</span>
                            <strong>{{ number_format($summary['active']) }}</strong>
                            <small>Customers with premium access</small>
                        </div>
                    </article>

                    <article>
                        <div class="subscription-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/><path d="M14 15h3"/></svg>
                        </div>
                        <div class="subscription-summary__body">
                            <span>Active Value</span>
                            <strong>Rp {{ number_format($summary['active_amount'], 0, ',', '.') }}</strong>
                            <small>Completed subscription value</small>
                        </div>
                    </article>

                    <article>
                        <div class="subscription-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/><path d="M12 7v5l3 2"/></svg>
                        </div>
                        <div class="subscription-summary__body">
                            <span>Renewal Window</span>
                            <strong>{{ number_format($summary['expiring_soon']) }}</strong>
                            <small>Expiring within 7 days</small>
                        </div>
                    </article>

                    <article>
                        <div class="subscription-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                        </div>
                        <div class="subscription-summary__body">
                            <span>Inactive Access</span>
                            <strong>{{ number_format($summary['expired'] + $summary['cancelled']) }}</strong>
                            <small>{{ number_format($summary['expired']) }} expired / {{ number_format($summary['cancelled']) }} cancelled</small>
                        </div>
                    </article>
                </div>

                <div class="subscription-panel">
                    <div class="subscription-panel__toolbar">
                        <div class="subscription-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            <input id="subscriptionSearchInput" type="search" placeholder="Search customer, email, reference">
                        </div>

                        <select id="subscriptionStatusFilter" aria-label="Filter subscription lifecycle" data-datatable-filter data-table="#adminSubscriptionTable" data-column="5">
                            <option value="">All Lifecycle</option>
                            <option value="Pending Payment">Needs Sync</option>
                            <option value="Active">Active</option>
                            <option value="Expiring Soon">Expiring Soon</option>
                            <option value="Expired">Expired</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="admin-datatable">
                        <table id="adminSubscriptionTable" class="table align-middle admin-datatable__table subscription-table js-admin-datatable" data-search="#subscriptionSearchInput" data-unsortable="6">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Package</th>
                                    <th>Payment</th>
                                    <th>Start Subscription</th>
                                    <th>Expires</th>
                                    <th>Lifecycle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscriptions as $subscription)
                                    @php
                                        $payment = $subscription->latestPayment;
                                        $lifecycle = $subscription->lifecycle_status;
                                        $lifecycleLabel = $lifecycle === 'Pending Payment' ? 'Needs Sync' : $lifecycle;
                                        $daysRemaining = $subscription->expires_at
                                            ? (int) now()->startOfDay()->diffInDays($subscription->expires_at->copy()->startOfDay(), false)
                                            : null;
                                        $expiryNote = match (true) {
                                            $subscription->expires_at === null => 'No expiry date',
                                            $lifecycle === 'Cancelled' => 'Access cancelled',
                                            $daysRemaining < 0 => abs($daysRemaining).' days expired',
                                            $daysRemaining === 0 => 'Expires today',
                                            default => $daysRemaining.' days left',
                                        };
                                        $actionLabel = match ($lifecycle) {
                                            'Pending Payment' => 'Fix Access',
                                            'Expiring Soon' => 'Extend',
                                            'Cancelled' => 'Restore',
                                            default => 'Renew',
                                        };
                                        $canAdjustAccess = in_array($lifecycle, ['Pending Payment', 'Expiring Soon', 'Expired', 'Cancelled'], true);
                                        $canCloseAccess = in_array($lifecycle, ['Pending Payment', 'Active', 'Expiring Soon', 'Expired'], true);
                                        $closeLabel = $lifecycle === 'Expired' ? 'Set Free' : 'Cancel';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="subscription-row__user">
                                                <div>{{ strtoupper(substr($subscription->user->name ?? 'U', 0, 1)) }}</div>
                                                <span>
                                                    <strong>{{ $subscription->user->name ?? 'Unknown User' }}</strong>
                                                    <small>{{ $subscription->user->email ?? '-' }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="subscription-row__package">{{ $subscription->package }}</td>
                                        <td>
                                            <div class="subscription-row__payment">
                                                <strong>Rp {{ number_format((int) ($payment?->amount ?? 0), 0, ',', '.') }}</strong>
                                                <span>{{ $payment?->reference ?? 'No payment reference' }}</span>
                                                <small>{{ $payment?->status ?? 'No payment' }}</small>
                                            </div>
                                        </td>
                                        <td class="subscription-row__date">{{ optional($subscription->starts_at)->format('d M Y') ?? '-' }}</td>
                                        <td class="subscription-row__date">
                                            <span>{{ optional($subscription->expires_at)->format('d M Y') ?? '-' }}</span>
                                            <small>{{ $expiryNote }}</small>
                                        </td>
                                        <td data-search="{{ $lifecycle }}">
                                            <span class="subscription-status subscription-status--{{ $subscription->lifecycle_class }}">
                                                {{ $lifecycleLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="subscription-row__actions">
                                                @if($canAdjustAccess)
                                                    <form action="{{ route('admin.subcription.renew', $subscription) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="subscription-row__renew" type="submit">{{ $actionLabel }}</button>
                                                    </form>
                                                @else
                                                    <span class="subscription-row__idle">Current</span>
                                                @endif

                                                @if($canCloseAccess)
                                                    <form action="{{ route('admin.subcription.suspend', $subscription) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="subscription-row__suspend" type="submit">{{ $closeLabel }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
