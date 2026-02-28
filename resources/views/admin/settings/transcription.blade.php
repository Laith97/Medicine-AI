@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Transcription Settings') }}</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="transcription_provider" class="form-label">Transcription Provider</label>
                            <select class="form-select" id="transcription_provider" name="transcription_provider">
                                <option value="google" {{ $provider == 'google' ? 'selected' : '' }}>Google Cloud Speech-to-Text</option>
                                <option value="assemblyai" {{ $provider == 'assemblyai' ? 'selected' : '' }}>AssemblyAI</option>
                            </select>
                            <div class="form-text">Select the default provider for ambient listening.</div>
                        </div>

                        <div class="mb-3">
                            <label for="assemblyai_api_key" class="form-label">AssemblyAI API Key</label>
                            <input type="password" class="form-control" id="assemblyai_api_key" name="assemblyai_api_key" value="{{ $assemblyai_key }}">
                        </div>

                        <div class="mb-3">
                            <label for="audio_retention_hours" class="form-label">Audio Retention (Hours)</label>
                            <input type="number" class="form-control" id="audio_retention_hours" name="audio_retention_hours" value="{{ $retention_hours }}">
                            <div class="form-text">How long to keep raw audio files before automatic deletion.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
