@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.add_ons_page.add_ons')]))
    
@section('content')
    <x-motor-layout id="add-ons" current-step="3">
        <x-slot name="content">
            <div class="row">
                <div class="col-12 col-lg-4">
                    <x-pricing-card insurer-logo="{{ asset("images/insurer/{$product->insurance_company->logo}.png") }}" insurer-name="{{ $product->insurance_company->name }}" data={{ $premium }} />
                </div>
                <div class="col-12 col-lg-8">
                    <div class="card border">
                        <form action="{{ route('motor.add-ons') }}" method="POST" id="add-ons-form">
                            <div class="card-body">
                                <h3 class="card-title fw-bold border-bottom pb-4 px-md-3 mt-3">{{ __('frontend.motor.add_ons_page.sum_insured_amount') }}</h3>
                                <div class="py-4 px-md-3" data-bs-toggle="tooltip" data-bs-placement="top" title="RM 69,000">
                                    <h5 class="card-text">{{ __('frontend.motor.add_ons_page.sum_insured') }}</h5>
                                    <input type="range" id="sum-insured-slider" class="form-range" min="69000" max="75000" step="1000">
                                </div>
                                <div id="extra-coverages">
                                    <h3 class="card-title fw-bold border-bottom py-4 px-md-3">{{ __('frontend.motor.add_ons_page.additional_coverage') }}</h3>
                                    @foreach (array_chunk(session('motor')->extra_cover_list, 5)[0] as $_extra_cover)
                                        <div class="extra-coverage">
                                            <div class="row px-md-3">
                                                <div class="col-1">
                                                    <input type="checkbox" class="form-check-input extra-coverage-checkbox" name="extra_coverage[]" id="{{ $_extra_cover->extra_cover_code }}" {{ $_extra_cover->selected ? 'checked' : '' }} />
                                                </div>
                                                <div class="col-8">
                                                    <label for="{{ '#' . $_extra_cover->extra_cover_code }}">{{ $_extra_cover->extra_cover_description }}</label>
                                                </div>
                                                <div class="col-1">
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
                                                <div class="col-2 premium">
                                                    {{ 'RM ' . $_extra_cover->premium }}
                                                </div>
                                            </div>
                                            @if (!empty($extra_cover->option_list))
                                                <div class="row">
                                                    <div class="col-5 px-md-3 mt-3">
                                                        <small>{{ $extra_cover->option_list->description . ':' }}</small>
                                                        <select id="{{ 'sum-insured-' . $_extra_cover->extra_cover_code }}" data-select>
                                                            @foreach ($extra_cover->option_list->values as $option)
                                                                <option value="{{ $option }}">{{ 'RM ' . $option }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                    <div class="mt-4">
                                        <button type="button" id="show-more-add-ons" class="btn btn-light float-end rounded">{{ __('frontend.button.show_more') }}</button>
                                    </div>
                                </div>
                                @if (session('motor')->named_drivers_needed)
                                    <div id="additional-driver" class="mt-3">
                                        <h3 class="card-title fw-bold border-bottom py-4 px-md-3">{{ __('frontend.motor.add_ons_page.additional_driver') }}</h3>
                                        <div class="alert alert-success mx-md-3" role="alert">
                                            {{ __('frontend.motor.add_ons_page.additional_driver_note') }}
                                        </div>
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
                                            <input type="checkbox" id="roaxtax-checkbox" class="form-check-input" name="roadtax" />
                                        </div>
                                        <div class="col-8">
                                            <div class="row align-items-center">
                                                <div class="col-3">{{ __('frontend.motor.add_ons_page.road_tax_fee') }}</div>
                                                <div class="col-6">
                                                    <select name="body_type" id="body-type" class="form-control">
                                                        <option value="">{{ __('frontend.motor.add_ons_page.body_type') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-1">
                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.motor.add_ons_page.tooltip.windscreen') }}">
                                                <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                                            </span>
                                        </div>
                                        <div id="roadtax-price-display" class="col-2">RM 0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-9">{{ __('frontend.motor.add_ons_page.myeg_fee') }}</div>
                                        <div id="myeg-fee-display" class="col-2">RM 0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-9">{{ __('frontend.motor.add_ons_page.eservice_fee') }}</div>
                                        <div id="eservice-fee-display" class="col-2">RM 0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-9">{{ __('frontend.motor.add_ons_page.delivery_fee') }}</div>
                                        <div id="delivery-fee-display" class="col-2">RM 0.00</div>
                                    </div>
                                    <div class="row align-items-center px-md-3 mt-2">
                                        <div class="col-1"></div>
                                        <div class="col-9">{{ __('frontend.motor.add_ons_page.service_tax') }}</div>
                                        <div id="service-tax-display" class="col-2">RM 0.00</div>
                                    </div>
                                    <div class="alert alert-success mt-4" role="alert">
                                        {{ __('frontend.motor.add_ons_page.mco_note') }}
                                    </div>
                                </div>
                            </div>
                            <div class="hidden">
                                <input type="hidden" id="motor" name="motor" value="@json(session('motor'))">
                            </div>
                        </form>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn btn-link text-dark fw-bold">{{ __('frontend.button.back') }}</button>
                        <button type="button" class="btn btn-primary text-white rounded">{{ __('frontend.button.next') }}</button>
                    </div>
                </div>
            </div>
            <x-modal maxWidth="md" id="body-type-modal" headerClass="bg-primary text-white">
                <x-slot name="title">{{ __('frontend.motor.add_ons_page.body_type_modal.header') }}</x-slot>
                <x-slot name="body">
                    <div class="form-check">
                        <input type="radio" id="saloon" class="form-check-input" />
                        <label for="saloon" class="form-check-label">{{ __('frontend.motor.add_ons_page.body_type_modal.saloon') }}</label>
                        <small>{{ __('frontend.motor.add_ons_page.body_type_modal.saloon_description') }}</small>
                    </div>
                    <div class="form-check">
                        <input type="radio" id="non-saloon" class="form-check-input" />
                        <label for="non-saloon" class="form-check-label">{{ __('frontend.motor.add_ons_page.body_type_modal.non_saloon') }}</label>
                        <small>{{ __('frontend.motor.add_ons_page.body_type_modal.non_saloon_description') }}</small>
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

        $('#btn-delete').on('click'. () => {
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

        $('#roadtax-checkbox, .extra-coverage-checkbox').on('change', () => {
            if($(this).checked) {
                refreshPremium();
            } else {
                $('#roadtax-price-display').text('RM 0.00');
                $('#myeg-fee-display').text('RM 0.00');
                $('#eservice-fee-display').text('RM 0.00');
                $('#delivery-fee-display').text('RM 0.00');
                $('#service-tax-display').text('RM 0.00');
            }
        });
    });

    function refreshPremium()
    {
        instapol.post("{{ route('motor.api.quote') }}", {
            product_id: motor.product_id,
            motor: motor
            extra_cover: extra_cover,
            roadtax: $('#roadtax-checkbox').is(':checked')
        }).then((res) => {
            console.log(res);
        }).catch((err) => {
            console.log(err.response);
        });
    }
</script>
@endpush