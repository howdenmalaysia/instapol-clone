@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.policy_holder_page.edit_personal_detail')]))
    
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
                                            data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.name') }}"
                                            data-parsley-pattern-message="{{ __('frontend.motor.policy_holder_page.error_messages.valid.name') }}"
                                        />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.id_type') }}</label>
                                    <div class="col-12 col-md-9">
                                        <select name="id_type" id="id-type" class="form-control" disabled required data-parsley-error-messages-disabled>
                                            <option value=""></option>
                                            <option value="{{ config('setting.id_type.nric_no') }}" {{ session('motor')->policy_holder->id_type === config('setting.id_type.nric_no') ? 'selected' : '' }}>{{ strtoupper(__('frontend.motor.nric')) }}</option>
                                            <option value="{{ config('setting.id_type.company_registration_no') }}" {{ session('motor')->policy_holder->id_type === config('setting.id_type.company_registration_no') ? 'selected' : '' }}>{{ strtoupper(__('frontend.motor.company_resgistration')) }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.id_number') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-id-number" class="form-control" name="id_number" value="{{ session('motor')->policy_holder->id_number }}" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.email') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="email" id="policy-holder-email" class="form-control" name="email"
                                            value="{{ session('motor')->policy_holder->email }}"
                                            required
                                            data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.email') }}"
                                            data-parsley-error-message="{{ __('frontend.motor.policy_holder_page.error_messages.valid.email') }}"
                                        />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.dob') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-dob" class="form-control" name="date_of_birth" value="{{ session('motor')->policy_holder->date_of_birth }}" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.gender') }}</label>
                                    <div class="col-12 col-md-9">
                                        <select name="gender" id="policy-holder-gender" class="form-control" disabled>
                                            <option value=""></option>
                                            <option value="M" {{ session('motor')->policy_holder->gender === 'M' ? 'selected' : '' }}>{{ strtoupper(__('frontend.motor.compare_page.male')) }}</option>
                                            <option value="F" {{ session('motor')->policy_holder->gender === 'F' ? 'selected' : '' }}>{{ strtoupper(__('frontend.motor.compare_page.female')) }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.phone_number') }}</label>
                                    <div class="col-12 col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-text" id="country-code">+60</span>
                                            <input type="text" id="policy-holder-phone-number" class="form-control" name="phone_number"
                                                value="{{ Illuminate\Support\Str::startsWith(session('motor')->policy_holder->phone_number, '0') ? substr(session('motor')->policy_holder->phone_number, 1) : session('motor')->policy_holder->phone_number }}"
                                                required min="1"
                                                pattern="(0?1)[0-46-9][0-9]{7,8}"
                                                data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.phone_number') }}"
                                                data-parsley-pattern-message="{{ __('frontend.motor.policy_holder_page.error_messages.valid.phone_number') }}"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.address') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-address-1" class="form-control uppercase" name="address_1" placeholder="{{ __('frontend.motor.policy_holder_page.placeholders.address_one') }}"
                                            required
                                            data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.address') }}"
                                        />
                                        <input type="text" id="policy-holder-address-2" class="form-control mt-2 uppercase" name="address_2" placeholder="{{ __('frontend.motor.policy_holder_page.placeholders.address_two') }}" />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.postcode') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-postcode" class="form-control" name="postcode" value="{{ session('motor')->postcode }}" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.city') }}</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" id="policy-holder-city" class="form-control uppercase" name="city" value="{{ strtoupper($city->post_office) }}" disabled />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.state') }}</label>
                                    <div class="col-12 col-md-9">
                                        <select name="state" id="policy-holder-state" class="form-control" disabled>
                                            <option value=""></option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->name }}" {{ $city->state->name === $state->name ? 'selected' : ''}}>{{ strtoupper($state->name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @if (!empty(session('motor')->roadtax))
                                    <div class="row mt-3">
                                        <div class="col-12 col-sm-3"></div>
                                        <div class="col-12 col-sm-9">
                                            <input type="checkbox" id="use-same-address" class="form-check-input col-3" name="use_same_address" />
                                            <label for="use-same-address" class="form-check-label col-9">{{ __('frontend.motor.policy_holder_page.use_same_address') }}</label>
                                        </div>
                                    </div>
                                    <div id="delivery-info">
                                        <div class="row mt-3">
                                            <div class="col-12 col-sm-3">
                                                <label for="delivery-recipient" class="col-form-label">{{ __('frontend.fields.recipient_name') }}</label>
                                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.policy_holder_page.recipient_tooltip') }}">
                                                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                </span>
                                            </div>
                                            <div class="col-12 col-sm-9">
                                                <input type="text" id="delivery-recipient" class="form-control uppercase" name="delivery_recipient" required />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <label for="delivery-phone-number" class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.phone_number') }}</label>
                                            <div class="col-12 col-md-9">
                                                <div class="input-group">
                                                    <span class="input-group-text" id="country-code">+60</span>
                                                    <input type="number" id="delivery-phone-number" class="form-control" name="delivery_phone_number"
                                                        required min="1"
                                                        pattern="(0?1)[0-46-9][0-9]{7,8}"
                                                        data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.phone_number') }}"
                                                        data-parsley-pattern-message="{{ __('frontend.motor.policy_holder_page.error_messages.valid.phone_number') }}"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <label for="delivery-address-1" class="col-form-label col-12 col-sm-3"></label>
                                            <div class="col-12 col-sm-9">
                                                <input type="text" id="delivery-address-1" class="form-control uppercase" name="delivery_address_1" placeholder="{{ __('frontend.motor.policy_holder_page.placeholders.address_one') }}"
                                                    required data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.address') }}"
                                                />
                                                <input type="text" id="delivery-address-2" class="form-control mt-2 uppercase" name="delivery_address_2" placeholder="{{ __('frontend.motor.policy_holder_page.placeholders.address_two') }}" />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <label for="delivery-postcode" class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.postcode') }}</label>
                                            <div class="col-12 col-sm-9">
                                                <input type="text" id="delivery-postcode" class="form-control uppercase" name="delivery_postcode" placeholder="{{ __('frontend.fields.postcode') }}"
                                                required data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.delivery_postcode') }}" />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <label for="delivery-city" class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.city') }}</label>
                                            <div class="col-12 col-sm-9">
                                                <input type="text" id="delivery-city" class="form-control uppercase" name="delivery_city" placeholder="{{ __('frontend.fields.city') }}"
                                                required data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.delivery_city') }}" />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <label for="delivery-state" class="col-form-label col-12 col-sm-3">{{ __('frontend.fields.state') }}</label>
                                            <div class="col-12 col-sm-9">
                                                <input type="text" id="delivery-state" class="form-control uppercase" name="delivery_state" placeholder="{{ __('frontend.fields.state') }}"
                                                required data-parsley-required-message="{{ __('frontend.motor.policy_holder_page.error_messages.required.delivery_state') }}" />
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="hidden">
                                <input type="hidden" id="motor" name="motor" value='@json(session('motor'))' />
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
        let motor = JSON.parse($('#motor').val());

        $(() => {
            $('#btn-next').on('click', (e) => {
                $(e.target).toggleClass('loadingButton');

                let form = $('#policy-holder-form');

                if(!form.parsley().validate()) {
                    return;
                }

                // Policy Holder Details
                motor.policy_holder.name = $('#policy-holder-name').val();
                motor.policy_holder.email = $('#policy-holder-email').val();
                motor.policy_holder.address_1 = $('#policy-holder-address-1').val();
                motor.policy_holder.address_2 = $('#policy-holder-address-2').val();
                motor.policy_holder.city = $('#policy-holder-city').val();
                motor.policy_holder.state = $('#policy-holder-state').val();
                motor.policy_holder.phone_number = $('#policy-holder-phone-number').val();

                // Roadtax Details
                if($('#use-same-address').is(':checked')) {
                    motor.roadtax.name = $('#policy-holder-name').val();
                    motor.roadtax.phone_number = $('#policy-holder-phone-number').val();
                    motor.roadtax.address_one = $('#policy-holder-address-1').val();
                    motor.roadtax.address_two = $('#policy-holder-address-2').val();
                    motor.roadtax.postcode = motor.postcode;
                    motor.roadtax.city = $('#policy-holder-city').val();
                    motor.roadtax.state = $('#policy-holder-state').val();
                } else {
                    motor.roadtax.recipient_name = $('#delivery-recipient').val();
                    motor.roadtax.recipient_phone_number = $('#delivery-phone-number').val();
                    motor.roadtax.address_one = $('#delivery-address-1').val();
                    motor.roadtax.address_two = $('#delivery-address-2').val();
                    motor.roadtax.postcode = $('#delivery-postcode').val();
                    motor.roadtax.city = $('#delivery-city').val();
                    motor.roadtax.state = $('#delivery-state').val();
                }

                $('#motor').val(JSON.stringify(motor));

                instapol.post("{{ route('motor.api.create-quotation') }}", {
                    motor: motor
                }).then((res) => {
                    console.log(res);

                    motor.insurance_code = res.data.insurance_code;
                    motor.quotation = res.data.quotation;

                    $('#motor').val(JSON.stringify(motor));
                    $(e.target).removeClass('loadingButton');

                    form.submit();
                }).catch((err) => {
                    console.log(err);
                });
            });

            $('#use-same-address').on('change', (e) => {
                if($(e.target).is(':checked')) {
                    $('#delivery-info').find('input').each((index, input) => {
                        $(input).removeAttr('required');
                    });

                    $('#delivery-info').hide();
                }
            });
        });
    </script>
@endpush