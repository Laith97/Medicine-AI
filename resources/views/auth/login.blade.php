<!-- resources/views/openai-form.blade.php -->
@extends('master')

@section('title', 'Patients Page')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-6">
          <div class="login-container my-7">
            <div class="logo">
              <!-- Replace the src with your logo path -->
            </div>
            <h2>Login</h2>
            <form method="POST" action="{{ route('login') }}">
              @csrf
          
              <input type="email" name="email" placeholder="Email" required />
              <input type="password" name="password" required placeholder="Password" />
              <button type="submit">Login</button>
            </form>
          
            <div class="footer">
              Don't have an account? <a href="{{ route('register') }}">Sign up</a>
            </div>
          </div>
        </div>
    </div>
  
  @endsection
