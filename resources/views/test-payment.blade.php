<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Payment System Test</h1>
        
        @if($invoices->count() > 0)
            <div class="alert alert-info">
                <h4>Available Test Invoices:</h4>
                <p>Click on any invoice to test the payment flow:</p>
            </div>
            
            <div class="row">
                @foreach($invoices as $invoice)
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Invoice #{{ $invoice->id }}</h5>
                                <p class="card-text">
                                    <strong>Amount:</strong> ${{ number_format($invoice->amount_due, 2) }}<br>
                                    <strong>Type:</strong> {{ ucfirst($invoice->invoice_type) }}<br>
                                    <strong>Status:</strong> {{ $invoice->status }}
                                </p>
                                
                                <div class="btn-group d-block">
                                    <a href="{{ route('invoices.pay', $invoice) }}" class="btn btn-success btn-sm">
                                        🔗 Standard Payment
                                    </a>
                                    <a href="{{ route('invoices.pay', $invoice) }}?direct=1" class="btn btn-primary btn-sm">
                                        ⚡ Direct Payment
                                    </a>
                                    <a href="{{ route('debug.payment', $invoice) }}" class="btn btn-info btn-sm" target="_blank">
                                        🔍 Debug Info
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-warning">
                <h4>No Test Invoices Available</h4>
                <p>Create some test invoices first.</p>
                <button onclick="createTestInvoice()" class="btn btn-primary">Create Test Invoice</button>
            </div>
        @endif
        
        <div class="mt-4">
            <h3>Test Results</h3>
            <div id="testResults" class="alert alert-secondary">
                Ready to test payment flows...
            </div>
        </div>
    </div>

    <script>
        function createTestInvoice() {
            fetch('/create-test-invoice', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error creating test invoice: ' + data.message);
                }
            });
        }
        
        // Log payment test results
        function logResult(message) {
            const results = document.getElementById('testResults');
            results.innerHTML += '<div>' + new Date().toLocaleTimeString() + ': ' + message + '</div>';
        }
        
        // Test payment URLs via AJAX
        function testPaymentUrl(invoiceId) {
            fetch('/debug/payment/' + invoiceId)
                .then(response => response.json())
                .then(data => {
                    logResult(`Invoice ${data.invoice_id}: ${data.is_stripe ? '✅ Stripe URL' : '❌ Not Stripe'} (${data.url_length} chars)`);
                })
                .catch(error => {
                    logResult(`Invoice ${invoiceId}: ❌ Error - ${error.message}`);
                });
        }
    </script>
</body>
</html>