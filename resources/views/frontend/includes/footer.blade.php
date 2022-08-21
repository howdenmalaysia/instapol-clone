<footer class="footer">
    <div class="container">
        <div class="row fw-bold">
            <div class="col-md-7">
                <x-instapol-logo navy width="218" />
                    <p class="mt-3 footer-link">
                        <a class="text-decoration-none text-dark border-end border-dark pe-2" href="{{ route('frontend.privacy') }}">{{ __('frontend.navbar.about.privacy_policy') }}</a>
                        <a class="text-decoration-none text-dark border-end border-dark px-2" href="{{ route('frontend.cookie') }}">{{ __('frontend.navbar.about.cookie_policy') }}</a>
                        <a class="text-decoration-none text-dark border-end border-dark px-2" href="{{ route('frontend.refund') }}">{{ __('frontend.navbar.about.refund_policy') }}</a>
                        <a class="text-decoration-none text-dark ps-2" href="{{ route('frontend.term-of-use') }}">{{ __('frontend.navbar.about.terms_and_conditions') }}</a>
                    </p>
                    <p class="copyright d-none d-lg-block">
                        {{ __('frontend.footer.copyright'). ' ' . config('app.name') . '. ' . __('frontend.footer.all_rights_reserved') }}
                    </p>
            </div>
            <div class="col-md-5 text-end">
                <p class="mb-3 fw-normal">{{ __('frontend.footer.owned_by') }}</p>
                <x-howden-logo width="250" />
                <p class="m-0 mt-3 font-howden">Howden Insurance Brokers Sdn. Bhd. (197801001023)</p>
                <p class="font-howden">Howden Takaful Brokers Sdn. Bhd. (formerly known as <br> Malene Insurance Brokers Sdn. Bhd.) (198001010734)</p>
                <p class="copyright d-lg-none mt-3">
                    {{ __('frontend.footer.copyright') . ' ' . config('app.name') . '. ' . __('frontend.footer.all_rights_reserved') }}
                </p>
            </div>
        </div>
    </div>
</footer>