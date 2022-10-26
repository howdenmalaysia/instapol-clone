@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __()]))
    
@section('content')
    <section class="pt-0">
        <div class="container-fluid header d-flex flex-column justify-content-center">
            <x-steps current-step="5" />
        </div>

        <section id="content" class="nm-15">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="card rounded text-center white-glow">
                            <div class="card-body">
                                <h2 class="card-title border-bottom border-5 pb-3">{{ __('frontend.motor.payment_summary_page.summary') }}</h2>
                                <div class="row">
                                    <div class="col-12 col-lg-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.payment_summary_page.policy_holder') }}</th>
                                                <td class="text-uppercase text-end">{{ $policy_holder->name }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.vehicle_details.car_number') }}</th>
                                                <td class="text-uppercase text-end">{{ $motor->vehicle_number }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.payment_summary_page.insurer') }}</th>
                                                <td class="text-uppercase text-end">{{ $product->insurance_company->name }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.payment_summary_page.product') }}</th>
                                                <td class="text-uppercase text-end">{{ $product->name }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.payment_summary_page.coverage_date') }}</th>
                                                <td class="text-uppercase text-end">{{ implode(' ', [$insurance->inception_date, __('frontend.general.to'), $insurance->expiry_date]) }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.payment_summary_page.sum_insured') }}</th>
                                                <td class="text-uppercase text-end">{{ 'RM ' . formatMoney($motor->market_value) }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.payment_summary_page.next_ncd') }}</th>
                                                <td class="text-uppercase text-end">{{ intval($motor->ncd_percentage) . '%' }} </td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('frontend.motor.add_ons_page.road_tax_renewal') }}</th>
                                                <td class="text-uppercase text-end">{{ !empty($motor->roadtax) ? __('frontend.general.yes') : __('frontend.general.no') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-primary text-white">
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-between">
                                        <h4 class="fw-bold">{{ __('frontend.motor.payment_summary_page.total_payable') }}</h4>
                                        <h4 class="fw-bold">{{ 'RM ' . $insurance->amount }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card rounded white-glow p-4">
                            <div class="row">
                                <div class="col-12 col-lg-4"></div>
                                <div class="col-12 col-lg-8">
                                    <div id="vehicle-details-card" class="card bg-light rounded">
                                        <div class="card-body">
                                            <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.vehicle_details.vehicle_details') }}</h4>
                                            <div class="row">
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.make') }}</td>
                                                            <td class="text-end">{{ $motor->make }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.model') }}</td>
                                                            <td class="text-end">{{ $motor->model }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.variant') }}</td>
                                                            <td class="text-end">{{ $motor->variant }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.chassis_number') }}</td>
                                                            <td class="text-end">{{ $motor->chassis_number }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.engine_number') }}</td>
                                                            <td class="text-end">{{ $motor->engine_number }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.engine_capacity') }}</td>
                                                            <td class="text-end">{{ $motor->engine_capacity }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.seating_capacity') }}</td>
                                                            <td class="text-end">{{ $motor->seating_capacity }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.year') }}</td>
                                                            <td class="text-end">{{ $motor->manufactured_year }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.nvic') }}</td>
                                                            <td class="text-end">{{ $motor->nvic }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="add-ons" class="card bg-light rounded mt-4">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-between">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.add_ons_page.add_ons') }}</h4>
                                                    <a href="{{ route('motor.add-ons') }}" class="btn btn-outline border text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            @if (!empty($extra_cover))
                                                @foreach ($extra_cover as $_extra_cover)
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="row">
                                                                <div class="col-12 col-lg-6">{{ $_extra_cover->description }}</div>
                                                                <div class="col-12 col-lg-6 text-end">{{ 'RM ' . formatMoney($_extra_cover->amount) }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12 col-lg-6">
                                                            <p class="mb-0">{{ __('frontend.motor.payment_summary_page.sum_insured' . ' amount :' . formatMoney($_extra_cover->sum_insured)) }}</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="row">
                                                    <div class="col-12 text-center">
                                                        <p class="mb-0">{{ __('frontend.motor.payment_summary_page.no_add_ons') }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div id="road-tax" class="card bg-light rounded mt-4">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-between">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.add_ons_page.road_tax_renewal') }}</h4>
                                                    <a href="{{ route('motor.add-ons') }}" class="btn btn-outline border text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            @if (!empty($motor->roadtax))
                                                <div class="row border-bottom pb-3">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6">{{ __('frontend.motor.add_ons_page.road_tax_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->roadtax_renewal_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6">{{ __('frontend.motor.add_ons_page.myeg_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->myeg_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6">{{ __('frontend.motor.add_ons_page.eservice_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->e_service_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6">{{ __('frontend.motor.add_ons_page.delivery_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->delivery_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6">{{ __('frontend.price_card.service_tax') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->tax }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <h5 class="fw-bold">{{ __('frontend.motor.payment_summary_page.recipient_info') }}</h5>
                                                    <div class="row">
                                                        <div class="col-6 col-lg-3">{{ __('frontend.fields.recipient_name') }}</div>
                                                        <div class="col-6 col-lg-3 text-end">{{ '' }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 col-lg-3">{{ __('frontend.fields.phone_number') }}</div>
                                                        <div class="col-6 col-lg-3 text-end">{{ '' }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 col-lg-3">{{ __('frontend.fields.address') }}</div>
                                                        <div class="col-6 col-lg-3 text-end">{{ '' }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="row">
                                                    <div class="col-12 text-center">
                                                        <p class="mb-0">{{ __('frontend.motor.payment_summary_page.no_roadtax') }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div id="additional-driver" class="card bg-light rounded mt-4">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-between">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.add_ons_page.additional_driver') }}</h4>
                                                    <a href="{{ route('motor.add-ons') }}" class="btn btn-outline border text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            @if (!empty($additional_driver))
                                                <div class="row">
                                                    <div class="col-4">{{ __('frontend.fields.name') }}</div>
                                                    <div class="col-4">{{ __('frontend.fields.id_number') }}</div>
                                                    <div class="col-4">{{ __('frontend.fields.relationship') }}</div>
                                                </div>
                                                @foreach ($additional_driver as $driver)
                                                    <div class="row">
                                                        <div class="col-4">{{ $driver->name }}</div>
                                                        <div class="col-4">{{ $driver->id_number }}</div>
                                                        <div class="col-4">{{ $driver->relationship }}</div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="row">
                                                    <div class="col-12 text-center">
                                                        <p class="mb-0">{{ __('frontend.motor.payment_summary_page.no_add_ons') }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div id="policy-holder-card" class="card bg-light rounded mt-4">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-between">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.payment_summary_page.policy_holder') }}</h4>
                                                    <a href="{{ route('motor.policy-holder') }}" class="btn btn-outline border text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td>{{ __('frontend.fields.name') }}</td>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.id_type') }}</td>
                                                            <td class="text-uppercase text-end">
                                                                {{ $policy_holder->id_type_id === config('setting.id_type.nric_no') ? __('frontend.motor.nric') : __('frontend.motor.company_resgistration') }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.id_number') }}</td>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->id_number }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.email') }}</td>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->email_address }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.address') }}</td>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->address }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td>{{ __('frontend.fields.dob') }}</td>
                                                            <td class="text-end">{{ $policy_holder->date_of_birth }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.gender') }}</td>
                                                            <td class="text-end">{{ $policy_holder->gender }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.phone_number') }}</td>
                                                            <td class="text-end">{{ '+60' . $policy_holder->phone_number }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection