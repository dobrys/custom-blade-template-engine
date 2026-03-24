<header class="navbar navbar-expand-lg navbar-dark bg-black shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-gradient" href="/">
            <i class="bi bi-stars me-2"></i>{{ __('My Site') }}
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarMenu">
            <ul class="navbar-nav align-items-lg-center gap-1">

                @foreach($nav as $item)
                    @if($item->hasChildren())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ $item->isActive($currentUrl) ? 'active' : '' }}"
                               href="{{ $item->url }}"
                               data-bs-toggle="dropdown"
                               aria-expanded="false">
                                @if($item->icon)
                                    <i class="{{ $item->icon }} me-1"></i>
                                @endif
                                {{ __($item->label) }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                @foreach($item->children as $child)
                                    <li>
                                        <a class="dropdown-item {{ $child->isActive($currentUrl) ? 'active' : '' }}"
                                           href="{{ $child->url }}">
                                            @if($child->icon)
                                                <i class="{{ $child->icon }} me-2"></i>
                                            @endif
                                            {{ __($child->label) }}
                                            @if($child->badge)
                                                <span class="badge bg-primary ms-2">{{ $child->badge }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ $item->isActive($currentUrl) ? 'active' : '' }}"
                               href="{{ $item->url }}">
                                @if($item->icon)
                                    <i class="{{ $item->icon }} me-1"></i>
                                @endif
                                {{ __($item->label) }}
                                @if($item->badge)
                                    <span class="badge bg-primary ms-2">{{ $item->badge }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                @endforeach

                {{-- Login / Logout --}}
                <li class="nav-item ms-lg-2">
                    @if($is_logged_in)
                        <a href="/logout" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>{{ __('Log-out') }}
                        </a>
                    @else
                        <a href="/login" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Log-in') }}
                        </a>
                    @endif
                </li>

            </ul>
        </div>
    </div>
</header>