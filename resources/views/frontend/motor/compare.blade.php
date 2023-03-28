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
                                                            @if (session('motor')->policy_holder->id_type === config('setting.id_type.nric_no'))
                                                                <p>{{ __('frontend.motor.nric') }}</p>
                                                            @else
                                                                <p>{{ __('frontend.motor.company_resgistration') }}</p>
                                                            @endif
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
                                                            @if (session('motor')->policy_holder->id_type === config('setting.id_type.nric_no'))
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
                                                                        <label id="male-label" class="{{ session('motor')->policy_holder->gender === 'M' ? 'btn btn-primary text-white rounded-start border active' : 'btn btn-light rounded-end' }}" for="male">
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
                                                                        <label id="female-label" class="{{ session('motor')->policy_holder->gender === 'F' ? 'btn btn-primary text-white rounded-start border active' : 'btn btn-light rounded-end' }}" for="female">
                                                                            {{ __('frontend.motor.compare_page.female') }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <div class="btn-group rounded" role="group">
                                                                    <input
                                                                        type="radio"
                                                                        id="company"
                                                                        class="btn-check"
                                                                        name="gender"
                                                                        value="O"
                                                                        {{ session('motor')->policy_holder->gender === 'O' ? 'checked' : '' }}
                                                                    >
                                                                    <label id="male-label" class="{{ session('motor')->policy_holder->gender === 'O' ? 'btn btn-primary text-white rounded border active' : 'btn btn-light rounded-end' }}" for="company">
                                                                        {{ __('frontend.motor.compare_page.company') }}
                                                                    </label>
                                                                </div>
                                                            @endif
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
                            <div class="col-4 border shadow rounded my-3 insurer-card">
                                <div class="row" id={{ 'insurer-' . $product->id }} data-insurer-id="{{ $product->insurance_company->id }}">
                                    <div class="col-12">
                                        <div class="row p-3">
                                            <div class="col-12 text-center align-self-center mb-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p class="text-primary">{{ $product->name }}</p>
                                                        <img
                                                            src="{{ asset("images/insurer/{$product->insurance_company->logo}") }}"
                                                            alt="{{ $product->insurance_company->name }}"
                                                            class="img-fluid d-block p-2 mx-auto align-self-center"
                                                            width="200"
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 text-center align-self-center">
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
                                                <div class="row justify-content-around mt-3">
                                                    <button type="button" class="col-5 btn btn-outline-primary border border-primary border-2 fw-bold rounded btn-compare" data-product_id="{{ $product->id }}">
                                                        {{ __('frontend.button.compare') }}
                                                    </button>
                                                    <button type="button" class="col-5 btn btn-primary text-white text-uppercase rounded btn-buy" data-product_id="{{ $product->id }}">
                                                        {{ __('frontend.button.buy') }}
                                                    </button>
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
            <div class="row compare">
                <div class="col-12">
                    <table id="compare-table" class="table table-bordered table-striped table-hover">
                        <tbody>
                            <tr id="insurer-name-row" class="bg-primary text-center">
                                <th></th>
                            </tr>
                            <tr id="buy-now-btn-row" class="bg-primary">
                                <th></th>
                            </tr>
                            <tr id="valuation-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.valuation') }}</th>
                            </tr>
                            <tr id="basic-info-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.basic_information')}}</th>
                            </tr>
                            <tr id="where-covered-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.where_covered') }}</th>
                            </tr>
                            <tr id="who-covered-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.who_covered') }}</th>
                            </tr>
                            <tr id="workshop-coverage-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.workshop_coverage') }}</th>
                            </tr>
                            <tr id="mobile-accident-response-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.mobile_accident_response_service') }}</th>
                            </tr>
                            <tr id="repair-warranty-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.warranty_on_repairs') }}</th>
                            </tr>
                            <tr id="young-driver-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.young_driver') }}</th>
                            </tr>
                            <tr id="excess-row">
                                <th class="bg-primary text-white">{{ __('frontend.motor.compare_page.compare_details.excess') }}</th>
                            </tr>
                            <tr id="view-add-ons-row">
                                <th class="bg-primary"></th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <x-modal id="occupation-modal" maxWidth="md" headerClass="bg-primary text-white" backdrop-static not-closable>
                <x-slot name="title">{{ __('frontend.motor.compare_page.need_occupation') }}</x-slot>
                <x-slot name="body">
                    <div class="row">
                        <div class="col-6">
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.car_number') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle_number }}</div>
                            </div>
                            <div class="row my-3">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.make') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->make }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.year') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->manufacture_year }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.engine_capacity') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->engine_capacity }}</div>
                            </div>
                            <div class="row my-3">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.model') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->model }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.variant') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->variant }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <p>{{ __('frontend.motor.compare_page.select_occupation') }}</p>
                            <select id="occupation">
                                <option value="">{{ __('frontend.motor.compare_page.please_select') }}</option>
                                @if (session('motor')->policy_holder->id_type === config('setting.id_type.nric_no'))
                                    @foreach (__('frontend.motor.compare_page.occupation.private') as $occupation)
                                        <option value="{{ $occupation }}">{{ $occupation }}</option>
                                    @endforeach
                                @else
                                    @foreach (__('frontend.motor.compare_page.occupation.company') as $occupation)
                                    <option value="{{ $occupation }}">{{ $occupation }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <p id="occupation-error" class="text-danger fw-bold d-none"></p>
                        </div>
                    </div>
                </x-slot>
                <x-slot name="footer">
                    <button type="button" id="occupation-next" class="btn btn-primary text-white text-uppercase">{{ __('frontend.button.get_quotation') }}</button>
                </x-slot>
            </x-modal>
            <x-modal id="avcode-modal" maxWidth="md" headerClass="bg-primary text-white" backdrop-static not-closable>
                <x-slot name="title">{{ __('frontend.motor.compare_page.need_vehicle_information') }}</x-slot>
                <x-slot name="body">
                    <div class="row">
                        <div class="col-6">
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.car_number') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle_number }}</div>
                            </div>
                            <div class="row my-3">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.make') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->make }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.year') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->manufacture_year }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.engine_capacity') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->engine_capacity }}</div>
                            </div>
                            <div class="row my-3">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.model') }}</div>
                                <div class="col-12">{{ session('motor')->vehicle->model }}</div>
                            </div>
                            <div class="row">
                                <div class="col-12 fw-bold">{{ __('frontend.motor.vehicle_details.variant') }}</div>
                                <div id="variant_display" class="col-12">{{ session('motor')->vehicle->variant }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <p>
                                {{ __('frontend.motor.compare_page.verify_variant') }}
                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.compare_page.affect_premium') }}">
                                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                </span>
                            </p>
                            <select id="allianz-variant">
                                <option value="">{{ __('frontend.motor.vehicle_details.select_car_specs') }}</option>
                            </select>
                        </div>
                    </div>
                </x-slot>
                <x-slot name="footer">
                    <button type="button" id="avcode-next" class="btn btn-primary text-white text-uppercase">{{ __('frontend.button.get_quotation') }}</button>
                </x-slot>
            </x-modal>
        </x-slot>
    </x-motor-layout>
@endsection

@push('after-scripts')
    <script>
        // Send Landing on Comapre Page Event to GA
        gtag('event', 'l_motor_cm', { 'debug_mode': true });

        let motor = JSON.parse($('#motor').val());
        let products = JSON.parse($('#products').val());
        let premiums = [];
        let allianz_variant = [];
        let controller = null;
        let add_ons_available = {
            'Windscreen': [],
            'Accessories': [],
            'Gas Conversion Kit & Tank': [],
            'NCD Relief': [],
            'CART': [],
            'Strike, Riot, Civil Commotion': [],
            'Legal Liability to Passengers': [],
            'Legal Liability Of Passsengers': [],
            'Special Perils': [],
        };

        $(() => {
            $('.row.compare').hide();
            $('#landing-banner').modal('show');
            getPremium();

            $('#male-label, #female-label').on('click', (e) => {
                if(!$(e.target).hasClass('active')) {
                    $(e.target).removeClass('btn-light').toggleClass('active btn-primary border text-white');
                    $(e.target).siblings('label.btn').removeClass('active btn-primary border text-white').toggleClass('btn-light');
                }
            });

            $('input[name=gender]').on('change', (e) => {
                if($(e.target).val() === 'M') {
                    $('#male-label').trigger('click');
                } else {
                    $('#female-label').trigger('click');
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

            $('#compare').on('click', '.btn-buy', (e) => {
                // Send Clicked Buy Button Event to GA
                gtag('event', 'c_cmp_buy', { 'debug_mode': true });

                $(e.target).addClass('loadingButton');

                if(controller) {
                    controller.abort();
                }

                let product_id = $(e.target).data('product_id');
                motor.vehicle = premiums[product_id].vehicle;
                motor.product_id = product_id;
                $('#motor').val(JSON.stringify(motor));

                $('#insurance-premium').val(JSON.stringify(premiums[product_id]));
                $('#h-gender').val($('input[name=gender]:checked').val());
                $('#h-marital-status').val($('#marital-status').val());

                if([15].includes(product_id)) {
                    $('#occupation-modal').modal('show');
                    $('#occupation').select2({
                        width: '100%',
                        theme: 'bootstrap-5',
                        dropdownParent: '#occupation-modal'
                    }).on('select2:select', function () {
                        $(this).parsley().validate();
                    }).attr('required', true);
                } else if([3].includes(product_id)) {
                    $('#avcode-modal').modal('show');
                    $('#allianz-variant').select2({
                        width: '100%',
                        theme: 'bootstrap-5',
                        dropdownParent: '#avcode-modal'
                    }).on('select2:select', function () {
                        $(this).parsley().validate();
                    }).attr('required', true);
                } else {
                    $('#product-form').submit();
                }
            });

            $('#occupation-next').on('click', async () => {
                if($('#occupation').val() != '') {
                    motor.policy_holder.occupation = $('#occupation').val();
                    $('#motor').val(JSON.stringify(motor));

                    await getPremium([motor.product_id]);
                    $('#product-form').submit();
                } else {
                    $('#occupation-error').text("{{ __('frontend.motor.compare_page.occupation_error') }}").removeClass('d-none');
                }
            });

            $('#occupation').on('change', () => {
                $('#occupation-error').addClass('d-none');
            });

            $('#allianz-variant').on('change', function(){
                var varianttext = allianz_variant.find((variant) => {
                    return variant.AvCode = $('#allianz-variant').val();
                }).Variant;
                $('#variant_display').text(varianttext);
            });

            $('#avcode-next').on('click', async () => {
                if($('#allianz-variant').val() != '') {
                    motor.vehicle.extra_attribute.avcode = allianz_variant.find((variant) => {
                        return variant.Variant = $('#allianz-variant').val();
                    }).AvCode;

                    motor.vehicle.variant =$('#allianz-variant').val();

                    $('#motor').val(JSON.stringify(motor));
                    $('#av-code').val(motor.vehicle.extra_attribute.avcode);

                    await getPremium([{id: motor.product_id}]);
                    $('#product-form').submit();
                } else {
                    $('#avcode-error').text("{{ __('frontend.motor.compare_page.avcode_error') }}").removeClass('d-none');
                }
            });

            $('.btn-view-details').on('click', (e) => {
                // Populate Compare Details Data
                let product_id = $(e.target).data('product_id');
                $('#compare-details-modal').modal('show');
            });

            $('.btn-compare').on('click', (e) => {
                // Send Clicked Compare Button Event to GA
                gtag('event', 'c_cmp_cmp', { 'debug_mode': true });

                let product_id = $(e.target).data('product_id');

                if(!$(e.target).hasClass('btn-primary text-white')) {
                    if($('.btn-compare.btn-primary').length === 3) {
                        swalAlert('You may choose up to 3 insurers for comparison', null, false, 'warning', 'Okay');
                        return;
                    }

                    let premium = premiums[product_id];
                    let product = products.filter((product) => {
                        return product.id === product_id;
                    })[0];
                    let benefits = JSON.parse(product.benefits.benefits);
                    let formatted_sum_insured = formatMoney(premium.sum_insured);
                    let buy_now_text = "{{ __('frontend.button.buy') }}";

                    // Populate
                    /// 1. Insurer Name
                    $('#insurer-name-row').append(`
                        <th class="text-white ${product_id}">
                            <p class="mb-0">${product.insurance_company.name}</p>
                        </th>
                    `);

                    /// 2. Buy Now Button
                    $('#buy-now-btn-row').append(`
                        <td class="bg-primary p-0 ${product_id}">
                            <div class="d-grid gap-1">
                                <button type="button" class="btn btn-primary text-white text-uppercase btn-buy" data-product_id="${product_id}">
                                    ${buy_now_text}
                                </button>
                            </div>
                        </td>
                    `);

                    /// 3. Vehicle Sum Insured
                    $('#valuation-row').append(`
                        <td class="${product_id}">
                            <p class="fw-bold">${premium.sum_insured_type}</p>
                            <p>RM ${formatted_sum_insured}</p>
                        </td>
                    `);

                    /// 4. Product Features
                    $('#basic-info-row').append(`
                        <th class="bg-primary text-white ${product_id}">
                            <p class="mb-0">Key Features</p>
                        </th>
                    `);
                    $('#where-covered-row').append(`
                        <td class="${product_id}">
                            <p>${benefits.where}</p>
                        </td>
                    `);
                    $('#who-covered-row').append(`
                        <td class="${product_id}">
                            <p>${benefits.who}</p>
                        </td>
                    `);
                    $('#workshop-coverage-row').append(`
                        <td class="${product_id}">
                            <p>${benefits.workshops}</p>
                        </td>
                    `);

                    let html = '';
                    benefits.mobile_accident_response.forEach((item) => {
                        html += `<p><i class="fa-regular fa-check text-primary"></i> ${item}</p>`;
                    });
                    $('#mobile-accident-response-row').append(`<td class="${product_id}">${html}</td>`);

                    $('#repair-warranty-row').append(`
                        <td class="${product_id}">
                            <p>${benefits.repair_warranty}</p>
                        </td>
                    `);
                    $('#young-driver-row').append(`
                        <td class="${product_id}">
                            <p>${benefits.young_driver_excess}</p>
                        </td>
                    `);
                    $('#excess-row').append(`
                        <td class="${product_id}">
                            <p>${benefits.excess}</p>
                        </td>
                    `);

                    /// 5. Add Ons
                    //// i. Populate a Button
                    $('#view-add-ons-row').append(`
                        <td class="bg-primary p-0 ${product_id}">
                            <div class="d-grid gap-1">
                                <button id="btn-view-add-ons" class="btn btn-primary text-white fw-bold py-2">View Available Add-Ons</button>
                            </div>
                        </td>
                    `);

                    //// ii. Store all available Add Ons to an array [Group by Extra Cover Code]
                    premium.extra_cover.forEach((add_ons) => {
                        switch(add_ons.extra_cover_code) {
                            case '89':
                            case '89A':
                            case 'M02': { // Windscreen
                                add_ons_available['Windscreen'].push(product_id);
                                break;
                            }
                            case '97': { // Accessories
                                add_ons_available['Accessories'].push(product_id);
                                break;
                            }
                            case '97A': { // Gas Conversion Kit & Tank
                                add_ons_available['Gas Conversion Kit & Tank'].push(product_id);
                                break;
                            }
                            case '111': { // NCD Relief
                                add_ons_available['NCD Relief'].push(product_id);
                                break;
                            }
                            case '112':
                            case 'M51' : { // CART
                                add_ons_available['CART'].push(product_id);
                                break;
                            }
                            case '25':
                            case 'M03': { // Strike, Riot, Civil Commotion
                                add_ons_available['Strike, Riot, Civil Commotion'].push(product_id);
                                break;
                            }
                            case '04':
                            case 'M01':
                            case '02': { // Legal Liability to Passengers
                                add_ons_available['Legal Liability to Passengers'].push(product_id);
                                break;
                            }
                            case '72': { // Legal Liability Of Passengers
                                add_ons_available['Legal Liability Of Passsengers'].push(product_id);
                                break;
                            }
                            case '57':
                            case 'M17': { // Special Perils
                                add_ons_available['Special Perils'].push(product_id);
                                break;
                            }
                            default: {
                                if(!(add_ons.extra_cover_description in add_ons_available)) {
                                    add_ons_available[add_ons.extra_cover_description] = [product_id];
                                } else {
                                    add_ons_available[add_ons.extra_cover_description].push(product_id);
                                }
                            }
                        }
                    });

                    //// iii. Remove Rendered Add Ons for Other Insurer
                    if($('#compare-table tbody .add-ons-row').length > 0) {
                        $('#compare-table tbody .add-ons-row').each((index, element) => {
                            $(element).remove();
                        });
                    }

                    //// iv. Render
                    renderAddOns();

                    /// 6. Update the Button Text after comparison
                    if($(e.target).hasClass('btn-outline-primary')) {
                        $(e.target).addClass('btn-primary text-white').removeClass('btn-outline-primary').text('Remove');

                        if(!$('.row.compare').is(':visible')) {
                            $('.row.compare').show();
                        }
                    }
                } else {
                    // Remove Add Ons
                    Object.keys(add_ons_available).forEach((name) => {
                       if(add_ons_available[name].includes(product_id))  {
                        add_ons_available[name].splice(add_ons_available[name].indexOf(product_id), 1);
                       }
                    });

                    const to_remove = [...$('#compare-table tbody .add-ons-row'), ...$('.compare .' + product_id)];
                    to_remove.forEach((element) => {
                        $(element).remove();
                    });

                    $(e.target).removeClass('btn-primary text-white').addClass('btn-outline-primary').text({{ __('frontend.button.compare') }});

                    if($('.btn-compare.btn-primary').length === 0) {
                        $('.row.compare').hide();
                    }

                    // Re-render Add Ons
                    renderAddOns();
                }
            });

            $('#compare-table').on('click', '#btn-view-add-ons', (e) => {
                $(e.target).parents('#view-add-ons-row').siblings('.add-ons-row').each((index, element) => {
                    $(element).removeClass('d-none');
                });
            });
        });

        function getPremium(ids = []) {
            if(ids.length > 0) {
                products = ids;
            }

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
                        motor.product_id = product.id;
                        motor.vehicle.variant = response.data.vehicle.variant;
                        motor.vehicle.sum_insured = response.data.sum_insured;
                        motor.vehicle.sum_insured_type = response.data.sum_insured_type;
                        $('#motor').val(JSON.stringify(motor));

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

                    if(product.id === 3) {
                        instapol.post("{{ route('motor.api.get-variant') }}", {
                            motor: motor,
                            product_id: product.id,
                        }).then((res) => {
                            console.log('Allianz AvCode', res);
                            allianz_variant = res.data.response;

                            res.data.response.forEach((variant) => {
                                $('#allianz-variant').append(`<option value="${variant.Variant}">${variant.Variant} (Sum Insured: ${'RM ' + formatMoney(variant.SumInsured)})</option>`);
                            });
                        })
                    }
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

        function renderAddOns() {
            let html = '';
            let should_hide = true;

            if($('#compare-table tbody .add-ons-row').length > 0) {
                if($($('#compare-table tbody .add-ons-row')[0]).hasClass('d-none')) {
                    should_hide = false;
                }
            }

            Object.keys(add_ons_available).forEach((name) => {
                html += `
                    <tr id="${name}" class="${should_hide ? 'd-none' : ''} add-ons-row">
                        <th class="bg-primary text-white">${name}</th>
                `;

                $('#insurer-name-row th').each((index, element) => {
                    if($(element).attr('class')) {
                        let product_id = $(element).attr('class').split(' ')[1];

                        if(add_ons_available[name].includes(parseInt(product_id))) {
                            html += `<td class="text-center">
                                <i class="fa-solid fa-circle-check fa-2x text-success"></i>
                            </td>`;
                        } else {
                            html += `<td class="text-center">
                                <i class="fa-solid fa-circle-xmark fa-2x text-danger"></i>
                            </td>`;
                        }
                    }
                });

                html += '</tr>';
            });

            $('#compare-table tbody').append(html);
        }
    </script>
@endpush
