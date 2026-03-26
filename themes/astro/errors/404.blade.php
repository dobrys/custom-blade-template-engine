@extends('layout.default')

@section('title', '404 - Page Not Found')

@section('content')
    <section class="min-vh-100 d-flex flex-column align-items-center justify-content-center text-center position-relative bg-dark text-light overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-glow"></div>

        <div class="container position-relative">
            <div class="display-1 fw-bold text-gradient mb-3">404</div>
            <h2 class="mb-3 text-gradient">Page Not Found</h2>
            <p class="text-muted mb-4">
                Oops! The page you’re looking for doesn’t exist or has been moved.
            </p>

            <a href="/home" class="btn btn-outline-light rounded-pill px-4 shadow">
                <i class="bi bi-house-door me-2"></i> Back to Home
            </a>

            <div class="mt-5">
                <i class="bi bi-stars display-5 text-gradient"></i>
            </div>
        </div>
    </section>
@endsection
