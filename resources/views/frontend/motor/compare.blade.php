@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.compare')] ))

@section('content')
    <x-motor-layout id="compare" current-step="2">
        <x-slot name="content">
            <div class="row">
                <div class="col-12 col-lg-8 mb-5">
                    <h4 class="fw-bold">{{ __('frontend.motor.compare_page.scroll_down') }}</h4>
                    <h4 class="fw-bold">{{ __('frontend.motor.compare_page.click_compare') }}</h4>
                </div>
                <div class="col-12 col-lg-4">
                    <select id="sort-by" class="form-select"></select>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-lg-4">
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 rounded">
                                <div class="card-header bg-primary">
                                    <h5 class="card-title text-white text-center px-3 mb-0">{{ __('frontend.motor.compare_page.tell_us_more') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="d-grid gap-0">
                                                <button type="button" class="btn btn-light">{{ '1.' . __('frontend.motor.compare_page.basic_info') }}</button>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-grid gap-0">
                                                <button type="button" class="btn btn-primary text-white">{{ '2.' . __('frontend.motor.compare_page.driver_info') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-5">
                                            <p>{{ __('frontend.motor.compare_page.gender') }}</p>
                                        </div>
                                        <div class="col-7">
                                            <div class="d-grid gap-0">
                                                <div class="btn-group" role="group">
                                                    <input type="radio" id="male" class="btn-check" name="gender" value="1" checked>
                                                    <label id="male-label" class="btn btn-primary text-white rounded-start border active" for="male">{{ __('frontend.motor.compare_page.male') }}</label>
            
                                                    <input type="radio" id="female" class="btn-check" name="gender" value="2">
                                                    <label id="female-label" class="btn btn-light rounded-end" for="female">{{ __('frontend.motor.compare_page.female') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-5">
                                            <p>{{ __('frontend.motor.compare_page.marital_status') }}</p>
                                        </div>
                                        <div class="col-7">
                                            <select id="marital-status" class="form-select rounded bg-primary text-white"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-grid gap-0">
                                        <button class="btn btn-primary text-white text-uppercase rounded">{{ __('frontend.button.update') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header text-center bg-transparent p-4">
                                    <img src="{{ asset('images/insurer/am.png') }}" alt="AmGen" height="100">
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="text-primary fs-4 fw-bold mb-0">Price</p>
                                        </div>
                                        <div class="col-6 text-end">
                                            <p class="text-primary fs-4 fw-bold mb-0 premium">RM 100.00</p>
                                        </div>
                                    </div>
                                    <div class="row border-top mt-3 pt-3">
                                        <div class="col-6">
                                            <p class="fw-bold mb-0">Agreed Value</p>
                                        </div>
                                        <div class="col-6 text-end">
                                            <p class="mb-0 fw-bold small">RM 70,000.00</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer border-top">
                                    <div class="row my-4">
                                        <div class="col-4">
                                            <img src="" alt="">
                                        </div>
                                        <div class="col-7">
                                            <p class="fw-bold">Free Towing</p>
                                        </div>
                                    </div>
                                    <div class="row mt-5">
                                        <div class="col-12 d-grid gap-2">
                                            <button type="button" class="btn btn-outline-primary border border-primary border-2 fw-bold rounded">{{ __('frontend.button.compare') }}</button>
                                            <button type="button" class="btn btn-primary text-white text-uppercase rounded">{{ __('frontend.button.buy') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-motor-layout>
@endsection