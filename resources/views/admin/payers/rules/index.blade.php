@extends('layouts.admin')

@section('title', 'Rules for ' . $payer->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Rules Configuration</h1>
                <p class="text-muted">{{ $payer->name }} ({{ $payer->payer_id }})</p>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.payers.rules.create', $payer) }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Rule
                </a>
                <a href="{{ route('admin.payers.show', $payer) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Payer
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Rules</label>
                        <input type="text" class="form-control" id="search" name="search"
                               value="{{ request('search') }}" placeholder="Search by rule type...">
                    </div>
                    <div class="col-md-3">
                        <label for="rule_type" class="form-label">Rule Type</label>
                        <select class="form-select" id="rule_type" name="rule_type">
                            <option value="">All Types</option>
                            @foreach($ruleTypes as $type)
                                <option value="{{ $type->id }}" {{ request('rule_type') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All</option>
                            <option value="1" {{ request('priority') == '1' ? 'selected' : '' }}>High (1-3)</option>
                            <option value="4" {{ request('priority') == '4' ? 'selected' : '' }}>Medium (4-7)</option>
                            <option value="8" {{ request('priority') == '8' ? 'selected' : '' }}>Low (8-10)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                            @if(request()->hasAny(['search', 'rule_type', 'priority']))
                                <a href="{{ route('admin.payers.rules.index', $payer) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rules Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rules ({{ $rules->total() }})</h5>
                <div class="btn-group">
                    <a href="{{ route('admin.payers.rules.export', $payer) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-download me-1"></i>Export
                    </a>
                    <a href="{{ route('admin.payers.rules.import', $payer) }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-upload me-1"></i>Import
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($rules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Priority</th>
                                    <th>Rule Type</th>
                                    <th>Conditions</th>
                                    <th>Actions</th>
                                    <th>Applications</th>
                                    <th>Last Applied</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $rule->priority <= 3 ? 'danger' : ($rule->priority <= 7 ? 'warning' : 'secondary') }}">
                                                {{ $rule->priority }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $rule->ruleType->name }}</strong>
                                            <br><small class="text-muted">{{ $rule->ruleType->description }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ count($rule->conditions) }} condition(s)
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ count($rule->actions) }} action(s)
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $rule->applications()->count() }}</span>
                                        </td>
                                        <td>
                                            @if($rule->applications()->exists())
                                                {{ $rule->applications()->latest()->first()->created_at->format('M d, H:i') }}
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.payers.rules.show', [$payer, $rule]) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.payers.rules.edit', [$payer, $rule]) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="testRule({{ $rule->id }})">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <form action="{{ route('admin.payers.rules.destroy', [$payer, $rule]) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this rule?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer">
                        {{ $rules->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No rules found</h5>
                        <p class="text-muted">Get started by adding your first rule for this payer.</p>
                        <a href="{{ route('admin.payers.rules.create', $payer) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add First Rule
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Rule Testing Modal -->
<div class="modal fade" id="testRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testRuleForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="test_claim_data" class="form-label">Sample Claim Data (JSON)</label>
                        <textarea class="form-control" id="test_claim_data" name="claim_data" rows="10" placeholder='{
  "patient_id": "PAT001",
  "provider_id": "DOC001",
  "service_code": "99213",
  "diagnosis_codes": ["M54.5"],
  "procedure_codes": ["CPT123"],
  "amount": 150.00,
  "date_of_service": "2024-01-15"
}'></textarea>
                        <div class="form-text">Enter sample claim data in JSON format to test the rule</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Test Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function testRule(ruleId) {
    $('#testRuleModal').modal('show');
    $('#testRuleForm').attr('action', `{{ route('admin.payers.rules.test', [$payer, 'RULE_ID']) }}`.replace('RULE_ID', ruleId));
}

$('#testRuleForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch($(this).attr('action'), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Rule test completed! Check the browser console for results.');
            // console.log('Rule Test Result:', data.result);
        } else {
            alert('Test failed: ' + (data.error || 'Unknown error'));
        }
        $('#testRuleModal').modal('hide');
    })
    .catch(error => {
        alert('Test failed: ' + error.message);
        $('#testRuleModal').modal('hide');
    });
});
</script>
@endsection
