@extends('master')

@section('title', 'My Invoices')

@push('styles')
<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .subscription-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .subscription-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
    
    .stats-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        height: 100%;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(222, 98, 98, 0.15);
    }

    /* Enhanced Table Styling */
    .table-custom {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .table-custom thead th {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table-custom tbody td {
        padding: 1rem;
        border-color: #f1f3f4;
        vertical-align: middle;
    }
    
    .table-custom tbody tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }

    /* Page Header */
    .page-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
    
    .page-header h1 {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    /* Enhanced stat numbers */
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }

    /* Legacy class mapping for backward compatibility */
    .invoice-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .invoice-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-file-invoice-dollar me-2"></i>My Invoices</h1>
                            <p class="text-muted mb-0">View and manage your billing history</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn-custom-secondary" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <i class="fas fa-filter"></i> Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-danger text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-2 opacity-75">Total Unpaid</h6>
                                    <div class="stat-number text-white">${{ number_format($totalUnpaid, 2) }}</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-2 opacity-75">Total Paid</h6>
                                    <div class="stat-number text-white">${{ number_format($totalPaid, 2) }}</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-2 opacity-75">Last Payment</h6>
                                    <div class="stat-number text-white">
                                        @if($lastPaidInvoice)
                                            {{ $lastPaidInvoice->paid_at->format('M d') }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-warning text-dark">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-2 opacity-75">Next Due</h6>
                                    <div class="stat-number text-dark">
                                        @if($nextDueInvoice)
                                            {{ $nextDueInvoice->due_date->format('M d') }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @if($overdueCount > 0)
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Attention!</strong> You have {{ $overdueCount }} overdue invoice(s). Please pay them immediately to avoid service interruption.
                </div>
            @endif

                <!-- Filters -->
                <div class="collapse mb-4" id="filterCollapse">
                    <div class="subscription-card">
                        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Invoices</h5>
                        <form method="GET" action="{{ route('invoices.index') }}">
                            <div class="row">
                                <div class="col-md-3">
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
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn-custom-primary">
                                        <i class="fas fa-search"></i>Apply Filters
                                    </button>
                                    <a href="{{ route('invoices.index') }}" class="btn-custom-secondary">
                                        <i class="fas fa-times"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="subscription-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="fas fa-table me-2"></i>Invoice History</h4>
                        @if($invoices->count() > 0)
                            <small class="text-muted">{{ $invoices->count() }} invoice(s) found</small>
                        @endif
                    </div>
                    
                    @if($invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
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
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn-sm-custom-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(!$invoice->isPaid())
                                                        <a href="{{ route('invoices.pay', $invoice) }}" class="btn-sm-custom-success" title="Pay Invoice">
                                                            <i class="fas fa-credit-card"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-sm-custom-secondary" title="Download PDF">
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
                        <div class="d-flex justify-content-center">
                            {{ $invoices->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                            <h5>No invoices found</h5>
                            <p class="text-muted">You don't have any invoices yet.</p>
                        </div>
                    @endif
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection