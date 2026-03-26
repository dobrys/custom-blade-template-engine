{{-- Created on 12/16/2025 --}}
@extends('layout.default')
@section('content')

    <div class="login-container">
        <div class="login-card">
            <h3 class="text-center mb-4">{{ __('Login to ') }} {{$brand}}</h3>
            <form method="POST" action="/">
                <div class=" mb-3">
                    <label for="msisdn" class="form-label fw-semibold">{{__('msisdn')}}</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent text-gradient border-secondary">
                            <i class="bi bi-lock-fill text-white"></i>
                        </span>
                        <input type="text" id="msisdn" name="msisdn" class="login-input form-control border-secondary bg-transparent text-light" required placeholder="Enter msisdn">
                    </div>
                </div>
                <button type="submit" class="btn btn-accent w-100 mb-3">{{ __('Login') }}</button>
                {{--<p class="text-center text-muted-custom">{{ __('Don't have an account?') }} <a href="{{ route('register') }}">{{ __('Sign Up') }}</a></p>--}}
            </form>
        </div>
    </div>
@endsection