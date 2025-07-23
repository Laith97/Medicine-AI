<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: right;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .invoice-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-info-left,
        .invoice-info-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .invoice-info-right {
            text-align: right;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .table .text-right {
            text-align: right;
        }
        .table .text-center {
            text-align: center;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-row {
            margin-bottom: 5px;
        }
        .total-row.final {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-open {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-draft {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">MedCura AI</div>
            <div>Medical Assistant Platform</div>
            <div>info@medcuraai.com</div>
        </div>
        <div class="invoice-title">INVOICE</div>
    </div>

    <div class="invoice-info">
        <div class="invoice-info-left">
            <div class="section-title">Bill To:</div>
            <div><strong>{{ $invoice->user->name }}</strong></div>
            <div>{{ $invoice->user->email }}</div>
        </div>
        <div class="invoice-info-right">
            <div class="section-title">Invoice Details:</div>
            <div><strong>Invoice #:</strong> {{ $invoice->id }}</div>
            <div><strong>Stripe ID:</strong> {{ $invoice->stripe_invoice_id }}</div>
            <div><strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</div>
            @if($invoice->due_date)
                <div><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</div>
            @endif
            <div>
                <strong>Status:</strong> 
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ $invoice->getHumanStatus() }}
                </span>
            </div>
        </div>
    </div>

    <div class="section-title">Description:</div>
    <p>{{ $invoice->description }}</p>

    @if($invoice->line_items && count($invoice->line_items) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->line_items as $item)
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td class="text-center">{{ $item['quantity'] ?? 1 }}</td>
                        <td class="text-right">${{ number_format(($item['unit_amount'] ?? $item['amount']) / 100, 2) }}</td>
                        <td class="text-right">${{ number_format($item['amount'] / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="total-section">
        <div class="total-row">
            <strong>Subtotal: {{ $invoice->getFormattedAmountDue() }}</strong>
        </div>
        @if($invoice->amount_paid > 0)
            <div class="total-row">
                <span style="color: #28a745;">Paid: -{{ $invoice->getFormattedAmountPaid() }}</span>
            </div>
            <div class="total-row final">
                Outstanding: {{ $invoice->getFormattedOutstandingAmount() }}
            </div>
        @else
            <div class="total-row final">
                Total Due: {{ $invoice->getFormattedAmountDue() }}
            </div>
        @endif
    </div>

    @if($invoice->metadata && count($invoice->metadata) > 0)
        <div style="margin-top: 30px;">
            <div class="section-title">Additional Information:</div>
            @foreach($invoice->metadata as $key => $value)
                @if($key !== 'stripe_data')
                    <div><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> 
                        @if(is_array($value))
                            {{ implode(', ', $value) }}
                        @else
                            {{ $value }}
                        @endif
                    </div>
                @endif
            @endforeach
            
            @if(isset($invoice->metadata['stripe_data']) && is_array($invoice->metadata['stripe_data']))
                @php
                    $stripeData = $invoice->metadata['stripe_data'];
                @endphp
                
                @if(isset($stripeData['customer_name']))
                    <div><strong>Customer Name:</strong> {{ $stripeData['customer_name'] }}</div>
                @endif
                
                @if(isset($stripeData['customer_email']))
                    <div><strong>Customer Email:</strong> {{ $stripeData['customer_email'] }}</div>
                @endif
                
                @if(isset($stripeData['billing_reason']))
                    <div><strong>Billing Reason:</strong> {{ ucwords(str_replace('_', ' ', $stripeData['billing_reason'])) }}</div>
                @endif
                
                @if(isset($stripeData['collection_method']))
                    <div><strong>Collection Method:</strong> {{ ucwords(str_replace('_', ' ', $stripeData['collection_method'])) }}</div>
                @endif
                
                @if(isset($stripeData['number']))
                    <div><strong>Invoice Number:</strong> {{ $stripeData['number'] }}</div>
                @endif
            @endif
        </div>
    @endif

    @if($invoice->isPaid())
        <div style="margin-top: 30px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
            <strong style="color: #155724;">✓ Payment Received</strong><br>
            This invoice was paid on {{ $invoice->paid_at->format('M d, Y \a\t g:i A') }}.
        </div>
    @elseif($invoice->isOverdue())
        <div style="margin-top: 30px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">
            <strong style="color: #721c24;">⚠ Overdue</strong><br>
            This invoice was due on {{ $invoice->due_date->format('M d, Y') }}.
        </div>
    @endif

    <div class="footer">
        <div>Thank you for using MedCura AI!</div>
        <div>For questions about this invoice, please contact us at info@medcuraai.com</div>
        <div>Generated on {{ now()->format('M d, Y \a\t g:i A') }}</div>
    </div>
</body>
</html>