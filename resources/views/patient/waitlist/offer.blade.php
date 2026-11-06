@extends('layouts.patient')

@section('title', 'Waitlist Offer')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Appointment Offer</h2>
            <p class="text-muted mb-0">Offer #{{ $entry->id }} for Dr. {{ $entry->waitlist->doctor->name ?? 'your doctor' }}</p>
        </div>
        <a href="{{ route('patient.waitlist.status', $entry->waitlist->id) }}" class="btn btn-outline-secondary">Back to Waitlist</a>
    </div>

    <div class="card">
        <div class="card-body">
            <h5>Available Slot</h5>
            <p class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($entry->slot_date)->format('l, F j, Y') }}</p>
            <p class="mb-1"><strong>Time:</strong> {{ \Carbon\Carbon::parse($entry->slot_time)->format('g:i A') }}</p>
            @if($entry->response_deadline)
                <p class="mb-3"><strong>Respond by:</strong> {{ \Carbon\Carbon::parse($entry->response_deadline)->format('M j, Y g:i A') }}</p>
            @endif
            <div class="d-flex gap-2">
                <form action="{{ route('patient.waitlist.accept-offer', $entry->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">Accept Offer</button>
                </form>
                <form action="{{ route('patient.waitlist.decline-offer', $entry->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Decline this offer? You will stay on the waitlist.')">Decline</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
