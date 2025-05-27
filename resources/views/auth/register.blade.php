@extends('master')

@section('title', 'Register')

@section('content')
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">

  <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="login-container">
      <div class="logo">
        {{-- <img src="{{ asset('images/logo.png') }}" alt="Logo"> --}}
      </div>

      <h2>Register</h2>

      <form method="POST" action="{{ route('register') }}">
        @csrf

        <input id="name" type="text" name="name" placeholder="Name" value="{{ old('name') }}" required autofocus>
        @error('name')
          <div class="text-danger mt-1">{{ $message }}</div>
        @enderror

        <input id="email" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
        @error('email')
          <div class="text-danger mt-1">{{ $message }}</div>
        @enderror

        <input id="password" type="password" name="password" placeholder="Password" required>
        @error('password')
          <div class="text-danger mt-1">{{ $message }}</div>
        @enderror

        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm Password" required>
        @error('password_confirmation')
          <div class="text-danger mt-1">{{ $message }}</div>
        @enderror

        <button type="submit">Register</button>
      </form>

      <div class="footer">
        Already have an account? <a href="{{ route('login') }}">Login</a>
      </div>
    </div>
  </div>
@endsection
