<header class="navbar navbar-expand-lg navbar-dark bg-black shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-gradient" href="/">
            <i class="bi bi-stars me-2"></i>{{__('My Site')}}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarMenu">
            <ul class="navbar-nav">
                <li class="nav-item"><a href="/" class="nav-link active">{{__('Home')}}</a></li>
                @if($is_logged_in)
                    <li class="nav-item"><a href="/logout" class="nav-link active">{{__('Log-out')}}</a></li>
                @else
                    <li class="nav-item"><a href="/login" class="nav-link active">{{__('Log-in')}}</a></li>
                @endif

            </ul>
        </div>
    </div>
</header>