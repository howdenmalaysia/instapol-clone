@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.general.insurance_comparison'), __('frontend.general.get_quotes_for_insurance')]))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 px-0">
                <x-image-carousel id="promo-carousel" interval="7000" :images="File::glob(public_path('images\banner\*'))" />
                <div class="products">
                    <div class="container product-wrapper mt-n5">
                        <div class="row justify-content-center">
                            <div class="col-xl-11 col-12">
                                <div class="card px-3 text-center shadow rounded">
                                    <div class="row">
                                        <div class="col d-flex align-items-center">
                                            <x-product url="" :image-path="asset('images/icons/motor.png')" :alt="__('frontend.products.motor')" :name="__('frontend.products.motor')" />
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
                                        <div class="col-4 tenang">
                                            <img src="{{ asset('images/MyTenang.jpg') }}" alt="MyTenang" height="190">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <section id="coming-soon">
                    <div class="container-fluid py-4 text-center">
                        <h3 class="fw-bold text-uppercase text-primary">{{ __('frontend.general.coming_soon') }}</h3>
                        <div class="row justify-content-center">
                            <div class="col-2">
                                <img src="{{ asset('images/icons/coming-soon/medical-cover.png') }}" alt="{{ __('frontend.products.medical_cover') }}" class="mb-3">
                                <h5 class="title">{{ __('frontend.products.medical_cover') }}</h5>
                            </div>
                            <div class="col-2">
                                <img src="{{ asset('images/icons/coming-soon/houseowner.png') }}" alt="{{ __('frontend.products.houseowner') }}" class="mb-3">
                                <h5 class="title">{{ __('frontend.products.houseowner') }}</h5>
                            </div>
                        </div>
                    </div>
                </section>
                <section id="made-easy">
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-12">
                                <div class="row justify-content-center">
                                    <div class="col-6">
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
                        <div class="row text-center justify-content-center mt-5">
                            @foreach (__('frontend.home_page.points_easy') as $index => $point)
                                <div class="col-4">
                                    <x-description-with-numbering :number="$index" :title="$point['title']" :description="$point['description']" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                <section id="info">
                    <div class="body-container p-5">
                        @foreach (__('frontend.home_page.info') as $index => $info)
                            <x-benefits :image-path='asset("images/info_{$index}.png")' :title="$info['title']" :description="$info['description']" />
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection