<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            <p class="mt-1 text-sm text-gray-600">{{ __('Used for SMS notifications about invoices and account updates.') }}</p>
        </div>

        <!-- WhatsApp Notification Preferences -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">WhatsApp Notifications</h3>
            <p class="mt-1 text-sm text-gray-600 mb-4">{{ __('Configure how you receive notifications via WhatsApp.') }}</p>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <input
                            id="whatsapp_enabled"
                            name="whatsapp_enabled"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('whatsapp_enabled', $user->getOrCreateNotificationPreferences()->whatsapp_enabled ?? false) ? 'checked' : '' }}
                        >
                        <div>
                            <x-input-label for="whatsapp_enabled" :value="__('Enable WhatsApp Notifications')" />
                            <p class="text-sm text-gray-500">{{ __('Receive important notifications via WhatsApp') }}</p>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Notification Types -->
                <div class="ml-7 space-y-2">
                    <div class="flex items-center">
                        <input
                            id="whatsapp_appointment_reminders"
                            name="whatsapp_appointment_reminders"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('whatsapp_appointment_reminders', $user->getOrCreateNotificationPreferences()->whatsapp_appointment_reminders ?? false) ? 'checked' : '' }}
                        >
                        <x-input-label for="whatsapp_appointment_reminders" :value="__('Appointment Reminders')" class="ml-2" />
                    </div>

                    <div class="flex items-center">
                        <input
                            id="whatsapp_urgent_alerts"
                            name="whatsapp_urgent_alerts"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('whatsapp_urgent_alerts', $user->getOrCreateNotificationPreferences()->whatsapp_urgent_alerts ?? false) ? 'checked' : '' }}
                        >
                        <x-input-label for="whatsapp_urgent_alerts" :value="__('Urgent Alerts')" class="ml-2" />
                    </div>

                    <div class="flex items-center">
                        <input
                            id="whatsapp_diagnosis_updates"
                            name="whatsapp_diagnosis_updates"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('whatsapp_diagnosis_updates', $user->getOrCreateNotificationPreferences()->whatsapp_diagnosis_updates ?? false) ? 'checked' : '' }}
                        >
                        <x-input-label for="whatsapp_diagnosis_updates" :value="__('Diagnosis Updates')" class="ml-2" />
                    </div>

                    <div class="flex items-center">
                        <input
                            id="whatsapp_review_requests"
                            name="whatsapp_review_requests"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('whatsapp_review_requests', $user->getOrCreateNotificationPreferences()->whatsapp_review_requests ?? false) ? 'checked' : '' }}
                        >
                        <x-input-label for="whatsapp_review_requests" :value="__('Review Requests')" class="ml-2" />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
