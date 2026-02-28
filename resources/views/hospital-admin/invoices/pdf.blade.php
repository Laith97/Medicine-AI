<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->stripe_invoice_id ?? $invoice->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #DE6262;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #DE6262;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 36px;
            color: #DE6262;
            margin: 0;
        }
        .invoice-number {
            font-size: 18px;
            color: #666;
            margin: 5px 0;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .billing-info, .invoice-info {
            width: 45%;
        }
        .billing-info h3, .invoice-info h3 {
            color: #DE6262;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-open {
            background-color: #fff3cd;
            color: #856404;
        }
        .line-items {
            margin: 40px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #DE6262;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-section {
            text-align: right;
            margin-top: 30px;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        .total-label {
            min-width: 150px;
            text-align: right;
            margin-right: 20px;
            font-weight: bold;
        }
        .total-amount {
            min-width: 100px;
            text-align: right;
        }
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #DE6262;
            border-top: 2px solid #DE6262;
            padding-top: 10px;
        }
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .payment-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .payment-info h4 {
            color: #DE6262;
            margin-bottom: 15px;
        }
        @media print {
            body { margin: 0; padding: 15px; }
            .container { max-width: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                MedCura AI
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-number">#{{ $invoice->stripe_invoice_id ?? $invoice->id }}</div>
                <div class="status-badge status-{{ $invoice->status }}">
                    {{ ucfirst($invoice->status) }}
                </div>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <div class="billing-info">
                <h3>Bill To</h3>
                <strong>{{ $invoice->user->name ?? 'Hospital Administrator' }}</strong><br>
                @if($invoice->user->hospital)
                    {{ $invoice->user->hospital->name }}<br>
                @endif
                {{ $invoice->user->email }}<br>
                @if($invoice->user->phone)
                    Phone: {{ $invoice->user->phone }}<br>
                @endif
            </div>
            <div class="invoice-info">
                <h3>Invoice Information</h3>
                <strong>Invoice Date:</strong> {{ $invoice->created_at->format('F d, Y') }}<br>
                @if($invoice->due_date)
                    <strong>Due Date:</strong> {{ $invoice->due_date->format('F d, Y') }}<br>
                @endif
                @if($invoice->invoice_month && $invoice->invoice_year)
                    <strong>Billing Period:</strong> {{ date('F Y', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }}<br>
                @endif
                <strong>Invoice Type:</strong> {{ ucfirst($invoice->invoice_type ?? 'subscription') }}<br>
                @if($invoice->paid_at)
                    <strong>Paid On:</strong> {{ $invoice->paid_at->format('F d, Y') }}<br>
                @endif
            </div>
        </div>

        <!-- Description -->
        @if($invoice->description)
            <div class="payment-info">
                <h4>Description</h4>
                <p>{{ $invoice->description }}</p>
            </div>
        @endif

        <!-- Line Items -->
        <div class="line-items">
            <h3 style="color: #DE6262; margin-bottom: 20px;">Invoice Details</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Period</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if($invoice->line_items && count($invoice->line_items) > 0)
                        @foreach($invoice->line_items as $item)
                            <tr>
                                <td>{{ $item['description'] ?? 'Subscription Service' }}</td>
                                <td>
                                    @if($invoice->invoice_month && $invoice->invoice_year)
                                        {{ date('M Y', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }}
                                    @else
                                        One-time
                                    @endif
                                </td>
                                <td>{{ $item['quantity'] ?? 1 }}</td>
                                <td>${{ number_format($item['unit_price'] ?? $invoice->amount_due, 2) }}</td>
                                <td>${{ number_format($item['amount'] ?? $invoice->amount_due, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>{{ ucfirst($invoice->invoice_type ?? 'subscription') }} Service</td>
                            <td>
                                @if($invoice->invoice_month && $invoice->invoice_year)
                                    {{ date('M Y', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }}
                                @else
                                    One-time
                                @endif
                            </td>
                            <td>1</td>
                            <td>${{ number_format($invoice->amount_due, 2) }}</td>
                            <td>${{ number_format($invoice->amount_due, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="total-section">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-amount">${{ number_format($invoice->amount_due, 2) }}</div>
            </div>
            @if($invoice->amount_paid > 0)
                <div class="total-row">
                    <div class="total-label">Amount Paid:</div>
                    <div class="total-amount">-${{ number_format($invoice->amount_paid, 2) }}</div>
                </div>
            @endif
            <div class="total-row grand-total">
                <div class="total-label">
                    @if($invoice->status === 'paid')
                        Total Paid:
                    @else
                        Amount Due:
                    @endif
                </div>
                <div class="total-amount">${{ number_format($invoice->amount_due - $invoice->amount_paid, 2) }}</div>
            </div>
        </div>

        <!-- Payment Information -->
        @if($invoice->status !== 'paid')
            <div class="payment-info">
                <h4>Payment Information</h4>
                <p><strong>Payment is due by {{ $invoice->due_date ? $invoice->due_date->format('F d, Y') : 'immediately' }}.</strong></p>
                @if($invoice->hosted_invoice_url)
                    <p>You can pay online at: <a href="{{ $invoice->hosted_invoice_url }}" target="_blank">{{ $invoice->hosted_invoice_url }}</a></p>
                @endif
                <p>For questions about this invoice, please contact our billing department.</p>
            </div>
        @elseif($invoice->status === 'paid')
            <div class="payment-info">
                <h4>Payment Confirmation</h4>
                <p><strong>✓ This invoice has been paid in full.</strong></p>
                @if($invoice->paid_at)
                    <p>Payment received on {{ $invoice->paid_at->format('F d, Y \a\t g:i A') }}.</p>
                @endif
                <p>Thank you for your prompt payment!</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>MedCura AI - Advanced Medical Diagnostic Platform</p>
            <p>This invoice was generated automatically. For support, contact: info@medcuraai.com</p>
        </div>
    </div>
</body>
</html>