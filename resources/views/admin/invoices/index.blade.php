@extends('layouts.admin')

@section('title', 'Invoice Management')

@push('styles')
<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 1rem 0;
    }
    
    .invoice-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 1.5rem;
    }
    
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: none;
        height: 100%;
    }

    .table td small {
        font-size: 0.7rem;
    }

    .table .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }

    .table .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }

    /* Column widths for invoices table */
    .table th:nth-child(1), .table td:nth-child(1) { width: 12%; }
    .table th:nth-child(2), .table td:nth-child(2) { width: 18%; }
    .table th:nth-child(3), .table td:nth-child(3) { width: 20%; }
    .table th:nth-child(4), .table td:nth-child(4) { width: 10%; }
    .table th:nth-child(5), .table td:nth-child(5) { width: 10%; }
    .table th:nth-child(6), .table td:nth-child(6) { width: 10%; }
    .table th:nth-child(7), .table td:nth-child(7) { width: 10%; }
    .table th:nth-child(8), .table td:nth-child(8) { width: 10%; }

    /* Pagination styling */
    .pagination {
        margin-bottom: 0;
    }

    .pagination .page-link {
        color: #DE6262;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        margin: 0 0.125rem;
    }

    .pagination .page-link:hover {
        color: white;
        background-color: #DE6262;
        border-color: #DE6262;
    }

    .pagination .page-item.active .page-link {
        background-color: #DE6262;
        border-color: #DE6262;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Invoice Management</h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <i class="fas fa-filter"></i> Filters
                    </button>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#generateInvoicesModal">
                        <i class="fas fa-calendar-plus"></i> Generate Monthly
                    </button>
                    <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Invoice
                    </a>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Invoices</h6>
                                    <h4 class="mb-0">{{ $totalInvoices }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-invoice fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Unpaid</h6>
                                    <h4 class="mb-0">${{ number_format($totalUnpaid, 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Paid</h6>
                                    <h4 class="mb-0">${{ number_format($totalPaid, 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Overdue</h6>
                                    <h4 class="mb-0">{{ $overdueCount }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="collapse mb-4" id="filterCollapse">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.invoices.index') }}">
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="user_id" class="form-label">Doctor</label>
                                    <select name="user_id" id="user_id" class="form-select">
                                        <option value="">All Doctors</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary me-2">Clear</a>
                                    <a href="{{ route('admin.invoices.export', request()->query()) }}" class="btn btn-success">
                                        <i class="fas fa-download"></i> Export CSV
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="invoice-card invoices-table">
                <div class="card-body">
                    @if($invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table custom-table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Doctor</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr class="{{ $invoice->isOverdue() ? 'table-danger' : '' }}">
                                            <td>
                                                <strong>#{{ $invoice->id }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $invoice->stripe_invoice_id }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $invoice->user->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $invoice->user->email }}</small>
                                            </td>
                                            <td>
                                                {{ $invoice->description }}
                                                @if($invoice->line_items && count($invoice->line_items) > 0)
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ count($invoice->line_items) }} item(s)
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $invoice->getFormattedAmountDue() }}</strong>
                                                @if($invoice->amount_paid > 0)
                                                    <br>
                                                    <small class="text-success">Paid: {{ $invoice->getFormattedAmountPaid() }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="{{ $invoice->getStatusBadgeClass() }}">
                                                    {{ $invoice->getHumanStatus() }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($invoice->due_date)
                                                    {{ $invoice->due_date->format('M d, Y') }}
                                                    @if($invoice->isOverdue())
                                                        <br>
                                                        <small class="text-danger">
                                                            {{ $invoice->due_date->diffForHumans() }}
                                                        </small>
                                                    @elseif($invoice->isDueSoon())
                                                        <br>
                                                        <small class="text-warning">
                                                            Due {{ $invoice->due_date->diffForHumans() }}
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">No due date</span>
                                                @endif
                                            </td>
                                            <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(!$invoice->isPaid() && $invoice->status !== 'void')
                                                        <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" 
                                                                    onclick="return confirm('Mark this invoice as paid?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($invoices->hasPages())
                            <div class="pagination-wrapper">
                                {{ $invoices->appends(request()->query())->links() }}
                                <div class="pagination-info">
                                    Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                            <h5>No invoices found</h5>
                            <p class="text-muted">No invoices match your current filters.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Generate Monthly Invoices Modal -->
<div class="modal fade" id="generateInvoicesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.invoices.generate-monthly') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Generate Monthly Invoices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="month" class="form-label">Select Month</label>
                        <input type="month" name="month" id="month" class="form-control" 
                               value="{{ now()->subMonth()->format('Y-m') }}" required>
                        <div class="form-text">
                            This will generate invoices for all doctors who had token usage in the selected month.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Invoices</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection