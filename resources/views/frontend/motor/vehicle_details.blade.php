@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.confirm_your_car')] ))

@section('content')
    <x-motor-layout id="vehicle-details" current-step="1">
        <x-slot name="content">
            <form action="{{ route('motor.vehicle-details') }}" method="POST" id="vehicle-details-form">
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
                                <p id="vehicle-number" class="text-decoration-underline"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.make') }}</p>
                            </div>
                            <div class="col-6">
                                <p id="make" class="text-decoration-underline"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.model') }}</p>
                            </div>
                            <div class="col-6">
                                <p id="model" class="text-decoration-underline"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.engine_capacity') }}</p>
                            </div>
                            <div class="col-6">
                                <p id="engine-capacity" class="text-decoration-underline"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.year') }}</p>
                            </div>
                            <div class="col-6">
                                <p id="manufacture-year" class="text-decoration-underline"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                <p>{{ __('frontend.motor.vehicle_details.variant') }}</p>
                            </div>
                            <div class="col-6">
                                <div id="variant-popover" data-bs-container="body" data-bs-placement="top">
                                    <select
                                        id="variant"
                                        name="variant"
                                        data-select
                                        required
                                        data-parsley-errors-messages-disabled
                                        data-bs-style="btn-danger"
                                    >
                                        <option value="">{{ '-- ' . __('frontend.general.select') . ' --' }}</option>
                                        @if (!empty($motor->variants))
                                            @foreach ($motor->variants as $variant)
                                                <option value="{{ $variant->nvic }}">{{ $variant->variant }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="invalid-tooltip">{{ __('frontend.motor.vehicle_details.select_car_specs') }}</div>
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
                                <p id="ncd-percentage" class="text-decoration-underline"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <p>{{ __('frontend.motor.vehicle_details.coverage') }}</p>
                            </div>
                            <div class="col-6">
                                <p id="coverage" class="text-decoration-underline"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <p>{{ __('frontend.motor.vehicle_details.next_coverage_period') }}</p>
                            </div>
                            <div class="col-6">
                                <p>
                                    <span id="inception-date" class="text-decoration-underline"></span>
                                    to
                                    <span id="expiry-date" class="text-decoration-underline"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <img id="car-make" src="{{ asset('images/manufacturer/' . strtoupper('Toyota') . '.png') }}" alt="Toyota">
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
        let motor = JSON.parse($('#motor').val());

        $(function() {
            $('#vehicle-details-form').hide();
            fetchData();
            $('#btn-continue').on('click', function() {

            });
        });

        function fetchData() {
            swalLoading();

            const controller = new AbortController();
            let calls = [];
            let selectedVariant = null;
            let gapInCover = false;
            let sumInsuredReferred = false;
            let errorMessage = '';

            products.forEach((product_id, key) => {
                calls[key] = instapol.post("{{ route('motor.api.vehicle-details') }}", {
                    vehicle_number: motor.vehicle_number,
                    postcode: motor.postcode,
                    id_number: motor.policy_holder.id_number,
                    id_type: motor.policy_holder.id_type,
                    email: motor.policy_holder.email,
                    phone_number: motor.policy_holder.phone_number,
                    product_id: product_id
                }, {
                    signal: controller.signal
                }).then((response) => {
                    console.log(response);

                    if(!response.data.code) {
                        let populated = $('#variant option').length > 1;
                        let singleVariant = response.data.variants.length === 1;

                        if(!populated && !singleVariant) {
                            populate(response.data, controller);

                            if(selectedVariant) {
                                controller.abort();
                            }
                        }

                        if(singleVariant) {
                            if(selectedVariant === null) {
                                selectedVariant = response.data;
                            }

                            if(populated) {
                                controller.abort();
                            }
                        }
                    }
                }).catch((error) => {
                    console.log(error);
                    
                    let shouldStop = false;
                    switch(error.response.status) {
                        case 460: // Earlier Renewal (2 months - MAX 62 days)
                        case 463: // Undergoing Renewal
                        case 464: // Invalid ID Number / Mismatch
                        case 465: { // Invalid Vehicle Number
                            shouldStop = true;

                            break;
                        }
                        case 461: {
                            sumInsuredReferred = true;
                            errorMessage = error.response;

                            break;
                        }
                        case 462: {
                            gapInCover = true;
                            errorMessage = error.response;

                            break;
                        }
                    }

                    if(shouldStop) {
                        swalAlert(error.response.data, () => {
                            window.location = "{{ route('motor.index') }}"
                        });
                    }
                });
            });

            axios.all(calls)
                .then(() => {
                    let length = $('#variants option').length;
                    let selected = $('#variants').val();

                    if(length === 1) {
                        if(selectedVariant) {
                            populate(selectedVariant);

                            $('#variants').val(selectedVariant.variants[0].nvic).trigger('change');

                            swalHide();
                        } else if(gapInCover || sumInsuredReferred) {
                            swalAlert(errorMessage, () => {
                                window.location = "{{ route('motor.index') }}"
                            });
                        } else if(selected == '') {
                            if(selectedVariant) {
                                $('#variants').val(selectedVariant.variants[0].nvic).trigger('change');
                            } else {
                                $('#variant-popover').popover('show');
                            }

                            swalHide();
                        }
                    }
                })
        }

        function populate(data) {
            // Car Make Logo
            $('#car-make').attr('src', `{{ asset('images/manufacturer/${data.make.toUpperCase()}.png') }}`)
            $('#car-make').attr('alt', data.make.toUpperCase());

            // Vehicle Details
            $('#vehicle-number').val(data.vehicle_number);
            $('#make').val(data.make);
            $('#model').val(data.model);
            $('#engine-capacity').val(data.engine_capcity);
            $('#manufacture-year').val(data.manufacture_year);
            $('#ncd-percentage').val(data.ncd_percentage);
            $('#coverage').val(data.coverage);
            $('#inception-date').val(data.inception_date);
            $('#expiry-date').val(data.expiry_date);
            $('#variants').val(data.nvic).trigger('change');
            swalHide();

            motor.vehicle = {
                make: data.make,
                model: data.model,
                engine_capcity: data.engine_capcity,
                chassis_number: data.chassis_number,
                engine_number: data.engine_number,
                manufacture_year: data.manufacture_year,
                ncd_percentage: data.ncd_percentage,
                coverage: data.coverage,
                inception_date: data.inception_date,
                expiry_date: data.expiry_date,
                seating_capacity: data.seating_capacity
            };

            data.variants.forEach((variant) => {
                let option = new Option(variant.variant, variant.nric, false, false);

                $('#variants').append(option).trigger('change');
            });

            motor.variants = data.variants;

            $('#motor').val(JSON.stringify('motor'));
            $('#vehicle-details-form').show();
        }
    </script>
@endpush