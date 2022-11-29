@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.compare')] ))

@section('content')
    <x-motor-layout id="compare" current-step="2">
        <x-slot name="content">
            <x-banner-modal image="{{ asset('images/banner/trapo_thank_you.png') }}" class="d-block mx-auto w-50" />
            <div class="row">
                <div class="col-12 col-lg-8 mb-5">
                    <h4 class="fw-bold">{{ __('frontend.motor.compare_page.scroll_down') }}</h4>
                    <h4 class="fw-bold">{{ __('frontend.motor.compare_page.click_compare') }}</h4>
                </div>
                <div class="col-12 col-lg-4">
                    <select id="sort-by" class="form-select">
                        <option value="LP" selected>{{ __('frontend.motor.compare_page.lowest_price') }}</option>
                        <option value="HP">{{ __('frontend.motor.compare_page.highest_price') }}</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="row" id="compare-wrapper">
                        <div class="col-12 col-md-4 mb-4">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card border-0 rounded">
                                        <div class="card-header bg-primary">
                                            <h5 class="card-title text-white text-center px-3 mb-0">{{ __('frontend.motor.compare_page.tell_us_more') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <ul class="nav nav-pills nav-fill justify-content-around mb-3" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button type="button" class="nav-link" id="basic-info-link" data-bs-toggle="pill" data-bs-target="#basic-info" role="tab">
                                                        {{ '1. ' . __('frontend.motor.compare_page.basic_info') }}
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button
                                                        type="button"
                                                        id="driver-info-link"
                                                        class="nav-link active"
                                                        data-bs-toggle="pill"
                                                        data-bs-target="#driver-info"
                                                        role="tab"
                                                    >
                                                        {{ '2. ' . __('frontend.motor.compare_page.driver_info') }}
                                                    </button>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                <div class="tab-pane fade px-3" id="basic-info">
                                                    <div class="row mt-4">
                                                        <div class="col-5">
                                                            <p>{{ __('frontend.motor.car_plate') }}</p>
                                                        </div>
                                                        <div class="col-7">
                                                            <input type="text" id="vehicle-number" class="form-control" value="{{ session('motor')->vehicle_number }}" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-4">
                                                        <div class="col-5">
                                                            <p>{{ __('frontend.motor.nric') }}</p>
                                                        </div>
                                                        <div class="col-7">
                                                            <input type="text" id="id-number" class="form-control" value="{{ session('motor')->policy_holder->id_number }}" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-4">
                                                        <div class="col-5">
                                                            <p>{{ __('frontend.motor.postcode') }}</p>
                                                        </div>
                                                        <div class="col-7">
                                                            <input type="text" id="postcode" class="form-control" value="{{ session('motor')->postcode }}" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade show active px-3" id="driver-info" role="tabpanel">
                                                    <div class="row mt-4">
                                                        <div class="col-5">
                                                            <p>{{ __('frontend.motor.compare_page.gender') }}</p>
                                                        </div>
                                                        <div class="col-7">
                                                            <div class="d-grid gap-0">
                                                                <div class="btn-group rounded" role="group">
                                                                    <input
                                                                        type="radio"
                                                                        id="male"
                                                                        class="btn-check"
                                                                        name="gender"
                                                                        value="M"
                                                                        {{ session('motor')->policy_holder->gender === 'M' ? 'checked' : '' }}
                                                                    >
                                                                    <label id="male-label" class="btn btn-primary text-white rounded-start border active" for="male">
                                                                        {{ __('frontend.motor.compare_page.male') }}
                                                                    </label>
                            
                                                                    <input
                                                                        type="radio"
                                                                        id="female"
                                                                        class="btn-check"
                                                                        name="gender"
                                                                        value="F"
                                                                        {{ session('motor')->policy_holder->gender === 'F' ? 'checked' : '' }}
                                                                    >
                                                                    <label id="female-label" class="btn btn-light rounded-end" for="female">
                                                                        {{ __('frontend.motor.compare_page.female') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-4">
                                                        <div class="col-5">
                                                            <p>{{ __('frontend.motor.compare_page.marital_status') }}</p>
                                                        </div>
                                                        <div class="col-7">
                                                            <select id="marital-status" class="form-select rounded bg-primary text-white">
                                                                <option value="">{{ __('frontend.general.select') }}</option>
                                                                <option value="S" {{ session('motor')->policy_holder->marital_status === 'S' ? 'selected' : 'selected' }}>
                                                                    {{ __('frontend.motor.compare_page.single') }}
                                                                </option>
                                                                <option value="M" {{ session('motor')->policy_holder->marital_status === 'M' ? 'selected' : '' }}>
                                                                    {{ __('frontend.motor.compare_page.married') }}
                                                                </option>
                                                                <option value="D" {{ session('motor')->policy_holder->marital_status === 'D' ? 'selected' : '' }}>
                                                                    {{ __('frontend.motor.compare_page.divorced') }}
                                                                </option>
                                                                <option value="O" {{ session('motor')->policy_holder->marital_status === 'O' ? 'selected' : '' }}>
                                                                    {{ __('frontend.motor.compare_page.others') }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-grid gap-0">
                                                <button id="btn-update" class="btn btn-primary text-white text-uppercase rounded">{{ __('frontend.button.update') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @foreach ($products as $product)
                            <div class="col-11 border shadow rounded my-3 mx-auto insurer-card">
                                <div class="row" id={{ 'insurer-' . $product->id }} data-insurer-id="{{ $product->insurance_company->id }}">
                                    <div class="col-12">
                                        <div class="row p-3">
                                            <div class="col-12 col-sm-7 text-center align-self-center">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p class="text-primary">{{ $product->name }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-5 text-center align-self-center">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <img
                                                            src="{{ asset("images/insurer/{$product->insurance_company->logo}") }}"
                                                            alt="{{ $product->insurance_company->name }}"
                                                            class="img-fluid d-block p-2 mx-auto align-self-center"
                                                            width="200"
                                                        >
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <p class="text-primary fs-4 fw-bold mb-0">Price</p>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <p class="text-primary fs-4 fw-bold mb-0 premium" data-premium="0.00">RM 0.00</p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <p class="fw-bold mb-0 valuation">Market Value</p>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <p class="mb-0 fw-bold small sum-insured">RM 0.00</p>
                                                    </div>
                                                </div>
                                                <div class="row justify-content-end mt-3">
                                                    <button type="button" class="col-6 btn btn-outline-primary border border-primary border-2 fw-bold rounded btn-compare">
                                                        {{ __('frontend.button.compare') }}
                                                    </button>
                                                    <button type="button" class="col-6 btn btn-primary text-white text-uppercase rounded btn-buy" data-product_id="{{ $product->id }}">
                                                        {{ __('frontend.button.buy') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="{{ "compare-details-accordion-{$product->id}" }}" class="accordion">
                                            <div class="row justify-content-between">
                                                <div class="col accordion-item px-0">
                                                    <h2 class="accordion-header h-100">
                                                        <button
                                                            type="button"
                                                            class="accordion-button fw-bold h-100 border"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="{{ "#basic-info-accordion-{$product->id}" }}"
                                                        >
                                                            {{ __('frontend.motor.compare_page.compare_details.tabs.basic_info') }}
                                                        </button>
                                                    </h2>
                                                    <div
                                                        id="{{ "basic-info-accordion-{$product->id}" }}"
                                                        class="accordion-collapse collapse"
                                                        data-bs-parent="{{ "#compare-details-accordion-{$product->id}" }}"
                                                    >
                                                        <table class="table compare-details-table">
                                                            <tr>
                                                                <th></th>
                                                                <td></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col accordion-item px-0">
                                                    <h2 class="accordion-header h-100">
                                                        <button
                                                            type="button"
                                                            class="accordion-button fw-bold h-100 border"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="{{ "#add-ons-accordion-{$product->id}" }}"
                                                        >
                                                            {{ __('frontend.motor.compare_page.compare_details.tabs.additional_coverage') }}
                                                        </button>
                                                    </h2>
                                                    <div
                                                        id="{{ "add-ons-accordion-{$product->id}" }}"
                                                        class="accordion-collapse collapse"
                                                        data-bs-parent="{{ "#compare-details-accordion-{$product->id}" }}"
                                                    >
                                                        <table class="table compare-details-table">
                                                            <tr>
                                                                <th></th>
                                                                <td></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col accordion-item px-0">
                                                    <h2 class="accordion-header h-100">
                                                        <button
                                                            type="button"
                                                            class="accordion-button fw-bold h-100 border"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="{{ "#loss-cover-accordion-{$product->id}" }}"
                                                        >
                                                            {{ __('frontend.motor.compare_page.compare_details.tabs.loss_damage') }}
                                                        </button>
                                                    </h2>
                                                    <div
                                                        id="{{ "loss-cover-accordion-{$product->id}" }}"
                                                        class="accordion-collapse collapse"
                                                        data-bs-parent="{{ "#compare-details-accordion-{$product->id}" }}"
                                                    >
                                                        <table class="table compare-details-table">
                                                            <tr>
                                                                <th></th>
                                                                <td></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col accordion-item px-0">
                                                    <h2 class="accordion-header h-100">
                                                        <button
                                                            type="button"
                                                            class="accordion-button fw-bold h-100 border"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="{{ "#assistance-accordion-{$product->id}" }}"
                                                        >
                                                            {{ __('frontend.motor.compare_page.compare_details.tabs.assistance_repairs') }}
                                                        </button>
                                                    </h2>
                                                    <div
                                                        id="{{ "assistance-accordion-{$product->id}" }}"
                                                        class="accordion-collapse collapse"
                                                        data-bs-parent="{{ "#compare-details-accordion-{$product->id}" }}"
                                                    >
                                                        <table class="table compare-details-table">
                                                            <tr>
                                                                <th></th>
                                                                <td></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col accordion-item px-0">
                                                    <h2 class="accordion-header h-100">
                                                        <button
                                                            type="button"
                                                            class="accordion-button fw-bold h-100 border"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="{{ "#third-party-accordion-{$product->id}" }}"
                                                        >
                                                            {{ __('frontend.motor.compare_page.compare_details.tabs.third_party') }}
                                                        </button>
                                                    </h2>
                                                    <div
                                                        id="{{ "third-party-accordion-{$product->id}" }}"
                                                        class="accordion-collapse collapse"
                                                        data-bs-parent="{{ "#compare-details-accordion-{$product->id}" }}"
                                                    >
                                                        <table class="table compare-details-table">
                                                            <tr>
                                                                <th></th>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <form action="{{ route('motor.compare') }}" method="POST" id="product-form">
                        @csrf
                        <input type="hidden" id="h-gender" name="gender" />
                        <input type="hidden" id="products" value='@json($products)'>
                        <input type="hidden" id="motor" name="motor" value='@json(session('motor'))' />
                        <input type="hidden" id="insurance-premium" name="premium" />
                        <input type="hidden" id="av-code" name="av_variant" />
                        <input type="hidden" id="av-variant" name="av_code" />
                        <input type="hidden" id="h-marital-status" name="marital_status" />
                    </form>
                </div>
            </div>
            <x-modal id="occupation-modal" maxWidth="md" headerClass="bg-primary text-white">
                <x-slot name="title">{{ __('frontend.motor.compare_page.need_occupation') }}</x-slot>
                <x-slot name="body">
                    <div class="row">
                        <div class="col-6">
                            <div class="row">
                                <div class="col-12">{{ __('frontend.motor.vehicle_details.car_number') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle_number }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12">{{ __('frontend.motor.vehicle_details.make') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->make }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12">{{ __('frontend.motor.vehicle_details.year') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->manufacture_year }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="row">
                                <div class="col-12">{{ __('frontend.motor.vehicle_details.engine_capacity') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->engine_capacity }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12">{{ __('frontend.motor.vehicle_details.model') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->model }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12">{{ __('frontend.motor.vehicle_details.variant') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->variant }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p>{{ __('frontend.motor.compare_page.select_occupation') }}</p>
                            <select id="occupation" data-select>
                                <option value="">{{ __('frontend.motor.compare_page.please_select') }}</option>
                            </select>
                        </div>
                    </div>
                </x-slot>
                <x-slot name="footer">
                    <button type="button" id="occupation-next" class="btn btn-primary text-white text-uppercase rounded">{{ __('frontend.button.get_quotation') }}</button>
                </x-slot>
            </x-modal>
        </x-slot>
    </x-motor-layout>
@endsection

@push('after-scripts')
    <script>
        let motor = JSON.parse($('#motor').val());
        let products = JSON.parse($('#products').val());
        let premiums = [];
        let controller = null;

        $(() => {
            $('#landing-banner').modal('show');
            getPremium();

            $('#male-label, #female-label').on('click', (e) => {
                if(!$(e.target).hasClass('active')) {
                    $(e.target).removeClass('btn-light').toggleClass('active btn-primary border text-white');
                    $(e.target).siblings('label.btn').removeClass('active btn-primary border text-white').toggleClass('btn-light');
                }
            });

            $('#sort-by').on('change', (e) => {
                sortPrice($(e.target).val() == 'HP');
            });

            $('#btn-update').on('click', () => {
                // Update Session Data
                motor.policy_holder.marital_status = $('#marital-status').val();
                motor.policy_holder.gender = $('input[name=gender]:checked').val();

                $('#motor').val(JSON.stringify(motor));
                getPremium();
            });

            $('.btn-buy').on('click', (e) => {
                $(e.target).addClass('loadingButton');

                if(controller) {
                    controller.abort();
                }

                let product_id = $(e.target).data('product_id');
                // if(['{{ config('insurer.config.bsib.product_id') }}'].includes(product_id)) {

                // }

                motor.insurance_company_id = product_id;
                $('#motor').val(JSON.stringify(motor));

                $('#insurance-premium').val(JSON.stringify(premiums[product_id]));
                $('#h-gender').val($('input[name=gender]:checked').val());
                $('#h-marital-status').val($('#marital-status').val());

                $('#product-form').submit();
            });

            $('.btn-view-details').on('click', (e) => {
                // Populate Compare Details Data
                let product_id = $(e.target).data('product_id');
                $('#compare-details-modal').modal('show');
            });
        });

        function getPremium() {
            if(controller) {
                controller.abort();
            }

            let agreed_value_text = "{{ __('frontend.motor.compare_page.agreed_value') }}";
            let market_value_text = "{{ __('frontend.motor.compare_page.market_value') }}";
            controller = new AbortController();

            motor.premium = [];
            motor.extra_cover_list = [];
            motor.product_id = '';
            motor.av_code = ''

            products.forEach((product) => {
                $('#insurer-' + product.id + ' .premium').html("<span style='border-width: 2px' class='spinner-border'></span>");

                $('#insurer-' + product.id).find('button').attr('disabled', true);

                instapol.post("{{ route('motor.api.quote') . '/full' }}", {
                    product_id: product.id,
                    av_code: $('#av-code').val(),
                    motor: motor
                }, {
                    signal: controller.signal
                }).then((response) => {
                    console.log(response);
                    premiums[product.id] = response.data;

                    if(response.data.total_payable) {
                        $(`#insurer-${product.id} .premium`).text('RM ' + formatMoney(response.data.total_payable)).data('premium', response.data.total_payable.toString());
                        $(`#insurer-${product.id} .valuation`).text(response.data.sum_insured_type === 'Agreed Value' ? agreed_value_text : market_value_text);
                        $(`#insurer-${product.id} .sum-insured`).text('RM ' + formatMoney(response.data.sum_insured));

                        $(`#insurer-${product.id} .btn-buy`).removeAttr('disabled');
                        $(`#insurer-${product.id} .btn-compare`).removeAttr('disabled');
                        $(`#insurer-${product.id} .btn-view-details`).removeAttr('disabled');
                    } else {
                        $(`#insurer-${product.id} .btn-buy`).attr('disabled', true);
                        $(`#insurer-${product.id} .btn-compare`).attr('disabled', true);
                        $(`#insurer-${product.id} .btn-view-details`).attr('disabled', true);
                        $(`#insurer-${product.id} .premium`).text("{{ __('frontend.motor.compare_page.offline') }}");
                    }

                    sortPrice();
                }).catch((error) => {
                    console.log(error.response);

                    $(`#insurer-${product.id} .btn-buy`).attr('disabled', true);
                    $(`#insurer-${product.id} .btn-compare`).attr('disabled', true);
                    $(`#insurer-${product.id} .btn-view-details`).attr('disabled', true);
                    
                    $(`#insurer-${product.id} .premium`).text("{{ __('frontend.motor.compare_page.offline') }}").data('premium', '0');

                    sortPrice();
                });
            });
        }

        function sortPrice(desc = false) {
            $('.insurer-card').sort((first, second) => {
                let first_price = $(first).find('.premium').data('premium');
                let second_price = $(second).find('.premium').data('premium');

                var first_price_text = checkZero(first_price, desc);
                var second_price_text = checkZero(second_price, desc);

                if(first_price !== undefined && first_price.trim() === '') {
                    if(desc) {
                        first_price_text = '1';
                    } else {
                        first_price_text -= first_price_text;
                    }
                }

                if(second_price !== undefined && second_price.trim() === '') {
                    if(desc) {
                        second_price_text = '1';
                    } else {
                        second_price_text -= second_price_text;
                    }
                }

                if($(first).data('insurer-id') === motor.insurance_company_id) {
                    if(desc) {
                        first_price_text = '1';
                    } else {
                        first_price_text = Number.MAX_VALUE.toString();
                    }

                    first_price_text = checkZero(first_price, desc);
                }

                if($(second).data('insurer-id') === motor.insurance_company_id) {
                    if(desc) {
                        second_price_text = '1';
                    } else {
                        second_price_text = Number.MAX_VALUE.toString();
                    }

                    second_price_text = checkZero(second_price, desc);
                }

                return parseFloat(first_price_text) - parseFloat(second_price_text);
            }).each((index, element) => {
                $(element).appendTo('#compare-wrapper');
            });
        }

        function checkZero(value, desc) {
            if(parseFloat(value) == 0) {
                if(desc) {
                    return '1';
                } else {
                    return Number.MAX_VALUE.toString();
                }
            } else {
                return value.toString();
            }
        }
    </script>
@endpush