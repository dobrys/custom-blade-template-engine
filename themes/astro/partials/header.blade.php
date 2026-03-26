<!-- HEADER / NAVBAR -->
<header class="position-fixed top-0 w-100" style="z-index: 1000; backdrop-filter: blur(8px); background: rgba(11,15,26,0.85);">
    <nav class="navbar navbar-expand-lg navbar-dark container py-3">
        <a class="navbar-brand fw-bold" href="/">
            {{ $brand  }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/day">{{ __('Services') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="#how">{{ __('How It Works') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="#pricing">{{ __('Pricing') }}</a></li>
            </ul>
            <div class="d-flex gap-2">
                <a href="/login" class="btn btn-outline-light btn-sm">{{ __('Login') }}</a>
                <a href="#pricing" class="btn btn-accent btn-sm">{{ __('Get Started') }}</a>
            </div>
        </div>
    </nav>
</header>
<div style="height: 96px"></div>
