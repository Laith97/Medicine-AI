@extends('layouts.admin')

@section('title', 'SMS Provider Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">SMS Provider Settings</h1>
            <p class="text-gray-600">Manage SMS providers and assign countries for intelligent routing</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Provider Configuration Panel -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Provider Configuration & Country Assignments</h2>

                    <!-- Provider Cards -->
                    <div class="space-y-6">
                        @foreach($providers as $key => $provider)
                            <div class="border rounded-lg p-4 {{ $provider['configured'] ? 'border-green-200 bg-green-50' : 'border-gray-200' }}">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            @if($provider['configured'])
                                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                            @else
                                                <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                            @endif
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">{{ $provider['name'] }}</h3>
                                            <p class="text-sm text-gray-600">
                                                @if($provider['configured'])
                                                    <span class="text-green-600 font-medium">✓ Configured</span>
                                                @else
                                                    <span class="text-red-600 font-medium">✗ Not Configured</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    @if($provider['configured'] && $key !== 'log')
                                        <div class="flex space-x-2">
                                            <button onclick="openAssignModal('{{ $key }}', '{{ $provider['name'] }}')"
                                                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                                Assign Countries
                                            </button>
                                            @if(isset($activeProvidersWithCountries[$key]) && !empty($activeProvidersWithCountries[$key]['countries']))
                                                <form method="POST" action="{{ route('admin.sms-settings.remove-assignments') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="provider" value="{{ $key }}">
                                                    <button type="submit"
                                                            onclick="return confirm('Remove all country assignments from {{ $provider['name'] }}?')"
                                                            class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                                                        Remove All
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Show assigned countries -->
                                @if(isset($activeProvidersWithCountries[$key]))
                                    @php $providerData = $activeProvidersWithCountries[$key]; @endphp
                                    @if(!empty($providerData['countries']))
                                        <div class="mb-3">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Assigned Countries:</h4>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($providerData['countries'] as $country)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $country['code'] }} - {{ $country['name'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif($providerData['is_fallback'])
                                        <div class="mb-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                🌍 Fallback Provider (handles unassigned countries)
                                            </span>
                                        </div>
                                    @endif
                                @endif

                                <!-- Show configuration requirements if not configured -->
                                @if(!$provider['configured'] && !empty($provider['requirements']))
                                    <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                        <h4 class="text-sm font-medium text-yellow-800 mb-2">Required Environment Variables:</h4>
                                        <ul class="text-sm text-yellow-700 space-y-1">
                                            @foreach($provider['requirements'] as $key => $description)
                                                <li><code class="bg-yellow-100 px-1 rounded">{{ $key }}</code> - {{ $description }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Test SMS Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Test SMS</h2>
                    <p class="text-gray-600 text-sm mb-4">Send a test SMS to verify country-based routing is working correctly.</p>

                    <form method="POST" action="{{ route('admin.sms-settings.test') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="test_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number (with country code)
                            </label>
                            <input type="text"
                                   id="test_phone"
                                   name="test_phone"
                                   placeholder="+962791234567"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Include country code (e.g., +962 for Jordan, +966 for Saudi Arabia)</p>
                        </div>

                        <button type="submit"
                                class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Send Test SMS
                        </button>
                    </form>
                </div>

                <!-- Unassigned Countries -->
                @if(!empty($unassignedCountries))
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Unassigned Countries</h2>
                        <p class="text-gray-600 text-sm mb-4">{{ count($unassignedCountries) }} countries without specific provider assignments (will use fallback provider)</p>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="space-y-1">
                                @foreach(array_slice($unassignedCountries, 0, 20) as $country)
                                    <div class="text-sm text-gray-600">{{ $country['code'] }} - {{ $country['name'] }}</div>
                                @endforeach
                                @if(count($unassignedCountries) > 20)
                                    <div class="text-sm text-gray-500 italic">... and {{ count($unassignedCountries) - 20 }} more</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Country Assignment Modal -->
<div id="assignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Assign Countries to <span id="modalProviderName"></span></h3>
                <button onclick="closeAssignModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="assignForm" method="POST" action="{{ route('admin.sms-settings.assign-countries') }}">
                @csrf
                <input type="hidden" id="modalProvider" name="provider" value="">

                <div class="px-6 py-4 max-h-96 overflow-y-auto">
                    <div class="mb-4">
                        <input type="text"
                               id="countrySearch"
                               placeholder="Search countries..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               onkeyup="filterCountries()">
                    </div>

                    <div class="space-y-2" id="countryList">
                        @foreach($allCountries as $country)
                            <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded country-item"
                                   data-country-name="{{ strtolower($country['name']) }}"
                                   data-country-code="{{ strtolower($country['code']) }}">
                                <input type="checkbox"
                                       name="countries[{{ $country['code'] }}][selected]"
                                       value="1"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <input type="hidden" name="countries[{{ $country['code'] }}][code]" value="{{ $country['code'] }}">
                                <input type="hidden" name="countries[{{ $country['code'] }}][name]" value="{{ $country['name'] }}">
                                <span class="text-sm">{{ $country['code'] }} - {{ $country['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button"
                            onclick="closeAssignModal()"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Assign Selected Countries
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAssignModal(provider, providerName) {
    document.getElementById('modalProvider').value = provider;
    document.getElementById('modalProviderName').textContent = providerName;
    document.getElementById('assignModal').classList.remove('hidden');

    // Reset form
    const checkboxes = document.querySelectorAll('#assignForm input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);

    // Reset search
    document.getElementById('countrySearch').value = '';
    filterCountries();
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

function filterCountries() {
    const search = document.getElementById('countrySearch').value.toLowerCase();
    const items = document.querySelectorAll('.country-item');

    items.forEach(item => {
        const countryName = item.dataset.countryName;
        const countryCode = item.dataset.countryCode;

        if (countryName.includes(search) || countryCode.includes(search)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Close modal when clicking outside
document.getElementById('assignModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAssignModal();
    }
});

// Handle form submission to only include selected countries
document.getElementById('assignForm').addEventListener('submit', function(e) {
    const checkboxes = this.querySelectorAll('input[type="checkbox"]');
    const selectedCountries = [];

    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            // Remove unchecked countries from form data
            const container = checkbox.closest('label');
            const hiddenInputs = container.querySelectorAll('input[type="hidden"]');
            hiddenInputs.forEach(input => input.remove());
            checkbox.remove();
        } else {
            // Collect selected countries for validation
            const codeInput = checkbox.parentNode.querySelector('input[name*="[code]"]');
            if (codeInput) {
                selectedCountries.push(codeInput.value);
            }
        }
    });

    if (selectedCountries.length === 0) {
        e.preventDefault();
        alert('Please select at least one country to assign.');
        return false;
    }
});
</script>
@endsection
