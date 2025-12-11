@extends('layouts.admin')

@section('title', 'Clearinghouse Account Management')

@push('styles')
<style>
    .account-card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border-radius: 12px;
        transition: transform 0.3s ease;
    }

    .account-card:hover {
        transform: translateY(-2px);
    }

    .account-status {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .account-status.active {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .account-status.inactive {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .account-status.pending {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .provider-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2px 8px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .connection-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .connection-indicator.connected {
        background: #198754;
        box-shadow: 0 0 6px rgba(25, 135, 84, 0.4);
    }

    .connection-indicator.disconnected {
        background: #dc3545;
        box-shadow: 0 0 6px rgba(220, 53, 69, 0.4);
    }

    .connection-indicator.connecting {
        background: #ffc107;
        box-shadow: 0 0 6px rgba(255, 193, 7, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border: none;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .metric-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    .action-buttons {
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .account-card:hover .action-buttons {
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div class="clearinghouse-accounts">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1">Clearinghouse Account Management</h2>
                    <p class="text-muted mb-0">Manage and monitor your clearinghouse provider connections</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="showAddAccountModal()">
                    <i class="fas fa-plus me-2"></i>Add New Account
                </button>
            </div>
        </div>
    </div>

    <!-- Metrics Overview -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-primary">{{ $stats['total_accounts'] ?? 0 }}</div>
                        <div class="metric-label">Total Accounts</div>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-building fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-success">{{ $stats['active_accounts'] ?? 0 }}</div>
                        <div class="metric-label">Active Connections</div>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-link fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-warning">{{ $stats['pending_submissions'] ?? 0 }}</div>
                        <div class="metric-label">Pending Submissions</div>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-danger">{{ $stats['failed_submissions'] ?? 0 }}</div>
                        <div class="metric-label">Failed Today</div>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accounts Grid -->
    <div class="row" id="accountsContainer">
        @forelse($accounts ?? [] as $account)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card account-card position-relative">
                <div class="account-status {{ strtolower($account->status ?? 'inactive') }}">
                    {{ ucfirst($account->status ?? 'Inactive') }}
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1">{{ $account->account_name ?? 'Unnamed Account' }}</h5>
                            <span class="provider-badge">{{ $account->provider_name ?? 'Unknown Provider' }}</span>
                        </div>
                        <div class="action-buttons">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="editAccount({{ $account->id }})">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="testConnection({{ $account->id }})">
                                        <i class="fas fa-plug me-2"></i>Test Connection
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteAccount({{ $account->id }})">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="connection-indicator {{ $account->connection_status ?? 'disconnected' }}"></span>
                            <small class="text-muted">
                                {{ $account->connection_status === 'connected' ? 'Connected' :
                                   ($account->connection_status === 'connecting' ? 'Connecting...' : 'Disconnected') }}
                            </small>
                        </div>
                        <small class="text-muted d-block">
                            <i class="fas fa-clock me-1"></i>
                            Last tested: {{ $account->last_tested ? $account->last_tested->diffForHumans() : 'Never' }}
                        </small>
                    </div>

                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold text-primary">{{ $account->successful_submissions ?? 0 }}</div>
                            <small class="text-muted">Success</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-warning">{{ $account->pending_submissions ?? 0 }}</div>
                            <small class="text-muted">Pending</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-danger">{{ $account->failed_submissions ?? 0 }}</div>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-building fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Clearinghouse Accounts</h4>
                <p class="text-muted mb-4">Get started by adding your first clearinghouse account</p>
                <button type="button" class="btn btn-primary" onclick="showAddAccountModal()">
                    <i class="fas fa-plus me-2"></i>Add Your First Account
                </button>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- Add/Edit Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalTitle">Add Clearinghouse Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="accountForm">
                @csrf
                <input type="hidden" id="account_id" name="account_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="account_name" class="form-label">
                                <i class="fas fa-tag me-2"></i>Account Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="account_name" name="account_name" required>
                            <div class="form-text">A descriptive name for this account</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="provider_id" class="form-label">
                                <i class="fas fa-building me-2"></i>Provider <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="provider_id" name="provider_id" required>
                                <option value="">Select Provider</option>
                                @foreach($providers ?? [] as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="api_endpoint" class="form-label">
                            <i class="fas fa-globe me-2"></i>API Endpoint
                        </label>
                        <input type="url" class="form-control" id="api_endpoint" name="api_endpoint"
                               placeholder="https://api.provider.com">
                        <div class="form-text">Leave blank to use default endpoint for selected provider</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="submitter_id" class="form-label">
                                <i class="fas fa-id-card me-2"></i>Submitter ID
                            </label>
                            <input type="text" class="form-control" id="submitter_id" name="submitter_id">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="receiver_id" class="form-label">
                                <i class="fas fa-id-card-alt me-2"></i>Receiver ID
                            </label>
                            <input type="text" class="form-control" id="receiver_id" name="receiver_id">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">
                            <i class="fas fa-sticky-note me-2"></i>Notes
                        </label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Additional configuration notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let accountModal;

document.addEventListener('DOMContentLoaded', function() {
    accountModal = new bootstrap.Modal(document.getElementById('accountModal'));
    loadAccounts();
});

function showAddAccountModal() {
    document.getElementById('accountModalTitle').textContent = 'Add Clearinghouse Account';
    document.getElementById('accountForm').reset();
    document.getElementById('account_id').value = '';
    accountModal.show();
}

function editAccount(accountId) {
    document.getElementById('accountModalTitle').textContent = 'Edit Clearinghouse Account';

    // Load account data
    fetch(`/admin/clearinghouse/accounts/${accountId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const account = data.account;
                document.getElementById('account_id').value = account.id;
                document.getElementById('account_name').value = account.account_name;
                document.getElementById('provider_id').value = account.provider_id;
                document.getElementById('username').value = account.username;
                document.getElementById('password').value = account.password;
                document.getElementById('api_endpoint').value = account.api_endpoint || '';
                document.getElementById('submitter_id').value = account.submitter_id || '';
                document.getElementById('receiver_id').value = account.receiver_id || '';
                document.getElementById('notes').value = account.notes || '';
                accountModal.show();
            }
        })
        .catch(error => {
            console.error('Error loading account:', error);
            alert('Error loading account details');
        });
}

function testConnection(accountId) {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';

    fetch(`/admin/clearinghouse/accounts/${accountId}/test-connection`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection test successful!');
            loadAccounts(); // Refresh the accounts list
        } else {
            alert('Connection test failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error testing connection:', error);
        alert('Error testing connection');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function deleteAccount(accountId) {
    if (!confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
        return;
    }

    fetch(`/admin/clearinghouse/accounts/${accountId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadAccounts(); // Refresh the accounts list
        } else {
            alert('Error deleting account: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting account:', error);
        alert('Error deleting account');
    });
}

function loadAccounts() {
    fetch('/admin/clearinghouse/accounts/data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stats
                updateStats(data.stats);
                // Re-render accounts grid
                renderAccounts(data.accounts);
            }
        })
        .catch(error => {
            console.error('Error loading accounts:', error);
        });
}

function updateStats(stats) {
    document.querySelector('.metric-value.text-primary').textContent = stats.total_accounts || 0;
    document.querySelector('.metric-value.text-success').textContent = stats.active_accounts || 0;
    document.querySelector('.metric-value.text-warning').textContent = stats.pending_submissions || 0;
    document.querySelector('.metric-value.text-danger').textContent = stats.failed_submissions || 0;
}

function renderAccounts(accounts) {
    const container = document.getElementById('accountsContainer');
    // This would re-render the accounts grid - simplified for now
    location.reload(); // Simple refresh for now
}

// Handle form submission
document.getElementById('accountForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const accountId = formData.get('account_id');
    const method = accountId ? 'PUT' : 'POST';
    const url = accountId ? `/admin/clearinghouse/accounts/${accountId}` : '/admin/clearinghouse/accounts';

    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            accountModal.hide();
            loadAccounts(); // Refresh the accounts list
        } else {
            alert('Error saving account: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving account:', error);
        alert('Error saving account');
    });
});
</script>
@endsection
