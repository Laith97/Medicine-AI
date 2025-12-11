@extends('layouts.app')

@section('page-title', 'Advanced Analytics Dashboard')

@section('content')
<div class="container-fluid">
    <div id="analytics-dashboard"></div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ mix('resources/js/analytics-dashboard/main.tsx') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ mix('resources/js/analytics-dashboard/index.css') }}">
@endpush
