@extends('layouts.admin')
@section('title','Usage Analytics')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-line" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Usage Analytics</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">OpenAI API usage patterns · {{ $startDate->format('M j, Y') }} – {{ $endDate->format('M j, Y') }}</p>
            </div>
        </div>
        <span class="badge d-none d-md-inline" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:6px 12px;font-weight:700">{{ ucfirst(str_replace('_',' ', $period)) }}</span>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.usage-analytics') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="period" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase">Time Period</label>
                    <select name="period" id="period" class="form-select" onchange="this.form.submit()" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                        <option value="7_days" {{ $period === '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30_days" {{ $period === '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="90_days" {{ $period === '90_days' ? 'selected' : '' }}>Last 90 Days</option>
                        <option value="1_year" {{ $period === '1_year' ? 'selected' : '' }}>Last Year</option>
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="p-2 px-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;font-size:0.82rem;color:#475569"><i class="far fa-calendar me-1" style="color:#94a3b8"></i> {{ $startDate->format('M j, Y') }} – {{ $endDate->format('M j, Y') }}</div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-chart-area" style="color:#64748b"></i> Daily Usage Trends</h5></div>
        <div class="card-body p-3">
            <div style="position:relative;height:300px"><canvas id="dailyUsageChart"></canvas></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-layer-group" style="color:#64748b"></i> Usage by Request Type</h5></div>
                <div class="table-responsive" style="border-top:1px solid #f1f5f9">
                    <table class="table mb-0" style="font-size:0.84rem;border-collapse:separate;border-spacing:0">
                        <thead>
                            <tr style="background:#f8fafc">
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Type</th>
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Requests</th>
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Tokens</th>
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usageByType as $type)
                            <tr>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle"><span class="badge" style="background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8;border-radius:20px;font-size:0.68rem">{{ ucfirst($type->request_type) }}</span></td>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-weight:700;color:#0f172a">{{ number_format($type->requests) }}</td>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">{{ number_format($type->tokens) }}</td>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-weight:600;color:#059669">${{ number_format($type->cost, 4) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No data available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-robot" style="color:#64748b"></i> Model Usage Statistics</h5></div>
                <div class="table-responsive" style="border-top:1px solid #f1f5f9">
                    <table class="table mb-0" style="font-size:0.84rem;border-collapse:separate;border-spacing:0">
                        <thead>
                            <tr style="background:#f8fafc">
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Model</th>
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Requests</th>
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Avg Tokens</th>
                                <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Total Tokens</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($modelUsage as $model)
                            <tr>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle"><code style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:2px 6px;font-size:0.76rem;color:#334155">{{ $model->model_used ?: 'Unknown' }}</code></td>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-weight:700;color:#0f172a">{{ number_format($model->requests) }}</td>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">{{ number_format($model->avg_tokens) }}</td>
                                <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">{{ number_format($model->tokens) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No data available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-users" style="color:#64748b"></i> Top Users by Usage <span class="badge bg-light border text-muted" style="border-radius:20px">{{ $topUsers->count() }}</span></h5></div>
        <div class="card-body p-0">
            @forelse($topUsers as $user)
            <div class="d-flex justify-content-between align-items-center px-3 py-3" style="border-bottom:1px solid #f1f5f9">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.82rem">{{ strtoupper(substr($user->name,0,1)) }}</div>
                    <div>
                        <div style="font-weight:700;color:#0f172a;font-size:0.88rem">{{ $user->name }}</div>
                        <div style="font-size:0.74rem;color:#64748b">{{ $user->email }}</div>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-weight:800;color:#0f172a;font-size:0.88rem">{{ number_format($user->total_requests) }} requests</div>
                    <div style="font-size:0.76rem;color:#64748b">
                        @php $totalTokens = $user->openaiUsages->sum('total_tokens'); $totalCost = $user->openaiUsages->sum('total_cost'); @endphp
                        {{ number_format($totalTokens) }} tokens · ${{ number_format($totalCost, 4) }}
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-users"></i></div><p class="text-muted small mb-0">No usage data available for the selected period.</p></div>
            @endforelse
        </div>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('dailyUsageChart').getContext('2d');
    const data = @json($dailyUsage);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => { const d=new Date(item.date); return d.toLocaleDateString('en-US',{month:'short',day:'numeric'}); }),
            datasets: [
                { label: 'Requests', data: data.map(item => item.requests), borderColor: '#0f172a', backgroundColor: 'rgba(15,23,42,0.06)', tension: 0.4, fill: true, yAxisID: 'y', borderWidth: 2 },
                { label: 'Tokens (thousands)', data: data.map(item => item.tokens / 1000), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.06)', tension: 0.4, yAxisID: 'y1', borderWidth: 2 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid:{display:false}, ticks:{color:'#64748b',font:{size:11}} },
                y: { type:'linear', display:true, position:'left', title:{display:true,text:'Requests',color:'#64748b'}, grid:{color:'#f1f5f9'}, ticks:{color:'#64748b'} },
                y1: { type:'linear', display:true, position:'right', title:{display:true,text:'Tokens (thousands)',color:'#64748b'}, grid:{drawOnChartArea:false}, ticks:{color:'#64748b'} }
            },
            plugins: { legend:{ position:'top', labels:{usePointStyle:true,color:'#0f172a',font:{weight:'700'}} }, tooltip:{ callbacks:{ afterLabel:function(c){ if(c.datasetIndex===0){ const p=data[c.dataIndex]; return `Cost: $${parseFloat(p.cost).toFixed(4)}`; } return ''; } } } }
        }
    });
});
</script>
@endpush
@endsection
