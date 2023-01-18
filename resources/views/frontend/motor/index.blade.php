@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.get_quote'), __('frontend.motor.insure_motor'), __('frontend.motor.car_insurance')]))

@section('meta_description', __('frontend.motor.meta_description'))

@section('content')
    <section id="motor">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 p-0">
                    <div class="header">
                        <div class="container">
                            <div class="row text-center">
                                <div class="col-12 col-lg-7 tag-line">
                                    <h1 class="title text-uppercase text-white aos-init" data-aos="fade-right" data-aos-duration="1000">{{ __('frontend.motor.compare_and_buy') }}</h1>
                                    <p class="text-white aos-init" data-aos="fade-right" data-aos-duration="2000">{{ __('frontend.motor.compare_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 p-0">
                    <form action="{{ route('motor.index') }}" method="POST" id="motor-details-form" data-parsley-validate>
                        @csrf
                        <section id="motor-details" class="mb-4 nm-10">
                            <div class="container">
                                <div class="row">
                                    <div class="col-12">
                                        <img
                                            src="{{ asset('images/icons/motor-with-bg.png') }}"
                                            alt="{{ __('frontend.products.motor') }}"
                                            class="float-start position-relative icon-motor"
                                            width="240"
                                        >
                                        <div class="bg-white rounded ms-3 justify-content-center position-relative white-glow vehicle-details">
                                            <div class="pt-4 col-12">
                                                <div class="row">
                                                    <div class="col-12 text-center">
                                                        <div class="btn-group rounded" role="group">
                                                            <input type="radio" id="id-type-1" class="btn-check" name="id_type" value="1" {{ !empty($motor->policy_holder->id_type) && $motor->policy_holder->id_type === 1 ? 'checked' : 'checked' }}>
                                                            <label id="private-reg" class="btn btn-primary text-white rounded-start border active text-uppercase" for="id-type-1">{{ __('frontend.motor.private_registered') }}</label>
                    
                                                            <input type="radio" id="id-type-2" class="btn-check" name="id_type" value="2" {{ !empty($motor->policy_holder->id_type) && $motor->policy_holder->id_type === 2 ? 'checked' : '' }}>
                                                            <label id="company-reg" class="btn btn-light rounded-end text-uppercase" for="id-type-2">{{ __('frontend.motor.company_registered') }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-4 align-items-center">
                                                    <div class="mt-2 col-12 col-lg-3">
                                                        <h5 class="text-center text-uppercase title">{{ __('frontend.motor.car_plate') }}</h5>
                                                    </div>
                                                    <div class="col-11 col-lg-7 col-xs-3 col-md-10">
                                                        <input
                                                            id="vehicle-no"
                                                            class="uppercase form-control"
                                                            type="text"
                                                            autocomplete="off"
                                                            name="vehicle_number"
                                                            placeholder="JRCxxxx"
                                                            minlength="2"
                                                            maxlength="15"
                                                            pattern="[a-zA-Z0-9]+"
                                                            value="{{ old('vehicle_number', $motor->vehicle_number ?? '') }}"
                                                            required
                                                            data-parsley-required-message="Please enter your vehicle number"
                                                            data-parsley-pattern-message="Please enter a valid vehicle number"
                                                            data-parsley-errors-container="#vehicle-number-errors"
                                                        >
                                                        <span id="vehicle-number-errors"></span>
                                                    </div>
                                                    <div class="col-1 col-xs-3">
                                                        <button type="button" id="vehicle-no-continue" class="btn btn-primary text-white rounded-circle px-3 py-2">
                                                            <i class="fa-solid fa-play align-middle"></i>
                                                        </button>
                                                    </div>
                                                    <div class="col-1"></div>
                                                </div>
                                            </div>
                                            @if ($errors->any())
                                                @foreach ($errors->all() as $error)
                                                    <div class="text-center text-danger">{{ $error }}</div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section id="owner-loacation-details" class="mb-3 programmatically-hidden">
                            <div class="container d-flex justify-content-center rounded">
                                <div id="extend-info-form" class="col-10 col-lg-9 rounded white-glow pl-4">
                                    <div class="card-body b-form">
                                        <div class="mt-5 mb-2 row">
                                            <div class="col-12 col-lg-5">
                                                <h4 class="title text-uppercase text-end mb-0">{{ __('frontend.motor.postcode') }}</h4>
                                            </div>
                                            <div class="col-10 col-lg-5">
                                                <input
                                                    class="form-control rounded"
                                                    type="text"
                                                    autocomplete="off"
                                                    autocorrect="off"
                                                    id="postcode"
                                                    name="postcode"
                                                    pattern="\d{5}"
                                                    placeholder="Vehicle Postcode eg: 52100"
                                                    maxlength="5"
                                                    value="{{ old('postcode', $motor->postcode ?? '') }}"
                                                    required
                                                    data-parsley-type="number"
                                                    data-parsley-required-message="Please enter your postcode"
                                                    data-parsley-pattern-message="Please enter a valid postcode"
                                                    data-parsley-errors-container="#vehicle-postcode-errors"
                                                >
                                                <span id="vehicle-postcode-errors"></span>
                                            </div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-12 col-lg-5">
                                                <h4 id="id-number-label" class="title text-uppercase text-end mb-0">{{ __('frontend.motor.nric') }}</h4>
                                            </div>
                                            <div class="col-10 col-lg-5">
                                                <input
                                                    id="id-number"
                                                    class="form-control rounded"
                                                    type="text"
                                                    placeholder="870312-12-1234"
                                                    autocomplete="off"
                                                    name="id_number"
                                                    minlength="12"
                                                    value="{{ old('id_number', $motor->policy_holder->id_number ?? '') }}"
                                                    pattern="\d{2}([0][1-9]|[1][0-2])([0][1-9]|[1-2][0-9]|[3][0-1])-\d{2}-\d{4}"
                                                    data-parsley-required-message="Please enter your ID number"
                                                    data-parsley-pattern-message="Please enter a valid ID number"
                                                    data-parsley-errors-container="#id-number-errors"
                                                    required
                                                >
                                                <span id="id-number-errors"></span>
                                            </div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-12 col-lg-5">
                                                <h4 class="title text-uppercase text-end mb-0">{{ __('frontend.motor.phone') }}</h4>
                                            </div>
                                            <div class="col-10 col-lg-5">
                                                <input
                                                    id="phone-number"
                                                    class="form-control rounded"
                                                    type="text"
                                                    autocomplete="off"
                                                    autocorrect="off"
                                                    spellcheck="off"
                                                    name="phone_number"
                                                    value="{{ old('phone_number', $motor->policy_holder->phone_number ?? '') }}"
                                                    placeholder="eg: 0122228888"
                                                    pattern="(0?1)[0-46-9][0-9]{7,8}"
                                                    minlength="9"
                                                    maxlength="11"
                                                    required
                                                    data-parsley-type="number"
                                                    data-parsley-required-message="Please enter your contact number"
                                                    data-parsley-pattern-message="Please enter a valid contact number"
                                                    data-parsley-errors-container="#phone-number-errors"
                                                >
                                                <span id="phone-number-errors"></span>
                                            </div>
                                        </div>
                                        <div class="mb-2 row" id="four-row">
                                            <div class="col-12 col-lg-5">
                                                <h4 class="title text-uppercase text-end mb-0">{{ __('frontend.motor.email') }}</h4>
                                            </div>
                                            <div class="col-10 col-lg-5">
                                                <input
                                                    id="email-address"
                                                    class="form-control rounded"
                                                    type="email"
                                                    autocomplete="off"
                                                    autocorrect="off"
                                                    spellcheck="off"
                                                    name="email"
                                                    value="{{ old('email', $motor->policy_holder->email ?? '') }}"
                                                    placeholder="E-Mail Address"
                                                    required
                                                    data-parsley-type="email"
                                                    data-parsley-required="true"
                                                    data-parsley-required-message="Please enter your email"
                                                    data-parsley-errors-container="#email-address-errors"
                                                >
                                                <span id="email-address-errors"></span>
                                            </div>
                                        </div>  
                                        <div class="my-3 row text-end">
                                            <div class="col-12 col-sm-10 col-md-11 col-lg-10 col-xl-10">
                                                <button type="reset" class="h4 d-inline-block bg-white border-0 text-center text-secondary text-uppercase fw-bold" id="btn-clear">{{ __('frontend.button.clear') }}</button>
                                                <button type="button" class="h4 bg-primary d-inline-block text-white border-0 text-center text-uppercase fw-bold" id="btn-continue">{{ __('frontend.button.continue') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div class="hidden">
                            <input type="hidden" id="motor" value='@json(session('motor'))'>
                        </div>
                    </form>
                    <div class="bg-light pt-4">
                        <x-make-easy-section />
                    </div>
                    <section id="partners">
                        <div class="container-fluid text-center">
                            <div class="row">
                                <div class="col-12 p-0">
                                    <h3 class="text-uppercase text-white fw-bold py-3">{{ __('frontend.motor.partner') }}</h3>
                                </div>
                            </div>
                            <x-instapol-motor-insurer :insurers="$insurers" />
                        </div>
                    </section>
                    <x-instapol-features />
                </div>
            </div>
        </div>
    </section>
@endsection

@push('after-scripts')
    <script>
        let motor = JSON.parse($('input#motor').val());

        $(() => {
            // Send Land on Motor Page to GA
            gtag('event', 'l_motor_la', { 'debug_mode': true });

            new Inputmask({mask: '999999-99-9999'}).mask('#id-number');

            $('#vehicle-no-continue').on('click', () => {
                if($('#vehicle-no').val()) {
                    $('#owner-loacation-details').slideDown();
                }
            });

            $('#company-reg, #private-reg').on('click', (e) => {
                if(!$(e.target).hasClass('active')) {
                    $(e.target).removeClass('btn-light').toggleClass('active btn-primary border text-white');
                    $(e.target).siblings('label.btn').removeClass('active btn-primary border text-white').toggleClass('btn-light');
                }
            });

            $('input[name=id_type]').on('change', () => {
                if($('input[name=id_type]:checked').val() == 2) {
                    $('#id-number-label').text("{{ __('frontend.motor.company_resgistration') }}");
                    $('#id-number').attr('placeholder', '1183636-M').removeAttr('pattern').attr('minlength', 8);

                    Inputmask.remove('#id-number')
                } else {
                    $('#id-number-label').text("{{ __('frontend.motor.nric') }}");
                    $('#id-number').attr('placeholder', '870312-12-1234').attr('pattern', '\d{2}([0][1-9]|[1][0-2])([0][1-9]|[1-2][0-9]|[3][0-1])-\d{2}-\d{4}')
                    new Inputmask({mask: '999999-99-9999'}).mask('#id-number');
                }
            });

            $('#btn-continue').on('click', (e) => {
                let form = $('#motor-details-form');

                if(form.parsley().validate()) {
                    $('input[name=id_type]').val($('input[name=id_type]:checked').val());
                    $(e.target).addClass('loadingButton');

                    // Set User Data to GA
                    gtag('set', 'user_data', {
                        'email': $('#email-address').val(),
                        'phone_number': '+6' + $('#phone-number').val().replace('-', ''),
                        'address': {
                            'postal_code': $('#postcode').val(),
                        }
                    });

                    form.submit();
                } else {
                    console.log(form.parsley())
                }
            });
        });
    </script>
@endpush