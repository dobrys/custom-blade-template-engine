@extends('layout.default')

@section('title', $title)
@section('content')
    <div class="login_div d-flex flex-column justify-content-center align-items-center bg-dark" style="min-height: 100%;">
        <div class="card p-4 p-md-5 shadow-lg border border-secondary rounded-4" style="max-width: 420px; width: 100%; backdrop-filter: blur(8px); color: white; background-color: rgba(0,0,0,0.65);">
            <div class="text-center mb-4">
                <i class="bi bi-stars display-4 text-gradient mb-2 text-white"></i>
                <h2 class="fw-bold text-gradient">{{__('Welcome Back')}}</h2>
            </div>

            <form method="POST" action="/login">
                <div class="mb-3 text-start">
                    <label for="username" class="form-label fw-semibold text-white">{{__('User name')}}</label>
                    <div class="input-group input-group-lg">
                    <span class="input-group-text bg-transparent text-gradient border-secondary">
                        <i class="bi bi-person text-white"></i>
                    </span>
                        <input type="text" id="username" name="username" class="login-input form-control border-secondary bg-transparent text-light" required autofocus placeholder="{{__('Enter your user name')}}">
                    </div>
                </div>

                <div class="mb-3 text-start">
                    <label for="password" class="form-label fw-semibold">{{__('Password')}}</label>
                    <div class="input-group input-group-lg">
                    <span class="input-group-text bg-transparent text-gradient border-secondary">
                        <i class="bi bi-lock-fill text-white"></i>
                    </span>
                        <input type="password" id="password" name="password" class="login-input form-control border-secondary bg-transparent text-light" required placeholder="Enter password">
                    </div>
                </div>

                <button type="submit" class="btn btn-outline-light btn-lg w-100 rounded-pill shadow">
                    <i class="bi bi-box-arrow-in-right me-1"></i> {{__('Sign In')}}
                </button>
            </form>
        </div>
    </div>
@endsection

