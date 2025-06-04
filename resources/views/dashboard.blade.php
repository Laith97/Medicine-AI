{{-- filepath: /home/laith/Documents/Medicine/resources/views/dashboard.blade.php --}}
@extends('master')

@section('title', 'Dashboard')

@section('content')
<div class="container">
    <br>
    @if(Auth::user())
        <h2 class="mb-4">Welcome, {{ Auth::user()->name }}!</h2>
    @endif

    <!-- Quick Actions -->
    <div class="mb-4 d-flex gap-2">
        <a href="{{ route('ask-openai') }}" 
           class="btn"
           style="background: #DE6262; color: #fff; border: none; font-weight: 500; box-shadow: 0 2px 6px rgba(222,98,98,0.15);">
            <i class="fas fa-user-plus me-1"></i> Add New Patient
        </a>
        <a href="{{ route('cases') }}" 
           class="btn"
           style="background: #fff; color: #DE6262; border: 2px solid #DE6262; font-weight: 500;">
            <i class="fas fa-list me-1"></i> View All Cases
        </a>
    </div>

    <!-- Statistics Section -->
    <div class="row mb-6">
        <div class="col-md-6">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Cases</h6>
                    <p class="card-text h3 mb-0">{{ $records->count() }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted">Last Case Added</h6>
                    <p class="card-text h3 mb-0">
                        @if($records->count())
                            {{ \Carbon\Carbon::parse($records->sortByDesc('created_at')->first()->created_at)->format('Y-m-d H:i') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cases Over Time Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Cases Over Time</h6>
                    <canvas id="casesChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

<!-- Recent Cases Table -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h6 class="card-title mb-3">Recent Cases</h6>
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Patient Name</th>
                        <th>Age</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records->sortByDesc('created_at')->take(10) as $record)
                        <tr>
                            <td>{{ $record->id }}</td>
                            <td>{{ $record->name ?? 'N/A' }}</td>
                            <td>{{ $record->age ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($record->created_at)->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                    @if($records->count() == 0)
                        <tr>
                            <td colspan="4" class="text-center">No cases found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const records = @json($records);

    // Cases Over Time Data
    const casesOverTimeData = {};
    records.forEach(record => {
        if(record.created_at){
            const date = (new Date(record.created_at)).toLocaleDateString();
            casesOverTimeData[date] = (casesOverTimeData[date] || 0) + 1;
        }
    });
    const casesOverTimeLabels = Object.keys(casesOverTimeData);
    const casesOverTimeValues = Object.values(casesOverTimeData);

    // Render Cases Over Time Chart
    new Chart(document.getElementById('casesChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: casesOverTimeLabels,
            datasets: [{
                label: 'Cases',
                data: casesOverTimeValues,
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>
@endpush