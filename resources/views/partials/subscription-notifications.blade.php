{{-- Subscription Status Notifications --}}
@auth
    @if(Auth::user()->isRestricted())
        <!-- Account Restricted Alert -->
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2); margin-bottom: 1.5rem;">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-ban fa-2x text-danger"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>Account Access Restricted
                    </h5>
                    <p class="mb-2">{{ Auth::user()->getRestrictionMessage() }}</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('invoices.index') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-credit-card me-1"></i> Pay Outstanding Invoices
                        </a>
                        <a href="{{ route('access.restricted') }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-info-circle me-1"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
            <!-- Note: No close button - notification persists until payment -->
        </div>
    @elseif(Auth::user()->isInGracePeriod())
        <!-- Grace Period Warning -->
        <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2); margin-bottom: 1.5rem;">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-clock fa-2x text-warning"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>Subscription Expired - Grace Period
                    </h5>
                    <p class="mb-2">
                        <strong>Your subscription expired on {{ Auth::user()->getSubscriptionEndDate() ? Auth::user()->getSubscriptionEndDate()->format('M d, Y') : 'Unknown Date' }}</strong>
                        <br>
                        You have <strong>{{ Auth::user()->getDaysRemainingInCurrentPeriod() }} days remaining</strong> in your grace period
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('subscription.manage') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-credit-card me-1"></i> Renew Subscription
                        </a>
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-file-invoice-dollar me-1"></i> View Invoices
                        </a>
                    </div>
                </div>
            </div>
            <!-- Note: No close button - notification persists until payment -->
        </div>
    @elseif(Auth::user()->isInWarningPeriod())
        <!-- Warning Period Alert -->
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2); margin-bottom: 1.5rem;">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>Final Warning - Account Will Be Restricted Soon
                    </h5>
                    <p class="mb-2">
                        <strong>Your subscription expired on {{ Auth::user()->getSubscriptionEndDate() ? Auth::user()->getSubscriptionEndDate()->format('M d, Y') : 'Unknown Date' }}</strong>
                        <br>
                        You have <strong>{{ Auth::user()->getDaysRemainingInCurrentPeriod() }} days remaining</strong> before your account is restricted
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('subscription.manage') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-credit-card me-1"></i> Renew Now
                        </a>
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-invoice-dollar me-1"></i> Pay Invoices
                        </a>
                    </div>
                </div>
            </div>
            <!-- Note: No close button - notification persists until payment -->
        </div>
    @endif
@endauth

<style>
/* Ensure notifications are responsive */
@media (max-width: 768px) {
    .alert .d-flex {
        flex-direction: column;
        text-align: center;
    }
    
    .alert .me-3 {
        margin-right: 0 !important;
        margin-bottom: 1rem;
    }
    
    .alert .btn {
        margin-bottom: 0.5rem;
    }
}

/* Pulse animation for urgent notifications */
@keyframes pulse {
    0% { box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2); }
    50% { box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4); }
    100% { box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2); }
}

.alert-warning {
    animation: pulse 2s infinite;
}

@keyframes pulseDanger {
    0% { box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2); }
    50% { box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4); }
    100% { box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2); }
}

.alert-danger {
    animation: pulseDanger 1.5s infinite;
}
</style>