@extends('master')

@section('title', 'Settings')

@section('content')
<div class="main-content">
    <div class="layout-px-spacing">
        <div class="middle-content container-xxl p-0">

            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form action="{{ route('settings.update') }}" method="POST" class="bg-white p-4 rounded shadow-sm">
                            @csrf
                            @method('PUT')
            
                            <h5 class="mb-4 text-center">Select Criterion for Symptom Evaluation</h5>
            
                            <div class="mb-3 text-center">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="nice" name="criterion" value="NICE"
                                        {{ ($setting && $setting->criterion == 'NICE') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nice">NICE</label>
                                </div>
            
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="cdc" name="criterion" value="CDC"
                                        {{ (!$setting || $setting->criterion == 'CDC') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cdc">CDC</label>
                                </div>
            
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="mayo_clinic" name="criterion" value="Mayo Clinic"
                                        {{ ($setting && $setting->criterion == 'Mayo Clinic') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mayo_clinic">Mayo Clinic</label>
                                </div>
                            </div>
            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn" style="background-color: #DE6262; color: white;">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            

        </div>
    </div>
</div>
@endsection
