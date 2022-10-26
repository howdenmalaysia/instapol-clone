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
                                                <th>{{ __('frontend.motor.payment_summary_page.policy_holder') }}</th>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('frontend.motor.vehicle_details.car_number') }}</th>
                                                <td class="text-uppercase text-end">{{ $insurance->policy_holder->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('frontend.motor.payment_summary_page.insurer') }}</th>
                                                <td class="text-uppercase text-end">{{ $insurance->product->insurance_company->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('frontend.motor.payment_summary_page.product') }}</th>
                                                <td class="text-uppercase text-end">{{ $insurance->product->name }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>{{ __('frontend.motor.payment_summary_page.coverage_date') }}</th>
                                                <td class="text-uppercase text-end"></td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('frontend.motor.payment_summary_page.sum_insured') }}</th>
                                                <td class="text-uppercase text-end"></td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('frontend.motor.payment_summary_page.next_ncd') }}</th>
                                                <td class="text-uppercase text-end"></td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('frontend.motor.payment_summary_page.road_tax_renewal') }}</th>
                                                <td class="text-uppercase text-end"></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-primary text-white">
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-between">
                                        <h4 class="fw-bold">{{ __('frontend.motor.payment_summary_page.total_payable') }}</h4>
                                        <h4 class="fw-bold">RM </h4>
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
                                                            <td class="text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.model') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.variant') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.chassis_number') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.engine_number') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.engine_capacity') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.seating_capacity') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.year') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.motor.vehicle_details.nvic') }}</td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="add-ons" class="card bg-light rounded mt-4">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.add_ons_page.add_ons') }}</h4>
                                                    <button type="button" class="btn btn-outline text-uppercase">{{ __('frontend.button.edit') }}</button>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-between">

                                                </div>
                                                <div class="col-12">
                                                    <p>{{ __('frontend.motor.payment_summary_page.sum_insured' . ' amount') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="policy_holder-card" class="card bg-light rounded mt-4">
                                        <div class="card-body">
                                            <h4 class="card-title fw-bold border-bottom border-4 pb-3">{{ __('frontend.motor.payment_summary_page.policy_holder') }}</h4>
                                            <div class="row">
                                                <div class="col-12 col-lg-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td>{{ __('frontend.fields.name') }}</td>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.name') }}</td>
                                                            <td class="text-uppercase text-end"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.id_type') }}</td>
                                                            <td class="text-uppercase text-end">
                                                                {{ $policy_holder->id_type_id === config('setting.id_type.nric_no') ? __('frontend.motor.nric') : __('frontend.motor.company_resgistration') }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ __('frontend.fields.email') }}</td>
                                                            <td class="text-uppercase text-end">{{ $policy_holder->email }}</td>
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
                                                            <td class="text-end">{{ '0' . $policy_holder->phone_number }}</td>
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