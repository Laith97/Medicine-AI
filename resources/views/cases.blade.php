@extends('master')

@section('title', 'Patients Page')

@section('content')
@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">

<style>
    /* Global Font (Cases Page Only) */
    .cases-container {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    .cases-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .cases-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
        position: relative;
        overflow: hidden;
    }

    .cases-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        opacity: 0.1;
        transform: skewX(-15deg);
    }

    .cases-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 2rem;
        color: white;
    }

    .cases-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 25px;
        box-shadow: 0 15px 50px rgba(44, 62, 80, 0.1);
        border: none;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .cases-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 60px rgba(44, 62, 80, 0.15);
    }

    .cases-card-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-bottom: none;
        position: relative;
    }

    .cases-card-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
    }

    .cases-card-body {
        padding: 2rem;
    }

    .btn-add-patient {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.6rem 1.5rem;
        border-radius: 25px;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-add-patient:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
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
        transition: all 0.3s ease;
        background: white;
    }

    .custom-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(222, 98, 98, 0.05) 0%, rgba(222, 98, 98, 0.02) 100%);
        transform: scale(1.01);
    }

    .custom-table tbody td {
        padding: 1rem;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }

    .btn-view-response {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        border: none;
        color: white;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3);
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }

    .btn-view-response:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(222, 98, 98, 0.4);
        background: linear-gradient(135deg, #c55252 0%, #b04848 100%);
        color: white;
    }

    /* DataTables Styling */
    .dataTables_filter input {
        border-radius: 12px !important;
        border: 2px solid #e9ecef !important;
        padding: 0.5rem 1rem !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
        transition: all 0.3s ease !important;
    }

    .dataTables_filter input:focus {
        border-color: #DE6262 !important;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.15) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #DE6262 !important;
        border-radius: 8px !important;
        margin: 0 2px !important;
        transition: all 0.3s ease !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%) !important;
        color: white !important;
        border: none !important;
    }

    .dataTables_wrapper .dataTables_length select {
        border-radius: 8px !important;
        border: 2px solid #e9ecef !important;
        padding: 0.25rem 0.5rem !important;
    }

    .dataTables_wrapper .dataTables_info {
        color: #6c757d !important;
        font-weight: 500 !important;
    }

    /* Modal Styling */
    .modal-xl {
        max-width: 95vw;
    }

    .response-modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    .response-modal-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        border: none;
        padding: 1.25rem 1.5rem;
    }

    .response-modal-body {
        padding: 1.5rem;
        max-height: 70vh;
        overflow-y: auto;
    }

    /* Enhanced styling for the entire popup content */
    .response-modal-body p {
        margin-bottom: 1rem;
        color: #2c3e50;
        font-size: 0.95rem;
    }

    /* No special styling for notes or medications - keep it simple like the summary */

    /* Patient Summary Styles */
    .ai-summary {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.05);
    }

    .ai-summary h4 {
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
    }

    .ai-summary ul {
        padding-left: 20px;
        margin-bottom: 15px;
    }

    .ai-summary li {
        margin-bottom: 8px;
    }

    .sources-list {
        margin: 0;
        padding-left: 1.5rem;
    }

    .sources-list li {
        margin-bottom: 0.5rem;
    }

    .patient-summary-btn {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .patient-summary-btn:hover {
        background-color: #138496;
        border-color: #117a8b;
        color: white;
    }

    .response-text {
        white-space: pre-wrap;
        word-break: break-word;
        font-family: "Segoe UI", Roboto, sans-serif;
        font-size: 0.95rem;
        color: #2c3e50;
        line-height: 1.8;
        padding: 10px;
    }

    /* Apply AI summary styling to response text */
    .response-text h1, .response-text h2, .response-text h3, .response-text h4 {
        color: #2c3e50;
        margin-top: 25px;
        margin-bottom: 15px;
        font-weight: 600;
        border-bottom: 2px solid #DE6262;
        padding-bottom: 10px;
        font-size: 1.4rem;
    }

    /* Style for the response text (EXACT COPY from AI Response Popup) */
    .response-text {
        white-space: pre-wrap;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.5;
        margin: 0;
        padding: 1rem;
        font-size: 15px;
        color: #333;
    }

    /* AI Content styling */
    .ai-content {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        line-height: 1.5;
        font-size: 15px;
        margin: 0;
        padding: 0;
    }

    /* Medical Section Styling */
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

    /* Table Styling */
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
        font-size: 0.9rem;
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

    /* Probability badges */
    .response-text .probability,
    .ai-content .probability {
        background-color: #007bff;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* List styling - EXACT COPY FROM AI RESPONSE POPUP */
    .response-text ul, .response-text ol,
    .ai-content ul, .ai-content ol {
        margin: 15px 0;
        padding-left: 25px;
    }

    .response-text li,
    .ai-content li {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    /* Legacy support for old format - redirect to professional style */
    .response-text p strong, .ai-content p strong {
        /* Remove old styling and use normal strong formatting */
        display: inline;
        font-size: inherit;
        color: inherit;
        margin: 0;
        font-weight: 600;
        border: none;
        padding: 0;
        background-color: transparent;
        border-radius: 0;
    }

    .response-text p {
        margin-bottom: 12px;
        padding: 0 5px;
    }

    .response-text ul, .response-text ol {
        padding-left: 25px;
        margin-bottom: 20px;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .response-text li {
        margin-bottom: 10px;
        padding-left: 5px;
    }

    .response-text li:last-child {
        margin-bottom: 0;
    }

    /* EXACT COPY FROM AI RESPONSE POPUP - Bullet Points */
    .bullet-item {
        padding: 6px 0;
        color: #495057;
        font-size: 0.95rem;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .bullet-item::before {
        content: "•";
        color: #DE6262;
        font-weight: bold;
        font-size: 1.1rem;
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* MedCura Section Styling - EXACT COPY FROM AI RESPONSE POPUP */
    .medcura-section {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .medcura-section .section-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #2c3e50;
        padding: 15px 20px;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        border-bottom: 1px solid #e8e8e8;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .medcura-section .section-content {
        padding: 20px;
        line-height: 1.6;
    }

    .medcura-section .section-content p {
        margin-bottom: 12px;
        color: #2c3e50;
        text-align: justify;
        word-spacing: 0.1em;
    }

    .medcura-section .section-content p:last-child {
        margin-bottom: 0;
    }

    /* Specific section colors */
    .medcura-section.patient-summary .section-header {
        background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
        color: #155724;
    }

    .medcura-section.differential-diagnoses .section-header {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
    }

    .medcura-section.recommended-tests .section-header {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        color: #0c5460;
    }

    .medcura-section.management-plan .section-header {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
    }

    .medcura-section.warning-signs .section-header {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
    }

    /* MedCura Table Styling - EXACT COPY FROM AI RESPONSE POPUP */
    .medcura-table {
        margin: 15px 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .medcura-table table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        background: #ffffff;
    }

    .medcura-table .table-header-cell {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        font-weight: 600;
        padding: 12px 15px;
        text-align: left;
        border: none;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .medcura-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: top;
    }

    .medcura-table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .medcura-table tr:hover {
        background-color: #e9ecef;
    }

    /* Medical List Styling - EXACT COPY FROM AI RESPONSE POPUP */
    .medical-list {
        margin: 15px 0;
        padding-left: 25px;
    }

    .medical-list li {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    /* Urgency Badge Styling - EXACT COPY FROM AI RESPONSE POPUP */
    .urgency-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        text-align: center;
        margin: 10px 0;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
    }

    .urgency-badge.emergency {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }

    .urgency-badge.urgent {
        background: linear-gradient(135deg, #ffa500 0%, #ff8c00 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 165, 0, 0.3);
    }

    .urgency-badge.routine {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    /* Subsection Header Styling - EXACT COPY FROM AI RESPONSE POPUP */
    .subsection-header {
        font-weight: 600;
        color: #2c3e50;
        margin: 15px 0 10px 0;
        padding-bottom: 5px;
        border-bottom: 1px solid #e9ecef;
        font-size: 1rem;
    }

    /* Clean AI Summary Design */
    .ai-summary-simple {
        background: #f8f9fa;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .ai-summary-simple h4 {
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
    }

    .ai-summary-simple ul {
        padding-left: 20px;
        margin-bottom: 15px;
    }

    .ai-summary-simple li {
        margin-bottom: 8px;
    }

    .ai-summary-simple p {
        margin-bottom: 12px;
        color: #2c3e50;
        line-height: 1.6;
    }

    /* Loading animation for AI summary */
    .ai-summary-loading {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid rgba(222, 98, 98, 0.1);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .ai-summary-loading::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
    }

    .ai-summary-loading .spinner-border {
        color: #DE6262;
        width: 3rem;
        height: 3rem;
        border-width: 0.3em;
    }

    .ai-summary-loading h6 {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .ai-summary-loading .text-muted {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .ai-summary-loading .progress {
        background-color: rgba(222, 98, 98, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }

    .ai-summary-loading .progress-bar {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
    }

    /* Enhanced AI content styling */
    .ai-content {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        border: 1px solid rgba(0,0,0,0.05);
        line-height: 1.8;
    }

    /* Enhanced Medical Section Styling - Same as OpenAI popup */
    .medcura-level1, .medcura-level2 {
        margin-bottom: 20px;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        border: 1px solid #e8ecef;
    }

    .level-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 18px 25px;
        font-weight: 700;
        font-size: 1.1rem;
        border: none;
        margin: 0;

        align-items: center;
        justify-content: space-between;
    }

    .level1-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-bottom: none;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .level2-header {
        background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
        color: white;
        border-bottom: none;
        cursor: pointer;
        transition: background 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    }

    .level2-header:hover {
        background: linear-gradient(135deg, #0056b3 0%, #520dc2 100%);
    }

    /* Level 2 Toggle */
    .level2-toggle {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .toggle-icon {
        float: right;
        transition: transform 0.3s ease;
        font-size: 1rem;
    }

    .toggle-icon.rotated {
        transform: rotate(180deg);
    }

    .toggle-hint {
        font-size: 0.85rem;
        opacity: 0.8;
        margin-top: 5px;
        font-weight: normal;
    }

    .level2-content {
        padding: 25px;
        background: white;
        border-top: 1px solid #e9ecef;
    }

    /* Enhanced MedCuraAI Container Styles */
    .medcura-level1 {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #e8ecef;
    }

    .medcura-level2 {
        background: #f8fafa;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        margin-top: 25px;
        overflow: hidden;
        border: 1px solid #e1e8ed;
    }

    .level-header {
background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-bottom: none;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .medcura-section {
        margin: 0;
        border-bottom: 1px solid #f1f3f4;
    }

    .medcura-section:last-child {
        border-bottom: none;
    }

    .section-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #2c3e50;
        padding: 15px 25px;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        border-bottom: 2px solid #DE6262;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-content {
        padding: 20px 25px;
        line-height: 1.7;
        color: #495057;
        background: white;
    }

    .section-content p {
        margin-bottom: 12px;
        font-size: 0.95rem !important;
    }

    .section-content p:last-child {
        margin-bottom: 0;
    }

    .response-text p {
        margin-bottom: 12px;
        padding: 0 5px;
    }

    /* Special Section Styling - Force Override with Higher Specificity */
    .response-text .patient-summary .section-header,
    .patient-summary .section-header,
    .medical-section.patient-summary .section-header {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
        color: #1565c0 !important;
        border-bottom-color: #2196f3 !important;
    }

    .response-text .case-urgency .section-header,
    .case-urgency .section-header,
    .medical-section.case-urgency .section-header {
        background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%) !important;
        color: #e65100 !important;
        border-bottom-color: #ff9800 !important;
    }

    .response-text .differential-diagnoses .section-header,
    .differential-diagnoses .section-header,
    .medical-section.differential-diagnoses .section-header {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%) !important;
        color: #6a1b9a !important;
        border-bottom-color: #9c27b0 !important;
    }

    .response-text .recommended-tests .section-header,
    .recommended-tests .section-header,
    .medical-section.recommended-tests .section-header {
        background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%) !important;
        color: #2e7d32 !important;
        border-bottom-color: #4caf50 !important;
    }

    .response-text .management-plan .section-header,
    .management-plan .section-header,
    .medical-section.management-plan .section-header {
        background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%) !important;
        color: #f57f17 !important;
        border-bottom-color: #ffc107 !important;
    }

    .response-text .warning-signs .section-header,
    .warning-signs .section-header,
    .medical-section.warning-signs .section-header {
        background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%) !important;
        color: #c62828 !important;
        border-bottom-color: #f44336 !important;
    }

    /* Urgency Badge Styling */
    .urgency-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 25px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 8px 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .urgency-badge.emergency {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        animation: pulse-red 2s infinite;
    }

    .urgency-badge.urgent {
        background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
        color: white;
    }

    .urgency-badge.routine {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        color: white;
    }

    @keyframes pulse-red {
        0% { box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); }
        50% { box-shadow: 0 4px 16px rgba(220, 53, 69, 0.6); }
        100% { box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); }
    }

    /* Bullet Points */
    .bullet-item {
        padding: 6px 0;
        color: #495057;
        font-size: 0.95rem;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .bullet-item::before {
        content: "•";
        color: #DE6262;
        font-weight: bold;
        font-size: 1.1rem;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .medcura-table {
        margin: 15px 0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .medcura-table table {
        margin: 0;
        width: 100%;
        border-collapse: collapse;
    }

    .medcura-table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        text-align: left;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .medcura-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.9rem;
        vertical-align: top;
    }

    .medcura-table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .medcura-table tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }

    /* Subsection Headers */
    .subsection-header {
        font-weight: 600;
        color: #2c3e50;
        margin: 15px 0 8px 0;
        padding-bottom: 5px;
        border-bottom: 1px solid #e9ecef;
        font-size: 1rem;
    }

    .level2-section-header {
        font-weight: 600;
        color: #2c3e50;
        margin: 20px 0 10px 0;
        padding: 10px 0;
        border-bottom: 2px solid #007bff;
        font-size: 1rem;
    }

    /* Enhanced Chat Section Styling */
    .chat-section {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e8ecef;
    }

    .chat-header {
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f3f4;
        margin-bottom: 1rem;
    }

    .chat-header h6 {
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .chat-messages-container {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
    }

    #chat-messages {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        background-color: #f9f9f9;
    }

    .chat-message {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 12px;
        max-width: 85%;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        animation: fadeIn 0.3s ease-out;
    }

    .user-message {
        background-color: #007bff;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 2px;
    }

    .ai-message {
        background-color: #f0f0f0;
        color: #333;
        border-bottom-left-radius: 2px;
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-top: 5px;
        text-align: right;
    }

    .typing-indicator {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: #f0f0f0;
        border-radius: 10px;
        margin-bottom: 10px;
        max-width: 60px;
    }

    .typing-indicator span {
        width: 8px;
        height: 8px;
        background-color: #999;
        border-radius: 50%;
        display: inline-block;
        margin-right: 3px;
        animation: typing 1.4s infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
        margin-right: 0;
    }

    @keyframes typing {
        0%, 60%, 100% {
            transform: translateY(0);
        }
        30% {
            transform: translateY(-10px);
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chat-form .input-group {
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .chat-form .form-control {
        border: none;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }

    .chat-form .btn {
        border: none;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        padding: 0.75rem 1.25rem;
        font-weight: 600;
    }

    .chat-form .btn:hover {
        background: linear-gradient(135deg, #c55252 0%, #b04545 100%);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .level-header {
            padding: 15px 20px;
            font-size: 1.2rem;
        }

        .medcura-section .section-content {
            padding: 15px;
        }

        .level2-content {
            padding: 20px;
        }

        .chat-section {
            padding: 1rem;
        }

        .chat-form .btn {
            padding: 0.75rem 1rem;
        }

        .chat-form .btn span {
            display: none !important;
        }
    }

    /* Style for headings in AI content */
    .ai-content h1, .ai-content h2, .ai-content h3, .ai-content h4 {
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
    }

    /* Style for lists in AI content */
    .ai-content ul, .ai-content ol {
        padding-left: 20px;
        margin-bottom: 15px;
    }

    .ai-content li {
        margin-bottom: 8px;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 5rem;
        color: #2c3e50;
        margin-bottom: 1.5rem;
    }

    .empty-state h5 {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    /* Recent Patients Section */
    .recent-patients-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(44, 62, 80, 0.1);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .recent-patients-card .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        border-radius: 15px 15px 0 0;
    }

    .recent-patients-card .badge {
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 30px;
    }

    .recent-patient-item {
        transition: all 0.3s ease;
        height: 100%;
    }

    .recent-patient-item:hover {
        background-color: rgba(222, 98, 98, 0.03);
    }

    .col-lg-2-4 {
        flex: 0 0 auto;
        width: 20%;
    }

    .btn-sm.btn-view-response {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    /* Improved DataTables styling */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_filter input {
        min-width: 250px;
    }

    .dataTables_wrapper .dataTables_length select {
        min-width: 80px;
    }

    .dataTables_processing {
        background: rgba(255,255,255,0.9) !important;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 15px !important;
        z-index: 100;
    }

    @media (max-width: 992px) {
        .col-lg-2-4 {
            width: 33.33%;
        }
    }

    @media (max-width: 768px) {
        .cases-header h5 {
            font-size: 1.5rem;
        }

        .cases-card-body {
            padding: 0.75rem;
        }

        /* Form responsive fixes */
        .cases-card-body h1,
        .cases-card-body h2,
        .cases-card-body h3,
        .cases-card-body h4,
        .cases-card-body h5,
        .cases-card-body h6 {
            font-size: 1.1rem !important;
            line-height: 1.3 !important;
            margin-bottom: 0.75rem !important;
            word-break: break-word !important;
        }

        .cases-card-body .form-label,
        .cases-card-body .col-form-label {
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.5rem !important;
            word-break: break-word !important;
        }

        .cases-card-body .form-control,
        .cases-card-body .form-select {
            font-size: 0.9rem !important;
            padding: 0.5rem 0.75rem !important;
        }

        .cases-card-body .btn {
            font-size: 0.85rem !important;
            padding: 0.5rem 1rem !important;
        }

        .cases-card-body .card-title {
            font-size: 1.1rem !important;
            margin-bottom: 0.75rem !important;
        }

        .cases-card-body .card-text {
            font-size: 0.9rem !important;
            line-height: 1.4 !important;
        }

        .cases-card-body .row {
            margin-bottom: 0.75rem !important;
        }

        .cases-card-body .col-md-6,
        .cases-card-body .col-lg-6 {
            margin-bottom: 0.5rem !important;
        }

        .col-lg-2-4 {
            width: 50%;
        }

        .dataTables_wrapper .dataTables_filter input {
            min-width: 180px;
        }

        .bullet-item {
            font-size: 0.9rem;
        }

        /* Enhanced modal responsive - EXACT COPY FROM AI RESPONSE */
        .modal-dialog.modal-xl {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .response-modal-header {
            padding: 1rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .response-modal-header .modal-title {
            font-size: 1.1rem;
            word-break: break-word;
            hyphens: auto;
            line-height: 1.3;
        }

        .response-modal-header > div {
            align-self: flex-end;
        }

        .response-modal-body {
            padding: 1rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Fix text display issues in modal body */
        .response-modal-body .ai-response-section,
        .response-modal-body .response-text,
        .response-modal-body .ai-content {
            font-size: 0.9rem !important;
            line-height: 1.5 !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            hyphens: auto !important;
        }

        .response-modal-body p {
            margin-bottom: 0.8rem !important;
            text-align: left !important;
        }

        .response-modal-body h1,
        .response-modal-body h2,
        .response-modal-body h3,
        .response-modal-body h4,
        .response-modal-body h5,
        .response-modal-body h6 {
            font-size: 1rem !important;
            line-height: 1.3 !important;
            word-break: break-word !important;
            margin-top: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .response-modal-body ul,
        .response-modal-body ol {
            padding-left: 1.2rem !important;
            margin-bottom: 1rem !important;
        }

        .response-modal-body li {
            margin-bottom: 0.5rem !important;
            line-height: 1.4 !important;
            word-break: break-word !important;
        }

        .response-modal-body table {
            font-size: 0.65rem !important;
            display: block !important;
            overflow-x: auto !important;
            white-space: nowrap !important;
        }

        .response-modal-body table th,
        .response-modal-body table td {
            padding: 0.25rem 0.3rem !important;
            min-width: 50px !important;
            line-height: 1.2 !important;
            vertical-align: top !important;
        }

        .response-modal-body table th {
            font-size: 0.6rem !important;
            font-weight: 600 !important;
            background-color: #f8f9fa !important;
        }

        .level-header {
            font-size: 1.1rem;
            padding: 15px 18px;
            word-break: break-word;
        }

        .section-header {
            font-size: 1rem;
            padding: 12px 18px;
            word-break: break-word;
        }

        /* Fix medical section styling for mobile */
        .medical-section .section-header {
            font-size: 0.9rem !important;
            padding: 0.8rem !important;
            word-break: break-word !important;
        }

        .medical-section .section-content {
            padding: 1rem !important;
            font-size: 0.85rem !important;
        }

        /* Fix bullet points for mobile */
        .bullet-item {
            font-size: 0.85rem !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.3rem !important;
        }

        .bullet-item::before {
            margin-top: 0 !important;
        }
    }

    @media (max-width: 576px) {
        .col-lg-2-4 {
            width: 100%;
        }

        /* Enhanced form responsive for very small screens */
        .cases-card-body {
            padding: 0.5rem;
        }

        .cases-card-body h1,
        .cases-card-body h2,
        .cases-card-body h3,
        .cases-card-body h4,
        .cases-card-body h5,
        .cases-card-body h6 {
            font-size: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .cases-card-body .form-label,
        .cases-card-body .col-form-label {
            font-size: 0.8rem !important;
            margin-bottom: 0.3rem !important;
        }

        .cases-card-body .form-control,
        .cases-card-body .form-select {
            font-size: 0.8rem !important;
            padding: 0.4rem 0.6rem !important;
        }

        .cases-card-body .btn {
            font-size: 0.75rem !important;
            padding: 0.4rem 0.8rem !important;
        }

        .cases-card-body .card-title {
            font-size: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .cases-card-body .card-text {
            font-size: 0.8rem !important;
        }

        .cases-card-body .row {
            margin-bottom: 0.5rem !important;
        }

        .cases-card-body .col-md-6,
        .cases-card-body .col-lg-6 {
            margin-bottom: 0.3rem !important;
        }

        /* Very small screen modal fixes */
        .modal-dialog.modal-xl {
            margin: 0.25rem;
            max-width: calc(100% - 0.5rem);
        }

        .response-modal-header {
            padding: 0.75rem;
        }

        .response-modal-header .modal-title {
            font-size: 1rem !important;
        }

        .response-modal-body {
            padding: 0.75rem;
        }

        /* Extra small screen text fixes */
        .response-modal-body .ai-response-section,
        .response-modal-body .response-text,
        .response-modal-body .ai-content {
            font-size: 0.8rem !important;
            line-height: 1.4 !important;
        }

        .response-modal-body h1,
        .response-modal-body h2,
        .response-modal-body h3,
        .response-modal-body h4,
        .response-modal-body h5,
        .response-modal-body h6 {
            font-size: 0.9rem !important;
        }

        .response-modal-body table {
            font-size: 0.55rem !important;
        }

        .response-modal-body table th,
        .response-modal-body table td {
            padding: 0.15rem 0.2rem !important;
            min-width: 40px !important;
            line-height: 1.1 !important;
        }

        .response-modal-body table th {
            font-size: 0.5rem !important;
            font-weight: 600 !important;
        }

        .level-header {
            font-size: 1rem;
            padding: 12px 15px;
        }

        .section-header {
            font-size: 0.9rem;
            padding: 10px 15px;
        }
    }
</style>
@endpush

<div class="cases-container">
    <div class="container-fluid">


        @php
            $hasRecords = $records->count() > 0;
        @endphp

        <!-- Cases Header -->
        <div class="cases-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5>Patient Records</h5>
                    <p class="mb-0 opacity-75">Manage and view all patient cases</p>
                </div>
                <a href="{{ route('ask-ai') }}" class="btn-add-patient">
                    <i class="fas fa-plus me-2"></i>Add New Patient
                </a>
            </div>
        </div>

        <!-- Recent Patients Section -->
        @if($hasRecords)
        <div class="recent-patients-card mb-4">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Patients</h6>
                <span class="badge bg-primary">Last 5 patients</span>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    @foreach($records->sortByDesc('created_at')->take(5) as $recentRecord)
                    <div class="col-md-4 col-lg-2-4 border-end border-bottom">
                        <div class="recent-patient-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 text-truncate" style="max-width: 150px;">{{ $recentRecord->name }}</h6>
                                <span class="badge bg-light text-dark">{{ $recentRecord->gender }}</span>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="fas fa-calendar-days me-1"></i> {{ \Carbon\Carbon::parse($recentRecord->created_at)->format('M d, Y') }}
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark">{{ $recentRecord->age }} years</span>
                                <button class="btn btn-sm btn-view-response"
                                        data-bs-toggle="modal"
                                        data-bs-target="#responseModal"
                                        data-record-id="{{ $recentRecord->id }}"
                                        data-patient-name="{{ $recentRecord->name }}"
                                        data-patient-key="{{ $recentRecord->patient_key }}"
                                        data-response="{{ htmlentities($recentRecord->ai_response) }}"
                                        data-visit-number="{{ $recentRecord->visit_number ?? 1 }}"
                                        style="background: linear-gradient(135deg, #DE6262 0%, #c55252 100%); border: none; color: white; font-weight: 500; padding: 0.25rem 0.75rem; border-radius: 15px; box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3); font-size: 0.75rem;">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Cases Card -->
        <div class="cases-card">
            <div class="cases-card-body">
                @if($hasRecords)
                    <div class="table-responsive">
                        <table id="recordsTable" class="table custom-table align-middle w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">Patient Name</th>
                                    <th class="text-center">Age</th>
                                    <th class="text-center">Gender</th>
                                    <th class="text-center">Height</th>
                                    <th class="text-center">Weight</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Visit #</th>
                                    <th class="text-center">Recommendations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($records as $record)
                                <tr class="text-center">
                                    <td><strong>#{{ $record->id }}</strong></td>
                                    <td>
                                        {{ $record->name }}
                                    </td>
                                    <td>{{ $record->age }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $record->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white; border-radius: 15px; padding: 0.4rem 0.8rem;">
                                            {{ ucfirst($record->gender) }}
                                        </span>
                                    </td>
                                    <td>{{ $record->height ?? 'N/A' }}</td>
                                    <td>{{ $record->weight ?? 'N/A' }}</td>
                                    <td data-order="{{ $record->created_at->timestamp }}">{{ $record->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">Visit #{{ $record->visit_number ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn view-response-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#responseModal"
                                                    data-response="{{ htmlentities($record->ai_response) }}"
                                                    data-patient-name="{{ $record->name }}"
                                                    data-visit-number="{{ $record->visit_number ?? 1 }}"
                                                    data-record-id="{{ $record->id }}"
                                                    data-patient-key="{{ $record->patient_key }}"
                                                    style="background: linear-gradient(135deg, #DE6262 0%, #c55252 100%); border: none; color: white; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px; box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3); font-size: 0.85rem; margin-right: 5px;">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                            <button class="btn patient-summary-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#summaryModal"
                                                    data-patient-name="{{ $record->name }}"
                                                    data-patient-age="{{ $record->age }}"
                                                    data-patient-gender="{{ $record->gender }}"
                                                    data-patient-key="{{ $record->patient_key }}"
                                                    style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border: none; color: white; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px; box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3); font-size: 0.85rem; margin-right: 5px;">
                                                <i class="fas fa-history me-1"></i>Summary
                                            </button>
                                            <a href="{{ route('ask-ai', ['edit_patient' => $record->id]) }}" class="btn"
                                               style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); border: none; color: white; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px; box-shadow: 0 2px 8px rgba(243, 156, 18, 0.3); font-size: 0.85rem;">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-user-doctor"></i>
                        <h5>No Patient Records Found</h5>
                        <p>Start building your patient database by adding your first case</p>
                        <a href="{{ route('ask-ai') }}" class="btn-add-patient mt-3">
                            <i class="fas fa-plus me-2"></i>Add First Patient
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="responseModalLabel" style="color: #fff">
                    <i class="fas fa-stethoscope me-2"></i>AI Recommendations
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printResponseBtn">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <!-- AI Response Section with Enhanced Structure -->
                <div class="ai-response-section mb-4">
                    <!-- Level 1: Core Analysis -->
                    <div class="medcura-level1">
                        <div class="level1-header level-header">
                            <i class="fas fa-stethoscope me-2"></i>
                            <span>Core Medical Analysis</span>
                        </div>
                        <div id="openaiReply" class="response-text"></div>
                    </div>

                    <!-- Level 2: Detailed Analysis (Initially Hidden) -->
                    <div class="medcura-level2">
                        <div class="level2-header level-header level2-toggle" onclick="toggleLevel2()">
                            <span>
                                <i class="fas fa-microscope me-2"></i>
                                Detailed Clinical Analysis
                                <div class="toggle-hint">Click to Expand</div>
                            </span>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div id="level2-content" class="level2-content" style="display: none;">
                            <div class="level2-section-header">Advanced Differential Diagnosis</div>
                            <p>This section provides detailed clinical reasoning, alternative diagnoses, and comprehensive management strategies based on current medical guidelines.</p>

                            <div class="level2-section-header">Risk Stratification</div>
                            <p>Detailed risk assessment considering patient-specific factors, comorbidities, and prognostic indicators.</p>

                            <div class="level2-section-header">Evidence-Based Recommendations</div>
                            <p>Treatment recommendations based on latest clinical evidence and best practice guidelines.</p>
                        </div>
                    </div>
                </div>

                <!-- Sources Section - Hidden as requested -->
                <div id="sourcesCitation" class="mt-4" style="display: none;">
                    <div id="sourcesContent" class="sources-list">
                        <!-- Source logos will be populated here but not displayed -->
                    </div>
                </div>

                <!-- Enhanced Chat Continuation Section -->
                <div class="chat-section mt-4">
                    <div class="chat-header">
                        <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Follow-up Questions</h6>
                        <small class="text-muted">Ask additional questions about the diagnosis or treatment</small>
                    </div>

                    <div id="chat-messages" class="chat-messages-container">
                        <!-- Additional messages will appear here -->
                    </div>

                    <div class="chat-input-container">
                        <form id="follow-up-form" class="chat-form">
                            @csrf
                            <input type="hidden" id="conversation-id" name="conversation_id" value="{{ session('conversation_id') ?? '' }}">
                            <div class="input-group">
                                <input type="text" id="follow-up-message" name="message" class="form-control"
                                       placeholder="Ask a follow-up question..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    <span class="d-none d-md-inline ms-1">Send</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Patient Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="summaryModalLabel" style="color: #fff">
                    <i class="fas fa-user-doctor me-2"></i><span id="patientSummaryTitle">Patient Summary</span>
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printSummaryBtn">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <!-- Patient Info Section -->
                <div class="patient-info-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-id-card me-2"></i>Patient Information</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Name:</strong> <span id="summaryPatientName"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Age:</strong> <span id="summaryPatientAge"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Gender:</strong> <span id="summaryPatientGender"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Visit Summary Section -->
                <div class="visit-summary-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-clipboard-list me-2"></i>Visit Summary</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div id="visitSummaryContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading patient history...</p>
                        </div>
                    </div>
                </div>

                <!-- AI Generated Summary Section -->
                <div class="ai-summary-section">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-robot me-2"></i>AI Generated Summary</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div id="aiSummaryContainer" class="response-text">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Generating AI summary...</p>
                        </div>
                    </div>

                    <!-- Sources Section for Summary -->
                    <div id="summarySourcesCitation" class="mt-4" style="display: none;">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="mb-0 me-2"><i class="fas fa-book me-2"></i>Sources</h6>
                            <hr class="flex-grow-1 ms-2">
                        </div>
                        <div id="summarySourcesContent" class="sources-list p-3 bg-light border rounded">
                            <!-- Sources will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        const hasRecords = @json($hasRecords);

        if (hasRecords) {
            $('#recordsTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                deferRender: true,
                stateSave: true,
                stateDuration: 60 * 60 * 24, // 1 day
                language: {
                    search: "🔍 Search:",
                    lengthMenu: "Show _MENU_ patients",
                    info: "Showing _START_ to _END_ of _TOTAL_ patients",
                    paginate: {
                        previous: "← Prev",
                        next: "Next →"
                    },
                    emptyTable: "No records available",
                    zeroRecords: "No matching records found",
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                },
                dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex"f>>rtip',
                order: [[6, 'desc']], // Sort by date column (index 6) by default
                responsive: true,
                autoWidth: false,
                // No custom initialization needed
                initComplete: function() {
                    // Gender filter has been removed
                }
            });
        }

        // Store all records for quick access
        const allRecords = @json($records);

        // Debug: Log all records to check patient_key values
        console.log('All patient records:', allRecords.map(r => ({
            id: r.id,
            name: r.name,
            patient_key: r.patient_key,
            visit_number: r.visit_number
        })));

        // Common function to handle both Recent Patients and main table view buttons
        function handleViewResponse(element) {
            const raw = $(element).data('response') || '';
            const patientName = $(element).data('patient-name');
            const visitNumber = $(element).data('visit-number') || 1;
            const recordId = $(element).data('record-id');
            const patientKey = $(element).data('patient-key');

            console.log('View button clicked:', {
                raw: raw ? 'Has data' : 'No data',
                patientName: patientName,
                visitNumber: visitNumber,
                recordId: recordId,
                patientKey: patientKey
            });

            // Check if we have all required data
            if (!patientName || !recordId) {
                console.error('Missing required data for modal');
                return;
            }

            // For buttons without response data, try to find it in allRecords
            if (!raw || raw === '') {
                const record = allRecords.find(r => r.id == recordId);
                if (record && record.ai_response) {
                    console.log('Found response in allRecords for record ID:', recordId);
                    processResponse(record.ai_response, patientName, visitNumber, recordId, patientKey);
                    return;
                } else {
                    console.error('No response found for record ID:', recordId);
                    $('#responseContent').html('<div class="alert alert-warning">No response data available for this record.</div>');
                    return;
                }
            }

            // For buttons with response data
            processResponse(raw, patientName, visitNumber, recordId, patientKey);
        }

        // Process and display the response
        function processResponse(raw, patientName, visitNumber, recordId, patientKey) {
            console.log('Processing response for:', patientName, 'Visit:', visitNumber, 'Record ID:', recordId);
            console.log('Raw parameter received:', raw ? 'Has data' : 'No data');

            // Set conversation ID to the record ID for follow-up questions
            // This allows follow-up questions to reference the original case
            document.getElementById('conversation-id').value = recordId;

            // Validate input
            if (!raw || raw.trim() === '') {
                console.error('Empty or invalid response data');
                $('#responseContent').html('<div class="alert alert-danger">No medical response data available for this patient.</div>');
                return;
            }

            // Unescape HTML entities to allow proper rendering of HTML tags
            raw = unescapeHtml(raw);
            console.log('After unescaping HTML:', raw.substring(0, 200) + '...');

            // Update the modal title and content
            $('#patientNameTitle').text(patientName);
            $('#visitNumber').text(visitNumber);
            $('#visitBadge').show();

            // Format the AI response with proper HTML formatting (EXACT SAME AS AI RESPONSE POPUP)

            // Apply the same formatting logic as the AI response popup
            let formattedResponse = raw
                // Remove markdown formatting
                .replace(/#{1,6}\s/g, '')  // Remove heading markers
                .replace(/\*\*/g, '')      // Remove bold markers
                .replace(/\*/g, '')        // Remove italic markers
                .replace(/- /g, '• ')      // Replace dashes with bullets

                // Remove introduction and conclusion sections
                .replace(/^Based on the provided.*?guidelines,.*?\n\n/s, '')  // Remove intro
                .replace(/^As a.*?specialist:.*?\n\n/s, '')                  // Remove specialty intro
                .replace(/^.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS)/s, '')          // Remove everything before section A
                .replace(/^.*?(?=A\)\s*DIAGNOS[IE]S)/s, '')                  // Alternative section A format
                .replace(/\n\nConclusion:.*$/s, '')                          // Remove conclusion
               .replace(/\n\nNote:.*$/s, '')                                // Remove notes at the end
                .replace(/^Note:.*\n\n/s, '')                                // Remove notes at the beginning
                .replace(/\n\nIn summary.*$/s, '')                           // Remove summary
                .replace(/\n\nSummary.*$/s, '')                                // Remove notes at the beginning

                // Clean up any remaining formatting issues
                .replace(/\n{3,}/g, '\n\n')                                  // Replace multiple newlines with double newlines
                .trim();                                                      // Remove leading/trailing whitespace

            // Format the response with proper HTML formatting
            const finalFormattedHTML = formatAIResponse(formattedResponse);

            // Display the formatted response
            $('#openaiReply').html(finalFormattedHTML);

            // Sources section is hidden as requested
            try {
                const sourcesMatch = raw.match(/Sources:([\s\S]*?)(?:$|(?=\n\n\w))/i);
                if (sourcesMatch && sourcesMatch[1].trim()) {
                    const sourcesContent = sourcesMatch[1].trim();
                    $('#sourcesContent').html(formatSources(sourcesContent));
                    // Keep sources hidden
                    $('#sourcesCitation').hide();
                } else {
                    $('#sourcesCitation').hide();
                }
            } catch (error) {
                console.error('Error processing sources:', error);
                $('#sourcesCitation').hide();
            }

            // Get patient age and gender for history
            let patientAge, patientGender;

            // Try to find the record in the table
            const tableRow = $(`#recordsTable tr td:contains(${recordId})`).closest('tr');
            if (tableRow.length) {
                patientAge = parseInt(tableRow.find('td:eq(2)').text());
                patientGender = tableRow.find('td:eq(3)').text().trim().toLowerCase();
            } else {
                // If not found in table, try to find in allRecords
                const record = allRecords.find(r => r.id === recordId);
                if (record) {
                    patientAge = record.age;
                    patientGender = record.gender;
                }
            }

            console.log('Looking for patient records with:', { patientName, patientAge, patientGender, patientKey });

            // Find all records for this patient using multiple methods
            let patientRecords = [];

            // Try using patient_key first if available
            if (patientKey) {
                patientRecords = allRecords.filter(record => record.patient_key === patientKey);
                console.log(`Found ${patientRecords.length} records using patient_key`);
            }

            // If no records found or patient_key not available, fall back to name-age-gender
            if (patientRecords.length === 0) {
                patientRecords = allRecords.filter(record =>
                    record.name === patientName &&
                    record.age === patientAge &&
                    record.gender === patientGender
                );
                console.log(`Found ${patientRecords.length} records using name-age-gender`);
            }

            // If there are multiple visits, show the history section
            if (patientRecords.length > 1) {
                $('#patientHistorySection').show();
                $('#patientHistoryList').empty();

                // First, sort records chronologically to assign correct visit numbers
                const sortedForNumbering = [...patientRecords].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

                // Create a mapping of record ID to visit number
                const visitNumberMap = {};
                sortedForNumbering.forEach((record, index) => {
                    visitNumberMap[record.id] = index + 1;
                });

                // Now sort for display (newest first)
                patientRecords.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                // Add buttons for each visit
                patientRecords.forEach((record) => {
                    const isActive = record.id === recordId;
                    const visitDate = new Date(record.created_at);

                    // Check if there are multiple visits on the same day
                    const sameDay = patientRecords.filter(r => {
                        const rDate = new Date(r.created_at);
                        return rDate.toDateString() === visitDate.toDateString();
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

                    // Get the correct chronological visit number
                    const correctVisitNumber = record.visit_number || visitNumberMap[record.id];

                    const button = $(`
                        <button class="btn ${isActive ? 'btn-primary' : 'btn-outline-secondary'} btn-sm history-btn"
                                data-record-id="${record.id}"
                                data-response="${htmlEntities(record.ai_response)}"
                                data-visit-number="${correctVisitNumber}"
                                data-patient-key="${record.patient_key}">
                            <i class="fas ${isActive ? 'fa-calendar-check' : 'fa-calendar'} me-1"></i>
                            Visit #${correctVisitNumber} (${formattedDate})
                            ${isActive ? '<span class="ms-1 badge bg-light text-dark">Current</span>' : ''}
                        </button>
                    `);

                    $('#patientHistoryList').append(button);
                });
            } else {
                $('#patientHistorySection').hide();
            }
        }

        // Clear modal content on show
        $('#responseModal').on('show.bs.modal', function (event) {
            // Clear previous content but don't hide patient history section
            // It will be shown/hidden by the processResponse function based on data
            $('#patientHistoryList').empty();
            $('#responseContent').html('');
            $('#patientNameTitle').text('Loading...');
            $('#visitBadge').hide();
        });

        // Attach event handler using delegation for both static and dynamic buttons
        $(document).on('click', '.view-response-btn, .btn-view-response', function() {
            handleViewResponse(this);
        });

        // We've replaced the legacy handler with the unified one above
        // No need for duplicate code here

        // Handle clicks on history buttons
        $(document).on('click', '.history-btn', function() {
            const recordId = $(this).data('record-id');
            const response = $(this).data('response');
            const visitNumber = $(this).data('visit-number');

            // Update the content with formatted HTML
            let rawResponse = decodeHtml(response);

            // Unescape HTML entities to allow proper rendering of HTML tags
            rawResponse = unescapeHtml(rawResponse);

            // Remove intro and conclusion sections
            rawResponse = removeIntroAndConclusion(rawResponse);
            console.log('History button - Raw response (after removing intro/conclusion):', rawResponse);

            // Check if the response contains section headers
            const diagnosisMatch = rawResponse.match(/[A-Z]\)?\s+.*?DIAGNOSIS.*?/i);
            const recommendationsMatch = rawResponse.match(/[A-Z]\)?\s+.*?RECOMMENDATIONS.*?/i);
            const treatmentMatch = rawResponse.match(/[A-Z]\)?\s+.*?TREATMENT.*?/i);
            const warningsMatch = rawResponse.match(/[A-Z]\)?\s+.*?WARNINGS.*?/i);

            // Check for exact format mentioned
            const exactFormatMatch = rawResponse.match(/A\)\s+POSSIBLE\s+DIAGNOSIS|B\)\s+RECOMMENDATIONS\s+FOR\s+TESTS|C\)\s+TREATMENT\s+RECOMMENDATIONS|D\)\s+WARNINGS/i);

            console.log('History button - Section headers found:', {
                diagnosis: diagnosisMatch ? diagnosisMatch[0] : null,
                recommendations: recommendationsMatch ? recommendationsMatch[0] : null,
                treatment: treatmentMatch ? treatmentMatch[0] : null,
                warnings: warningsMatch ? warningsMatch[0] : null,
                exactFormat: exactFormatMatch ? exactFormatMatch[0] : null
            });

            const formattedResponse = formatAIResponse(rawResponse);
            console.log('History button - Formatted response:', formattedResponse);

            $('#openaiReply').html(formattedResponse);
            $('#visitNumber').text(visitNumber);

            // Update active button
            $('.history-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
            $('.history-btn .badge').remove();
            $('.history-btn i').removeClass('fa-calendar-check').addClass('fa-calendar');

            $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
            $(this).find('i').removeClass('fa-calendar').addClass('fa-calendar-check');
            if (!$(this).find('.badge').length) {
                $(this).append('<span class="ms-1 badge bg-light text-dark">Current</span>');
            }
        });

        function decodeHtml(html) {
            const txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }

        function unescapeHtml(str) {
            return str
                .replace(/&amp;/g, '&')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&quot;/g, '"')
                .replace(/&#39;/g, "'");
        }

        function htmlEntities(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        /**
         * Format AI response text with proper HTML formatting
         */
        function formatTable(rows) {
            if (!rows || rows.length === 0) return '';

            let tableHtml = '<div class="medcura-table"><table class="table table-striped table-hover">';

            for (let i = 0; i < rows.length; i++) {
                let cells = rows[i].split('|').map(cell => cell.trim()).filter(cell => cell);

                if (cells.length < 2) continue;

                tableHtml += '<tr>';

                if (i === 0) {
                    // Header row
                    for (let cell of cells) {
                        tableHtml += `<th class="table-header-cell">${cell}</th>`;
                    }
                } else {
                    // Data rows
                    for (let cell of cells) {
                        tableHtml += `<td>${cell}</td>`;
                    }
                }

                tableHtml += '</tr>';
            }

            tableHtml += '</table></div>';
            return tableHtml;
        }

        function formatAIResponse(text) {
            if (!text) return '';

            // Clean up text: remove excessive whitespace and normalize line breaks
            let cleanedText = text
                .replace(/\r\n/g, '\n')  // Normalize line endings
                .replace(/\n{3,}/g, '\n\n')  // Replace 3+ line breaks with 2
                .replace(/[ \t]{2,}/g, ' ')  // Replace multiple spaces/tabs with single space
                .replace(/^\s+|\s+$/gm, '')  // Trim whitespace from start/end of each line
                .trim();

            // Remove the Sources section from the text before formatting
            const sourcesMatch = cleanedText.match(/(📚\s*SOURCES:|Sources:)([\s\S]*?)(?:$|(?=\n\n\w))/i);
            if (sourcesMatch) {
                cleanedText = cleanedText.replace(sourcesMatch[0], '').trim();
            }

            // Debug: Log the cleaned text to see what headers we're dealing with
            console.log('Cleaned text for header matching:', cleanedText.substring(0, 500));

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
                .replace(/^📋\s*PATIENT\s+CASE\s+SUMMARY:?$/gm, '<div class="medical-section patient-summary"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">')

                // Case Urgency Section
                .replace(/^🚨\s*CASE\s+URGENCY:?$/gm, '</div></div><div class="medical-section case-urgency"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">')

                // A) Differential Diagnosis Section - Handle with or without dashes
                .replace(/^(-{0,3}A\)?\s*(DIFFERENTIAL\s+)?DIAGNOSIS.*?:?|🔬\s*.*?DIAGNOSIS.*?:?)$/gmi, '</div></div><div class="medical-section differential-diagnoses"><h4 class="section-header"><i class="fas fa-microscope"></i> A) DIFFERENTIAL DIAGNOSIS</h4><div class="section-content">')

                // B) Investigations Section - Handle with or without dashes
                .replace(/^(-{0,3}B\)?\s*.*?(RECOMMENDED\s+)?(INVESTIGATIONS?|TESTS?|DIAGNOSTIC|WORKUP).*?:?)$/gmi, '</div></div><div class="medical-section recommended-tests"><h4 class="section-header"><i class="fas fa-vials"></i> B) RECOMMENDED INVESTIGATIONS</h4><div class="section-content">')

                // C) Treatment/Management Section - Handle with or without dashes
                .replace(/^(-{0,3}C\)?\s*.*?(TREATMENT|MANAGEMENT|PLAN|THERAPY|INTERVENTION).*?:?)$/gmi, '</div></div><div class="medical-section management-plan"><h4 class="section-header"><i class="fas fa-pills"></i> C) MANAGEMENT RECOMMENDATIONS</h4><div class="section-content">')

                // D) Warning Signs Section - Handle with or without dashes
                .replace(/^(-{0,3}D\)?\s*WARNING\s+SIGNS.*?:?|⚠️\s*WARNING\s+SIGNS.*?:?)$/gmi, '</div></div><div class="medical-section warning-signs"><h4 class="section-header"><i class="fas fa-exclamation-triangle"></i>WARNING SIGNS TO MONITOR</h4><div class="section-content">')

                // Specific pattern for the exact format: "---B) RECOMMENDED INVESTIGATIONS:"
                .replace(/^---([ABCD])\)\s*(.+?):\s*$/gmi, function(match, letter, text) {
                    let icon = '';
                    let sectionClass = 'medical-section';

                    switch(letter) {
                        case 'A': icon = '<i class="fas fa-microscope"></i>'; break;
                        case 'B': icon = '<i class="fas fa-vials"></i>'; break;
                        case 'C': icon = '<i class="fas fa-pills"></i>'; break;
                        case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; break;
                    }

                    return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letter}) ${text.toUpperCase()}</h4><div class="section-content">`;
                })

                // General fallback for any remaining letter-based headers
                .replace(/^([A-D]\)\s*[A-Z\s]{5,}:?)$/gmi, function(match, p1) {
                    let sectionClass = 'medical-section';
                    let headerText = match.replace(/^[A-D]\)\s*/, '').replace(/:$/, '');
                    let letterPrefix = match.charAt(0);
                    let icon = '';

                    switch(letterPrefix) {
                        case 'A': icon = '<i class="fas fa-microscope"></i>'; break;
                        case 'B': icon = '<i class="fas fa-vials"></i>'; break;
                        case 'C': icon = '<i class="fas fa-pills"></i>'; break;
                        case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; break;
                    }

                    return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letterPrefix}) ${headerText}</h4><div class="section-content">`;
                })

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

                // Handle numbered lists - EXACT SAME AS AI RESPONSE
                if (/^\d+[\.\)]\s+/.test(line)) {
                    if (!inList || listType !== 'ol') {
                        if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        formatted += '<ol class="medical-list">';
                        inList = true;
                        listType = 'ol';
                    }
                    formatted += '<li class="bullet-item">' + line.replace(/^\d+[\.\)]\s+/, '') + '</li>';
                    continue;
                }

                // Handle bullet points - Enhanced to catch all bullet patterns
                if (/^[•\-\*]\s+/.test(line) || /^\s*[\-\*]\s+/.test(line) || /^[\s]*[•]\s*/.test(line)) {
                    if (!inList || listType !== 'ul') {
                        if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        formatted += '<ul class="medical-list">';
                        inList = true;
                        listType = 'ul';
                    }
                    formatted += '<li class="bullet-item">' + line.replace(/^[•\-\*\s]+/, '') + '</li>';
                    continue;
                } else if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }

                // Check if this might be a bullet point that wasn't caught
                if (line.includes('•') && !line.startsWith('<')) {
                    // Convert any remaining bullet points to proper list items
                    if (!inList) {
                        formatted += '<ul class="medical-list">';
                        inList = true;
                        listType = 'ul';
                    }
                    formatted += '<li class="bullet-item">' + line.replace(/^[•\-\*\s]+/, '') + '</li>';
                } else {
                    // Regular paragraph
                    formatted += `<p>${line}</p>`;
                }
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

        // Handle patient summary button click
        $(document).on('click', '.patient-summary-btn', function() {
            console.log('Patient summary button clicked!');
            const patientName = $(this).data('patient-name');
            const patientAge = $(this).data('patient-age');
            const patientGender = $(this).data('patient-gender');
            const patientKey = $(this).data('patient-key');

            console.log('Patient data:', {
                name: patientName,
                age: patientAge,
                gender: patientGender,
                key: patientKey
            });

            // Update patient info in the summary modal
            $('#summaryPatientName').text(patientName);
            $('#summaryPatientAge').text(patientAge);
            $('#summaryPatientGender').text(patientGender.charAt(0).toUpperCase() + patientGender.slice(1));
            $('#patientSummaryTitle').text(`${patientName}'s Medical Summary`);

            // Reset containers
            $('#visitSummaryContainer').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading patient history...</p>
                </div>
            `);

            $('#aiSummaryContainer').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Generating AI summary...</p>
                </div>
            `);

            // Find all records for this patient
            let patientRecords = [];

            // Try using patient_key first if available
            if (patientKey) {
                patientRecords = allRecords.filter(record => record.patient_key === patientKey);
                console.log(`Found ${patientRecords.length} records using patient_key`);
            }

            // If no records found or patient_key not available, fall back to name-age-gender
            if (patientRecords.length === 0) {
                patientRecords = allRecords.filter(record =>
                    record.name === patientName &&
                    record.age === patientAge &&
                    record.gender === patientGender
                );
                console.log(`Found ${patientRecords.length} records using name-age-gender`);
            }

            // Sort records by visit number or date
            patientRecords.sort((a, b) => {
                if (a.visit_number && b.visit_number) {
                    return a.visit_number - b.visit_number;
                }
                return new Date(a.created_at) - new Date(b.created_at);
            });

            // Generate visit summary HTML
            if (patientRecords.length > 0) {
                let visitSummaryHtml = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Visit #</th>
                                    <th>Date</th>
                                    <th>Key Findings</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                // First, sort chronologically to assign correct visit numbers
                const sortedForNumbering = [...patientRecords].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

                // Create a mapping of record ID to visit number
                const visitNumberMap = {};
                sortedForNumbering.forEach((record, index) => {
                    visitNumberMap[record.id] = index + 1;
                });

                // Now sort for display (newest first)
                patientRecords.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                patientRecords.forEach((record) => {
                    const visitDate = new Date(record.created_at);

                    // Check if there are multiple visits on the same day
                    const sameDay = patientRecords.filter(r => {
                        const rDate = new Date(r.created_at);
                        return rDate.toDateString() === visitDate.toDateString();
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

                    // Get the correct chronological visit number
                    const correctVisitNumber = record.visit_number || visitNumberMap[record.id];

                    // Extract first 100 characters of AI response as summary
                    const responseSummary = record.ai_response ?
                        record.ai_response.substring(0, 100) + (record.ai_response.length > 100 ? '...' : '') :
                        'No response available';

                    visitSummaryHtml += `
                        <tr>
                            <td>Visit #${correctVisitNumber}</td>
                            <td>${formattedDate}</td>
                            <td>${responseSummary}</td>
                        </tr>
                    `;
                });

                visitSummaryHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

                $('#visitSummaryContainer').html(visitSummaryHtml);

                // Generate AI summary
                console.log('About to call generateAISummary with', patientRecords.length, 'records');
                generateAISummary(patientRecords);
            } else {
                $('#visitSummaryContainer').html('<div class="alert alert-info">No visit history found for this patient.</div>');
                $('#aiSummaryContainer').html('<div class="alert alert-info">Cannot generate summary without patient history.</div>');
            }
        });

        // Function to generate AI summary from patient records
        let currentPatientRecords = [];

        function generateAISummary(patientRecords) {
            console.log('generateAISummary function called with:', patientRecords);
            currentPatientRecords = patientRecords; // Store for retry functionality

            // Track performance
            const startTime = performance.now();

            // Prepare the data for the AI summary
            const summaryData = {
                patient_name: $('#summaryPatientName').text(),
                patient_age: $('#summaryPatientAge').text(),
                patient_gender: $('#summaryPatientGender').text().toLowerCase(),
                visit_count: patientRecords.length,
                visits: patientRecords.map(record => ({
                    visit_number: record.visit_number || 'unknown',
                    date: new Date(record.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }),
                    ai_response: record.ai_response || 'No response available'
                }))
            };

            // Show enhanced loading state
            $('#aiSummaryContainer').html(`
                <div class="ai-summary-loading">
                    <div class="spinner-border mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="mb-2">🤖 AI Analysis in Progress</h6>
                    <p class="text-muted mb-0">Analyzing ${patientRecords.length} visit(s) to generate comprehensive summary...</p>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            `);

            // Make AJAX request to generate summary
            console.log('Sending AI summary request with data:', summaryData);
            $.ajax({
                url: '{{ route("patient.summary") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    summary_data: JSON.stringify(summaryData)
                },
                beforeSend: function() {
                    console.log('AI summary request started');
                },
                success: function(response) {
                    const endTime = performance.now();
                    const duration = ((endTime - startTime) / 1000).toFixed(2);

                    console.log('AI summary response received:', response);
                    console.log(`Summary generation completed in ${duration} seconds`);

                    if (response.success) {
                        // Extract the plain text content from the response
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = response.summary;
                        const plainTextContent = tempDiv.textContent || tempDiv.innerText || response.summary;

                        // Apply the same formatting as the response popup
                        const formattedSummary = formatAIResponse(plainTextContent);

                        // Simple, clean AI Summary Design with enhanced styling
                        const styledSummary = `
                            <div class="ai-summary-simple">
                                ${formattedSummary}
                            </div>
                        `;

                        $('#aiSummaryContainer').html(styledSummary);

                        // Extract and display sources if they exist
                        const sourcesMatch = response.summary.match(/Sources:([\s\S]*?)(?:$|(?=\n\n\w))/i);
                        if (sourcesMatch && sourcesMatch[1].trim()) {
                            const sourcesContent = sourcesMatch[1].trim();
                            $('#summarySourcesContent').html(formatSources(sourcesContent));
                            $('#summarySourcesCitation').show();
                        } else {
                            $('#summarySourcesCitation').hide();
                        }
                    } else {
                        $('#aiSummaryContainer').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${response.message || 'Failed to generate summary.'}
                                <br><button class="btn btn-smbtn-primary-custom mt-2" onclick="generateAISummary(currentPatientRecords)">
                                    <i class="fas fa-redo me-1"></i>Try Again
                                </button>
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    const endTime = performance.now();
                    const duration = ((endTime - startTime) / 1000).toFixed(2);

                    console.error('Error generating summary:', xhr);
                    console.log('Response text:', xhr.responseText);
                    console.log('Status:', xhr.status);
                    console.error(`Failed after ${duration} seconds`);

                    let errorMessage = 'An error occurred while generating the summary.';

                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        if (xhr.status === 429) {
                            errorMessage = 'Too many requests. Please wait a moment and try again.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error. Please try again later.';
                        } else if (xhr.status === 0) {
                            errorMessage = 'Network error. Please check your connection.';
                        }
                    }

                    $('#aiSummaryContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${errorMessage}
                            <br><small>Status: ${xhr.status} - ${xhr.statusText} (${duration}s)</small>
                            <br><button class="btn btn-smbtn-primary-custom mt-2" onclick="generateAISummary(currentPatientRecords)">
                                <i class="fas fa-redo me-1"></i>Try Again
                            </button>
                        </div>
                    `);
                }
            });
        }

        /**
         * Format sources content for display
         */
        function formatSources(sourcesContent) {
            if (!sourcesContent || sourcesContent.trim() === '') {
                return '';
            }

            // Split by lines and format each source
            const lines = sourcesContent.split('\n').filter(line => line.trim() !== '');
            let formatted = '<ul class="sources-list">';

            lines.forEach(line => {
                line = line.trim();
                if (line.startsWith('- ')) {
                    line = line.substring(2);
                }
                if (line.startsWith('* ')) {
                    line = line.substring(2);
                }
                formatted += `<li>${line}</li>`;
            });

            formatted += '</ul>';
            return formatted;
        }

        /**
         * Remove Patient Information section from the AI response
         */
        function removePatientInfoSection(text) {
            // Check if the text contains a Patient Information section
            const patientInfoRegex = /Patient Information:[\s\S]*?---/i;
            const match = text.match(patientInfoRegex);

            if (match) {
                // Remove the entire section including the separator line
                text = text.replace(match[0], '');

                // Clean up any extra newlines that might be left
                text = text.replace(/\n{3,}/g, '\n\n');
            }

            // Also check for the specific format with Age, Gender, Total Visits
            const patientDetailsRegex = /Age:\s*\d+\s*\n+Gender:\s*[a-zA-Z]+\s*\n+Total Visits:\s*\d+/i;
            const detailsMatch = text.match(patientDetailsRegex);

            if (detailsMatch) {
                // Remove this section as well
                text = text.replace(detailsMatch[0], '');

                // Clean up any extra newlines that might be left
                text = text.replace(/\n{3,}/g, '\n\n');
            }

            return text;
        }

        /**
         * Remove introduction and conclusion sections from the AI response
         */
        function removeIntroAndConclusion(text) {
            // First remove Patient Information section
            text = removePatientInfoSection(text);

            // Check if there's a Current Symptoms section before processing
            let currentSymptoms = null;
            const currentSymptomsRegex = /Current\s+Symptoms:.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS:?|$)/is;
            const currentSymptomsMatch = text.match(currentSymptomsRegex);

            if (currentSymptomsMatch) {
                currentSymptoms = currentSymptomsMatch[0].trim();
                console.log('Found Current Symptoms section:', currentSymptoms);
            }

            // Split the text into lines
            const lines = text.split('\n');
            let startIndex = 0;
            let endIndex = lines.length - 1;

            // Find the first section header (likely A) DIAGNOSIS)
            for (let i = 0; i < lines.length; i++) {
                if (/^A\)\s*POSSIBLE\s*DIAGNOSIS/i.test(lines[i]) ||
                    /^A\)\s*DIAGNOS[IE]S/i.test(lines[i]) ||
                    /^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i]) ||
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i])) {
                    startIndex = i;
                    break;
                }
            }

            // Find the last section header and include all content after it
            for (let i = lines.length - 1; i >= 0; i--) {
                if (/^D\)\s*WARNING\s*SIGNS/i.test(lines[i]) ||
                    /^D\)\s*WARNINGS/i.test(lines[i]) ||
                    /^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i]) ||
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i])) {
                    // Find the end of this section (next empty line or end of text)
                    for (let j = i + 1; j < lines.length; j++) {
                        // Stop at conclusion or summary
                        if (j === lines.length - 1 ||
                            (lines[j].trim() === '' && j > i + 5) ||
                            /^In\s+summary/i.test(lines[j]) ||
                            /^Summary/i.test(lines[j]) ||
                            /^Conclusion/i.test(lines[j])) {
                            endIndex = j;
                            // If we found a conclusion/summary, don't include it
                            if (/^In\s+summary/i.test(lines[j]) || /^Summary/i.test(lines[j]) || /^Conclusion/i.test(lines[j])) {
                                endIndex = j - 1;
                            }
                            break;
                        }
                    }
                    break;
                }
            }

            // Get the content between the first section header and the end of the last section
            let result = lines.slice(startIndex, endIndex + 1).join('\n');

            // Do one final check for any patient information that might be in the result
            result = removePatientInfoSection(result);

            // If we found Current Symptoms, add it back at the beginning
            if (currentSymptoms) {
                result = currentSymptoms + '\n\n' + result;
            }

            return result;
        }

        // Sources section has been removed

        // Print functionality for response modal
        $('#printResponseBtn').on('click', function() {
            const patientName = $('#patientNameTitle').text();
            const visitNumber = $('#visitNumber').text();
            let responseContent = $('#openaiReply').html();
            const sourcesContent = ''; // Sources are hidden as requested

            // Improve formatting for print by adding spacing between sections
            responseContent = responseContent
                .replace(/(A\)\s*POSSIBLE\s*DIAGNOSIS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS\s*OR\s*IMAGING:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(C\)\s*TREATMENT\s*RECOMMENDATIONS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(D\)\s*WARNING\s*SIGNS:)/g, '<h4 class="mt-4">$1</h4>');

            // Create a new window for printing
            const printWindow = window.open('', '_blank');

            // Add content to the print window
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Medical Recommendations - ${patientName}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .content { margin-bottom: 30px; line-height: 1.6; }
                        .sources { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                        h4 { color: #2c3e50; margin-top: 25px; margin-bottom: 15px; }
                        ul, ol { margin-bottom: 20px; }
                        li { margin-bottom: 8px; }
                        @media print {
                            .no-print { display: none; }
                            a { text-decoration: none; color: #000; }
                            h4 { page-break-after: avoid; }
                            ul, ol { page-break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Medical Recommendations</h2>
                        <h4>${patientName}</h4>
                        ${visitNumber ? `<p>Visit #${visitNumber}</p>` : ''}
                        <p>${new Date().toLocaleDateString()}</p>
                    </div>

                    <div class="content">
                        ${responseContent}
                    </div>

                    ${sourcesContent ? `
                    <div class="sources">
                        <h5>Sources</h5>
                        ${sourcesContent}
                    </div>
                    ` : ''}

                    <div class="text-center mt-4 no-print">
                        <button class="btn btn-primary" onclick="window.print()">Print</button>
                        <button class="btn btn-secondary ms-2" onclick="window.close()">Close</button>
                    </div>
                </body>
                </html>
            `);

            // Focus the new window
            printWindow.document.close();
            printWindow.focus();
        });

        // Print functionality for summary modal
        $('#printSummaryBtn').on('click', function() {
            const patientName = $('#patientSummaryTitle').text();
            const patientInfo = {
                name: $('#summaryPatientName').text(),
                age: $('#summaryPatientAge').text(),
                gender: $('#summaryPatientGender').text(),
                height: $('#summaryPatientHeight').text(),
                weight: $('#summaryPatientWeight').text()
            };

            let summaryContent = $('#aiSummaryContainer').html();
            const sourcesContent = $('#summarySourcesCitation').is(':visible') ? $('#summarySourcesContent').html() : '';
            const visitHistoryContent = $('#visitSummaryContainer').html();

            // Improve formatting for print by adding spacing between sections
            summaryContent = summaryContent
                .replace(/(A\)\s*POSSIBLE\s*DIAGNOSIS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS\s*OR\s*IMAGING:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(C\)\s*TREATMENT\s*RECOMMENDATIONS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(D\)\s*WARNING\s*SIGNS:)/g, '<h4 class="mt-4">$1</h4>');

            // Create a new window for printing
            const printWindow = window.open('', '_blank');

            // Add content to the print window
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Patient Summary - ${patientInfo.name}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .patient-info { margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
                        .content { margin-bottom: 30px; line-height: 1.6; }
                        .sources { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                        h4 { color: #2c3e50; margin-top: 25px; margin-bottom: 15px; }
                        h5 { color: #2c3e50; margin-top: 30px; margin-bottom: 15px; font-weight: 600; }
                        ul, ol { margin-bottom: 20px; }
                        li { margin-bottom: 8px; }
                        .table { margin-top: 15px; }
                        @media print {
                            .no-print { display: none; }
                            a { text-decoration: none; color: #000; }
                            h4, h5 { page-break-after: avoid; }
                            ul, ol { page-break-inside: avoid; }
                            .table { border-collapse: collapse; }
                            .table td, .table th { border: 1px solid #ddd; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Patient Summary</h2>
                        <h4>${patientInfo.name}</h4>
                        <p>${new Date().toLocaleDateString()}</p>
                    </div>

                    <div class="patient-info">
                        <div class="row">
                            <div class="col-md-4"><strong>Name:</strong> ${patientInfo.name}</div>
                            <div class="col-md-4"><strong>Age:</strong> ${patientInfo.age}</div>
                            <div class="col-md-4"><strong>Gender:</strong> ${patientInfo.gender}</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4"><strong>Height:</strong> ${patientInfo.height}</div>
                            <div class="col-md-4"><strong>Weight:</strong> ${patientInfo.weight}</div>
                        </div>
                    </div>

                    <div class="content">
                        <h5>Visit History</h5>
                        ${visitHistoryContent}
                    </div>

                    <div class="content">
                        <h5>AI Generated Summary</h5>
                        ${summaryContent}
                    </div>

                    <div class="text-center mt-4 no-print">
                        <button class="btn btn-primary" onclick="window.print()">Print</button>
                        <button class="btn btn-secondary ms-2" onclick="window.close()">Close</button>
                    </div>
                </body>
                </html>
            `);

            // Focus the new window
            printWindow.document.close();
            printWindow.focus();
        });
    });

    // Toggle Level 2 Content for Cases Modal
    function toggleLevel2() {
        const level2Content = document.getElementById('level2-content');
        const toggleIcon = document.querySelector('.level2-toggle .toggle-icon');

        if (level2Content.style.display === 'none') {
            level2Content.style.display = 'block';
            toggleIcon.style.transform = 'rotate(180deg)';
        } else {
            level2Content.style.display = 'none';
            toggleIcon.style.transform = 'rotate(0deg)';
        }
    }

    // Enhanced AI Response Formatting Function (EXACT COPY from OpenAI page)
    // This is a duplicate - using the main formatAIResponse function above

    function formatMedicalResponse(text) {
        if (!text) return '';

        // Check if this is Level 1 format (contains Quick Clinical Summary)
        if (text.includes('🟢 LEVEL 1: QUICK CLINICAL SUMMARY') ||
            text.includes('🟢 QUICK CLINICAL SUMMARY') ||
            text.includes('LEVEL 1: QUICK CLINICAL SUMMARY') ||
            text.includes('📋 PATIENT SUMMARY:') ||
            text.includes('🚨 CASE URGENCY') ||
            text.includes('🔍 TOP 3 DIFFERENTIAL DIAGNOSES:') ||
            text.includes('🧪 RECOMMENDED TESTS:') ||
            text.includes('💊 INITIAL MANAGEMENT PLAN:')) {
            return formatLevel1(text);
        }

        // Professional medical formatting for structured response
        let enhancedText = text
            // Handle Summary Format Headers
            .replace(/^OVERALL\s+HEALTH\s+TRAJECTORY:?$/gmi, '<div class="medcura-section patient-summary"><h4 class="section-header"><i class="fas fa-chart-line"></i> OVERALL HEALTH TRAJECTORY</h4><div class="section-content">')

            .replace(/^KEY\s+MEDICAL\s+ISSUES\s+IDENTIFIED:?$/gmi, '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header"><i class="fas fa-stethoscope"></i> KEY MEDICAL ISSUES IDENTIFIED</h4><div class="section-content">')

            .replace(/^IMPORTANT\s+TRENDS\s+IN\s+SYMPTOMS\s+OR\s+TEST\s+RESULTS:?$/gmi, '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header"><i class="fas fa-chart-area"></i> IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS</h4><div class="section-content">')

            .replace(/^TREATMENT\s+EFFECTIVENESS\s+BASED\s+ON\s+VISIT\s+PROGRESSION:?$/gmi, '</div></div><div class="medcura-section management-plan"><h4 class="section-header"><i class="fas fa-clipboard-check"></i> TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION</h4><div class="section-content">')

            .replace(/^RECOMMENDATIONS\s+FOR\s+FUTURE\s+CARE:?$/gmi, '</div></div><div class="medcura-section warning-signs"><h4 class="section-header"><i class="fas fa-user-md"></i> RECOMMENDATIONS FOR FUTURE CARE</h4><div class="section-content">')

            // Handle Sub-sections within the main sections
            .replace(/^(Status:|Reason:|Symptoms:|Vital Signs:|Laboratory Findings:|Immediate Diagnostic Steps:|Critical Interventions:|Long-term Care Considerations:|Lifestyle and Risk Factor Modification:)/gmi, '<div class="subsection-header">$1</div>');

        // Split the text into lines for processing
        let lines = enhancedText.split('\n');
        let formatted = '';
        let sectionOpened = false;

        // Process each line
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();

            // Skip empty lines
            if (!line) {
                formatted += '<br>';
                continue;
            }

            // Skip processing if line is already HTML (from our replacement above)
            if (line.startsWith('<div') || line.startsWith('</div>') || line.startsWith('<h') || line.startsWith('<hr')) {
                formatted += line;
                if (line.includes('section-content')) {
                    sectionOpened = true;
                }
                continue;
            }

            // Regular paragraph
            if (!sectionOpened) {
                // If no section is opened yet, start with a default section
                formatted += '<div class="medcura-section"><div class="section-content">';
                sectionOpened = true;
            }
            formatted += '<p>' + line + '</p>';
        }

        // Close any open sections
        if (sectionOpened) {
            formatted += '</div></div>';
        }

        return formatted;
    }

    function formatLevel1(text) {
        if (!text) return '';

        let formatted = '<div class="medcura-level1">';

        // Handle Level 1 header
        text = text.replace(/🟢\s*LEVEL\s+1:\s*QUICK\s+CLINICAL\s+SUMMARY/i,
            '<div class="level-header level1-header">🟢 LEVEL 1: QUICK CLINICAL SUMMARY</div>');

        // Handle "Core Medical Analysis" header
        text = text.replace(/^Core\s+Medical\s+Analysis/i,
            '<div class="level-header level1-header">Core Medical Analysis</div>');

        // Patient Summary Section
        text = text.replace(/📋\s*PATIENT\s+SUMMARY:/i,
            '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT SUMMARY</h4><div class="section-content">');

        // Case Urgency Section
        text = text.replace(/🚨\s*CASE\s+URGENCY/i,
            '</div></div><div class="medcura-section case-urgency"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">');

        // Top 3 Differential Diagnoses Section
        text = text.replace(/🔍\s*TOP\s+3\s+DIFFERENTIAL\s+DIAGNOSES:/i,
            '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header">🔍 TOP 3 DIFFERENTIAL DIAGNOSES</h4><div class="section-content">');

        // Recommended Tests Section
        text = text.replace(/🧪\s*RECOMMENDED\s+TESTS:/i,
            '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header">🧪 RECOMMENDED TESTS</h4><div class="section-content">');

        // Initial Management Plan Section
        text = text.replace(/💊\s*INITIAL\s+MANAGEMENT\s+PLAN:/i,
            '</div></div><div class="medcura-section management-plan"><h4 class="section-header">💊 INITIAL MANAGEMENT PLAN</h4><div class="section-content">');

        // Warning Signs Section
        text = text.replace(/D\)\s*WARNING\s+SIGNS\s+TO\s+MONITOR/i,
            '</div></div><div class="medcura-section warning-signs"><h4 class="section-header"> WARNING SIGNS TO MONITOR</h4><div class="section-content">');

        // Level 2 Detailed Medical Report Section
        text = text.replace(/🔵\s*DETAILED\s+MEDICAL\s+REPORT\s+\(Click\s+to\s+Expand\)/i,
            '</div></div><div class="medcura-section level2-section"><h4 class="section-header toggle-section" onclick="toggleLevel2()">🔵 DETAILED MEDICAL REPORT (Click to Expand) <i class="fas fa-chevron-down toggle-icon"></i></h4><div class="section-content level2-content" style="display: none;">');

        // Process the text line by line
        let lines = text.split('\n');
        let processedText = '';
        let inTable = false;
        let tableRows = [];

        for (let line of lines) {
            line = line.trim();

            // Skip if already HTML
            if (line.includes('<div') || line.includes('</div>') || line.includes('<h4')) {
                if (inTable) {
                    processedText += formatMedCuraTable(tableRows);
                    inTable = false;
                    tableRows = [];
                }
                processedText += line + '\n';
                continue;
            }

            // Handle table rows (for differential diagnoses)
            if (line.includes('|') && line.split('|').length >= 4) {
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                }
                tableRows.push(line);
                continue;
            } else if (inTable) {
                processedText += formatMedCuraTable(tableRows);
                inTable = false;
                tableRows = [];
            }

            // Handle bullet points
            if (line.match(/^[•\-\*]\s+/)) {
                processedText += '<li class="bullet-item">' + line.replace(/^[•\-\*]\s+/, '') + '</li>\n';
                continue;
            }

            // Handle urgency levels
            if (line.match(/^\s*(EMERGENCY|URGENT|ROUTINE)\s*$/i)) {
                const urgency = line.toLowerCase();
                processedText += `<div class="urgency-badge ${urgency}">${line.toUpperCase()}</div>\n`;
                continue;
            }

            // Handle regular text
            if (line.trim()) {
                processedText += '<p>' + line + '</p>\n';
            } else {
                processedText += '<br>\n';
            }
        }

        // Close any remaining table
        if (inTable) {
            processedText += formatMedCuraTable(tableRows);
        }

        formatted += processedText;
        formatted += '</div></div></div>';

        return formatted;
    }

    function formatMedCuraTable(rows) {
        if (!rows || rows.length === 0) return '';

        let tableHtml = '<div class="medcura-table"><table class="table table-striped table-hover">';

        for (let i = 0; i < rows.length; i++) {
            let cells = rows[i].split('|').map(cell => cell.trim()).filter(cell => cell);

            if (cells.length < 2) continue;

            tableHtml += '<tr>';

            if (i === 0) {
                // Header row
                for (let cell of cells) {
                    tableHtml += `<th>${cell}</th>`;
                }
            } else {
                // Data rows
                for (let cell of cells) {
                    tableHtml += `<td>${cell}</td>`;
                }
            }

            tableHtml += '</tr>';
        }

        tableHtml += '</table></div>';
        return tableHtml;
    }

    // Toggle Level 2 section function (same as AI response popup)
    function toggleLevel2() {
        const level2Content = document.querySelector('.level2-content');
        const toggleIcon = document.querySelector('.toggle-icon');

        if (level2Content.style.display === 'none' || level2Content.style.display === '') {
            level2Content.style.display = 'block';
            toggleIcon.style.transform = 'rotate(180deg)';
            toggleIcon.classList.add('rotated');
        } else {
            level2Content.style.display = 'none';
            toggleIcon.style.transform = 'rotate(0deg)';
            toggleIcon.classList.remove('rotated');
        }
    }

    // Follow-up chat functionality (copied from AI response popup)
    function setupFollowUpChat() {
        const followUpForm = document.getElementById('follow-up-form');
        const chatMessages = document.getElementById('chat-messages');

        if (followUpForm) {
            followUpForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const messageInput = document.getElementById('follow-up-message');
                const message = messageInput.value.trim();
                const conversationId = document.getElementById('conversation-id').value;

                if (!message) return;

                // Add user message to chat
                addChatMessage(message, 'user');

                // Clear input
                messageInput.value = '';

                // Show typing indicator
                const typingIndicator = addTypingIndicator();

                // Send to server
                fetch('{{ route("openai.follow-up") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: conversationId
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 401) {
                            throw new Error('API_KEY_ERROR');
                        }
                        throw new Error('SERVER_ERROR');
                    }
                    return response.json();
                })
                .then(data => {
                    removeTypingIndicator(typingIndicator);

                    if (data.success) {
                        addChatMessage(data.message, 'ai');

                        if (data.conversation_id) {
                            document.getElementById('conversation-id').value = data.conversation_id;
                        }
                    } else if (data.api_key_error) {
                        addErrorMessage(data.message || 'OpenAI API key is invalid or expired. Please contact the administrator.', true);
                    } else {
                        addErrorMessage(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    removeTypingIndicator(typingIndicator);

                    if (error.message === 'API_KEY_ERROR') {
                        addErrorMessage('OpenAI API key is invalid or expired. Please contact the administrator.', true);
                    } else {
                        addErrorMessage('Failed to connect to the server. Please try again later.');
                    }
                    console.error('Error:', error);
                });
            });
        }
    }

    // Supporting functions for follow-up chat
    function addChatMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${sender}-message`;

        // Create message content
        if (sender === 'ai') {
            const pre = document.createElement('pre');
            pre.className = 'response-text';
            pre.style.margin = '0';
            pre.style.whiteSpace = 'pre-wrap';

            // Add empty pre element first
            messageDiv.appendChild(pre);

            // Add timestamp
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            const now = new Date();
            timeDiv.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            messageDiv.appendChild(timeDiv);

            // Add to chat
            document.getElementById('chat-messages').appendChild(messageDiv);

            // Format the response to remove markdown symbols and unwanted sections
            let formattedResponse = content
                // Remove markdown formatting
                .replace(/#{1,6}\s/g, '')  // Remove heading markers
                .replace(/\*\*/g, '')      // Remove bold markers
                .replace(/\*/g, '')        // Remove italic markers
                .replace(/- /g, '• ')      // Replace dashes with bullets

                // Remove introduction and conclusion sections
                .replace(/^Based on the provided.*?guidelines,.*?\n\n/s, '')  // Remove intro
                .replace(/^As a.*?specialist:.*?\n\n/s, '')                  // Remove specialty intro
                .replace(/^.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS)/s, '')          // Remove everything before section A
                .replace(/^.*?(?=A\)\s*DIAGNOS[IE]S)/s, '')                  // Alternative section A format
                .replace(/\n\nConclusion:.*$/s, '')                          // Remove conclusion
                .replace(/\n\nNote:.*$/s, '')                                // Remove notes at the end
                .replace(/^Note:.*\n\n/s, '')                                // Remove notes at the beginning
                .replace(/\n\nIn summary.*$/s, '')                           // Remove summary
                .replace(/\n\nSummary.*$/s, '')                                // Remove notes at the beginning

                // Clean up any remaining formatting issues
                .replace(/\n{3,}/g, '\n\n')                                  // Replace multiple newlines with double newlines
                .trim();                                                     // Remove leading/trailing whitespace

            // Start typing animation
            typeText(pre, formattedResponse);
        } else {
            // For user messages, show immediately
            messageDiv.textContent = content;

            // Add timestamp
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            const now = new Date();
            timeDiv.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            messageDiv.appendChild(timeDiv);

            // Add to chat
            document.getElementById('chat-messages').appendChild(messageDiv);
        }

        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
    }

    function addTypingIndicator() {
        const id = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.className = 'typing-indicator';
        typingDiv.id = id;

        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('span');
            typingDiv.appendChild(dot);
        }

        document.getElementById('chat-messages').appendChild(typingDiv);

        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;

        return id;
    }

    function removeTypingIndicator(id) {
        const indicator = document.getElementById(id);
        if (indicator) {
            indicator.remove();
        }
    }

    function addErrorMessage(message, isApiKeyError = false) {
        const errorDiv = document.createElement('div');
        errorDiv.className = isApiKeyError ? 'alert alert-danger' : 'alert alert-warning';

        if (isApiKeyError) {
            // Create icon element
            const icon = document.createElement('i');
            icon.className = 'fas fa-exclamation-triangle me-2';
            errorDiv.appendChild(icon);

            // Create strong element for the title
            const strong = document.createElement('strong');
            strong.textContent = 'API Key Error: ';
            errorDiv.appendChild(strong);

            // Add the message text
            const textNode = document.createTextNode(message);
            errorDiv.appendChild(textNode);
        } else {
            errorDiv.textContent = message;
        }

        document.getElementById('chat-messages').appendChild(errorDiv);

        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;

        // Only auto-remove regular errors, not API key errors
        if (!isApiKeyError) {
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }
    }

    function typeText(element, text, speed = 10) {
        let i = 0;
        element.textContent = '';

        function typing() {
            if (i < text.length) {
                // Add character by character
                element.textContent += text.charAt(i);
                i++;

                // Scroll to bottom as text is being typed
                const container = element.closest('.modal-body');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }

                // Adjust typing speed based on punctuation
                const currentChar = text.charAt(i - 1);
                let delay = speed;
                if (currentChar === '.' || currentChar === '!' || currentChar === '?') {
                    delay = speed * 10;
                } else if (currentChar === ',' || currentChar === ';' || currentChar === ':') {
                    delay = speed * 5;
                }

                setTimeout(typing, delay);
            }
        }

        typing();
    }

    // Initialize follow-up chat when modal is shown
    $('#responseModal').on('shown.bs.modal', function () {
        setupFollowUpChat();
    });
</script>

@endpush
