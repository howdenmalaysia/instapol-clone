@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.general.insurance_comparison'), __('frontend.general.get_quotes_for_insurance')]))

@section('content')
    <x-banner-modal image="{{ asset('images/banner/roadtax_2.jpg') }}" />
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 px-0">
                <x-image-carousel id="promo-carousel" interval="7000" :images="array_diff(File::glob(public_path('images/banner/*')), [public_path('images/banner/mobile')])" />
                <div class="products">
                    <div class="container product-wrapper mt-n5">
                        <div class="row justify-content-center">
                            <div class="col-xl-11 col-12">
                                <div id="product-card" class="card text-center ps-4 pe-3 py-5 shadow rounded">
                                    <div class="row">
                                        <a href="{{ route('motor.index') }}" class="col d-flex align-items-center text-decoration-none justify-content-center">
                                            <x-product :image-path="asset('images/icons/motor.png')" :alt="__('frontend.products.motor')" :name="__('frontend.products.motor')" />
                                        </a>
                                        <a href="/motor-extended" class="col d-flex align-items-center text-decoration-none justify-content-center">
                                            <x-product :image-path="asset('images/icons/extended-motor.png')" :alt="__('frontend.products.motor_extended')" :name="__('frontend.products.motor_extended')" />
                                        </a>
                                        <a href="/bike" class="col d-flex align-items-center text-decoration-none justify-content-center">
                                            <x-product :image-path="asset('images/icons/bicycle.png')" :alt="__('frontend.products.bicycle')" :name="__('frontend.products.bicycle')" />
                                        </a>
                                        <a href="/travel" class="col d-flex align-items-center text-decoration-none justify-content-center">
                                            <x-product :image-path="asset('images/icons/travel.png')" :alt="__('frontend.products.travel')" :name="__('frontend.products.travel')" />
                                        </a>
                                        <a href="/sme" class="col d-flex align-items-center text-decoration-none justify-content-center">
                                            <x-product :image-path="asset('images/icons/sme.png')" :alt="__('frontend.products.doc_pro')" :name="__('frontend.products.doc_pro')" />
                                        </a>
                                        <a href="/hho" class="col d-flex align-items-center text-decoration-none justify-content-center">
                                            <x-product :image-path="asset('images/icons/house.svg')" :alt="__('frontend.products.houseowner')" :name="__('frontend.products.houseowner')" />
                                        </a>
                                        <a href="/criticalsafe" class="col d-flex align-items-center text-decoration-none justify-content-center">
                                            <x-product :image-path="asset('images/icons/critical.svg')" :alt="__('frontend.products.critical')" :name="__('frontend.products.critical')" />
                                        </a>
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
                            <div class="col-6 col-sm-5 col-md-4 col-lg-3 aos-init" data-aos="flip-left" data-aos-duration="2000">
                                <img src="{{ asset('images/icons/coming-soon/medical-cover.png') }}" alt="{{ __('frontend.products.medical_cover') }}" class="mb-3">
                                <h5 class="title">{{ __('frontend.products.medical_cover') }}</h5>
                            </div>
                        </div>
                    </div>
                </section>
                <x-make-easy-section />
                <x-instapol-features />
            </div>
        </div>
    </div>
    <!-- Virtual Promo -->
    <!-- <div class="modal fade rounded" tabindex="-1" id="virtual-banner">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded">
                <div class="row">
                    <div class="col-12">
                    <div class="modal-body p-0 d-flex flex-column align-items-end">
                        <button type="button" class="btn-close position-absolute m-1" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="w-100">
                            <a href="https://howdenvirtualrun.com/" target="_blank">
                                <img src="{{ asset('images/banner/virtual.jpg') }}" style="width:100%; height: 100%" alt="Banner Image" class="img-fluid rounded">
                            </a>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
@endsection

@push('after-scripts')
<script>
    $(function() {
        //$('#virtual-banner').modal('show');
        $('#landing-banner').modal('show');
    });

    // $('#virtual-banner').on('hidden.bs.modal', function () {
    //     $('#landing-banner').modal('show');
    // })
</script>
@endpush
