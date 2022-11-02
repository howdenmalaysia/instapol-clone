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
                    />
                </div>
                <div class="col-12 col-lg-8">
                    <div class="card border">
                        <form action="{{ route('motor.add-ons') }}" method="POST" id="add-ons-form">
                            @csrf
                            <div class="card-body">
                                <h3 class="card-title fw-bold border-bottom pb-4 px-md-3 mt-3">{{ __('frontend.motor.add_ons_page.sum_insured_amount') }}</h3>
                                <div class="py-4 px-md-3" data-bs-toggle="tooltip" data-bs-placement="top" title="RM 69,000">
                                    <h5 class="card-text">{{ __('frontend.motor.add_ons_page.sum_insured') }}</h5>
                                    <input type="range" id="sum-insured-slider" class="form-range" min="69000" max="75000" step="1000">
                                </div>
                                <div id="extra-coverages">
                                    <h3 class="card-title fw-bold border-bottom py-4 px-md-3">{{ __('frontend.motor.add_ons_page.additional_coverage') }}</h3>
                                    @foreach (array_chunk(session('motor')->extra_cover_list, 5)[0] as $_extra_cover)
                                        <div class="mb-2 extra-coverage">
                                            <div class="row px-md-3">
                                                <div class="col-1">
                                                    <input type="checkbox" id="{{ 'checkbox-' . $_extra_cover->extra_cover_code }}" class="form-check-input extra-coverage-checkbox" name="extra_coverage[]" value="{{ $_extra_cover->extra_cover_code }}" {{ $_extra_cover->selected ? 'checked' : '' }} />
                                                </div>
                                                <div class="col-8 d-flex justify-content-between">
                                                    <label for="{{ '#checkbox-' . $_extra_cover->extra_cover_code }}">{{ $_extra_cover->extra_cover_description }}</label>
                                                    
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
                                                        <small>{{ $_extra_cover->option_list->description . ':' }}</small>
                                                        <select id="{{ 'sum-insured-' . $_extra_cover->extra_cover_code }}" class="option-list" data-select data-extra-cover-code="{{ $_extra_cover->extra_cover_code }}">
                                                            @foreach ($_extra_cover->option_list->values as $option)
                                                                <option value="{{ $option }}">{{ 'RM ' . $option }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if (count(session('motor')->extra_cover_list) > 5)
                                        <div class="mt-4">
                                            <button type="button" id="show-more-add-ons" class="btn btn-light float-end rounded">{{ __('frontend.button.show_more') }}</button>
                                        </div>
                                    @endif
                                </div>
                                @if (session('motor')->named_drivers_needed)
                                    <div id="additional-driver" class="mt-3">
                                        <h3 class="card-title fw-bold border-bottom py-4 px-md-3">{{ __('frontend.motor.add_ons_page.additional_driver') }}</h3>
                                        <div class="alert alert-success mx-md-3" role="alert">
                                            {{ __('frontend.motor.add_ons_page.additional_driver_note') }}
                                        </div>
                                        <div class="row info px-md-3">
                                            <div class="col-4">
                                                <label for="additional-driver-name" class="form-label uppercase">{{ __('frontend.fields.name') }}</label>
                                                <input type="text" id="additional-driver-name" class="form-control" />
                                            </div>
                                            <div class="col-4">
                                                <label for="additional-driver-id-number" class="form-label">{{ __('frontend.fields.id_number') }}</label>
                                                <input type="text" id="additional-driver-id-number" class="form-control" />
                                            </div>
                                            <div class="col-3">
                                                <label for="additional-driver-relationship" class="form-label">{{ __('frontend.fields.relationship') }}</label>
                                                <select id="additional-driver-relationship" class="form-control" data-select>
                                                    <option value=""></option>
                                                    @foreach ($relationships as $relationship)
                                                        <option value="{{ $relationship->id }}">{{ __("frontend.relationships.{$relationship->name}") }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-1 align-self-end">
                                                <button type="button" id="btn-delete" class="btn btn-danger text-white">
                                                    <i class="fa-solid fa-trash"></i>
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
                                            <input type="checkbox" id="roadtax-checkbox" class="form-check-input" name="roadtax" />
                                        </div>
                                        <div class="col-8">
                                            <div class="row align-items-center">
                                                <div class="col-3">{{ __('frontend.motor.add_ons_page.road_tax_fee') }}</div>
                                                <div class="col-9 d-flex justify-content-between align-items-center">
                                                    <select name="body_type" id="body-type" class="form-control w-75" disabled>
                                                        <option value="">{{ __('frontend.motor.add_ons_page.body_type') }}</option>
                                                        <option value="saloon">{{ __('frontend.motor.add_ons_page.body_type_modal.saloon') }}</option>
                                                        <option value="non-saloon">{{ __('frontend.motor.add_ons_page.body_type_modal.non_saloon') }}</option>
                                                    </select>
                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.windscreen') }}">
                                                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="roadtax-price-display" class="col-2 text-end t-end">0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-8">{{ __('frontend.motor.add_ons_page.myeg_fee') }}</div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="myeg-fee-display" class="col-2 text-end">0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-8">{{ __('frontend.motor.add_ons_page.eservice_fee') }}</div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="eservice-fee-display" class="col-2 text-end">0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-8">{{ __('frontend.motor.add_ons_page.delivery_fee') }}</div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="delivery-fee-display" class="col-2 text-end">0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-8">{{ __('frontend.motor.add_ons_page.service_tax') }}</div>
                                        <div class="col-1 text-end">RM</div>
                                        <div id="service-tax-display" class="col-2 text-end">0.00</div>
                                    </div>
                                    <div class="alert alert-success mt-4" role="alert">
                                        {{ __('frontend.motor.add_ons_page.mco_note') }}
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
            <x-modal maxWidth="md" id="body-type-modal" headerClass="bg-primary text-white">
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
                            <a href="mailto:instapol@my.howdengroup.com"> {{ config('setting.customer_service.email') }}</a>
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

    $(() => {
        $('#show-more-add-ons').on('click', () => {
            $(this).text("{{ __('frontend.button.show_less') }}");
        });

        $('#btn-delete').on('click', () => {
            $(this).closest('info').remove();
        });

        $('#add-additional-driver').on('click', () => {
            let html = `
                <div class="row info px-md-3">
                    <div class="col-4">
                        <label for="additional-driver-name" class="form-label">{{ __('frontend.fields.name') }}</label>
                        <input type="text" id="additional-driver-name" class="form-control" />
                    </div>
                    <div class="col-4">
                        <label for="additional-driver-id-number" class="form-label">{{ __('frontend.fields.id_number') }}</label>
                        <input type="text" id="additional-driver-id-number" class="form-control" />
                    </div>
                    <div class="col-3">
                        <label for="additional-driver-relationship" class="form-label">{{ __('frontend.fields.relationship') }}</label>
                        <select id="additional-driver-relationship" class="form-control" data-select>
                            <option value=""></option>
                            @foreach ($relationships as $relationship)
                                <option value="{{ $relationship->id }}">{{ __("frontend.relationships.{$relationship->name}") }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-1 align-self-end">
                        <button type="button" id="btn-delete" class="btn btn-danger text-white">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>`;

            $(html).insertAfter($('.info').last());
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

        $('.extra-coverage-checkbox').on('change', (e) => {
            if($(e.target).is(':checked')) {
                $(e.target).parent().parent().find('.premium').text(' ').toggleClass('loadingButton');
                $('#btn-next').toggleClass('loadingButton');
                refreshPremium();
            }
        });

        $('#roadtax-checkbox').on('change', (e) => {
            if($(e.target).is(':checked')) {
                $('#body-type-modal').modal('show');
            }
        });

        $('#btn-back').on('click', () => {
            window.history.back();
        });

        $('#btn-next').on('click', () => {
            let selected_extra_cover = []

            // Consolidate Add Ons
            $('.extra-coverage-checkbox:checked').each((index, element) => {
                selected_extra_cover.push({
                    extra_cover_code: $(element).val(),
                    sum_insured: $(`#sum-insured-${$(element).val()}`).val()
                })
            });

            $('#selected-extra-coverage').val(JSON.stringify(selected_extra_cover));

            // Consolidate Additional Drivers
            let additional_driver = [];
            $('.info').each((index, element) => {
                additional_driver.push({
                    driver_name: $(element).find('#additional-driver-name').val(),
                    driver_id_number: $(element).find('#additional-driver-id-number').val(),
                    driver_relationship: $(element).find('#additional-driver-relationship').val()
                })
            });

            $('#h-additional-driver').val(JSON.stringify(additional_driver));

            if(!$('#roadtax-checkbox').is(':checked')) {
                swalAlert("{{ __('frontend.modal.forget_road_tax') }}", (result) => {
                    if(result.isConfirmed) {
                        $('#roadtax-checkbox').attr('checked', true);
                    } else {
                        $('#add-ons-form').submit();
                    }
                }, true, 'warning', "{{ __('frontend.button.yes_i_want') }}");
            } else {
                $('#add-ons-form').submit();
            }
        });

        $('.option-list').on('change', (e) => {
            if(!$(`#checkbox-${$(e.target).data('extra-cover-code')}`).is(':checked')) {
                $(`#checkbox-${$(e.target).data('extra-cover-code')}`).attr('checked', true).trigger('change');
            }
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

        selected_extra_cover.forEach((extra_cover) => {
            if(extra_cover.option_list) {
                extra_cover.sum_insured = $(`#sum-insured-${extra_cover.extra_cover_code}`).val();
            }
        });

        instapol.post("{{ route('motor.api.quote') }}", {
            product_id: motor.product_id,
            motor: motor,
            extra_cover: selected_extra_cover,
            roadtax: $('#roadtax-checkbox').is(':checked')
        }).then((res) => {
            console.log(res);

            // Update Pricing Card
            $('#add-ons-premium').text(`RM ${formatMoney(res.data.total_benefit_amount)}`);
            $('#gross-premium').text(`RM ${formatMoney(res.data.gross_premium)}`);
            $('#sst').text(`RM ${formatMoney(res.data.sst_amount)}`);
            $('#total-payable').text(`RM ${formatMoney(res.data.total_payable)}`);

            // Update Add Ons Pricing
            res.data.extra_cover.forEach((extra_cover) => {
                $(`#${extra_cover.extra_cover_code}-premium`).text(`${formatMoney(extra_cover.premium)}`).removeClass('loadingButton');
            });

            // Remove Loading for Next Button
            $('#btn-next').removeClass('loadingButton');
        }).catch((err) => {
            console.log(err.response);
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
            console.log(res);

            $('#h-roadtax').val(JSON.stringify(res.data));

            // Update Pricing Display
            $('#roadtax-price-display').removeClass('loadingButton').text(`RM ${formatMoney(res.data.roadtax_price)}`);
            $('#myeg-fee-display').removeClass('loadingButton').text(`RM ${formatMoney(res.data.myeg_fee)}`);
            $('#eservice-fee-display').removeClass('loadingButton').text(`RM ${formatMoney(res.data.eservice_fee)}`);
            $('#delivery-fee-display').removeClass('loadingButton').text(`RM ${formatMoney(res.data.delivery_fee)}`);
            $('#service-tax-display').removeClass('loadingButton').text(`RM ${formatMoney(res.data.sst)}`);

            // Update Pricing Card
            $('#road-tax').text(`RM ${formatMoney(res.data.total)}`);
            $('#total-payable').text(`RM ${formatMoney(parseFloat(res.data.total))}`);
        }).catch((err) => {
            console.log(err.response);
        });
    }
</script>
@endpush