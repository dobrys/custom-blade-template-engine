{{-- Created on 12/16/2025 --}}
@extends('layout.default')
@section('content')

    <div class="login-container">
        <div class="login-card astro-color">
            <h3 class="text-center mb-4">{{ __('Login to ') }} {{$brand}}</h3>
            <form method="POST" action="/">
                <div class=" mb-3 text-white">
                    <label for="msisdn" class="form-label fw-semibold astro-color">{{__('msisdn')}}</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent text-gradient border-secondary text-white">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="text" id="msisdn" name="msisdn" class="login-input form-control border-secondary bg-transparent text-white" required placeholder="Enter msisdn">
                    </div>
                </div>
                <button type="submit" class="btn btn-accent w-100 mb-3">{{ __('Login') }}</button>
                {{--<p class="text-center text-muted-custom">{{ __('Don't have an account?') }} <a href="{{ route('register') }}">{{ __('Sign Up') }}</a></p>--}}
            </form>
        </div>
    </div>
@endsection