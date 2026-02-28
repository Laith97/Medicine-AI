@extends('master')

@section('title', 'Video Room Error')

@section('content')
<div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #1a1a1a; color: white; flex-direction: column; padding: 20px;">
    <i class="fas fa-exclamation-triangle" style="font-size: 64px; color: #ff6b6b; margin-bottom: 20px;"></i>
    <h2>Unable to Start Video Call</h2>
    <p style="color: #ccc; margin: 20px 0;">{{ $error }}</p>
    <div style="margin-top: 30px;">
        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-primary" style="padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;">
            Back to Appointment
        </a>
        <button onclick="location.reload()" class="btn btn-secondary" style="padding: 12px 24px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Try Again
        </button>
    </div>
</div>
@endsection
