@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __()]))
    
@section('content')
    <x-motor-layout id="policy-holder" current-step="4">
        <x-slot name="content">
            <div class="row">
                <div class="col-12 col-lg-4">
                    <x-pricing-card
                        insurer-logo='{{ asset("images/insurer/{$product->insurance_company->logo}") }}'
                        insurer-name="{!! $product->insurance_company->name !!}"
                        basic-premium="{{ $premium->basic_premium }}"
                        ncd-amount="{{ $premium->ncd_amount }}"
                        total-benefit-amount="{{ $premium->total_benefit_amount }}"
                        gross-premium="{{ $premium->gross_premium }}"
                        sst-amount="{{ $premium->sst_amount }}"
                        stamp-duty="{{ $premium->stamp_duty }}"
                        total-payable="{{ $premium->total_payable }}"
                    />
                </div>
                <div class="col-12 col-lg-8">
                    <div class="card border">
                        <form action="{{ route('motor.policy-holder') }}" method="POST" id="policy-holder-form" data-parsley-validate>
                            @csrf
                            <div class="card-body">
                                <h3 class="card-title fw-bold border-bottom pb-4 px-md-3 mt-3">{{ __('frontend.motor.policy_holder_page.policy_holder_details') }}</h3>
                                <div class="row">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.name') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-name" class="form-control uppercase" name="name"
                                            required
                                            data-parsley-pattern="[a-z A-Z&@',\/]{6,}"
                                            data-parsley-required-message="Please enter your name"
                                            data-parsley-pattern-message="Please enter a valid name"
                                        />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.id_type') }}</label>
                                    <div class="col-12 col-md-9">
                                        <select name="id_type" id="id-type" class="form-control" disabled required data-parsley-error-messages-disabled>
                                            <option value=""></option>
                                            <option value="{{ config('setting.id_type.nric_no') }}">{{ __('frontend.motor.nric') }}</option>
                                            <option value="{{ config('setting.id_type.company_registration_no') }}">{{ __('frontend.motor.company_resgistration') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.id_number') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-id-number" class="form-control" name="id_number" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.email') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="email" id="policy-holder-email" class="form-control" name="email"
                                            required
                                            data-parsley-required-message="Please enter your email address"
                                            data-parsley-error-message="Please enter a valid email address"
                                        />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.dob') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-dob" class="form-control" name="date_of_birth" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.gender') }}</label>
                                    <div class="col-12 col-md-9">
                                        <select name="gender" id="policy-holder-gender" class="form-control" disabled>
                                            <option value=""></option>
                                            <option value="M">{{ __('frontend.motor.compare_page.male') }}</option>
                                            <option value="F">{{ __('frontend.motor.compare_page.female') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.phone_number') }}</label>
                                    <div class="col-12 col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-text" id="country-code">+60</span>
                                            <input type="text" id="policy-holder-phone-number" class="form-control" name="phone_number"
                                                required min="1"
                                                pattern="(0?1)[0-46-9][0-9]{7,8}"
                                                data-parsley-required-message="Please enter your phone number"
                                                data-parsley-pattern-message="Please enter a valid phone number"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.address') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-address-1" class="form-control uppercase" name="address_1" placeholder="Address line 1"
                                            required
                                            data-parsley-required-message="Please enter your address"
                                        />
                                        <input type="text" id="policy-holder-address-2" class="form-control mt-2 uppercase" name="address_2" placeholder="Address line 2 (Optional)" />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.postcode') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-postcode" class="form-control" name="postcode" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.city') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-city" class="form-control" name="city" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.state') }}</label>
                                    <div class="col-12 col-md-9">
                                        <select name="state" id="policy-holder-state" class="form-control" disabled>
                                            <option value=""></option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state }}">{{ __($state) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" id="btn-back" class="btn btn-link text-dark fw-bold">{{ __('frontend.button.back') }}</button>
                        <button type="button" id="btn-next" class="btn btn-primary text-white rounded">{{ __('frontend.button.next') }}</button>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-motor-layout>
@endsection

@push('after-scripts')
    <script>
        $(() => {
            $('#btn-next').on('click', () => {
                let form = $('#policy-holder-form');

                if(!form.isValid()) {
                    return;
                }

                // form.submit();
            });
        });
    </script>
@endpush