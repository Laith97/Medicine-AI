@extends('master')

@section('title', 'Edit Profile')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header py-2 border-bottom">
            <h2 class="h1 mb-1" style="font-weight: 700;">Profile</h2>
            <p>Edit your profile information</p>
        </div>

        <div class="table-card">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg mt-4">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg mt-4">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
