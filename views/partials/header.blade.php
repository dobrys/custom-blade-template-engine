<!-- Start Navbar Area -->
<div class="navbar-area">
    <div class="mobile-responsive-nav">
        <div class="container">
            <div class="mobile-responsive-menu">
                <div class="logo">
                    <a href="{{ $site }}">
                        <img src="{{ $site }}assets/images/logos/small-white-logo.png" alt="logo">
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Menu For Desktop Device -->
    <div class="desktop-nav nav-area">
        <div class="container-fluid">
            <nav class="navbar navbar-expand-md navbar-light ">
                <a class="navbar-brand" href="{{ $site }}">
                    <img src="{{ $site }}assets/images/logos/logo.png" alt="Logo">
                </a>

                <div class="collapse navbar-collapse mean-menu" id="navbarSupportedContent">
                    <ul class="navbar-nav m-auto">
                        <li class="nav-item">
                            <a href="{{ $site }}" class="nav-link">
                                {{  __('Home')  }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{  $user }} class="nav-link">
                                {{  __('My profile')  }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ $site }}#workouts" class="nav-link">
                                {{  __('Workouts') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ $site }}screenshots" class="nav-link">
                                {{  __('Screenshots')  }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ $site }}exercises" class="nav-link">
                                {{  __('Exercises')  }}
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>
</div>
