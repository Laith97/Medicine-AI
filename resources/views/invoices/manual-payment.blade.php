@extends('master')

@section('title', 'Payment Instructions')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 20px;">
                <div class="card-header bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-credit-card fa-2x me-3"></i>
                        <div>
                            <h4 class="mb-0">Payment Instructions</h4>
                            <small class="opacity-75">Invoice #{{ $invoice->id }}</small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Invoice Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-file-invoice me-2"></i>Invoice Details
                                </h6>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <strong>Amount Due:</strong><br>
                                        <span class="h4 text-success">${{ number_format($invoice->amount_due, 2) }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Due Date:</strong><br>
                                        {{ $invoice->due_date ? $invoice->due_date->format('M j, Y') : 'N/A' }}
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <strong>Description:</strong><br>
                                    {{ $invoice->description ?: 'Monthly Service Fee' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Payment Status
                                </h6>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning text-dark px-3 py-2">
                                        <i class="fas fa-clock me-1"></i>Awaiting Payment
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Your account access will be restored once payment is received and processed.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="fas fa-info-circle fa-2x me-3 mt-1"></i>
                        <div>
                            <h6 class="alert-heading">Alternative Payment Methods</h6>
                            <p class="mb-0">If you prefer not to use online payment or encountered any issues, you can use the alternative payment methods below.</p>
                        </div>
                    </div>

                    <!-- Payment Options -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-phone fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Call to Pay</h5>
                                    <p class="card-text">Speak with our billing team to process your payment over the phone.</p>
                                    <div class="mt-3">
                                        <strong class="text-success h5">1-800-MEDCURA</strong><br>
                                        <small class="text-muted">Mon-Fri, 9 AM - 6 PM EST</small>
                                    </div>
                                    <a href="tel:1-800-633-2872" class="btn btn-success mt-3">
                                        <i class="fas fa-phone me-2"></i>Call Now
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Email Support</h5>
                                    <p class="card-text">Send us an email with your invoice details and preferred payment method.</p>
                                    <div class="mt-3">
                                        <strong class="text-primary">billing@medcuraai.com</strong><br>
                                        <small class="text-muted">Include Invoice #{{ $invoice->id }}</small>
                                    </div>
                                    <a href="mailto:billing@medcuraai.com?subject=Payment for Invoice #{{ $invoice->id }}&body=Hello,%0D%0A%0D%0AI would like to make a payment for Invoice #{{ $invoice->id }} in the amount of ${{ number_format($invoice->amount_due, 2) }}.%0D%0A%0D%0APlease advise on payment options.%0D%0A%0D%0AThank you." 
                                       class="btn btn-primary mt-3">
                                        <i class="fas fa-envelope me-2"></i>Send Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Transfer Information -->
                    <div class="mt-4">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-university me-2"></i>Bank Transfer / Wire Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Account Name:</strong> MedCura AI Inc.<br>
                                        <strong>Account Number:</strong> 1234567890<br>
                                        <strong>Routing Number:</strong> 021000021
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Bank Name:</strong> Chase Bank<br>
                                        <strong>Reference:</strong> Invoice #{{ $invoice->id }}<br>
                                        <strong>Amount:</strong> ${{ number_format($invoice->amount_due, 2) }}
                                    </div>
                                </div>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <small>
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Important:</strong> Please include Invoice #{{ $invoice->id }} in the transfer reference to ensure proper crediting.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Invoices
                        </a>
                        
                        <div>
                            <a href="{{ route('contact') }}" class="btn btn-outline-info me-2">
                                <i class="fas fa-question-circle me-2"></i>Need Help?
                            </a>
                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Instructions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .btn, .card-header, .alert-info { 
        display: none !important; 
    }
    .card { 
        border: none !important; 
        box-shadow: none !important; 
    }
}
</style>
@endpush
@endsection