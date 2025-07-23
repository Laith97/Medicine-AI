@extends('master')

@section('title', 'Find Your Appointments')

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-md mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-search text-primary-600 text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Find Your Appointments</h1>
                <p class="text-gray-600">Enter your email address to view your appointment history</p>
            </div>

            <form method="POST" action="{{ route('appointments.guest.search') }}">
                @csrf
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input type="email" name="email" id="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter your email address" value="{{ old('email') }}">
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-primary-600 text-white py-3 px-4 rounded-lg hover:bg-primary-700 transition-colors font-medium">
                    <i class="fas fa-search mr-2"></i>
                    Find My Appointments
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <p class="text-sm text-gray-500 mb-4">Want to create an account for easier management?</p>
                    <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        Create Patient Account
                    </a>
                </div>
            </div>

            <div class="mt-6 p-4 bg-primary-50 rounded-lg">
                <h3 class="font-medium text-primary-900 mb-2">Need Help?</h3>
                <ul class="text-sm text-primary-800 space-y-1">
                    <li>• Use the same email address you provided when booking</li>
                    <li>• Check your spam folder for appointment confirmations</li>
                    <li>• Contact support if you can't find your appointments</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
