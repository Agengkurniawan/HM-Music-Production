@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')

        <div class="hall-second">
            @include('components.admin-header')

            @php
                $users = $users ?? collect();
            @endphp

            <section class="admin-usermanagement">
                @if(session('success'))
                    <div class="usermanagement-alert usermanagement-alert--success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="usermanagement-alert usermanagement-alert--error">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="usermanagement-summary">
                    <article>
                        <div class="usermanagement-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <span>Total Users</span>
                            <strong>{{ count($users) }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="usermanagement-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <div>
                            <span>Active Accounts</span>
                            <strong>{{ $users->where('status', 'Active')->count() }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="usermanagement-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.36 6.64a9 9 0 1 1-12.72 0"/><path d="M12 2v10"/></svg>
                        </div>
                        <div>
                            <span>Suspended Users</span>
                            <strong>{{ $users->where('status', 'Suspended')->count() }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="usermanagement-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.47 6.03.88-4.36 4.25 1.03 6-5.4-2.84-5.4 2.84 1.03-6-4.36-4.25 6.03-.88L12 3z"/></svg>
                        </div>
                        <div>
                            <span>N27 Requests</span>
                            <strong>{{ $users->sum('sampling_requests_count') }}</strong>
                        </div>
                    </article>
                </div>

                <div class="usermanagement-panel">
                    <div class="usermanagement-panel__toolbar">
                        <div class="usermanagement-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            <input id="userManagementSearchInput" type="search" placeholder="Search name or email">
                        </div>

                        <select id="userManagement" aria-label="Filter user status" data-datatable-filter data-table="#adminUserManagementTable" data-column="3">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Suspended">Suspended</option>
                            <option value="Review">Review</option>
                        </select>

                        <select id="userPlanFilter" aria-label="Filter user plan" data-datatable-filter data-table="#adminUserManagementTable" data-column="1">
                            <option value="">All Plans</option>
                            <option value="Free">Free</option>
                            <option value="Premium Monthly">Premium Monthly</option>
                            <option value="Premium 90 Days">Premium 90 Days</option>
                            <option value="Premium Yearly">Premium Yearly</option>
                            <option value="Studio Pro">Studio Pro</option>
                        </select>
                    </div>

                    <div class="admin-datatable">
                        <table id="adminUserManagementTable" class="table align-middle admin-datatable__table usermanagement-table js-admin-datatable" data-search="#userManagementSearchInput" data-unsortable="6">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Plan</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>N27 Requests</th>
                                    <th>Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    @php
                                        $latestSubscription = $user->latestSubscription;
                                        $latestPayment = $user->latestPayment;
                                        $latestSamplingRequest = $user->latestSamplingRequest;
                                        $latestDownloadSale = $user->latestDownloadSale;
                                        $downloadRevenue = (int) ($user->download_sales_sum_amount ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="usermanagement-row__user">
                                                <div>{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                                <span>
                                                    <strong>{{ $user->name }}</strong>
                                                    <small>{{ $user->email }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="usermanagement-row__plan" data-search="{{ $user->plan }}">{{ $user->plan }}</td>
                                        <td class="usermanagement-row__date">{{ $user->created_at->format('d M Y') }}</td>
                                        <td data-search="{{ $user->status }}">
                                            <span class="usermanagement-status usermanagement-status--{{ strtolower($user->status) }}">
                                                {{ $user->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <a class="usermanagement-row__requests" href="{{ route('admin.sampling-requests') }}">
                                                {{ $user->sampling_requests_count }} requests
                                            </a>
                                        </td>
                                        <td class="usermanagement-row__activity">{{ $user->last_activity ?? 'No activity yet' }}</td>
                                        <td>
                                            <div class="usermanagement-row__actions">
                                                <button type="button"
                                                    title="Manage user"
                                                    aria-label="Manage {{ $user->name }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#userManageModal"
                                                    data-user-action="manage"
                                                    data-user-name="{{ $user->name }}"
                                                    data-user-email="{{ $user->email }}"
                                                    data-user-plan="{{ $user->plan }}"
                                                    data-user-status="{{ $user->status }}"
                                                    data-user-created="{{ $user->created_at->format('d M Y') }}"
                                                    data-user-last-activity="{{ $user->last_activity ?? 'No activity yet' }}"
                                                    data-user-expires="{{ optional($user->activeSubscription?->expires_at)->format('d M Y') ?? '-' }}"
                                                    data-user-expires-value="{{ optional($user->activeSubscription?->expires_at)->format('Y-m-d') }}"
                                                    data-user-requests="{{ $user->sampling_requests_count }}"
                                                    data-user-downloads="{{ $user->download_sales_count }}"
                                                    data-user-payments="{{ $user->payments_count }}"
                                                    data-user-revenue="Rp {{ number_format($downloadRevenue, 0, ',', '.') }}"
                                                    data-subscription-package="{{ $latestSubscription?->package ?? '-' }}"
                                                    data-subscription-status="{{ $latestSubscription?->status ?? 'None' }}"
                                                    data-subscription-starts="{{ optional($latestSubscription?->starts_at)->format('d M Y') ?? '-' }}"
                                                    data-subscription-expires="{{ optional($latestSubscription?->expires_at)->format('d M Y') ?? '-' }}"
                                                    data-payment-reference="{{ $latestPayment?->reference ?? '-' }}"
                                                    data-payment-status="{{ $latestPayment?->status ?? 'None' }}"
                                                    data-payment-method="{{ $latestPayment?->method ?? '-' }}"
                                                    data-payment-package="{{ $latestPayment?->package ?? '-' }}"
                                                    data-payment-amount="Rp {{ number_format((int) ($latestPayment?->amount ?? 0), 0, ',', '.') }}"
                                                    data-payment-date="{{ optional($latestPayment?->created_at)->format('d M Y H:i') ?? '-' }}"
                                                    data-sampling-reference="{{ $latestSamplingRequest?->order_reference ?? '-' }}"
                                                    data-sampling-status="{{ $latestSamplingRequest?->status ?? 'None' }}"
                                                    data-sampling-payment-status="{{ $latestSamplingRequest?->payment_status ?? '-' }}"
                                                    data-sampling-product="{{ $latestSamplingRequest?->product_name ?? '-' }}"
                                                    data-sampling-pack="{{ $latestSamplingRequest?->pack_name ?? '-' }}"
                                                    data-sampling-n27="{{ optional($latestSamplingRequest?->n27_uploaded_at)->format('d M Y H:i') ?? '-' }}"
                                                    data-sampling-delivered="{{ optional($latestSamplingRequest?->delivered_at)->format('d M Y H:i') ?? '-' }}"
                                                    data-download-style="{{ $latestDownloadSale?->style_name ?? '-' }}"
                                                    data-download-file="{{ $latestDownloadSale?->file_name ?? '-' }}"
                                                    data-download-status="{{ $latestDownloadSale?->status ?? 'None' }}"
                                                    data-download-date="{{ optional($latestDownloadSale?->downloaded_at)->format('d M Y H:i') ?? '-' }}"
                                                    data-user-access-url="{{ route('admin.usermanagement.sync-access', $user) }}"
                                                    data-user-status-url="{{ route('admin.usermanagement.status', $user) }}"
                                                    data-user-plan-url="{{ route('admin.usermanagement.plan', $user) }}"
                                                    data-user-password-url="{{ route('admin.usermanagement.password', $user) }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.4 2.6a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                                                </button>
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

@include('components.modal')
@endsection

@push('script')
<script>
    document.querySelectorAll('[data-user-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetSelector = button.getAttribute('data-bs-target');
            const modal = document.querySelector(targetSelector);

            if (!modal) return;

            const userName = button.dataset.userName || 'Selected user';
            const userEmail = button.dataset.userEmail || '-';
            const userStatus = button.dataset.userStatus || 'Review';
            const userPlan = button.dataset.userPlan || 'Free';

            modal.querySelectorAll('[data-user-name]').forEach((target) => {
                target.textContent = userName;
            });

            modal.querySelectorAll('[data-user-email]').forEach((target) => {
                target.textContent = userEmail;
            });

            const formActions = {
                '[data-user-access-form]': button.dataset.userAccessUrl,
                '[data-user-status-form]': button.dataset.userStatusUrl,
                '[data-user-plan-form]': button.dataset.userPlanUrl,
                '[data-user-password-form]': button.dataset.userPasswordUrl,
            };

            Object.entries(formActions).forEach(([selector, action]) => {
                const form = modal.querySelector(selector);
                if (form && action) form.action = action;
            });

            const textTargets = {
                '[data-user-profile-plan]': userPlan,
                '[data-user-profile-status]': userStatus,
                '[data-user-profile-created]': button.dataset.userCreated || '-',
                '[data-user-profile-activity]': button.dataset.userLastActivity || 'No activity yet',
                '[data-user-profile-expires]': button.dataset.userExpires || '-',
                '[data-user-profile-requests]': `${button.dataset.userRequests || '0'} requests`,
                '[data-user-profile-downloads]': `${button.dataset.userDownloads || '0'} downloads`,
                '[data-user-profile-payments]': `${button.dataset.userPayments || '0'} payments`,
                '[data-user-profile-revenue]': button.dataset.userRevenue || 'Rp 0',
                '[data-subscription-package]': button.dataset.subscriptionPackage || '-',
                '[data-subscription-status]': button.dataset.subscriptionStatus || 'None',
                '[data-subscription-starts]': button.dataset.subscriptionStarts || '-',
                '[data-subscription-expires]': button.dataset.subscriptionExpires || '-',
                '[data-payment-reference]': button.dataset.paymentReference || '-',
                '[data-payment-status]': button.dataset.paymentStatus || 'None',
                '[data-payment-method]': button.dataset.paymentMethod || '-',
                '[data-payment-package]': button.dataset.paymentPackage || '-',
                '[data-payment-amount]': button.dataset.paymentAmount || 'Rp 0',
                '[data-payment-date]': button.dataset.paymentDate || '-',
                '[data-sampling-reference]': button.dataset.samplingReference || '-',
                '[data-sampling-status]': button.dataset.samplingStatus || 'None',
                '[data-sampling-payment-status]': button.dataset.samplingPaymentStatus || '-',
                '[data-sampling-product]': button.dataset.samplingProduct || '-',
                '[data-sampling-pack]': button.dataset.samplingPack || '-',
                '[data-sampling-n27]': button.dataset.samplingN27 || '-',
                '[data-sampling-delivered]': button.dataset.samplingDelivered || '-',
                '[data-download-style]': button.dataset.downloadStyle || '-',
                '[data-download-file]': button.dataset.downloadFile || '-',
                '[data-download-status]': button.dataset.downloadStatus || 'None',
                '[data-download-date]': button.dataset.downloadDate || '-',
            };

            Object.entries(textTargets).forEach(([selector, value]) => {
                modal.querySelectorAll(selector).forEach((target) => {
                    target.textContent = value;
                });
            });

            const statusInput = modal.querySelector('[data-user-status-input]');
            const planInput = modal.querySelector('[data-user-plan-input]');
            const expiresInput = modal.querySelector('[data-user-expires-input]');
            const statusNoteInput = modal.querySelector('[data-user-status-note-input]');
            const cancelSubscriptionInput = modal.querySelector('[data-user-cancel-subscription-input]');
            const passwordInput = modal.querySelector('[data-user-password-input]');
            const passwordConfirmInput = modal.querySelector('[data-user-password-confirmation-input]');

            if (statusInput) statusInput.value = userStatus;
            if (planInput) planInput.value = userPlan;
            if (expiresInput) expiresInput.value = button.dataset.userExpiresValue || '';
            if (statusNoteInput) statusNoteInput.value = '';
            if (cancelSubscriptionInput) cancelSubscriptionInput.checked = false;
            if (passwordInput) passwordInput.value = '';
            if (passwordConfirmInput) passwordConfirmInput.value = '';

            modal.querySelectorAll('[data-show-when-legacy-access]').forEach((target) => {
                target.hidden = button.dataset.paymentStatus !== 'Pending' &&
                    button.dataset.subscriptionStatus !== 'Pending';
            });

            const accessButton = modal.querySelector('[data-user-access-form] button[type="submit"]');
            const canSyncAccess = button.dataset.paymentStatus === 'Pending' ||
                button.dataset.subscriptionStatus === 'Pending';

            if (accessButton) accessButton.disabled = !canSyncAccess;

            const defaultTab = modal.querySelector(canSyncAccess ? '#access-user-tab' : '#status-user-tab');

            if (defaultTab && window.bootstrap?.Tab) {
                bootstrap.Tab.getOrCreateInstance(defaultTab).show();
            }
        });
    });
</script>
@endpush
