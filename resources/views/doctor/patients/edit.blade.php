@extends('master')

@section('content')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%);border-radius:12px;padding:2.5rem;margin-bottom:2rem}
</style>
<div class="container-fluid" style="background:#f8fafc">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-user-edit me-2"></i>Edit Patient</h2>
                    <p class="text-muted mb-0">Update patient information</p>
                </div>
                @php
                    $prev = url()->previous();
                    $backUrl = $prev !== url()->current() && str_contains($prev, '/doctor/') ? $prev : route('doctor.patients.show', $patient->id);
                @endphp
                <a href="{{ $backUrl }}" class="btn" style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);color:#ffffff;border-radius:8px;padding:0.55rem 1rem;font-weight:500;font-size:0.84rem"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4" style="background:#f8fafc">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05)">
                <div class="px-4 py-3 d-flex align-items-center gap-2" style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <span class="d-flex align-items-center justify-content-center" style="width:32px;height:32px;border-radius:8px;background:#ffffff;border:1px solid #e2e8f0;color:#DE6262"><i class="fas fa-user-edit"></i></span>
                    <h4 class="mb-0" style="font-size:1rem;font-weight:600;color:#0f172a">Edit Patient</h4>
                    <span class="ms-auto" style="font-size:0.72rem;font-weight:600;padding:0.25rem 0.6rem;border-radius:99px;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569">ID {{ $patient->id }}</span>
                </div>
                <div class="p-4">
                    <form method="POST" action="{{ route('doctor.patients.update', $patient->id) }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label" style="font-size:0.84rem;font-weight:500;color:#334155">Full name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $patient->name) }}" required style="border:1px solid #e2e8f0;border-radius:8px;padding:0.6rem 0.85rem;font-size:0.875rem">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label" style="font-size:0.84rem;font-weight:500;color:#334155">Email address *</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $patient->email) }}" required style="border:1px solid #e2e8f0;border-radius:8px;padding:0.6rem 0.85rem;font-size:0.875rem">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="age" class="form-label" style="font-size:0.84rem;font-weight:500;color:#334155">Age *</label>
                                <input type="number" class="form-control @error('age') is-invalid @enderror" id="age" name="age" value="{{ old('age', $patient->age) }}" min="1" max="150" required style="border:1px solid #e2e8f0;border-radius:8px;padding:0.6rem 0.85rem;font-size:0.875rem">
                                @error('age')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label" style="font-size:0.84rem;font-weight:500;color:#334155">Gender *</label>
                                <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required style="border:1px solid #e2e8f0;border-radius:8px;padding:0.6rem 0.85rem;font-size:0.875rem">
                                    <option value="">Select gender</option>
                                    <option value="male" {{ old('gender', $patient->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $patient->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $patient->gender) === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="phone" class="form-label" style="font-size:0.84rem;font-weight:500;color:#334155">Phone number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $patient->phone) }}" style="border:1px solid #e2e8f0;border-radius:8px;padding:0.6rem 0.85rem;font-size:0.875rem">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn flex-grow-1" style="background:#0f172a;border:1px solid #0f172a;color:#ffffff;border-radius:8px;padding:0.65rem;font-weight:600;font-size:0.875rem"><i class="fas fa-save me-2"></i>Update Patient</button>
                            <a href="{{ route('doctor.patients.show', $patient->id) }}" class="btn flex-grow-1 text-center" style="background:#ffffff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.65rem;font-weight:500;font-size:0.875rem">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
