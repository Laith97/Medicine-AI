@extends('master')

@section('title', 'Video Consultation')

@push('styles')
<style>
    body { margin: 0; padding: 0; overflow: hidden; }
    #video-container { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: #1a1a1a; }
</style>
@endpush

@section('content')
<div id="video-container"></div>
@endsection

@push('scripts')
<script crossorigin src="https://unpkg.com/@daily-co/daily-js"></script>
<script>
    // Use Daily.co public room (no API call needed)
    const roomUrl = 'https://{{ config('daily.domain') }}/appointment-{{ $appointment->id }}';
    
    console.log('🎥 Joining room:', roomUrl);
    console.log('👤 User role:', '{{ auth()->user()->role }}');
    console.log('📋 Appointment ID:', '{{ $appointment->id }}');
    
    const callFrame = window.DailyIframe.createFrame(document.getElementById('video-container'), {
        showLeaveButton: true,
        iframeStyle: { position: 'fixed', width: '100%', height: '100%', border: '0', top: '0', left: '0' }
    });

    callFrame.join({ url: roomUrl })
        .then(() => {
            console.log('✅ Successfully joined video call');
        })
        .catch(error => {
            console.error('❌ Failed to join video call:', error);
            document.getElementById('video-container').innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100vh; background: #1a1a1a; color: white; flex-direction: column;">
                    <h2>Unable to Join Video Call</h2>
                    <p>Network connectivity issue. Please check your internet connection.</p>
                    <p style="color: #888; font-size: 14px;">Error: ${error.errorMsg || error.message || 'Unknown error'}</p>
                    <p style="color: #888; font-size: 12px;">Room: ${roomUrl}</p>
                    <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Retry</button>
                </div>
            `;
        });
</script>
@endpush
