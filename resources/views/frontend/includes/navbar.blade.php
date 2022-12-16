<nav class="navbar navbar-expand-lg navbar-fixed-top bg-primary">
    <div class="container">
        <a href="{{ route('frontend.index') }}" class="navbar-brand">
            <x-instapol-logo width="115" />
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-collapse" aria-controls="#navbar-collapse" aria-expanded="false" aria-label="Toggle Navigation Bar">
            <i class="fas fa-bars"></i>
        </button>
        <div id="navbar-collapse" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto text-uppercase">
                <li class="nav-item">
                    <a href="/" class="nav-link">{{ __('frontend.navbar.home') }}</a>
                </li>
                <li class="nav-item dropdown">
                    <a id="about-dropdown" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false"><span class="me-1">{{ __('frontend.navbar.about.about') }}</span></a>
                    <ul class="dropdown-menu p-0" aria-labelledby="about-dropdown">
                        <li>
                            <a href="{{ route('frontend.about-us') }}" class="dropdown-item py-3">{{ __('frontend.navbar.about.about_us') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.privacy') }}" class="dropdown-item py-3">{{ __('frontend.navbar.about.privacy_policy') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.cookie') }}" class="dropdown-item py-3">{{ __('frontend.navbar.about.cookie_policy') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.refund') }}" class="dropdown-item py-3">{{ __('frontend.navbar.about.refund_policy') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.term-of-use') }}" class="dropdown-item py-3">{{ __('frontend.navbar.about.terms_and_conditions') }}</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link">{{ __('frontend.navbar.promotion') }}</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('frontend.claims') }}" class="nav-link">{{ __('frontend.navbar.claims') }}</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('instapol_blog') }}" class="nav-link">{{ __('frontend.navbar.blog') }}</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link">{{ __('frontend.navbar.login') }}</a>
                </li>
            </ul>
        </div>
    </div>
</nav>