@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.add_ons_page.add_ons')]))
    
@section('content')
    <x-motor-layout id="add-ons" current-step="3">
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
                        roadtax-total="{{ $premium->roadtax ?? session('motor')->roadtax->total ?? 0.00 }}"
                        promo
                    />
                </div>
                <div class="col-12 col-lg-8">
                    <div class="card border">
                        <form action="{{ route('motor.add-ons') }}" method="POST" id="add-ons-form">
                            @csrf
                            <div class="card-body">
                                <h3 class="card-title fw-bold border-bottom pb-4 px-md-3 mt-3">{{ __('frontend.motor.add_ons_page.sum_insured_amount') }}</h3>
                                <h5 class="card-text">{{ __('frontend.motor.add_ons_page.sum_insured') }}</h5>
                                <div class="pb-4 px-md-3">
                                    @if (session('motor')->vehicle->min_sum_insured !== session('motor')->vehicle->max_sum_insured)
                                        <label class="float-left text-primary fw-bold">{{ 'RM ' . number_format(session('motor')->vehicle->min_sum_insured) }}</label>
                                        <label class="float-end text-primary fw-bold">{{ 'RM ' . number_format(session('motor')->vehicle->max_sum_insured) }}</label>
                                        <div id="sum-insured-tooltip" class="range" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ 'RM ' . number_format(session('motor')->vehicle->sum_insured) }}">
                                            <input type="range" id="sum-insured-slider" class="form-range" min="{{ session('motor')->vehicle->min_sum_insured }}" max="{{ session('motor')->vehicle->max_sum_insured }}" value="{{ session('motor')->vehicle->sum_insured }}" step="1000">
                                        </div>
                                    @else
                                        <div class="text-center mt-4">
                                            <h4 class="fw-bold text-primary">
                                                {{ 'RM ' . number_format(session('motor')->vehicle->sum_insured) }}
                                            </h4>
                                            <h4 class="fw-bold text-primary">{{ '(' . __('frontend.motor.add_ons_page.fixed_value') . ')' }}</h4>
                                        </div>
                                    @endif
                                </div>
                                <div id="extra-coverages">
                                    <h3 class="card-title fw-bold border-bottom py-4 px-md-3">{{ __('frontend.motor.add_ons_page.additional_coverage') }}</h3>
                                    <div id="add-on-item">
                                        @foreach (array_chunk(session('motor')->extra_cover_list, 5)[0] as $_extra_cover)
                                            <div class="mb-2 extra-coverage">
                                                <div class="row px-md-3">
                                                    <div class="col-1">
                                                        <input
                                                            type="checkbox"
                                                            id="{{ 'checkbox-' . $_extra_cover->extra_cover_code }}"
                                                            class="form-check-input extra-coverage-checkbox"
                                                            name="extra_coverage[]"
                                                            value="{{ $_extra_cover->extra_cover_code }}"
                                                            {{ $_extra_cover->selected ? 'checked' : '' }}
                                                            {{ $_extra_cover->readonly ? 'disabled' : '' }}
                                                        />
                                                    </div>
                                                    <div class="col-8 d-flex justify-content-between">
                                                        <label for="{{ 'checkbox-' . $_extra_cover->extra_cover_code }}" id="{{ 'label-checkbox-' . $_extra_cover->extra_cover_code }}">{{ $_extra_cover->extra_cover_description }}</label>
                                                        
                                                        @if (strpos($_extra_cover->extra_cover_description, 'Windscreen') !== false)
                                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.windscreen') }}">
                                                                <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                            </span>
                                                        @else
                                                            @if (strpos($_extra_cover->extra_cover_description, 'Legal Liability Of') !== false)
                                                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.llop') }}">
                                                                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                                </span>
                                                            @else
                                                                @if (strpos($_extra_cover->extra_cover_description, 'Legal Liability to') !== false)
                                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.lltp') }}">
                                                                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                                    </span>
                                                                @else
                                                                    @if (strpos($_extra_cover->extra_cover_description, 'Strike, Riot') !== false)
                                                                        <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.srcc') }}">
                                                                            <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                                        </span>
                                                                    @else
                                                                        @if (strpos($_extra_cover->extra_cover_description, 'Accessories') !== false)
                                                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.accessories') }}">
                                                                                <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                                            </span>
                                                                        @else
                                                                            @if (strpos($_extra_cover->extra_cover_description, 'Personal Accident') !== false)
                                                                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.pa') }}">
                                                                                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                                                </span>
                                                                            @else
                                                                                @if (strpos($_extra_cover->extra_cover_description, 'NCD') !== false)
                                                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.ncd') }}">
                                                                                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                                                    </span>
                                                                                @endif
                                                                            @endif
                                                                        @endif
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <div class="col-1 text-end">RM</div>
                                                    <div id="{{ $_extra_cover->extra_cover_code . '-premium' }}" class="col-2 text-end premium">{{ number_format($_extra_cover->premium, 2) }}</div>
                                                </div>
                                                @if (!empty($_extra_cover->option_list))
                                                    <div class="row">
                                                        <div class="col-5 px-md-3 mb-3 ms-3">
                                                            @if (!empty($_extra_cover->option_list->description))
                                                                <small>{{ $_extra_cover->option_list->description . ':' }}</small>
                                                            @endif
                                                            <select id="{{ 'sum-insured-' . $_extra_cover->extra_cover_code }}" class="option-list" data-select data-extra-cover-code="{{ $_extra_cover->extra_cover_code }}">
                                                                @foreach ($_extra_cover->option_list->values as $index => $option)
                                                                    @if (is_string($option))
                                                                        <option value="{{ $option }}" {{ $index === 0 ? 'selected' : ''}}>{{ $option }}</option>
                                                                    @else
                                                                        <option value="{{ $option }}" {{ $option === 1000 ? 'selected' : '' }}>{{ 'RM ' . $option }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (!empty($_extra_cover->cart_list))
                                                    <div class="row">
                                                        <div class="col-5 px-md-3 mb-3 ms-3">
                                                            <small>{{ __('frontend.motor.add_ons_page.days') . ':' }}</small>
                                                            <select id="{{ 'cart-day-' .  $_extra_cover->extra_cover_code }}" class="cart-day" data-select data-extra-cover-code="{{ $_extra_cover->extra_cover_code }}">
                                                                @foreach ($_extra_cover->cart_list as $cart)
                                                                    <option value="{{ $cart->cart_day }}" {{ $cart->cart_day === 7 ? 'selected' : '' }}>{{ $cart->cart_day }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-5 px-md-3 mb-3 ms-3">
                                                            <small>{{ __('frontend.motor.add_ons_page.amount') . ':' }}</small>
                                                            <select id="{{ 'cart-amount-' .  $_extra_cover->extra_cover_code }}" class="cart-amount" data-select data-extra-cover-code="{{ $_extra_cover->extra_cover_code }}">
                                                                @foreach ($_extra_cover->cart_list[0]->cart_amount_list as $cart_amount)
                                                                    <option value="{{ $cart_amount }}" {{ $cart_amount === 100 ? 'selected' : '' }}>{{ 'RM ' . $cart_amount }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                        <div id="show-more-wrapper"></div>
                                    </div>
                                    @if (count(session('motor')->extra_cover_list) > 5)
                                        <div class="mt-4">
                                            <button type="button" id="show-more-add-ons" class="btn btn-light float-end rounded" data-shown="false">{{ __('frontend.button.show_more') }}</button>
                                        </div>
                                    @endif
                                </div>
                                @if (session('motor')->named_drivers_needed)
                                    <div id="additional-driver" class="mt-3">
                                        <h3 class="card-title fw-bold border-bottom py-4 px-md-3">{{ __('frontend.motor.add_ons_page.additional_driver') }}</h3>
                                        <div class="alert alert-success mx-md-3" role="alert">
                                            {{ __('frontend.motor.add_ons_page.additional_driver_note') }}
                                        </div>
                                        <div class="row info px-md-3 driver-0">
                                            <div class="col-4">
                                                <label for="driver-name-0" class="form-label uppercase">{{ __('frontend.fields.name') }}</label>
                                                <input type="text" id="driver-name-0" class="form-control text-uppercase additional-driver-name" />
                                            </div>
                                            <div class="col-4">
                                                <label for="driver-id-number-0" class="form-label">{{ __('frontend.fields.id_number') }}</label>
                                                <input type="text" id="driver-id-number-0" class="form-control text-uppercase additional-driver-id-number" />
                                            </div>
                                            <div class="col-3">
                                                <label for="driver-relationship-0" class="form-label">{{ __('frontend.fields.relationship') }}</label>
                                                <select id="driver-relationship-0" class="form-control additional-driver-relationship" data-select>
                                                    <option value=""></option>
                                                    @foreach ($relationships as $relationship)
                                                        <option value="{{ $relationship->id }}">{{ __("frontend.relationships.{$relationship->name}") }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-1 align-self-end">
                                                <button type="button" class="btn btn-danger text-white btn-delete-driver" data-id="0">
                                                    <i class="fa-solid fa-trash" data-id="0"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row px-md-3">
                                            <div class="col-12 text-end mt-3">
                                                <button type="button" id="add-additional-driver" class="btn btn-primary text-white px-4 rounded">{{ __('frontend.motor.add_ons_page.add_driver') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div id="roadtax" class="mt-3">
                                    <h3 class="card-title fw-bold border-bottom py-4 px-md-3">{{ __('frontend.motor.add_ons_page.road_tax_renewal') }}</h3>
                                    <div class="row align-items-center px-md-3">
                                        <div class="col-1">
                                            <input type="checkbox" id="roadtax-checkbox" class="form-check-input" name="roadtax" {{ !empty(session('motor')->roadtax) ? 'checked' : '' }} />
                                        </div>
                                        <div class="col-8">
                                            <div class="row align-items-center">
                                                <div class="col-3">{{ __('frontend.motor.add_ons_page.road_tax_fee') }}</div>
                                                <div id="body-type-wrapper" class="col-9 d-flex justify-content-between align-items-center">
                                                    <select name="body_type" id="body-type" class="form-control w-75" disabled>
                                                        <option value="">{{ __('frontend.motor.add_ons_page.body_type') }}</option>
                                                        <option value="saloon">{{ __('frontend.motor.add_ons_page.body_type_modal.saloon') }}</option>
                                                        <option value="non-saloon">{{ __('frontend.motor.add_ons_page.body_type_modal.non_saloon') }}</option>
                                                    </select>
                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="{!! __('frontend.motor.add_ons_page.tooltip.roadtax') !!}">
                                                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="roadtax-price-display" class="col-2 text-end t-end">{{ number_format(session('motor')->roadtax->roadtax_price ?? 0, 2) }}</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-8">{{ __('frontend.motor.add_ons_page.myeg_fee') }}</div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="myeg-fee-display" class="col-2 text-end">{{ number_format(session('motor')->roadtax->myeg_fee ?? 0, 2) }}</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-8">{{ __('frontend.motor.add_ons_page.eservice_fee') }}</div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="eservice-fee-display" class="col-2 text-end">{{ number_format(session('motor')->roadtax->eservice_fee ?? 0, 2) }}</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-8">{{ __('frontend.motor.add_ons_page.service_tax') }}</div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="service-tax-display" class="col-2 text-end">{{ number_format(session('motor')->roadtax->sst ?? 0, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="hidden">
                                <input type="hidden" id="motor" name="motor" value='@json(session('motor'))' />
                                <input type="hidden" id="selected-extra-coverage" name="selected_extra_coverage" />
                                <input type="hidden" id="h-additional-drivers" name="additional_drivers" />
                                <input type="hidden" id="h-roadtax" name="roadtax" />
                            </div>
                        </form>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" id="btn-back" class="btn btn-link text-dark fw-bold">{{ __('frontend.button.back') }}</button>
                        <button type="button" id="btn-next" class="btn btn-primary text-white rounded">{{ __('frontend.button.next') }}</button>
                    </div>
                </div>
            </div>
            <x-modal maxWidth="md" id="body-type-modal" headerClass="bg-primary text-white" backdrop-static>
                <x-slot name="title">{{ __('frontend.motor.add_ons_page.body_type_modal.header') }}</x-slot>
                <x-slot name="body">
                    <div class="form-check border-bottom py-3">
                        <div class="row">
                            <div class="col-12">
                                <input type="radio" id="saloon" class="form-check-input" name="modal_body_type" value="saloon" />
                                <label for="saloon" class="form-check-label">{{ __('frontend.motor.add_ons_page.body_type_modal.saloon') }}</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <small>{{ __('frontend.motor.add_ons_page.body_type_modal.saloon_description') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-check border-bottom py-3">
                        <div class="row">
                            <div class="col-12">
                                <input type="radio" id="non-saloon" class="form-check-input" name="modal_body_type" value="non-saloon" />
                                <label for="non-saloon" class="form-check-label">{{ __('frontend.motor.add_ons_page.body_type_modal.non_saloon') }}</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <small>{{ __('frontend.motor.add_ons_page.body_type_modal.non_saloon_description') }}</small>
                            </div>
                        </div>
                    </div>
                    <div id="assistance">
                        <p class="mb-0">{{ __('frontend.motor.add_ons_page.body_type_modal.need_assisstance') }}</p>
                        <span>
                            <p class="mb-0">{{ __('frontend.motor.add_ons_page.body_type_modal.contact_us') }}</p>
                            <a href="{{ 'mailto:' . config('setting.customer_service.email') }}">{{ config('setting.customer_service.email') }}</a>
                        </span>
                    </div>
                </x-slot>
            </x-modal>
        </x-slot>
    </x-motor-layout>
@endsection

@push('after-scripts')
<script>
    let motor = JSON.parse($('#motor').val());
    var request = 0;

    $(() => {
        // Send Land on Add Ons Page to GA
        gtag('event', 'l_motor_ao', { 'debug_mode': true });

        populateDrivers();

        $('#show-more-add-ons').on('click', (e) => {
            let shown = $(e.target).data('shown');
            
            if(!shown) {
                $(e.target).data('shown', true);
                $(e.target).text("{{ __('frontend.button.show_less') }}");

                let new_select_fields = [];
                var additional_add_ons = JSON.parse(JSON.stringify(motor.extra_cover_list));
                additional_add_ons.splice(0, 5);

                additional_add_ons.forEach((extra) => {
                    let html = `
                        <div class="mb-2 extra-coverage">
                            <div class="row px-md-3">
                                <div class="col-1">
                                    <input type="checkbox" id="${'checkbox-' + extra.extra_cover_code}" class="form-check-input extra-coverage-checkbox" name="extra_coverage[]" value="${extra.extra_cover_code}" ${extra.selected ? 'checked' : ''} />
                                </div>
                                <div class="col-8 d-flex justify-content-between">
                                    <label for="${'checkbox-' + extra.extra_cover_code}">${extra.extra_cover_description}</label>
                                    
                                    ${getTootip(extra.extra_cover_description)}
                                </div>
                                <div class="col-1 text-end">RM</div>
                                <div id="${extra.extra_cover_code + '-premium'}" class="col-2 text-end premium">${formatMoney(extra.premium)}</div>
                            </div>`;
                        
                    if(extra.option_list) {
                        new_select_fields.push('sum-insured-' + extra.extra_cover_code);

                        html += `
                            <div class="row">
                                <div class="col-5 px-md-3 mb-3 ms-3">
                                    <small>${extra.option_list.description + ':'}</small>
                                    <select id="${'sum-insured-' + extra.extra_cover_code}" class="option-list" data-select data-extra-cover-code="${extra.extra_cover_code}">
                        `;

                        Object.values(extra.option_list.values).forEach((option) => {
                            if (typeof option === 'string'){
                                html += `<option value="${option}" ${option === 0 ? 'selected' : ''}>${option}</option>`;
                            }
                            else{
                                html += `<option value="${option}" ${option === 1000 ? 'selected' : ''}>${'RM ' + option}</option>`;
                            }
                        });

                        html += `</select></div></div></div>`;
                    } else if(extra.cart_list) {
                        new_select_fields.push('cart-day-' + extra.extra_cover_code);
                        new_select_fields.push('cart-amount-' + extra.extra_cover_code);

                        html += `
                            <div class="row">
                                <div class="col-5 px-md-3 mb-3 ms-3">
                                    <small>{{ __('frontend.motor.add_ons_page.days') . ':' }}</small>
                                    <select id="${'cart-day-' +  extra.extra_cover_code}" class="cart-day" data-select data-extra-cover-code="${extra.extra_cover_code}">
                        `;
                         
                        extra.cart_list.forEach((cart) => {
                            html += `<option value="${cart.cart_day}" ${cart.cart_day === 7 ? 'selected' : ''}>${cart.cart_day}</option>`;
                        });

                        html += `</select></div>
                            <div class="col-5 px-md-3 mb-3 ms-3">
                                <small>{{ __('frontend.motor.add_ons_page.amount') . ':' }}</small>
                                <select id="${'cart-amount-' +  extra.extra_cover_code}" class="cart-amount" data-select data-extra-cover-code="${extra.extra_cover_code}">
                        `;

                        extra.cart_list[0].cart_amount_list.forEach((amount) => {
                            html += `<option value="${amount}" ${amount === 100 ? 'selected' : ''}>${'RM ' + amount}</option>`;
                        });

                        html += `</select></div></div>`;
                    } else {
                        html += '</div>';
                    }

                    $('#add-on-item #show-more-wrapper').append(html);
                });

                // Initialize Dynamic Elements
                new_select_fields.forEach((field_id) => {
                    $('#' + $.escapeSelector(field_id)).select2({
                        width: '100%',
                        theme: 'bootstrap-5'
                    }).on('select2:select', function () {
                        $(this).parsley().validate();
                    });
                });

                $('[data-bs-toggle=tooltip]').each((index, element) => {
                    new bootstrap.Tooltip(element);
                });
            } else {
                $(e.target).data('shown', false);
                $('#add-on-item #show-more-wrapper').empty();
            }
        });

        $('#btn-delete').on('click', () => {
            $(this).closest('info').remove();
        });

        $('#sum-insured-slider').on('change', (e) => {
            motor.vehicle.sum_insured = parseFloat($(e.target).val());
            $('#motor').val(JSON.stringify(motor));

            // Set Loading Effect
            if(!$('#pricing-table #basic-premium').hasClass('loadingButton')) {
                $('#pricing-table #basic-premium').text(' ').toggleClass('loadingButton');
                $('#pricing-table #gross-premium').text(' ').toggleClass('loadingButton');
                $('#pricing-table #sst').text(' ').toggleClass('loadingButton');
                $('#pricing-table #total-payable').text(' ').toggleClass('loadingButton');
            }

            refreshPremium();
        });

        $('#sum-insured-slider').on('input', (e) => {
            // Update Tooltip Position
            let percentage = (parseFloat($(e.target).val()) - parseFloat($(e.target).attr('min'))) / (parseFloat($(e.target).attr('max')) - parseFloat($(e.target).attr('min')));
            let correct = Math.round(((percentage - 0.5) * 25 * -1));
            
            $('.tooltip .tooltip-inner').text('RM ' + formatMoney($(e.target).val(), 0).replace('.00', ''));
            $('.tooltip').css('left', Math.ceil((percentage * $(e.target).width()) - ($(e.target).width() / 2) + correct));
        });

        $('#sum-insured-tooltip').on('shown.bs.tooltip', (e) => {
            // Update Tooltip Position
            let percentage = (parseFloat($('#sum-insured-slider').val()) - parseFloat($('#sum-insured-slider').attr('min'))) / (parseFloat($('#sum-insured-slider').attr('max')) - parseFloat($('#sum-insured-slider').attr('min')));
            let correct = Math.round(((percentage - 0.5) * 25 * -1));
            
            $('.tooltip .tooltip-inner').text('RM ' + formatMoney($('#sum-insured-slider').val(), 0).replace('.00', ''));
            $('.tooltip').css('left', Math.ceil((percentage * $('#sum-insured-slider').width()) - ($('#sum-insured-slider').width() / 2) + correct));
        });

        $('#add-additional-driver').on('click', () => {
            let count = $('.additional-driver-name').length + 1;
            let html = `
                <div class="row info px-md-3 driver-${count}">
                    <div class="col-4">
                        <label for="driver-name-${count}" class="form-label">{{ __('frontend.fields.name') }}</label>
                        <input type="text" id="driver-name-${count}" class="form-control text-uppercase additional-driver-name" />
                    </div>
                    <div class="col-4">
                        <label for="driver-id-number-${count}" class="form-label">{{ __('frontend.fields.id_number') }}</label>
                        <input type="text" id="driver-id-number-${count}" class="form-control additional-driver-id-number" />
                    </div>
                    <div class="col-3">
                        <label for="driver-relationship-${count}" class="form-label">{{ __('frontend.fields.relationship') }}</label>
                        <select id="driver-relationship-${count}" class="form-control additional-driver-relationship" data-select>
                            <option value=""></option>
                            @foreach ($relationships as $relationship)
                                <option value="{{ $relationship->id }}">{{ __("frontend.relationships.{$relationship->name}") }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-1 align-self-end">
                        <button type="button" class="btn btn-danger text-white btn-delete-driver" data-id="${count}">
                            <i class="fa-solid fa-trash" data-id="${count}"></i>
                        </button>
                    </div>
                </div>`;

            $(html).insertAfter($('.info').last());

            $('.additional-driver-relationship').select2({
                width: '100%',
                theme: 'bootstrap-5'
            }).on('select2:select', function () {
                $(this).parsley().validate();
            });
        });

        $('#btn-continue-modal').on('click', () => {
            $('#body-type').val($('input[name=modal_body_type]:checked').val());

            $('#roadtax-price-display').text(' ').toggleClass('loadingButton');
            $('#myeg-fee-display').text(' ').toggleClass('loadingButton');
            $('#eservice-fee-display').text(' ').toggleClass('loadingButton');
            $('#delivery-fee-display').text(' ').toggleClass('loadingButton');
            $('#service-tax-display').text(' ').toggleClass('loadingButton');

            $('#body-type-modal').modal('hide');

            calculateRoadtax();
        });

        $('#extra-coverages').on('change', '.extra-coverage-checkbox', (e) => {
            // Send Selected Add Ons Event to GA
            gtag('event', 's_ao_add', { 'debug_mode': true });

            if(!$(e.target).parent().parent().find('.premium').hasClass('loadingButton')) {
                $(e.target).parent().parent().find('.premium').text(' ').toggleClass('loadingButton');
            }
            
            if(!$('#pricing-table #add-ons-premium').hasClass('loadingButton')) {
                $('#pricing-table #add-ons-premium').text(' ').toggleClass('loadingButton');
                $('#pricing-table #gross-premium').text(' ').toggleClass('loadingButton');
                $('#pricing-table #sst').text(' ').toggleClass('loadingButton');
                $('#pricing-table #total-payable').text(' ').toggleClass('loadingButton');
                $('#btn-next').toggleClass('loadingButton');
            }

            if($(`#label-${$(e.target).attr('id')}`).text().includes('Drivers')) {
                if($(e.target).is(':checked')) {
                    $('#additional-driver').slideUp('slow');
                } else {
                    $('#additional-driver').slideDown('slow');
                }
            }

            refreshPremium();
        });

        $('#roadtax-checkbox').on('change', (e) => {
            if($(e.target).is(':checked')) {
                // Send Selected Roadtax Event to GA
                gtag('event', 's_ao_rdt', { 'debug_mode': true });

                $('#body-type-modal').modal('show');
            } else {
                // Send de-select Roadtax Event to GA
                gtag('event', 's_ao_rdt_n', { 'debug_mode': true });
                
                motor.premium.total_payable -= motor.premium.roadtax - parseFloat(motor.premium.discounted_amount);
                delete motor.premium.roadtax;
                delete motor.roadtax;
                $('#motor').val(JSON.stringify(motor));

                $('#roadtax-price-display').removeClass('loadingButton').text('0.00');
                $('#myeg-fee-display').removeClass('loadingButton').text('0.00');
                $('#eservice-fee-display').removeClass('loadingButton').text('0.00');
                $('#delivery-fee-display').removeClass('loadingButton').text('0.00');
                $('#service-tax-display').removeClass('loadingButton').text('0.00');

                $('#road-tax').text('0.00').removeClass('loadingButton');
                $('#promo-amount').text('0.00').parents('tr').addClass('d-none');
                $('#promo-code').val('');
                $('#total-payable').text(formatMoney(motor.premium.total_payable)).removeClass('loadingButton');
            }
        });

        $('#btn-back').on('click', () => {
            window.history.back();
        });

        $('#btn-next').on('click', () => {
            let selected_extra_cover = []

            // Consolidate Add Ons
            $('.extra-coverage-checkbox:checked').each((index, element) => {
                let sum_insured = parseFloat($(`#sum-insured-${$.escapeSelector($(element).val())}`).val());

                if(isNaN($(`#sum-insured-${$.escapeSelector($(element).val())}`).val())) {
                    sum_insured = parseFloat($('#sum-insured-slider').val());
                }

                if($(element).val() != 112) { // CART
                    selected_extra_cover.push({
                        extra_cover_code: $(element).val(),
                        sum_insured: sum_insured,
                        premium: $(`#${$.escapeSelector($(element).val())}-premium`).text()
                    });
                } else {
                    selected_extra_cover.push({
                        extra_cover_code: $(element).val(),
                        cart_amount: $(`#cart-amount-${$.escapeSelector($(element).val())}`).val(),
                        cart_day: $(`#cart-day-${$.escapeSelector($(element).val())}`).val(),
                        premium: $(`#${$.escapeSelector($(element).val())}-premium`).text()
                    });
                }
            });

            $('#selected-extra-coverage').val(JSON.stringify(selected_extra_cover));

            // Consolidate Additional Drivers
            let additional_driver = [];
            $('.additional-driver-name').each((index, element) => {
                if($(`#driver-name-${index + 1}`).val() != '') {
                    additional_driver.push({
                        name: $(`#driver-name-${index + 1}`).val(),
                        id_number: $(`#driver-id-number-${index + 1}`).val(),
                        relationship: $(`#driver-relationship-${index + 1}`).val()
                    });
                }
            });

            $('#h-additional-drivers').val(JSON.stringify(additional_driver));

            if(!$('#roadtax-checkbox').is(':checked')) {
                swalAlert("{{ __('frontend.modal.forget_road_tax') }}", (result) => {
                    if(result.isConfirmed) {
                        $('#roadtax-checkbox').attr('checked', true).trigger('change');
                    } else {
                        $('#add-ons-form').submit();
                    }
                }, true, 'warning', "{{ __('frontend.button.yes_i_want') }}");
            } else {
                $('#add-ons-form').submit();
            }
        });

        $('#extra-coverages').on('change', '.option-list, .cart-amount', (e) => {
            $(`#checkbox-${$.escapeSelector($(e.target).data('extra-cover-code'))}`).attr('checked', true).trigger('change');
        });

        $('#extra-coverages').on('change', '.cart-day', (e) => {
            $('.cart-amount').empty();

            motor.extra_cover_list.forEach((add_ons) => {
                if(add_ons.extra_cover_code == $(e.target).data('extra-cover-code')) {
                    add_ons.cart_list.forEach((cart) => {
                        if(cart.cart_day == $(e.target).val()) {
                            cart.cart_amount_list.forEach((amount, index) => {
                                $('.cart-amount').append(`<option value="${amount}">${'RM ' + amount}</option>`);

                                if(index == 0) {
                                    $('.cart-amount').val(amount);
                                }
                            });
                        }
                    });
                }
            });

            $('.cart-amount').trigger('change');
        });

        $('#body-type-wrapper').on('click', () => {
            $('#body-type-modal').modal('show');
        });

        $('.card-body').on('change', '.additional-driver-relationship', (e) => {
            refreshPremium(); 
        });

        $('.card-body').on('click', '.btn-delete-driver', (e) => {
            if($(e.target).data('id') != 0) {
                $(`.row.driver-${$(e.target).data('id')}`).remove();
            } else {
                $(`#driver-name-${$(e.target).data('id')}`).val('');
                $(`#driver-id-number-${$(e.target).data('id')}`).val('');
                $(`#driver-relationship-${$(e.target).data('id')}`).val('');
            }

            // Remove in Additional Driver List
            refreshPremium();
        });
    });

    function refreshPremium()
    {
        let selected_extra_cover = [];
        $('.extra-coverage-checkbox:checked').each((index, element) => {
            selected_extra_cover.push(motor.extra_cover_list.find((item) => {
                return item.extra_cover_code === $(element).val();
            }));
        });

        // Disable all checkboxes
        $('.extra-coverage-checkbox').each((index, element) => {
            $(element).attr('disabled', true);
        });

        selected_extra_cover.forEach((extra_cover) => {
            if(extra_cover.option_list) {
                if(extra_cover.option_list.description == 'Option List'){
                    extra_cover.plan_type = $(`#sum-insured-${$.escapeSelector(extra_cover.extra_cover_code)}`).val();
                }
                else{
                    extra_cover.sum_insured = parseFloat($(`#sum-insured-${$.escapeSelector(extra_cover.extra_cover_code)}`).val());
                }
            } else {
                extra_cover.sum_insured = parseFloat($('#sum-insured-slider').val());
            }

            if(extra_cover.cart_list) {
                extra_cover.cart_day = $(`#cart-day-${$.escapeSelector(extra_cover.extra_cover_code)}`).val();
                extra_cover.cart_amount = $(`#cart-amount-${$.escapeSelector(extra_cover.extra_cover_code)}`).val();
            }
        });

        let additional_driver = [];
        $('.additional-driver-name').each((index, element) => {
            additional_driver.push({
                name: $(element).val().toUpperCase(),
                id_number: $($('.additional-driver-id-number')[index]).val(),
                relationship: $($('.additional-driver-relationship')[index]).val()
            });
        });

        instapol.post("{{ route('motor.api.quote') }}", {
            product_id: motor.product_id,
            motor: motor,
            extra_cover: selected_extra_cover,
            additional_driver: additional_driver
        }).then((res) => {
            request--;

            if(res.data) {
                console.log('refreshPremium', res);
    
                motor.premium.total_benefit_amount = res.data.total_benefit_amount;
                motor.premium.basic_premium = res.data.basic_premium;
                motor.premium.gross_premium = res.data.gross_premium;
                motor.premium.ncd_amount = res.data.ncd_amount;
                motor.premium.sst_amount = res.data.sst_amount;

                if(motor.premium.discounted_amount) {
                    motor.premium.total_payable = res.data.total_payable + parseFloat($('#road-tax').text()) - motor.premium.discounted_amount;
                } else {
                    motor.premium.total_payable = res.data.total_payable + parseFloat($('#road-tax').text());
                }

                $('#motor').val(JSON.stringify(motor));
    
                // Update Pricing Card
                $('#basic-premium').text(formatMoney(motor.premium.basic_premium));
                $('#ncd').text(formatMoney(motor.premium.ncd_amount));
                $('#add-ons-premium').text(formatMoney(motor.premium.total_benefit_amount));
                $('#gross-premium').text(formatMoney(motor.premium.gross_premium));
                $('#sst').text(formatMoney(motor.premium.sst_amount));
                $('#total-payable').text(formatMoney(motor.premium.total_payable));
    
                // Update Add Ons Pricing
                motor.extra_cover_list.forEach((extra_cover) => {
                    $(`#${$.escapeSelector(extra_cover.extra_cover_code)}-premium`).text(formatMoney(extra_cover.premium)).removeClass('loadingButton');
                });

                if(res.data.extra_cover.length > 0) {
                    res.data.extra_cover.forEach((extra_cover) => {
                        $(`#${$.escapeSelector(extra_cover.extra_cover_code)}-premium`).text(formatMoney(extra_cover.premium)).removeClass('loadingButton');
                    });
                }
    
                // Remove Loading for Next Button
                $('#btn-next').removeClass('loadingButton');
    
                // Remove Loading in Pricing Card
                $('#pricing-table #basic-premium').removeClass('loadingButton');
                $('#pricing-table #add-ons-premium').removeClass('loadingButton')
                $('#pricing-table #gross-premium').removeClass('loadingButton');
                $('#pricing-table #sst').removeClass('loadingButton');
                $('#pricing-table #total-payable').removeClass('loadingButton');

                // Enable all checkboxes
                $('.extra-coverage-checkbox').each((index, element) => {
                    $(element).removeAttr('disabled');
                });
            }
        }).catch((err) => {
            console.log(err.response);
            swalAlert(err.response.data.message, () => {
                window.history.back();
            });
        });
    }

    function calculateRoadtax()
    {
        instapol.post("{{ route('motor.api.calculate-roadtax') }}", {
            engine_capacity: motor.vehicle.engine_capacity,
            id_type: motor.policy_holder.id_type,
            postcode: motor.postcode,
            body_type: $('#body-type').val(),
        }).then((res) => {
            console.log('RoadTax', res);

            $('#h-roadtax').val(JSON.stringify(res.data));

            // Update Pricing Display
            $('#roadtax-price-display').removeClass('loadingButton').text(formatMoney(res.data.roadtax_price));
            $('#myeg-fee-display').removeClass('loadingButton').text(formatMoney(res.data.myeg_fee));
            $('#eservice-fee-display').removeClass('loadingButton').text(formatMoney(res.data.eservice_fee));
            $('#delivery-fee-display').removeClass('loadingButton').text(formatMoney(res.data.delivery_fee));
            $('#service-tax-display').removeClass('loadingButton').text(formatMoney(res.data.sst));

            // Update Pricing Card
            $('#road-tax').text(formatMoney(res.data.total));
            motor.premium.total_payable += parseFloat(res.data.total);
            motor.premium.roadtax = res.data.total;
            motor.roadtax = res.data;
            $('#motor').val(JSON.stringify(motor));
            $('#total-payable').text(formatMoney(motor.premium.total_payable));

            // Auto Apply Promo Code
            instapol.post("{{ route('motor.api.use-promo') }}", {
                motor: motor,
                isAutoRoadTax: true
            }).then((res) => {
                console.log('Auto Apply Promo', res);
                // Send Use Promo Event to GA
                gtag('event', 'motor_use_promo', { 'debug_mode': true });

                if(res.data !== '') {
                    $('#motor').val(JSON.stringify(res.data));
        
                    // Update Pricing Card
                    $('#road-tax').text(formatMoney(res.data.roadtax.total)).removeClass('loadingButton');
                    $('#total-payable').text(formatMoney(res.data.premium.total_payable)).removeClass('loadingButton');
                    $('#promo-amount').text(formatMoney(res.data.premium.discounted_amount || 0.00));
        
                    if(parseFloat($('#promo-amount').text()) > 0) {
                        $('#discount').removeClass('d-none');
                    }
        
                    $('#promo-code').val(res.data.promo.code);
                    motor.premium.discounted_amount = res.data.premium.discounted_amount;
                    motor.premium.total_payable = res.data.premium.total_payable;
                    $('#motor').val(JSON.stringify(motor));
                }
            }).catch((err) => {
                console.log(err);
            });
        }).catch((err) => {
            console.log(err.response);
        });
    }

    function getTootip(description)
    {
        if(description.includes('Windscreen')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.windscreen') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>`;
        } else if (description.includes('Legal Liability Of')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.llop') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('Legal Liability to')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.lltp') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('Strike, Riot')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.srcc') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('Accessories')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.accessories') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('Personal Accident')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.pa') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('NCD')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.ncd') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('NGV') || description.includes('Gas')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.ngv') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('CART')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.cart') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('Special Perils')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.special_perils') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        } else if (description.includes('Thailand')) {
            return `
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.thailand') }}">
                    <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                </span>
            `;
        }

        return '';
    }

    function populateDrivers()
    {
        if(motor.additional_drivers) {
            motor.additional_drivers.forEach((driver, index) => {
                $('#add-additional-driver').trigger('click');
                $(`#driver-name-${index + 1}`).val(driver.name);
                $(`#driver-id-number-${index + 1}`).val(driver.id_number);
                $(`#driver-relationship-${index + 1}`).val(driver.relationship);
            });
        }
    }
</script>
@endpush