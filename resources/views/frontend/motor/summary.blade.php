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
                        <div id="summary" class="card rounded text-center white-glow">
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
                                                <td class="text-uppercase text-end">{{ 'RM ' . number_format($motor->market_value, 2) }}</td>
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
                                <div class="col-12 col-lg-4">
                                    <x-pricing-card
                                        insurer-logo='{{ asset("images/insurer/{$product->insurance_company->logo}") }}'
                                        insurer-name="{!! $product->insurance_company->name !!}"
                                        basic-premium="{{ session('motor')->quotation->basic_premium }}"
                                        ncd-amount="{{ session('motor')->quotation->ncd_amount }}"
                                        total-benefit-amount="{{ session('motor')->quotation->total_benefit_amount }}"
                                        gross-premium="{{ session('motor')->quotation->gross_premium }}"
                                        sst-amount="{{ session('motor')->quotation->sst_amount }}"
                                        stamp-duty="{{ session('motor')->quotation->stamp_duty }}"
                                        total-payable="{{ session('motor')->quotation->total_payable }}"
                                    />
                                </div>
                                <div class="col-12 col-lg-8">
                                    <div id="vehicle-details-card" class="card bg-light rounded">
                                        <div class="card-body">
                                            <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.vehicle_details.vehicle_details') }}</h4>
                                            <div class="row">
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.make') }}</th>
                                                            <td class="text-end">{{ $motor->make }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.model') }}</th>
                                                            <td class="text-end">{{ $motor->model }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.variant') }}</th>
                                                            <td class="text-end">{{ $motor->variant }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.chassis_number') }}</th>
                                                            <td class="text-end">{{ $motor->chassis_number }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.engine_number') }}</th>
                                                            <td class="text-end">{{ $motor->engine_number }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.engine_capacity') }}</th>
                                                            <td class="text-end">{{ $motor->engine_capacity }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.seating_capacity') }}</th>
                                                            <td class="text-end">{{ $motor->seating_capacity }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.year') }}</th>
                                                            <td class="text-end">{{ $motor->manufactured_year }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.motor.vehicle_details.nvic') }}</th>
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
                                                <div class="col-12 d-flex justify-content-between align-items-start">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.add_ons_page.add_ons') }}</h4>
                                                    <a href="{{ route('motor.add-ons') }}" class="btn btn-outline-secondary text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            @if (!empty($extra_cover))
                                                @foreach ($extra_cover as $_extra_cover)
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="row">
                                                                <div class="col-12 col-lg-6 ">{{ $_extra_cover->description }}</div>
                                                                <div class="col-12 col-lg-6 text-end">{{ 'RM ' . number_format($_extra_cover->amount, 2) }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12 col-lg-6">
                                                            <p class="mb-0">{{ __('frontend.motor.payment_summary_page.sum_insured' . ' amount :' . number_format($_extra_cover->sum_insured, 2)) }}</p>
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
                                                <div class="col-12 d-flex justify-content-between align-items-start">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.add_ons_page.road_tax_renewal') }}</h4>
                                                    <a href="{{ route('motor.add-ons') }}" class="btn btn-outline-secondary text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            @if (!empty($motor->roadtax))
                                                <div class="row border-bottom pb-3">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6 fw-bold">{{ __('frontend.motor.add_ons_page.road_tax_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->roadtax_renewal_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6 fw-bold">{{ __('frontend.motor.add_ons_page.myeg_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->myeg_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6 fw-bold">{{ __('frontend.motor.add_ons_page.eservice_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->e_service_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6 fw-bold">{{ __('frontend.motor.add_ons_page.delivery_fee') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->delivery_fee }}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 col-lg-6 fw-bold">{{ __('frontend.price_card.service_tax') }}</div>
                                                            <div class="col-12 col-lg-6 text-end">{{ $motor->roadtax->service_tax }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <h5 class="fw-bold">{{ __('frontend.motor.payment_summary_page.recipient_info') }}</h5>
                                                    <div class="row">
                                                        <div class="col-6 col-lg-3 fw-bold">{{ __('frontend.fields.recipient_name') }}</div>
                                                        <div class="col-6 col-lg-3 text-end">{{ $motor->roadtax->recipient_name }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 col-lg-3 fw-bold">{{ __('frontend.fields.phone_number') }}</div>
                                                        <div class="col-6 col-lg-3 text-end">{{ $motor->roadtax->recipient_phone_number }}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 col-lg-3 fw-bold">{{ __('frontend.fields.address') }}</div>
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
                                                    <a href="{{ route('motor.add-ons') }}" class="btn btn-outline-secondary text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            @if (!empty($additional_driver))
                                                <div class="row">
                                                    <div class="col-4 fw-bold">{{ __('frontend.fields.name') }}</div>
                                                    <div class="col-4 fw-bold">{{ __('frontend.fields.id_number') }}</div>
                                                    <div class="col-4 fw-bold">{{ __('frontend.fields.relationship') }}</div>
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
                                                <div class="col-12 d-flex justify-content-between align-items-start">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.payment_summary_page.policy_holder') }}</h4>
                                                    <a href="{{ route('motor.policy-holder') }}" class="btn btn-outline-secondary text-uppercase">{{ __('frontend.button.edit') }}</a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <th>{{ __('frontend.fields.name') }}</th>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.fields.id_type') }}</th>
                                                            <td class="text-uppercase text-end">
                                                                {{ $policy_holder->id_type_id === config('setting.id_type.nric_no') ? __('frontend.motor.nric') : __('frontend.motor.company_resgistration') }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.fields.id_number') }}</th>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->id_number }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.fields.email') }}</th>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->email_address }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.fields.address') }}</th>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->address }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <th>{{ __('frontend.fields.dob') }}</th>
                                                            <td class="text-end">{{ $policy_holder->date_of_birth }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.fields.gender') }}</th>
                                                            <td class="text-end">{{ $policy_holder->gender }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __('frontend.fields.phone_number') }}</th>
                                                            <td class="text-end">{{ '+60' . $policy_holder->phone_number }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3">
                                        <button type="button" id="btn-back" class="btn btn-link text-dark fw-bold">{{ __('frontend.button.back') }}</button>
                                        <button type="button" id="btn-pay" class="btn btn-primary text-white rounded">{{ __('frontend.button.pay') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form action="{{ route('payment.store') }}" method="POST" id="payment-form">
                    <div class="hidden">
                        <input type="hidden" id="motor" name="motor" value='@json(session('motor'))' />
                        <input type="hidden" id="description" name="description" value="{{ $product->product_type->name . ' : ' . $motor->vehicle_number }}" />
                        <input type="hidden" id="total-payable" name="total_payable" value="{{ $insurance->amount }}" />
                        <input type="hidden" id="insurance-code" name="insurance_code" value="{{ $insurance->insurance_code }}" />
                    </div>
                </form>
                <x-modal maxWidth="md" id="agree-modal" headerClass="bg-primary text-white">
                    <x-slot name="title">{{ __('frontend.motor.payment_summary_page.confirm_modal.title') }}</x-slot>
                    <x-slot name="body">
                        <p>{{ str_replace(':insured_name', $policy_holder->name, __('frontend.motor.payment_summary_page.confirm_modal.line_1')) }}</p>
                        <p>{{ '- ' . __('frontend.motor.payment_summary_page.confirm_modal.line_2') }}</p>
                        <p>{!! '- ' . str_replace(':pds', '', __('frontend.motor.payment_summary_page.confirm_modal.line_3')) !!}</p>
                        <div id="tnc-radio">
                            <div class="form-check form-check-inline">
                                <input type="radio" id="agree" class="form-check-input" value="agree">
                                <label for="agree" class="form-check-label">{{ __('frontend.general.agree') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" id="disagree" class="form-check-input" value="disagree" />
                                <label for="disagree" class="form-check-label">{{ __('frontend.general.disagree') }}</label>
                            </div>
                        </div>
                        <p>{{ __('frontend.motor.payment_summary_page.confirm_modal.line_4') }}</p>
                        <p>{{ __('frontend.motor.payment_summary_page.confirm_modal.line_5') }}</p>
                    </x-slot>
                    <x-slot name="footer">
                        <div class="modal-footer text-end">
                            <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">{{ __('frontend.button.close') }}</button>
                            <button type="button" id="btn-pay-modal" class="btn btn-primary text-white rounded">{{ __('frontend.button.pay') }}</button>
                        </div>
                    </x-slot>
                </x-modal>
            </div>
        </section>
    </section>
@endsection

@push('after-scripts')
    <script>
        $(() => {
            $('#btn-pay').on('click', () => {
                $('#agree-modal').modal('show');
            });

            $('#btn-pay-modal').on('click', () => {
                $('#payment-form').submit();
            });
        });
    </script>
@endpush