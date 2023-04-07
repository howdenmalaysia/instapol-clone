<div class="row">
    <div class="col-12">
        @foreach ($insurers->chunk(13) as $insurers)
            <div class="row align-items-center border border-primary border-5 rounded-pill p-3  mb-4 bg-white">
                @foreach ($insurers as $insurer)
                    @if (!$insurer->coming_soon)
                        <div class="col-3">
                            <img src="{{ asset("images/insurer/{$insurer->logo}") }}" class="img-fluid">
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="col-10 d-none d-lg-block">
        <div class="row align-items-center border border-primary border-5 rounded-pill p-3 bg-white">
            <div class="col-2 mt-2">
                <h4 class="fw-bold text-start text-uppercase">{{ __('frontend.motor.coming_soon') }}</h4>
            </div>
            @foreach ($insurers as $insurer)
                @if ($insurer->coming_soon)
                <div class="col-2 mt-2">
                    <img src="{{ asset("images/insurer/coming-soon/{$insurer->logo}") }}" class="img-fluid">
                </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
