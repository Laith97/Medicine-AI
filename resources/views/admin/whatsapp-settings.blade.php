@extends('layouts.admin')

@section('title', 'WhatsApp Provider Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">WhatsApp Provider Settings</h1>
            <p class="text-gray-600">Manage WhatsApp providers for notifications</p>
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
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Provider Configuration</h2>

                    <!-- Provider Cards -->
                    <div class="space-y-6">
                        @foreach($providers as $key => $name)
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">{{ $name }}</h3>
                                            <p class="text-sm text-gray-600">Provider: {{ $key }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuration Form -->
                                <form method="POST" action="{{ route('admin.whatsapp-settings.update') }}">
                                    @csrf
                                    <input type="hidden" name="provider_key" value="{{ $key }}">

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Active Configuration
                                        </label>
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   name="is_active"
                                                   value="1"
                                                   class="h-4 w-4 text-blue-600 rounded"
                                                   {{ $systemConfigs->where('provider_key', $key)->first()->is_active ?? false ? 'checked' : '' }}>
                                            <span class="ml-2 text-sm text-gray-600">Enable this provider</span>
                                        </div>
                                    </div>

                                    @if($key === 'twilio')
                                        <div class="mb-4">
                                            <label for="provider_config_account_sid_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-2">
                                                Account SID
                                            </label>
                                            <input type="text"
                                                   id="provider_config_account_sid_{{ $key }}"
                                                   name="provider_config[account_sid]"
                                                   placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   value="{{ old('provider_config.account_sid', $systemConfigs->where('provider_key', $key)->first()->provider_config['account_sid'] ?? '') }}">
                                        </div>

                                        <div class="mb-4">
                                            <label for="provider_config_auth_token_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-2">
                                                Auth Token
                                            </label>
                                            <input type="password"
                                                   id="provider_config_auth_token_{{ $key }}"
                                                   name="provider_config[auth_token]"
                                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   value="{{ old('provider_config.auth_token', $systemConfigs->where('provider_key', $key)->first()->provider_config['auth_token'] ?? '') }}">
                                        </div>

                                        <div class="mb-4">
                                            <label for="provider_config_from_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-2">
                                                WhatsApp Number
                                            </label>
                                            <input type="text"
                                                   id="provider_config_from_{{ $key }}"
                                                   name="provider_config[from]"
                                                   placeholder="whatsapp:+1234567890"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   value="{{ old('provider_config.from', $systemConfigs->where('provider_key', $key)->first()->provider_config['from'] ?? '') }}">
                                        </div>
                                    @elseif($key === 'graph_api')
                                        <div class="mb-4">
                                            <label for="provider_config_access_token_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-2">
                                                Access Token
                                            </label>
                                            <input type="password"
                                                   id="provider_config_access_token_{{ $key }}"
                                                   name="provider_config[access_token]"
                                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   value="{{ old('provider_config.access_token', $systemConfigs->where('provider_key', $key)->first()->provider_config['access_token'] ?? '') }}">
                                        </div>

                                        <div class="mb-4">
                                            <label for="provider_config_phone_number_id_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-2">
                                                Phone Number ID
                                            </label>
                                            <input type="text"
                                                   id="provider_config_phone_number_id_{{ $key }}"
                                                   name="provider_config[phone_number_id]"
                                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   value="{{ old('provider_config.phone_number_id', $systemConfigs->where('provider_key', $key)->first()->provider_config['phone_number_id'] ?? '') }}">
                                        </div>
                                    @endif

                                    <button type="submit"
                                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        Save Configuration
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Test WhatsApp Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Test WhatsApp</h2>
                    <p class="text-gray-600 text-sm mb-4">Send a test WhatsApp message to verify the provider is working correctly.</p>

                    <form method="POST" action="{{ route('admin.whatsapp-settings.test') }}">
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

                        <div class="mb-4">
                            <label for="test_message" class="block text-sm font-medium text-gray-700 mb-2">
                                Test Message
                            </label>
                            <textarea id="test_message"
                                      name="test_message"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Enter your test message here...">Test WhatsApp message from MedcuraAI - Configuration is working!</textarea>
                        </div>

                        <button type="submit"
                                class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Send Test WhatsApp
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection