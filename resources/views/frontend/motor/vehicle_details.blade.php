@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.confirm_your_car')] ))

@section('content')
    <x-motor-layout id="vehicle-details" current-step="1">
        <x-slot name="content">
            <form action="{{ route('motor.vehicle-details') }}" method="POST">
                @csrf
                <div class="row mb-4">
                    <div class="col-12 col-lg-6">
                        <div class="row mb-3">
                            <div class="col-12">
                                <h5 class="text-primary fw-bold">{{ __('frontend.motor.vehicle_details.want_best_quote') }}</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.car_number') }}</p>
                            </div>
                            <div class="col-6">
                                <p class="text-decoration-underline">{{ 'VFM9388' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.make') }}</p>
                            </div>
                            <div class="col-6">
                                <p class="text-decoration-underline">{{ 'Toyota' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.model') }}</p>
                            </div>
                            <div class="col-6">
                                <p class="text-decoration-underline">{{ 'Vios' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.engine_capacity') }}</p>
                            </div>
                            <div class="col-6">
                                <p class="text-decoration-underline">{{ '1496' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.year') }}</p>
                            </div>
                            <div class="col-6">
                                <p class="text-decoration-underline">{{ '2020' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.variant') }}</p>
                            </div>
                            <div class="col-6">
                                <select name="varaint" data-select></select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="row mb-3">
                            <div class="col-12">
                                <h5 class="text-primary fw-bold">{{ __('frontend.motor.vehicle_details.insurance_information') }}</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <p>{{ __('frontend.motor.vehicle_details.ncd') }}</p>
                            </div>
                            <div class="col-6">
                                <p class="text-decoration-underline">{{ '0%' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <p>{{ __('frontend.motor.vehicle_details.coverage') }}</p>
                            </div>
                            <div class="col-6">
                                <p class="text-decoration-underline">{{ 'Comprehensive' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <p>{{ __('frontend.motor.vehicle_details.next_coverage_period') }}</p>
                            </div>
                            <div class="col-6">
                                <p>
                                    <span class="text-decoration-underline">{{ '05-11-2022' }}</span>
                                    to
                                    <span class="text-decoration-underline">{{ '04-11-2023' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <img src="{{ asset('images/manufacturer/' . strtoupper('Toyota') . '.png') }}" alt="Toyota">
                        <p>{{ __('frontend.motor.vehicle_details.my_vehicle_information') }}</p>
                        <a href="{{ route('motor.index') }}" class="btn btn-link text-uppercase text-dark fw-bold">{{ __('frontend.button.back') }}</a>
                        <button type="button" id="btn-continue" class="btn btn-primary text-white text-uppercase fw-bold">{{ __('frontend.button.continue') }}</button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 text-center">
                    <a href="">{{ __('frontend.motor.vehicle_details.need_assistance') }}</a>
                    </div>
                </div>
                <div class="hidden">
                    <input type="hidden" id="motor" value='@json(session('motor'))'>
                </div>
            </form>
        </x-slot>
    </x-motor-layout>
@endsection

@push('after-scripts')
<script>
        let products = JSON.parse("{{ json_encode($product_ids) }}");

        $(function() {
            fetchData();
            $('#btn-continue').on('click', function() {

            });
        });

        function fetchData() {
            swalLoading();
            let motor = JSON.parse($('#motor').val());

            products.forEach(product => {
                instapol.post("{{ route('motor.api.vehicle-details') }}", {
                    vehicle_number: motor.vehicle_number,
                    postcode: motor.postcode,
                    id_number: motor.policy_holder.id_number,
                    id_type: motor.policy_holder.id_type,
                    email: motor.policy_holder.email,
                    phone_number: motor.policy_holder.phone_number,
                    product_id: product.id
                }).then((response) => {
                    console.log(response);
                }).catch((error) => {
                    console.log(error.response);
                });
            });

        }
    </script>
@endpush