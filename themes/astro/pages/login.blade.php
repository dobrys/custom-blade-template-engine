{{-- Created on 12/16/2025 --}}
@extends('layout.default')
@section('content')
    <h1> AAAAAAAAAAAAAAAAAAAAAAAA </h1>
    <div class="login-container">
        <div class="login-card">
            <h3 class="text-center mb-4">{{ __('Login to ') }} {{$brand}}</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="{{ __('Enter your email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="{{ __('Enter your password') }}" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="remember" name="remember">
                        <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
                    </div>
                    {{--<a href="{{ route('password.request') }}" class="text-muted-custom">{{ __('Forgot Password?') }}</a>--}}
                </div>
                <button type="submit" class="btn btn-accent w-100 mb-3">{{ __('Login') }}</button>
                {{--<p class="text-center text-muted-custom">{{ __('Don't have an account?') }} <a href="{{ route('register') }}">{{ __('Sign Up') }}</a></p>--}}
            </form>
        </div>
    </div>
@endsection