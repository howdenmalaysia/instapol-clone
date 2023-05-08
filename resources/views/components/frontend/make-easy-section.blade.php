<section id="made-easy">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="row justify-content-center">
                    <div class="col-11 col-md-6">
                        <div class="text-center align-items-end">
                            <h2 class="text-uppercase fw-bold">
                                {{ __('frontend.general.how') }}
                                <span>
                                    <img src="{{ asset('images/instapol-navy.png') }}" alt="instaPol" class="img-fluid mx-1 align-baseline">
                                </span>
                                {{ __('frontend.home_page.makes_easy') }}
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row text-center justify-content-center mt-5 px-3">
            @foreach (__('frontend.home_page.points_easy') as $index => $point)
                <div class="col-4 mx-2">
                    <x-description-with-numbering :number="$index" :title="$point['title']" :description="$point['description']" />
                </div>
            @endforeach
        </div>
    </div>
</section>
