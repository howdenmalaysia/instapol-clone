@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.general.insurance_comparison'), __('frontend.general.get_quotes_for_insurance')]))

@section('content')
    <x-banner-modal image="{{ asset('images/banner/main.png') }}" />
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 px-0">
                <x-image-carousel id="promo-carousel" interval="7000" :images="File::glob(public_path('images\banner\*'))" />
                <div class="products">
                    <div class="container product-wrapper mt-n5">
                        <div class="row justify-content-center">
                            <div class="col-xl-11 col-12">
                                <div class="card ps-3 text-center shadow rounded">
                                    <div class="row">
                                        <div class="col d-flex align-items-center">
                                            <x-product url="{{ route('motor.index') }}" :image-path="asset('images/icons/motor.png')" :alt="__('frontend.products.motor')" :name="__('frontend.products.motor')" />
                                        </div>
                                        <div class="col d-flex align-items-center">
                                            <x-product url="{{ config('setting.redirects.motor_extended') }}" :image-path="asset('images/icons/extended-motor.png')" :alt="__('frontend.products.motor_extended')" :name="__('frontend.products.motor_extended')" />
                                        </div>
                                        <div class="col d-flex align-items-center">
                                            <x-product url="{{ config('setting.redirects.bicycle') }}" :image-path="asset('images/icons/bicycle.png')" :alt="__('frontend.products.bicycle')" :name="__('frontend.products.bicycle')" />
                                        </div>
                                        <div class="col d-flex align-items-center">
                                            <x-product url="{{ config('setting.redirects.travel') }}" :image-path="asset('images/icons/travel.png')" :alt="__('frontend.products.travel')" :name="__('frontend.products.travel')" />
                                        </div>
                                        <div class="col d-flex align-items-center">
                                            <x-product url="{{ config('setting.redirects.doc_pro') }}" :image-path="asset('images/icons/doc-pro.png')" :alt="__('frontend.products.doc_pro')" :name="__('frontend.products.doc_pro')" />
                                        </div>
                                        <div class="col-4 tenang p-0">
                                            <img src="{{ asset('images/MyTenang.jpg') }}" alt="MyTenang" class="img-fluid rounded-end" height="190">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <section id="coming-soon">
                    <div class="container-fluid py-4 text-center">
                        <h3 class="fw-bold text-uppercase text-primary aos-init" data-aos="fade-up" data-aos-duration="1000">{{ __('frontend.general.coming_soon') }}</h3>
                        <div class="row justify-content-center mb-5">
                            <div class="col-2 aos-init" data-aos="flip-left" data-aos-duration="2000">
                                <img src="{{ asset('images/icons/coming-soon/medical-cover.png') }}" alt="{{ __('frontend.products.medical_cover') }}" class="mb-3">
                                <h5 class="title">{{ __('frontend.products.medical_cover') }}</h5>
                            </div>
                            <div class="col-2 aos-init" data-aos="flip-left" data-aos-duration="2000">
                                <img src="{{ asset('images/icons/coming-soon/houseowner.png') }}" alt="{{ __('frontend.products.houseowner') }}" class="mb-3">
                                <h5 class="title">{{ __('frontend.products.houseowner') }}</h5>
                            </div>
                        </div>
                    </div>
                </section>
                <x-make-easy-section />
                <x-instapol-features />
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
<script>
    $(function() {
        $('#landing-banner').modal('show');
    });
</script>
@endpush