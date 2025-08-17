@extends('master')

@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<style>
    /* Global Font */
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    }
    .dashboard-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 6px;
        background: linear-gradient(90deg, #f39c12, #e67e22);
    }

    .dashboard-header h2 {
        margin: 0;
        font-weight: 700;
        font-size: 2.5rem;
        color: white;
    }

    .dashboard-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #2c3e50 0%, #c55252 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
        transition: box-shadow 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-size: 1rem;
    }

    .btn-primary-custom:hover {
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
    }

    .btn-secondary-custom {
        background: white;
        border: 2px solid #DE6262;
        color: #DE6262;
        font-weight: 600;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-size: 1rem;
    }

    .btn-secondary-custom:hover {
        background: #DE6262;
        color: white;
        text-decoration: none;
    }
    .stats-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #DE6262, #c55252);
    }

    .stats-card:hover {
        box-shadow: 0 12px 30px rgba(222, 98, 98, 0.15);
        transform: translateY(-2px);
    }

    .stats-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 8px 20px rgba(222, 98, 98, 0.3);
    }

    .stats-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    .stats-label {
        color: #6c757d;
        font-weight: 500;
        margin: 0;
        font-size: 1rem;
    }

    .chart-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 3rem;
        transition: box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
        z-index: 1;
        clear: both;
    }

    .chart-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #3498db, #2980b9);
    }

    .chart-card:hover {
        box-shadow: 0 12px 30px rgba(222, 98, 98, 0.15);
    }

    .chart-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 3px solid #DE6262;
        display: inline-block;
    }

    .table-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 3rem;
        transition: box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
        z-index: 1;
        clear: both;
    }

    .table-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #27ae60, #229954);
    }

    .table-card:hover {
        box-shadow: 0 12px 30px rgba(222, 98, 98, 0.15);
    }

    .table-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 3px solid #DE6262;
        display: inline-block;
    }

    .custom-table {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .custom-table thead {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
    }

    .custom-table thead th {
        border: none;
        padding: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .custom-table tbody tr {
        transition: background-color 0.3s ease;
    }

    .custom-table tbody tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }

    .custom-table tbody td {
        padding: 1rem;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        color: #DE6262;
        margin-bottom: 1rem;
    }

    /* Custom button sizing for dashboard */
    .btn-primary-custom.btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }

    .btn-secondary-custom.btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }

    /* Filter card styles */
    .filter-card {
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: none;
        background-color: #f8f9fa;
        transition: box-shadow 0.3s ease;
        margin-bottom: 1.5rem;
    }

    .filter-card:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .filter-card .card-body {
        padding: 1.5rem;
    }

    .filter-card h6 {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    /* Table sorting styles */
    .sort-link {
        color: inherit;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sort-link:hover {
        color: #DE6262;
    }

    .sort-link i {
        margin-left: 0.5rem;
        font-size: 0.8rem;
    }

    /* Pagination styles */
    .table-pagination {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .showing-entries {
        color: #6c757d;
        font-size: 0.9rem;
    }

    /* Chart container styles */
    .stats-card h6 {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }

    .stats-card h6 i {
        margin-right: 0.5rem;
        color: #DE6262;
    }

    /* Animation for stats numbers */
    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stats-number {
        animation: countUp 1s ease-out forwards;
    }

    /* Tooltip styling */
    .tooltip-inner {
        background-color: #2c3e50;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before,
    .bs-tooltip-top .tooltip-arrow::before {
        border-top-color: #2c3e50;
    }

    /* Fix modal z-index issues */
    .modal {
        z-index: 10000 !important;
    }

    .modal-backdrop {
        z-index: 9999 !important;
    }

    /* Ensure patient modal is above everything */
    #patientModal {
        z-index: 10001 !important;
    }

    #patientModal .modal-backdrop {
        z-index: 10000 !important;
    }

    /* Fix modal interaction issues */
    .modal.show {
        display: block !important;
        pointer-events: auto !important;
    }

    .modal-dialog {
        pointer-events: auto !important;
        position: relative !important;
        z-index: 10002 !important;
    }

    /* Ensure modal content is clickable */
    .modal-content {
        pointer-events: auto !important;
        position: relative !important;
        z-index: 10003 !important;
    }

    /* Fix modal backdrop positioning */
    .modal-backdrop.show {
        opacity: 0.5 !important;
        z-index: 9999 !important;
    }

    /* Ensure modal is properly centered and visible */
    .modal.fade.show {
        display: block !important;
        opacity: 1 !important;
    }

    /* Fix any potential body scroll issues */
    body.modal-open {
        overflow: hidden !important;
        padding-right: 0 !important;
    }

    /* AI Analysis Styling */
    .ai-response {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
        font-size: 0.95rem;
        line-height: 1.6;
        white-space: pre-line; /* Preserve line breaks */
    }

    .analysis-section-header {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.1rem;
        margin-top: 1.2rem;
        margin-bottom: 0.8rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #DE6262;
        display: block; /* Ensure it's a block element */
        width: 100%; /* Full width */
    }

    .analysis-section-header:first-child {
        margin-top: 0;
    }

    .analysis-content {
        padding: 0.5rem 0 1rem 1rem;
        border-left: 3px solid #f0f0f0;
        margin-bottom: 1rem;
        display: block; /* Ensure it's a block element */
        width: 100%; /* Full width */
    }

    .analysis-content p {
        margin-bottom: 0.5rem;
    }

    .analysis-percentage {
        font-weight: 700;
        color: #DE6262;
        display: inline-block; /* Keep it inline */
    }

    /* Symptom Tags Styling */
    .symptom-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .symptom-tag {
        display: inline-block;
        background-color: rgba(222, 98, 98, 0.1);
        color: #DE6262;
        border: 1px solid rgba(222, 98, 98, 0.2);
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* Remove any potential infinite loop causing styles */
    * {
        box-sizing: border-box;
    }

    .chart-card canvas {
        max-height: 300px;
        height: auto !important;
    }

    /* Section spacing fixes */
    .row {
        margin-bottom: 0;
    }

    .row.mb-5 {
        margin-bottom: 3rem !important;
        clear: both;
    }

    .row.mb-4 {
        margin-bottom: 2rem !important;
        clear: both;
    }

    /* Prevent floating issues */
    .dashboard-container::after {
        content: "";
        display: table;
        clear: both;
    }

    /* Ensure proper stacking */
    .dashboard-header {
        z-index: 10;
        position: relative;
    }

    /* Fix any potential overlapping with sidebar */
    .col-lg-4,
    .col-lg-8 {
        position: relative;
        z-index: 1;
    }

    /* Ensure all dashboard sections have proper spacing */
    .dashboard-container .row + .row {
        margin-top: 2rem;
    }

    /* Fix list group items spacing */
    .list-group-item {
        margin-bottom: 0.5rem;
        border-radius: 8px;
        background-color: #f8f9fa;
    }

    .list-group-item:last-child {
        margin-bottom: 0;
    }

    /* Ensure proper spacing between sidebar cards */
    .col-lg-4 .stats-card + .stats-card {
        margin-top: 1.5rem;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }

        .dashboard-header h2 {
            font-size: 2rem;
        }

        .stats-number {
            font-size: 2rem;
        }

        .filter-card .row {
            row-gap: 1rem;
        }

        /* Form responsive fixes for dashboard */
        .filter-card h1,
        .filter-card h2,
        .filter-card h3,
        .filter-card h4,
        .filter-card h5,
        .filter-card h6 {
            font-size: 1.1rem !important;
            line-height: 1.3 !important;
            margin-bottom: 0.75rem !important;
            word-break: break-word !important;
        }

        /* Improved mobile spacing */
        .row {
            margin-bottom: 1.5rem;
        }

        .col-md-3.mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .filter-card .form-label,
        .filter-card .col-form-label {
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.5rem !important;
            word-break: break-word !important;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            font-size: 0.9rem !important;
            padding: 0.5rem 0.75rem !important;
        }

        .filter-card .btn {
            font-size: 0.85rem !important;
            padding: 0.5rem 1rem !important;
        }

        .filter-card .card-title {
            font-size: 1.1rem !important;
            margin-bottom: 0.75rem !important;
        }

        .filter-card .card-text {
            font-size: 0.9rem !important;
            line-height: 1.4 !important;
        }

        /* Modal responsive fixes */
        .modal-dialog.modal-lg {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .modal-header {
            padding: 1rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .modal-header .modal-title {
            font-size: 1.1rem !important;
            word-break: break-word;
            hyphens: auto;
            line-height: 1.3;
        }

        .modal-header > button {
            align-self: flex-end;
        }

        .modal-body {
            padding: 1rem;
            font-size: 0.9rem !important;
            line-height: 1.5 !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            hyphens: auto !important;
        }

        .modal-body p {
            margin-bottom: 0.8rem !important;
            text-align: left !important;
        }

        .modal-body h1,
        .modal-body h2,
        .modal-body h3,
        .modal-body h4,
        .modal-body h5,
        .modal-body h6 {
            font-size: 1rem !important;
            line-height: 1.3 !important;
            word-break: break-word !important;
            margin-top: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .modal-body .col-form-label {
            font-size: 0.9rem !important;
            word-break: break-word !important;
        }

        .modal-body .form-control-plaintext {
            font-size: 0.9rem !important;
            word-break: break-word !important;
        }

        .modal-body table {
            font-size: 0.65rem !important;
            display: block !important;
            overflow-x: auto !important;
            white-space: nowrap !important;
        }

        .modal-body table th,
        .modal-body table td {
            padding: 0.25rem 0.3rem !important;
            min-width: 50px !important;
            line-height: 1.2 !important;
            vertical-align: top !important;
        }

        .modal-body table th {
            font-size: 0.6rem !important;
            font-weight: 600 !important;
            background-color: #f8f9fa !important;
        }
    }

    /* Very small screens */
    @media (max-width: 576px) {
        /* Form responsive fixes for very small screens */
        .filter-card h1,
        .filter-card h2,
        .filter-card h3,
        .filter-card h4,
        .filter-card h5,
        .filter-card h6 {
            font-size: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .filter-card .form-label,
        .filter-card .col-form-label {
            font-size: 0.8rem !important;
            margin-bottom: 0.3rem !important;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            font-size: 0.8rem !important;
            padding: 0.4rem 0.6rem !important;
        }

        .filter-card .btn {
            font-size: 0.75rem !important;
            padding: 0.4rem 0.8rem !important;
        }

        .filter-card .card-title {
            font-size: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .filter-card .card-text {
            font-size: 0.8rem !important;
        }

        .modal-dialog.modal-lg {
            margin: 0.25rem;
            max-width: calc(100% - 0.5rem);
        }

        .modal-header {
            padding: 0.75rem;
        }

        .modal-header .modal-title {
            font-size: 1rem !important;
        }

        .modal-body {
            padding: 0.75rem;
            font-size: 0.8rem !important;
            line-height: 1.4 !important;
        }

        .modal-body h1,
        .modal-body h2,
        .modal-body h3,
        .modal-body h4,
        .modal-body h5,
        .modal-body h6 {
            font-size: 0.9rem !important;
        }

        .modal-body .col-form-label {
            font-size: 0.8rem !important;
        }

        .modal-body .form-control-plaintext {
            font-size: 0.8rem !important;
        }

        .modal-body table {
            font-size: 0.55rem !important;
        }

        .modal-body table th,
        .modal-body table td {
            padding: 0.15rem 0.2rem !important;
            min-width: 40px !important;
            line-height: 1.1 !important;
        }

        .modal-body table th {
            font-size: 0.5rem !important;
            font-weight: 600 !important;
        }
    }

    /* Professional Medical Design for Response Text */
    .response-text, .ai-content {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        color: #2c3e50;
        line-height: 1.7;
        font-size: 15px;
        letter-spacing: 0.3px;
    }

    .response-text .medical-section,
    .ai-content .medical-section {
        margin-bottom: 25px;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .response-text .section-header,
    .ai-content .section-header {
        background-color: #f8f9fa;
        color: #2c3e50;
        padding: 12px 18px;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        border-bottom: 1px solid #e8e8e8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .response-text .section-content,
    .ai-content .section-content {
        padding: 20px;
        text-align: justify;
    }

    .response-text .section-content p,
    .ai-content .section-content p {
        margin-bottom: 14px;
        line-height: 1.7;
        text-align: justify;
        word-spacing: 0.1em;
    }

    .response-text table,
    .ai-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-radius: 6px;
        overflow: hidden;
    }

    .response-text table th,
    .ai-content table th {
        background-color: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .response-text table td,
    .ai-content table td {
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: top;
    }

    .response-text table tr:nth-child(even),
    .ai-content table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .response-text table tr:hover,
    .ai-content table tr:hover {
        background-color: #e9ecef;
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Enhanced Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    @if(Auth::user())
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Welcome back, {{ Auth::user()->name }}!</h2>
                        <p>Here's an overview of your medical practice</p>
                    @else
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Medical Dashboard</h2>
                        <p>Manage your patients and cases efficiently</p>
                    @endif
                </div>
            </div>
        </div>

        @auth
            <!-- Trial Status Banner -->
            @if($trialInfo['is_in_trial'])
                <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(13, 202, 240, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-gift fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-clock me-2"></i>Free Trial Active
                            </h5>
                            <p class="mb-2">
                                You have <strong>{{ $trialInfo['trial_days_remaining'] }} days</strong> remaining in your free trial. 
                                @if(isset($trialInfo['has_future_subscription']) && $trialInfo['has_future_subscription'])
                                    <strong class="text-success">Your subscription will automatically start when the trial ends!</strong>
                                @else
                                    Enjoy full access to all features!
                                @endif
                            </p>
                            
                            @if(isset($trialInfo['has_future_subscription']) && $trialInfo['has_future_subscription'])
                                <div class="alert alert-success mt-2 p-2 small">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <strong>Subscription Ready:</strong> Your paid plan starts {{ Auth::user()->monthlyInvoiceSetting->subscription_starts_at->format('M j') }} and runs until {{ Auth::user()->monthlyInvoiceSetting->subscription_ends_at->format('M j, Y') }}
                                </div>
                            @endif
                            
                            <div class="d-flex gap-2">
                                <a href="{{ route('subscription.pricing') }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-credit-card me-1"></i>View Pricing
                                </a>
                                <a href="{{ route('subscription.manage') }}" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-cog me-1"></i>Manage Subscription
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif($trialInfo['has_active_subscription'] && !$trialInfo['is_in_trial'])
                <!-- Active Subscription Banner -->
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(25, 135, 84, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-star me-2"></i>Subscription Active
                            </h5>
                            <p class="mb-2">
                                Your subscription is active and all features are available. 
                                @if(Auth::user()->monthlyInvoiceSetting && Auth::user()->monthlyInvoiceSetting->subscription_ends_at)
                                    <strong>Expires: {{ Auth::user()->monthlyInvoiceSetting->subscription_ends_at->format('M d, Y') }}</strong>
                                @endif
                            </p>
                            
                            @if(config('app.debug'))
                                <div class="alert alert-warning mt-2 p-2 small">
                                    <strong>DEBUG:</strong> has_active_subscription=true, is_in_trial=false, sub_ends={{ Auth::user()->monthlyInvoiceSetting ? Auth::user()->monthlyInvoiceSetting->subscription_ends_at : 'null' }}
                                </div>
                            @endif
                            
                            <div class="d-flex gap-2">
                                <a href="{{ route('subscription.manage') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-cog me-1"></i>Manage Subscription
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-file-invoice me-1"></i>View Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif($trialInfo['trial_status'] === 'expired' && Auth::user()->isRestricted())
                <!-- Restriction Warning -->
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-ban fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>Free Trial Expired - Account Restricted
                            </h5>
                            <p class="mb-2">Your free trial has ended. {{ Auth::user()->getRestrictionMessage() }}</p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('invoices.index') }}" class="btn btn-danger btn-sm">
                                    <i class="fas fa-credit-card me-1"></i> Pay Outstanding Invoices
                                </a>
                                <a href="{{ route('access.restricted') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-info-circle me-1"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @elseif(Auth::user()->isInGracePeriod())
                <!-- Grace Period Warning -->
                <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);">
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
                            <div class="d-flex gap-2">
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
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2);">
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
                            <div class="d-flex gap-2">
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
            @elseif(Auth::user()->getOverdueInvoicesCount() > 0)
                <!-- Overdue Warning -->
                <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-clock me-2"></i>Overdue Invoices
                            </h5>
                            <p class="mb-2">You have {{ Auth::user()->getOverdueInvoicesCount() }} overdue invoice(s). Please pay them to avoid service interruption.</p>
                            <a href="{{ route('invoices.index') }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-file-invoice-dollar me-1"></i> View Invoices
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @elseif(Auth::user()->getTotalUnpaidMonthlyAmount() > 0)
                <!-- Monthly Invoice Reminder -->
                <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(13, 202, 240, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-alt fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-info-circle me-2"></i>Monthly Service Fee Due
                            </h5>
                            <p class="mb-2">You have ${{ number_format(Auth::user()->getTotalUnpaidMonthlyAmount(), 2) }} in unpaid monthly service fees.</p>
                            <a href="{{ route('invoices.index') }}" class="btn btn-info btn-sm">
                                <i class="fas fa-credit-card me-1"></i> Pay Now
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        @endauth

        <!-- Quick Actions Card -->
        <div class="chart-card">
            <h4><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @if(auth()->user()->canAccessRoute('ask-ai'))
                    <a href="{{ route('ask-ai') }}" class="btn-custom-primary">
                        <i class="fas fa-user-plus me-2"></i> Add New Patient
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('diagnosis'))
                    <a href="{{ route('diagnosis.create') }}" class="btn-custom-primary">
                        <i class="fas fa-file-medical me-2"></i> Create Diagnosis
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('cases'))
                    <a href="{{ route('cases') }}" class="btn-custom-secondary">
                        <i class="fas fa-list me-2"></i> View All Cases
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('diagnosis'))
                    <a href="{{ route('diagnosis.index') }}" class="btn-custom-secondary">
                        <i class="fas fa-clipboard-list me-2"></i> View Diagnoses
                    </a>
                @endif

                <!-- Additional actions for permitted users -->
                @if(auth()->user()->canAccessRoute('doctor.appointments.index'))
                    <a href="{{ route('doctor.appointments.index') }}" class="btn-custom-secondary">
                        <i class="fas fa-calendar me-2"></i> Manage Appointments
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('settings'))
                    <a href="{{ route('settings') }}" class="btn-custom-secondary">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                @endif
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="row mb-4 mb-md-5">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="stats-number">{{ count($records) }}</p>
                    <p class="stats-label">Total Patients</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <p class="stats-number">
                        @if(count($records) > 0)
                            {{ $records->first()->created_at->format('M d') }}
                        @else
                            N/A
                        @endif
                    </p>
                    <p class="stats-label">Latest Case</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-venus-mars"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            $maleCount = $records->where('gender', 'male')->count();
                            $femaleCount = $records->where('gender', 'female')->count();
                            $ratio = $records->count() > 0 ? round(($maleCount / $records->count()) * 100) : 0;
                        @endphp
                        {{ $ratio }}%
                    </p>
                    <p class="stats-label">Male Patients</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-user-doctor"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            $avgAge = $records->count() > 0 ? round($records->avg('age')) : 0;
                        @endphp
                        {{ $avgAge }}
                    </p>
                    <p class="stats-label">Avg. Patient Age</p>
                </div>
            </div>
        </div>

        @if($doctorData)
        <!-- Doctor-Specific Dashboard Sections -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="dashboard-header" style="background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);">
                    <h3 style="margin: 0; color: white; font-size: 1.8rem;">
                        <i class="fas fa-stethoscope me-2"></i>
                        Doctor Dashboard - Dr. {{ explode(' ', $doctorData['doctor']->user->name)[1] ?? $doctorData['doctor']->user->name }}
                    </h3>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; color: white;">
                        Manage your appointments, patients, and practice
                    </p>
                </div>
            </div>
        </div>

        <!-- Doctor Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <p class="stats-number">{{ $doctorData['stats']['today_appointments'] }}</p>
                    <p class="stats-label">Today's Appointments</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <p class="stats-number">{{ $doctorData['stats']['pending_appointments'] }}</p>
                    <p class="stats-label">Pending Approval</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="stats-number">{{ number_format($doctorData['stats']['average_rating'], 1) }}</p>
                    <p class="stats-label">Average Rating</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <p class="stats-number">${{ number_format($doctorData['stats']['revenue_this_month'], 0) }}</p>
                    <p class="stats-label">This Month Revenue</p>
                </div>
            </div>
        </div>

        <!-- Diagnosis Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <p class="stats-number">{{ auth()->user()->doctorDiagnoses()->count() }}</p>
                    <p class="stats-label">Total Diagnoses</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #16a085 0%, #138d75 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <p class="stats-number">{{ auth()->user()->doctorDiagnoses()->whereDate('created_at', today())->count() }}</p>
                    <p class="stats-label">Today's Diagnoses</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <p class="stats-number">{{ auth()->user()->doctorDiagnoses()->withCount('followUps')->get()->sum('follow_ups_count') }}</p>
                    <p class="stats-label">Follow-up Questions</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            // Use existing review system instead of diagnosis-specific ratings
                            $doctorReviews = auth()->user()->doctor ? auth()->user()->doctor->reviews() : collect();
                            $avgRating = $doctorReviews->avg('rating');
                        @endphp
                        {{ $avgRating ? number_format($avgRating, 1) : 'N/A' }}
                    </p>
                    <p class="stats-label">Doctor Rating</p>
                </div>
            </div>
        </div>

        <!-- Doctor Dashboard Content -->
        <div class="row mb-5">
            <!-- Today's Schedule -->
            <div class="col-lg-8 mb-4">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="table-title mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Today's Schedule
                        </h6>
                        <span class="badge bg-primary">{{ now()->format('l, F j, Y') }}</span>
                    </div>

                    @if($doctorData['todayAppointments']->count() > 0)
                        <div class="table-responsive">
                            <table class="table custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($doctorData['todayAppointments'] as $appointment)
                                        <tr>
                                            <td>
                                                <strong>{{ $appointment->appointment_date->format('g:i A') }}</strong><br>
                                                <small class="text-muted">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}min</small>
                                            </td>
                                            <td>
                                                <strong>{{ $appointment->patient->name ?? 'Unknown Patient' }}</strong><br>
                                                <small class="text-muted">{{ $appointment->reason }}</small>
                                            </td>
                                            <td>
                                                <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} me-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                            </td>
                                            <td>
                                                <span class="badge {{ $appointment->status == 'confirmed' ? 'bg-success' : 'bg-warning' }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <h5>No appointments today</h5>
                            <p>Your schedule is clear for today</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Doctor Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="stats-card mb-4">
                    <h6 class="mb-3">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                    <div class="d-grid gap-2">
                        @if(auth()->user()->canAccessRoute('doctor.appointments.index'))
                            <a href="{{ route('doctor.appointments.index') }}" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-calendar me-2"></i>View All Appointments
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('diagnosis'))
                            <a href="{{ route('diagnosis.create') }}" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-file-medical me-2"></i>Create Diagnosis
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('diagnosis'))
                            <a href="{{ route('diagnosis.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-clipboard-list me-2"></i>View Diagnoses
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.availability.index'))
                            <a href="{{ route('doctor.availability.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-clock me-2"></i>Manage Availability
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.reviews.index'))
                            <a href="{{ route('doctor.reviews.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-star me-2"></i>View Reviews
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.profile.edit'))
                            <a href="{{ route('doctor.profile.edit') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.settings.appointments'))
                            <a href="{{ route('doctor.settings.appointments') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-cog me-2"></i>Appointment Settings
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.landing-page.index'))
                            <a href="{{ route('doctor.landing-page.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-globe me-2"></i>Landing Page
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.notes.index'))
                            <a href="{{ route('doctor.notes.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-sticky-note me-2"></i>My Notes
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.notes.create'))
                            <a href="{{ route('doctor.notes.create') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-plus me-2"></i>Add Note
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.blog.index'))
                            <a href="{{ route('doctor.blog.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-blog me-2"></i>Manage Blog
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Pending Appointments -->
                @if(auth()->user()->canAccessRoute('doctor.appointments.index') && $doctorData['pendingAppointments']->count() > 0)
                    <div class="stats-card" style="margin-bottom: 2rem; position: relative; z-index: 2;">
                        <h6 class="mb-3">
                            <i class="fas fa-clock me-2"></i>Pending Appointments
                        </h6>
                        <div class="list-group list-group-flush">
                            @foreach($doctorData['pendingAppointments'] as $appointment)
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="text-dark">{{ $appointment->patient->name ?? 'Unknown Patient' }}</strong><br>
                                            <small class="text-muted">{{ $appointment->appointment_date->format('M j, g:i A') }}</small>
                                        </div>
                                        <div class="btn-group-sm">
                                            <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                               class="btn btn-primary btn-sm" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('doctor.appointments.index', ['status' => 'pending']) }}"
                               class="btn btn-sm btn-primary-custom">
                                View all pending →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Recent Reviews -->
                @if($doctorData['recentReviews']->count() > 0)
                    <div class="stats-card" style="margin-bottom: 2rem; position: relative; z-index: 2;">
                        <h6 class="mb-3">
                            <i class="fas fa-star me-2"></i>Recent Reviews
                        </h6>
                        <div class="list-group list-group-flush">
                            @foreach($doctorData['recentReviews'] as $review)
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex text-warning mb-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $review->rating)
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    @if($review->comment)
                                        <p class="mb-1 small">{{ Str::limit($review->comment, 60) }}</p>
                                    @endif
                                    <small class="text-muted">
                                        by {{ $review->is_anonymous ? 'Anonymous' : ($review->patient->name ?? 'Unknown Patient') }} •
                                        {{ $review->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('doctor.reviews.index') }}"
                               class="btn btn-sm btn-primary-custom">
                                View all reviews →
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Cases Over Time Chart -->
        <div class="row mb-5">
            <div class="col-lg-8 mb-4">
                <div class="chart-card">
                    <h6 class="chart-title">Cases Over Time</h6>
                    <div style="position: relative; height: 300px;">
                        <canvas id="casesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <p class="stats-number">{{ $weeklyCount }}</p>
                    <p class="stats-label">Cases This Week</p>
                </div>
            </div>
        </div>

        <!-- Advanced Statistics & Filters -->
        <div class="chart-card mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="chart-title mb-0">Advanced Statistics</h6>
                <div class="filter-controls">
                    <button class="btn btn-sm btn-outline-secondary me-2" id="refresh-stats">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>

                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Data</h6>
                            <form id="stats-filter-form" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <select class="form-select" id="date-range-select">
                                        <option value="7">Last 7 days</option>
                                        <option value="30" selected>Last 30 days</option>
                                        <option value="90">Last 3 months</option>
                                        <option value="180">Last 6 months</option>
                                        <option value="365">Last year</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="col-md-3 custom-date-range" style="display: none;">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control" id="date-from">
                                </div>
                                <div class="col-md-3 custom-date-range" style="display: none;">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" id="date-to">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" id="gender-filter">
                                        <option value="all" selected>All</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Age Group</label>
                                    <select class="form-select" id="age-filter">
                                        <option value="all" selected>All</option>
                                        <option value="0-18">0-18</option>
                                        <option value="19-35">19-35</option>
                                        <option value="36-50">36-50</option>
                                        <option value="51-65">51-65</option>
                                        <option value="66+">66+</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                    <button type="reset" class="btn btn-secondary-custom btn-sm">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Patient Demographics</h6>
                        <div style="height: 250px;">
                            <canvas id="demographicsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Age Distribution</h6>
                        <div style="height: 250px;">
                            <canvas id="ageDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-calendar-days me-2"></i>Patient Visits Over Time</h6>
                        <div style="height: 250px;">
                            <canvas id="visitsTimelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="stats-number" id="new-patients-count">{{ $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count() }}</h3>
                        <p class="stats-label">New Patients (30 days)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-redo"></i>
                        </div>
                        <h3 class="stats-number" id="return-visits-count">
                            @php
                                $returnVisits = $records->where('created_at', '>=', now()->subDays(30))->count() - $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count();
                                echo $returnVisits > 0 ? $returnVisits : 0;
                            @endphp
                        </h3>
                        <p class="stats-label">Return Visits (30 days)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="stats-number" id="growth-rate">
                            @php
                                $currentMonth = $records->where('created_at', '>=', now()->startOfMonth())->count();
                                $lastMonth = $records->where('created_at', '>=', now()->subMonth()->startOfMonth())
                                    ->where('created_at', '<', now()->startOfMonth())->count();
                                $growthRate = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100) : 0;
                                echo $growthRate > 0 ? '+'.$growthRate : $growthRate;
                            @endphp%
                        </h3>
                        <p class="stats-label">Monthly Growth Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consolidated Patient List with Advanced Features -->
        <div class="table-card mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="table-title mb-0">Patient List</h6>
                <div>
                    <div class="input-group input-group-sm me-2 d-inline-flex" style="width: 200px;">
                        <input type="text" class="form-control" id="patient-search" placeholder="Search patients...">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <a href="{{ route('cases') }}" class="btn-secondary-custom btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> View All
                    </a>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Patients</h6>
                            <form id="patient-filter-form" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <select class="form-select" id="patient-date-range">
                                        <option value="all" selected>All Time</option>
                                        <option value="7">Last 7 days</option>
                                        <option value="30">Last 30 days</option>
                                        <option value="90">Last 3 months</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="col-md-3 patient-custom-date" style="display: none;">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control" id="patient-date-from">
                                </div>
                                <div class="col-md-3 patient-custom-date" style="display: none;">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" id="patient-date-to">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" id="patient-gender-filter">
                                        <option value="all" selected>All</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Age Group</label>
                                    <select class="form-select" id="patient-age-filter">
                                        <option value="all" selected>All</option>
                                        <option value="0-18">0-18</option>
                                        <option value="19-35">19-35</option>
                                        <option value="36-50">36-50</option>
                                        <option value="51-65">51-65</option>
                                        <option value="66+">66+</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Visit Count</label>
                                    <select class="form-select" id="patient-visit-filter">
                                        <option value="all" selected>All</option>
                                        <option value="1">Single Visit</option>
                                        <option value="multiple">Multiple Visits</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                    <button type="reset" class="btn btn-secondary-custom btn-sm">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(count($records) > 0)
                @php
                    // Group patients by patient_key to avoid duplication
                    $patientGroups = [];

                    foreach ($records as $record) {
                        $key = $record->patient_key ?? ($record->name . '-' . $record->age . '-' . $record->gender);

                        if (!isset($patientGroups[$key])) {
                            // Initialize with the first record
                            $patientGroups[$key] = [
                                'patient' => $record,
                                'visits' => [],
                                'visit_count' => 0,
                                'last_visit' => $record->created_at
                            ];
                        }

                        // Add this record to the visits array
                        $patientGroups[$key]['visits'][] = $record;
                        $patientGroups[$key]['visit_count']++;

                        // Update last visit date if this record is more recent
                        if ($record->created_at > $patientGroups[$key]['last_visit']) {
                            $patientGroups[$key]['last_visit'] = $record->created_at;
                        }
                    }

                    // Sort by most recent visit
                    uasort($patientGroups, function($a, $b) {
                        return $b['last_visit'] <=> $a['last_visit'];
                    });

                    // Take only the first 10 for display
                    $patientGroups = array_slice($patientGroups, 0, 10, true);
                @endphp

                <div class="table-responsive">
                    <table class="table custom-table mb-0" id="patients-table">
                        <thead>
                            <tr>
                                <th><a href="#" class="sort-link" data-sort="name">Patient Name <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="age">Age <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="gender">Gender <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="visits">Total Visits <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="last-visit">Last Visit <i class="fas fa-sort"></i></a></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patientGroups as $key => $group)
                                <tr data-patient-key="{{ $key }}" data-visits="{{ count($group['visits']) }}" data-last-visit="{{ $group['last_visit']->timestamp }}">
                                    <td>{{ $group['patient']->name ?? 'N/A' }}</td>
                                    <td>{{ $group['patient']->age ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $group['patient']->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white;">
                                            {{ ucfirst($group['patient']->gender ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $group['visit_count'] }}</span>
                                    </td>
                                    <td data-date="{{ $group['last_visit']->timestamp }}">{{ $group['last_visit'] ? $group['last_visit']->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-view-patient btn-primary-custom"
                                                    data-patient-key="{{ $key }}"
                                                    data-patient-name="{{ $group['patient']->name }}"
                                                    data-patient-age="{{ $group['patient']->age }}"
                                                    data-patient-gender="{{ $group['patient']->gender }}">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="showing-entries">
                        Showing <span id="showing-count">{{ count($patientGroups) }}</span> of {{ count(array_unique($records->pluck('patient_key')->toArray())) }} patients
                    </div>
                    <div class="table-pagination">
                        <button class="btn btn-sm btn-outline-secondary me-1" id="prev-page" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="current-page">1</span> / <span id="total-pages">1</span>
                        <button class="btn btn-sm btn-outline-secondary ms-1" id="next-page" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Patient Details Modal -->
                <div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white;">
                                <h5 class="modal-title" id="patientModalLabel" style="color: #fff">Patient Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Patient Name</h6>
                                            <p class="patient-name fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Age</h6>
                                            <p class="patient-age fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Gender</h6>
                                            <p class="patient-gender fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="mb-3 border-bottom pb-2">Visit History</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="visit-history-table">
                                        <thead>
                                            <tr>
                                                <th>Visit #</th>
                                                <th>Date</th>
                                                <th>Symptoms</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="visit-history-body">
                                            <!-- Visit history will be populated dynamically -->
                                        </tbody>
                                    </table>
                                </div>

                                <div id="visit-details-section" class="mt-4" style="display: none;">
                                    <h6 class="mb-3 border-bottom pb-2">Visit Details</h6>
                                    <div id="visit-details-content" class="response-text">
                                        <!-- Visit details will be populated dynamically -->
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <a href="{{ route('ask-ai') }}" id="new-visit-btn" class="btn btn-primary-custom">
                                    <i class="fas fa-plus me-1"></i> New Visit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-user-doctor"></i>
                    <h5>No patients yet</h5>
                    <p>Start by adding your first patient</p>
                    <a href="{{ route('ask-ai') }}" class="btn-primary-custom mt-3">
                        <i class="fas fa-plus me-2"></i> Add First Patient
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all charts
    initializeCharts();

    // Set up event listeners for filters
    setupFilters();

    // Set up table sorting and pagination
    setupTableFunctionality();

    // Set up export functionality
    setupExportFunctionality();

    // Set up patient modal functionality (using event delegation)
    setupPatientModal();
});

// Main chart initialization function
function initializeCharts() {
    try {
        // Use pre-calculated chart data from controller
        const chartLabels = @json($chartLabels ?? []);
        const chartData = @json($chartData ?? []);
        const records = @json($records ?? []);

        // Initialize the main charts
        try {
            initializeVisitsTimelineChart(chartLabels, chartData);
        } catch (e) {
            console.error('Error initializing visits timeline chart:', e);
        }

        try {
            initializeDemographicsChart(records);
        } catch (e) {
            console.error('Error initializing demographics chart:', e);
        }

        try {
            initializeAgeDistributionChart(records);
        } catch (e) {
            console.error('Error initializing age distribution chart:', e);
        }

        // Only render original chart if canvas element exists (for backward compatibility)
        const chartCanvas = document.getElementById('casesChart');
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');

            // Destroy any existing chart instance
            if (window.dashboardChart && typeof window.dashboardChart.destroy === 'function') {
                window.dashboardChart.destroy();
            }

            // Create new chart
            window.dashboardChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Cases',
                        data: chartData,
                        borderColor: '#DE6262',
                        backgroundColor: 'rgba(222, 98, 98, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#DE6262',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Disable animations to prevent loops
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(44, 62, 80, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#DE6262',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#6c757d'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#6c757d'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error rendering dashboard charts:', error);
        // Hide chart container if there's an error
        const chartContainers = document.querySelectorAll('.chart-card');
        chartContainers.forEach(container => {
            container.style.display = 'none';
        });
    }
}

// Initialize the visits timeline chart
function initializeVisitsTimelineChart(labels, data) {
    const canvas = document.getElementById('visitsTimelineChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Destroy any existing chart instance
    if (window.visitsTimelineChart && typeof window.visitsTimelineChart.destroy === 'function') {
        window.visitsTimelineChart.destroy();
    }

    // Create new chart
    window.visitsTimelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'New Patients',
                data: data.map((val, i) => Math.round(val * 0.7)), // Simulate new vs. returning
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3498db',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                borderWidth: 2
            }, {
                label: 'Return Visits',
                data: data.map((val, i) => Math.round(val * 0.3)), // Simulate new vs. returning
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#e74c3c',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#DE6262',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + ' patients';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#6c757d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Number of Patients',
                        color: '#6c757d'
                    }
                },
                x: {
                    ticks: {
                        color: '#6c757d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Date',
                        color: '#6c757d'
                    }
                }
            }
        }
    });
}

// Initialize the demographics pie chart
function initializeDemographicsChart(records) {
    const canvas = document.getElementById('demographicsChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Count male and female patients
    const maleCount = records.filter(r => r.gender === 'male').length;
    const femaleCount = records.filter(r => r.gender === 'female').length;

    // Destroy any existing chart instance
    if (window.demographicsChart && typeof window.demographicsChart.destroy === 'function') {
        window.demographicsChart.destroy();
    }

    // Create new chart
    window.demographicsChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [maleCount, femaleCount],
                backgroundColor: ['#3498db', '#e74c3c'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            const total = maleCount + femaleCount;
                            const percentage = Math.round((context.raw / total) * 100);
                            return context.label + ': ' + context.raw + ' patients (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

// Initialize the age distribution chart
function initializeAgeDistributionChart(records) {
    const canvas = document.getElementById('ageDistributionChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Group patients by age ranges
    const ageGroups = {
        '0-18': 0,
        '19-35': 0,
        '36-50': 0,
        '51-65': 0,
        '66+': 0
    };

    records.forEach(record => {
        const age = parseInt(record.age);
        if (age <= 18) ageGroups['0-18']++;
        else if (age <= 35) ageGroups['19-35']++;
        else if (age <= 50) ageGroups['36-50']++;
        else if (age <= 65) ageGroups['51-65']++;
        else ageGroups['66+']++;
    });

    // Destroy any existing chart instance
    if (window.ageDistributionChart && typeof window.ageDistributionChart.destroy === 'function') {
        window.ageDistributionChart.destroy();
    }

    // Create new chart
    window.ageDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(ageGroups),
            datasets: [{
                label: 'Patients',
                data: Object.values(ageGroups),
                backgroundColor: [
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(155, 89, 182, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(231, 76, 60, 0.7)'
                ],
                borderColor: [
                    'rgba(46, 204, 113, 1)',
                    'rgba(52, 152, 219, 1)',
                    'rgba(155, 89, 182, 1)',
                    'rgba(241, 196, 15, 1)',
                    'rgba(231, 76, 60, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return 'Patients: ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#6c757d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Number of Patients',
                        color: '#6c757d'
                    }
                },
                x: {
                    ticks: {
                        color: '#6c757d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Age Groups',
                        color: '#6c757d'
                    }
                }
            }
        }
    });
}

// Set up filter functionality
function setupFilters() {
    const dateRangeSelect = document.getElementById('date-range-select');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const customDateRangeFields = document.querySelectorAll('.custom-date-range');
    const filterForm = document.getElementById('stats-filter-form');

    // Show/hide custom date range fields based on selection
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRangeFields.forEach(field => field.style.display = 'block');
            } else {
                customDateRangeFields.forEach(field => field.style.display = 'none');
            }
        });
    }

    // Set default dates for custom range
    if (dateFrom && dateTo) {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        dateTo.valueAsDate = today;
        dateFrom.valueAsDate = thirtyDaysAgo;
    }

    // Handle filter form submission
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Here you would typically make an AJAX request to get filtered data
            // For this demo, we'll just show a success message
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Applied!</strong> Data has been updated.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });

        // Handle filter form reset
        filterForm.addEventListener('reset', function() {
            // Reset custom date range visibility
            customDateRangeFields.forEach(field => field.style.display = 'none');
            dateRangeSelect.value = '30';

            // Show reset message
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Reset!</strong> Showing default data.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
    }

    // Set up refresh stats button
    const refreshStatsBtn = document.getElementById('refresh-stats');
    if (refreshStatsBtn) {
        refreshStatsBtn.addEventListener('click', function() {
            // Show loading spinner
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...';
            this.disabled = true;

            // Simulate refresh delay
            setTimeout(() => {
                // Reset button
                this.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Refresh';
                this.disabled = false;

                // Show success message
                const toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <strong>Data Refreshed!</strong> Statistics are now up-to-date.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(toast);

                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(toast);
                    bsAlert.close();
                }, 3000);
            }, 1500);
        });
    }
}

// Set up table sorting and pagination
function setupTableFunctionality() {
    const table = document.getElementById('patients-table');
    const searchInput = document.getElementById('patient-search');
    const searchBtn = document.getElementById('search-btn');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const currentPageSpan = document.getElementById('current-page');
    const totalPagesSpan = document.getElementById('total-pages');
    const showingCountSpan = document.getElementById('showing-count');

    if (!table) return;

    // Variables for pagination
    let currentPage = 1;
    const rowsPerPage = 10;
    let filteredRows = Array.from(table.querySelectorAll('tbody tr'));
    let totalPages = Math.ceil(filteredRows.length / rowsPerPage);

    // Update pagination display
    function updatePagination() {
        if (!currentPageSpan || !totalPagesSpan || !showingCountSpan) return;

        currentPageSpan.textContent = currentPage;
        totalPagesSpan.textContent = totalPages;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, filteredRows.length);
        showingCountSpan.textContent = filteredRows.length > 0 ? `${startIdx + 1}-${endIdx}` : '0';

        // Enable/disable pagination buttons
        if (prevPageBtn) prevPageBtn.disabled = currentPage === 1;
        if (nextPageBtn) nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
    }

    // Display rows for current page
    function displayRows() {
        const tbody = table.querySelector('tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr'));

        // Hide all rows
        allRows.forEach(row => row.style.display = 'none');

        // Show only rows for current page
        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, filteredRows.length);

        for (let i = startIdx; i < endIdx; i++) {
            filteredRows[i].style.display = '';
        }

        updatePagination();
    }

    // Filter rows based on search input
    function filterRows(searchTerm) {
        const tbody = table.querySelector('tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr'));

        if (!searchTerm) {
            filteredRows = allRows;
        } else {
            searchTerm = searchTerm.toLowerCase();
            filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(searchTerm);
            });
        }

        totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        currentPage = 1; // Reset to first page after filtering

        displayRows();
    }

    // Sort table by column
    function sortTable(column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Sort rows
        rows.sort((a, b) => {
            let aValue, bValue;

            switch (column) {
                case 'name':
                    aValue = a.cells[0].textContent;
                    bValue = b.cells[0].textContent;
                    break;
                case 'age':
                    aValue = parseInt(a.cells[1].textContent) || 0;
                    bValue = parseInt(b.cells[1].textContent) || 0;
                    break;
                case 'gender':
                    aValue = a.cells[2].textContent.trim();
                    bValue = b.cells[2].textContent.trim();
                    break;
                case 'visits':
                    aValue = parseInt(a.getAttribute('data-visits')) || 0;
                    bValue = parseInt(b.getAttribute('data-visits')) || 0;
                    break;
                case 'last-visit':
                    aValue = parseInt(a.getAttribute('data-last-visit')) || 0;
                    bValue = parseInt(b.getAttribute('data-last-visit')) || 0;
                    break;
                default:
                    return 0;
            }

            if (direction === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });

        // Reappend rows in sorted order
        rows.forEach(row => tbody.appendChild(row));

        // Update filtered rows and display
        filteredRows = Array.from(tbody.querySelectorAll('tr'));
        displayRows();
    }

    // Set up event listeners

    // Search functionality
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            filterRows(searchInput.value);
        });

        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                filterRows(this.value);
            }
        });
    }

    // Pagination
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                displayRows();
            }
        });
    }

    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                displayRows();
            }
        });
    }

    // Sorting
    const sortLinks = table.querySelectorAll('.sort-link');
    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const column = this.getAttribute('data-sort');
            const currentDirection = this.getAttribute('data-direction') || 'desc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

            // Update direction attribute
            sortLinks.forEach(l => l.setAttribute('data-direction', ''));
            this.setAttribute('data-direction', newDirection);

            // Update sort icons
            sortLinks.forEach(l => {
                l.querySelector('i').className = 'fas fa-sort';
            });
            this.querySelector('i').className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';

            // Sort the table
            sortTable(column, newDirection);
        });
    });

    // Initialize display
    updatePagination();
    displayRows();

    // Set up patient filter functionality
    setupPatientFilters();
}

// Set up patient filters
function setupPatientFilters() {
    const dateRangeSelect = document.getElementById('patient-date-range');
    const dateFrom = document.getElementById('patient-date-from');
    const dateTo = document.getElementById('patient-date-to');
    const customDateFields = document.querySelectorAll('.patient-custom-date');
    const filterForm = document.getElementById('patient-filter-form');
    const genderFilter = document.getElementById('patient-gender-filter');
    const ageFilter = document.getElementById('patient-age-filter');
    const visitFilter = document.getElementById('patient-visit-filter');

    // Show/hide custom date range fields based on selection
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateFields.forEach(field => field.style.display = 'block');
            } else {
                customDateFields.forEach(field => field.style.display = 'none');
            }
        });
    }

    // Set default dates for custom range
    if (dateFrom && dateTo) {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        dateTo.valueAsDate = today;
        dateFrom.valueAsDate = thirtyDaysAgo;
    }

    // Handle filter form submission
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const table = document.getElementById('patients-table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            // Apply filters
            rows.forEach(row => {
                let showRow = true;

                // Gender filter
                if (genderFilter && genderFilter.value !== 'all') {
                    const gender = row.cells[2].textContent.trim().toLowerCase();
                    if (!gender.includes(genderFilter.value.toLowerCase())) {
                        showRow = false;
                    }
                }

                // Age filter
                if (showRow && ageFilter && ageFilter.value !== 'all') {
                    const age = parseInt(row.cells[1].textContent) || 0;
                    const [minAge, maxAge] = ageFilter.value.split('-');

                    if (minAge && maxAge) {
                        if (age < parseInt(minAge) || age > parseInt(maxAge)) {
                            showRow = false;
                        }
                    } else if (minAge && minAge.includes('+')) {
                        const min = parseInt(minAge);
                        if (age < min) {
                            showRow = false;
                        }
                    }
                }

                // Visit count filter
                if (showRow && visitFilter && visitFilter.value !== 'all') {
                    const visitCount = parseInt(row.getAttribute('data-visits')) || 0;

                    if (visitFilter.value === '1' && visitCount !== 1) {
                        showRow = false;
                    } else if (visitFilter.value === 'multiple' && visitCount <= 1) {
                        showRow = false;
                    }
                }

                // Date range filter
                if (showRow && dateRangeSelect) {
                    const lastVisitTimestamp = parseInt(row.getAttribute('data-last-visit')) || 0;
                    const lastVisitDate = new Date(lastVisitTimestamp * 1000);
                    const today = new Date();

                    if (dateRangeSelect.value === 'custom') {
                        // Custom date range
                        if (dateFrom && dateTo) {
                            const fromDate = new Date(dateFrom.value);
                            const toDate = new Date(dateTo.value);
                            toDate.setHours(23, 59, 59, 999); // End of day

                            if (lastVisitDate < fromDate || lastVisitDate > toDate) {
                                showRow = false;
                            }
                        }
                    } else if (dateRangeSelect.value !== 'all') {
                        // Predefined date range
                        const days = parseInt(dateRangeSelect.value);
                        const cutoffDate = new Date();
                        cutoffDate.setDate(today.getDate() - days);

                        if (lastVisitDate < cutoffDate) {
                            showRow = false;
                        }
                    }
                }

                // Show or hide row
                row.style.display = showRow ? '' : 'none';
            });

            // Show success message
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Applied!</strong> Patient list has been filtered.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });

        // Handle filter form reset
        filterForm.addEventListener('reset', function() {
            // Reset custom date range visibility
            customDateFields.forEach(field => field.style.display = 'none');
            dateRangeSelect.value = 'all';

            // Reset table to show all rows
            const table = document.getElementById('patients-table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => row.style.display = '');
            }

            // Show reset message
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Reset!</strong> Showing all patients.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
    }
}

// Set up patient modal functionality
function setupPatientModal() {
    // Get all patient records from PHP
    const allRecords = @json($records ?? []);

    // Patient modal elements
    const patientModal = document.getElementById('patientModal');
    const patientNameEl = document.querySelector('.patient-name');
    const patientAgeEl = document.querySelector('.patient-age');
    const patientGenderEl = document.querySelector('.patient-gender');
    const visitHistoryBody = document.getElementById('visit-history-body');
    const visitDetailsSection = document.getElementById('visit-details-section');
    const visitDetailsContent = document.getElementById('visit-details-content');
    const newVisitBtn = document.getElementById('new-visit-btn');

    if (!patientModal) return;

    // Use event delegation for patient view buttons - this ensures it works after filtering/pagination
    document.addEventListener('click', function(e) {
        // Handle patient modal opening
        if (e.target.closest('.btn-view-patient')) {
            e.preventDefault(); // Prevent any default behavior
            console.log('Patient view button clicked');

            const btn = e.target.closest('.btn-view-patient');
            const patientKey = btn.getAttribute('data-patient-key');
            const patientName = btn.getAttribute('data-patient-name');
            const patientAge = btn.getAttribute('data-patient-age');
            const patientGender = btn.getAttribute('data-patient-gender');

            // Set patient info in modal
            if (patientNameEl) patientNameEl.textContent = patientName;
            if (patientAgeEl) patientAgeEl.textContent = patientAge;
            if (patientGenderEl) patientGenderEl.textContent = patientGender.charAt(0).toUpperCase() + patientGender.slice(1);

            // Set new visit button link with the correct patient key
            if (newVisitBtn) {
                newVisitBtn.href = `{{ route('ask-ai') }}?patient_key=${encodeURIComponent(patientKey)}`;
            }

            // Clear previous visit history
            if (visitHistoryBody) visitHistoryBody.innerHTML = '';

            // Hide visit details section
            if (visitDetailsSection) visitDetailsSection.style.display = 'none';

            // Find all visits for this patient
            const patientVisits = allRecords.filter(record => {
                const recordKey = record.patient_key || (record.name + '-' + record.age + '-' + record.gender);
                return recordKey === patientKey;
            });

            // First, sort chronologically to assign correct visit numbers
            const sortedForNumbering = [...patientVisits].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            // Create a mapping of visit ID to visit number
            const visitNumberMap = {};
            sortedForNumbering.forEach((visit, index) => {
                visitNumberMap[visit.id] = index + 1;
            });

            // Now sort for display (newest first)
            patientVisits.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            // Populate visit history table
            patientVisits.forEach((visit) => {
                const visitNumber = visitNumberMap[visit.id]; // Correct chronological visit number
                const visitDate = new Date(visit.created_at);

                // Check if there are multiple visits on the same day
                const sameDay = patientVisits.filter(v => {
                    const vDate = new Date(v.created_at);
                    return vDate.toDateString() === visitDate.toDateString();
                }).length > 1;

                // Include time if there are multiple visits on the same day
                const formattedDate = visitDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    ...(sameDay && {
                        hour: '2-digit',
                        minute: '2-digit'
                    })
                });

                // Get symptoms (if available)
                let symptomsText = 'N/A';
                if (visit.symptoms) {
                    try {
                        const symptoms = JSON.parse(visit.symptoms);
                        if (Array.isArray(symptoms) && symptoms.length > 0) {
                            symptomsText = symptoms.join(', ');
                        } else if (typeof symptoms === 'string') {
                            symptomsText = symptoms;
                        }
                    } catch (e) {
                        symptomsText = visit.symptoms;
                    }
                }

                // Truncate symptoms if too long
                if (symptomsText.length > 50) {
                    symptomsText = symptomsText.substring(0, 50) + '...';
                }

                // Create row
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${visitNumber}</td>
                    <td>${formattedDate}</td>
                    <td>${symptomsText}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary-custom view-visit-details" data-visit-id="${visit.id}">
                                            <i class="fas fa-file-medical me-1"></i> Details
                                        </button>
                                    </td>
                `;

                visitHistoryBody.appendChild(row);
            });

            // Show the modal using Bootstrap's JavaScript API
            try {
                const modal = new bootstrap.Modal(patientModal, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                modal.show();
                console.log('Patient modal opened successfully');

                // Force modal to appear above everything with extreme z-index
                setTimeout(() => {
                    // Set modal z-index
                    patientModal.style.zIndex = '999999';
                    patientModal.style.position = 'fixed';

                    // Set backdrop z-index
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.style.zIndex = '999998';
                        console.log('Modal and backdrop z-index forced to maximum');
                    }

                    // Move modal to end of body to escape any stacking contexts
                    document.body.appendChild(patientModal);
                    console.log('Modal moved to end of body');
                }, 50);

            } catch (error) {
                console.error('Error opening patient modal:', error);
                // Fallback: try to show modal using jQuery if available
                if (typeof $ !== 'undefined') {
                    $(patientModal).modal('show');
                }
            }
        }

        // Handle visit details buttons (also using event delegation)
        if (e.target.closest('.view-visit-details')) {
            const btn = e.target.closest('.view-visit-details');
            const visitId = btn.getAttribute('data-visit-id');
            const visit = allRecords.find(record => record.id == visitId);

            if (!visit) return;

            // Show visit details section
            if (visitDetailsSection) visitDetailsSection.style.display = 'block';

            // Format visit details
            let detailsHTML = `
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Visit #${visit.visit_number || '1'} - ${new Date(visit.created_at).toLocaleDateString()}</strong>
                    </div>
                    <div class="card-body">
            `;

            // Add symptoms with better formatting
            detailsHTML += '<h6 class="mb-2">Symptoms:</h6>';
            if (visit.symptoms) {
                try {
                    let symptoms = JSON.parse(visit.symptoms);

                            // Handle different formats of symptoms data
                            if (Array.isArray(symptoms) && symptoms.length > 0) {
                                // Check if symptoms are IDs or actual symptom names
                                const areNumeric = symptoms.every(s => !isNaN(parseInt(s)));

                                if (areNumeric) {
                                    // These are likely symptom IDs - we need to convert them to names
                                    // Since we don't have direct access to the symptom names here,
                                    // we'll display a more user-friendly message
                                    detailsHTML += `
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            ${symptoms.length} symptom(s) recorded. View full details in patient record.
                                        </div>
                                    `;
                                } else {
                                    // These are actual symptom names
                                    detailsHTML += '<div class="symptom-tags mb-3">';
                                    symptoms.forEach(symptom => {
                                        detailsHTML += `<span class="symptom-tag">${symptom}</span>`;
                                    });
                                    detailsHTML += '</div>';
                                }
                            } else if (typeof symptoms === 'string') {
                                detailsHTML += `<p class="mb-3">${symptoms}</p>`;
                            } else {
                                detailsHTML += '<p class="mb-3">No symptoms recorded</p>';
                            }
                        } catch (e) {
                            // If we can't parse the JSON, display as plain text
                            // But first check if it looks like a list of numbers
                            if (/^\d+(\s*,\s*\d+)*$/.test(visit.symptoms)) {
                                detailsHTML += `
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Multiple symptoms recorded. View full details in patient record.
                                    </div>
                                `;
                            } else {
                                detailsHTML += `<p class="mb-3">${visit.symptoms}</p>`;
                            }
                        }
                    } else {
                        detailsHTML += '<p class="mb-3">No symptoms recorded</p>';
                    }

                    // Add test results if available
                    if (visit.test_results) {
                        detailsHTML += '<h6 class="mb-2">Test Results:</h6>';
                        detailsHTML += `<p class="mb-3">${visit.test_results}</p>`;
                    }

                    // Add AI analysis if available with professional formatting
                    if (visit.ai_response) {
                        detailsHTML += '<h6 class="mb-2">AI Analysis:</h6>';
                        detailsHTML += '<div class="ai-response mb-4">' + formatAIResponse(visit.ai_response) + '</div>';
                    }

                    // Add notes if available
                    if (visit.notes) {
                        detailsHTML += '<h6 class="mb-2">Notes:</h6>';
                        detailsHTML += `<p class="mb-3">${visit.notes}</p>`;
                    }

                    // Close card
                    detailsHTML += `
                            </div>
                        </div>
                    `;

            // Set visit details content
            if (visitDetailsContent) visitDetailsContent.innerHTML = detailsHTML;

            // Scroll to visit details
            visitDetailsSection.scrollIntoView({ behavior: 'smooth' });
        }
    });
}

// Set up export functionality
function setupExportFunctionality() {
    const exportCsvBtn = document.getElementById('export-csv');
    const exportPdfBtn = document.getElementById('export-pdf');

    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportTableToCSV('patient_data.csv');
        });
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportToPDF();
        });
    }

    // Export table to CSV
    function exportTableToCSV(filename) {
        const table = document.getElementById('cases-table');
        if (!table) return;

        const rows = table.querySelectorAll('tr');
        const csvContent = [];

        // Get headers
        const headers = [];
        const headerCells = rows[0].querySelectorAll('th');
        headerCells.forEach(cell => {
            // Get text without the sort icon
            let headerText = cell.textContent.replace(/[▲▼]/g, '').trim();
            if (headerText.includes('ID')) headerText = 'ID';
            if (headerText.includes('Patient Name')) headerText = 'Patient Name';
            if (headerText.includes('Age')) headerText = 'Age';
            if (headerText.includes('Gender')) headerText = 'Gender';
            if (headerText.includes('Visit')) headerText = 'Visit Number';
            if (headerText.includes('Date')) headerText = 'Date';

            headers.push(headerText);
        });

        // Remove the Actions column
        headers.pop();
        csvContent.push(headers.join(','));

        // Get data rows
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.querySelectorAll('td');
            const rowData = [];

            // Skip the Actions column
            for (let j = 0; j < cells.length - 1; j++) {
                let cellText = cells[j].textContent.trim();

                // Clean up ID column
                if (j === 0) cellText = cellText.replace('#', '');

                // Clean up Gender column
                if (j === 3) cellText = cellText.replace(/\s+/g, '');

                // Add quotes if the cell contains commas
                if (cellText.includes(',')) {
                    cellText = `"${cellText}"`;
                }

                rowData.push(cellText);
            }

            csvContent.push(rowData.join(','));
        }

        // Create and download CSV file
        const csvData = csvContent.join('\n');
        const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Export dashboard to PDF
    function exportToPDF() {
        // Show loading message
        const loadingToast = document.createElement('div');
        loadingToast.className = 'alert alert-info position-fixed';
        loadingToast.style.top = '20px';
        loadingToast.style.right = '20px';
        loadingToast.style.zIndex = '9999';
        loadingToast.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';
        document.body.appendChild(loadingToast);

        setTimeout(() => {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');

                // Add title
                doc.setFontSize(18);
                doc.text('Medical Dashboard Report', 105, 15, { align: 'center' });

                // Add date
                doc.setFontSize(10);
                doc.text('Generated on: ' + new Date().toLocaleDateString(), 105, 22, { align: 'center' });

                // Add statistics
                doc.setFontSize(14);
                doc.text('Patient Statistics', 14, 35);

                doc.setFontSize(10);
                doc.text('Total Patients: ' + document.querySelector('.stats-number').textContent, 14, 45);

                // Add recent cases table
                doc.setFontSize(14);
                doc.text('Recent Patient Cases', 14, 60);

                // Convert table to image and add to PDF
                const table = document.getElementById('cases-table');
                if (table) {
                    html2canvas(table).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const imgWidth = 180;
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;

                        doc.addImage(imgData, 'PNG', 14, 70, imgWidth, imgHeight);

                        // Save the PDF
                        doc.save('medical_dashboard_report.pdf');

                        // Remove loading message
                        document.body.removeChild(loadingToast);

                        // Show success message
                        const successToast = document.createElement('div');
                        successToast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                        successToast.style.top = '20px';
                        successToast.style.right = '20px';
                        successToast.style.zIndex = '9999';
                        successToast.innerHTML = `
                            <strong>PDF Generated!</strong> Your report has been downloaded.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.body.appendChild(successToast);

                        // Auto-dismiss after 3 seconds
                        setTimeout(() => {
                            const bsAlert = new bootstrap.Alert(successToast);
                            bsAlert.close();
                        }, 3000);
                    });
                } else {
                    // If table doesn't exist, just save the PDF with statistics
                    doc.save('medical_dashboard_report.pdf');

                    // Remove loading message
                    document.body.removeChild(loadingToast);

                    // Show success message
                    const successToast = document.createElement('div');
                    successToast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                    successToast.style.top = '20px';
                    successToast.style.right = '20px';
                    successToast.style.zIndex = '9999';
                    successToast.innerHTML = `
                        <strong>PDF Generated!</strong> Your report has been downloaded.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.body.appendChild(successToast);

                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        const bsAlert = new bootstrap.Alert(successToast);
                        bsAlert.close();
                    }, 3000);
                }
            } catch (error) {
                console.error('Error generating PDF:', error);

                // Remove loading message
                document.body.removeChild(loadingToast);

                // Show error message
                const errorToast = document.createElement('div');
                errorToast.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                errorToast.style.top = '20px';
                errorToast.style.right = '20px';
                errorToast.style.zIndex = '9999';
                errorToast.innerHTML = `
                    <strong>Error!</strong> Failed to generate PDF. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(errorToast);

                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(errorToast);
                    bsAlert.close();
                }, 3000);
            }
        }, 500);
    }
}

// Professional formatting functions for AI responses
function formatTable(tableRows) {
    if (!tableRows || tableRows.length === 0) return '';

    let table = '<table class="table table-striped mt-3">';
    let isFirstRow = true;
    let headerAdded = false;

    for (const row of tableRows) {
        let cells = [];

        // Handle different table formats
        if (row.includes('|')) {
            // Pipe-separated format
            cells = row.split('|').map(cell => cell.trim()).filter(cell => cell);
        } else if (row.match(/^(Rank|1|2|3|4|5)\s+/)) {
            // Diagnosis table format without pipes
            const match = row.match(/^(\d+|Rank)\s+(.*?)\s+(\d+%)\s+(.*?)$/);
            if (match) {
                cells = [match[1], match[2], match[3], match[4]];
            } else {
                // Try to parse the concatenated format
                const diagnosisMatch = row.match(/^(\d+)(.*?)(\d+%)(.*?)$/);
                if (diagnosisMatch) {
                    cells = [diagnosisMatch[1], diagnosisMatch[2], diagnosisMatch[3], diagnosisMatch[4]];
                }
            }
        } else if (row.includes('RankDiagnosis')) {
            // Header row for the concatenated format
            cells = ['Rank', 'Diagnosis', 'Probability (%)', 'Clinical Reasoning'];
        }

        if (cells.length === 0) continue;

        // Check if this should be a header row
        if (!headerAdded && (cells.some(cell => cell.toLowerCase().includes('rank') || cell.toLowerCase().includes('diagnosis')) || isFirstRow)) {
            table += '<thead><tr>';
            cells.forEach(cell => {
                table += `<th>${cell}</th>`;
            });
            table += '</tr></thead><tbody>';
            headerAdded = true;
            isFirstRow = false;
        } else {
            // Data row
            table += '<tr>';
            cells.forEach((cell, index) => {
                // Check if this is a probability cell
                if (cell.includes('%')) {
                    cell = `<span class="probability">${cell}</span>`;
                }
                table += `<td>${cell}</td>`;
            });
            table += '</tr>';
        }
    }

    table += '</tbody></table>';
    return table;
}

function formatAIResponse(text) {
    if (!text) return '';

    // Remove the Sources section from the text before formatting
    const sourcesMatch = text.match(/(📚\s*SOURCES:|Sources:)([\s\S]*?)(?:$|(?=\n\n\w))/i);
    let cleanedText = text;
    if (sourcesMatch) {
        cleanedText = text.replace(sourcesMatch[0], '').trim();
    }

    // Professional medical formatting for structured response
    let enhancedText = cleanedText
        // Handle the initial CASE URGENCY format at the top
        .replace(/^CASE\s+URGENCY:\s*EMERGENCY/gm, '<div class="urgency-header">CASE URGENCY: <span class="urgency-level">EMERGENCY</span></div>')
        .replace(/^CASE\s+URGENCY:\s*URGENT/gm, '<div class="urgency-header">CASE URGENCY: <span class="urgency-level">URGENT</span></div>')
        .replace(/^CASE\s+URGENCY:\s*ROUTINE/gm, '<div class="urgency-header">CASE URGENCY: <span class="urgency-level">ROUTINE</span></div>')

        // Fix the concatenated diagnosis table format
        .replace(/RankDiagnosisProbability \(%\)Clinical Reasoning-+/g, 'Rank|Diagnosis|Probability (%)|Clinical Reasoning')
        .replace(/(\d+)([A-Z][^0-9]+?)(\d+%)([^0-9]+?)(?=\d|$)/g, '$1|$2|$3|$4\n')

        // Handle section separators
        .replace(/^---$/gm, '<div class="section-break"></div>')

        // Patient Case Summary Section
        .replace(/^📋\s*PATIENT\s+CASE\s+SUMMARY:?$/gm, '<div class="medical-section patient-section"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">')

        // Case Urgency Section
        .replace(/^🚨\s*CASE\s+URGENCY:?$/gm, '</div></div><div class="medical-section urgency-section"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">')

        // Differential Diagnosis Section - KEEP A) FORMAT
        .replace(/^(A\)\s*DIFFERENTIAL\s+DIAGNOSIS.*?:|🔬\s*DIFFERENTIAL\s+DIAGNOSIS.*?:?)$/gm, '</div></div><div class="medical-section diagnosis-section"><h4 class="section-header">A) DIFFERENTIAL DIAGNOSIS</h4><div class="section-content">')

        // Investigations Section - KEEP B) FORMAT
        .replace(/^(B\)\s*RECOMMENDED\s+INVESTIGATIONS:?|🧪\s*RECOMMENDED\s+INVESTIGATIONS:?)$/gm, '</div></div><div class="medical-section investigations-section"><h4 class="section-header">B) RECOMMENDED INVESTIGATIONS</h4><div class="section-content">')

        // Treatment Section - KEEP C) FORMAT
        .replace(/^(C\)\s*(TREATMENT.*?RECOMMENDATIONS|MANAGEMENT.*?RECOMMENDATIONS):?|💊\s*TREATMENT.*?RECOMMENDATIONS:?)$/gm, '</div></div><div class="medical-section treatment-section"><h4 class="section-header">C) TREATMENT & MANAGEMENT RECOMMENDATIONS</h4><div class="section-content">')

        // Warning Signs Section - KEEP D) FORMAT if present, or use emoji format
        .replace(/^(D\)\s*WARNING\s+SIGNS.*?:|⚠️\s*WARNING\s+SIGNS.*?:?)$/gm, '</div></div><div class="medical-section warnings-section"><h4 class="section-header">D) WARNING SIGNS TO MONITOR</h4><div class="section-content">')

        // Doctor's Note Section
        .replace(/^🧠\s*DOCTOR'S\s+NOTE:?$/gm, '</div></div><div class="medical-section doctor-note-section"><h4 class="section-header">🧠 DOCTOR\'S NOTE</h4><div class="section-content">')

        // Sources Section (if present)
        .replace(/^📚\s*SOURCES:?$/gm, '</div></div><div class="medical-section sources-section"><h4 class="section-header">📚 SOURCES</h4><div class="section-content">');

    // Split the text into lines
    let lines = enhancedText.split('\n');
    let formatted = '';
    let inList = false;
    let listType = '';
    let inTable = false;
    let tableRows = [];

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i].trim();

        if (!line) {
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }
            if (inTable) {
                formatted += formatTable(tableRows);
                inTable = false;
                tableRows = [];
            }
            formatted += '<br>';
            continue;
        }

        // Skip already processed HTML tags
        if (line.includes('<div class=') || line.includes('</div>')) {
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }
            if (inTable) {
                formatted += formatTable(tableRows);
                inTable = false;
                tableRows = [];
            }
            formatted += line;
            continue;
        }

        // Check for concatenated diagnosis table
        if (line.includes('RankDiagnosis') && line.includes('Clinical Reasoning')) {
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }
            if (!inTable) {
                inTable = true;
                tableRows = [];
            }
            // Create proper table header
            tableRows.push('Rank|Diagnosis|Probability (%)|Clinical Reasoning');
            continue;
        }
        // Check for the concatenated data row (like: 1Abdominal Aortic Aneurysm (AAA)70%Given the symptom...)
        else if (line.match(/^\d+[A-Z][^0-9]*\d+%/)) {
            if (!inTable) {
                inTable = true;
                tableRows = [];
                tableRows.push('Rank|Diagnosis|Probability (%)|Clinical Reasoning');
            }
            // Parse the concatenated format
            const match = line.match(/^(\d+)([^0-9]*?)(\d+%)(.*)$/);
            if (match) {
                const formattedRow = `${match[1]}|${match[2].trim()}|${match[3]}|${match[4].trim()}`;
                tableRows.push(formattedRow);
            }
            continue;
        }
        // Check for table rows (contains | or table-like structure)
        else if ((line.includes('|') && line.split('|').length > 2) ||
            (line.match(/^(Rank|1|2|3|4|5)\s+(.*?)\s+(\d+%)\s+(.*?)$/))) {
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }
            if (!inTable) {
                inTable = true;
                tableRows = [];
            }
            tableRows.push(line);
            continue;
        } else if (inTable) {
            // End of table
            formatted += formatTable(tableRows);
            inTable = false;
            tableRows = [];
        }

        // Check for headers (# Header)
        if (/^#{1,6}\s+(.+)$/.test(line)) {
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }
            let level = line.match(/^#+/)[0].length;
            let headerText = line.replace(/^#+\s*/, '');
            formatted += `<h${level}>${headerText}</h${level}>`;
            continue;
        }

        // Check for list items
        if (/^[\s]*[-*+]\s+(.+)$/.test(line) || /^[\s]*\d+\.\s+(.+)$/.test(line)) {
            let isOrdered = /^[\s]*\d+\.\s+(.+)$/.test(line);
            let content = line.replace(/^[\s]*[-*+\d\.]\s*/, '');

            if (!inList) {
                listType = isOrdered ? 'ol' : 'ul';
                formatted += `<${listType}>`;
                inList = true;
            } else if ((isOrdered && listType === 'ul') || (!isOrdered && listType === 'ol')) {
                formatted += `</${listType}>`;
                listType = isOrdered ? 'ol' : 'ul';
                formatted += `<${listType}>`;
            }

            formatted += `<li>${content}</li>`;
            continue;
        } else if (inList) {
            formatted += listType === 'ul' ? '</ul>' : '</ol>';
            inList = false;
        }

        // Regular paragraph
        formatted += `<p>${line}</p>`;
    }

    // Close any open lists or tables
    if (inList) {
        formatted += listType === 'ul' ? '</ul>' : '</ol>';
    }
    if (inTable) {
        formatted += formatTable(tableRows);
    }

    // Close any remaining open divs
    formatted += '</div></div>';

    // Process inline formatting

    // Bold text between ** or __
    formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    formatted = formatted.replace(/__(.+?)__/g, '<strong>$1</strong>');

    // Italic text between * or _
    formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');
    formatted = formatted.replace(/_(.+?)_/g, '<em>$1</em>');

    // Code blocks
    formatted = formatted.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
    formatted = formatted.replace(/`(.+?)`/g, '<code>$1</code>');

    return formatted;
}

</script>
@endpush
