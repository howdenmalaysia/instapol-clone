@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.payment_success_page.payment_received')]))

@section('content')
    <section id="payment-success" class="p-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                    Payment Success
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="row align-items-center">
                    <div class="col-6">
                        <div class="px-3 py-4">
                            <p>{{ implode(' ', [__('frontend.general.hi'), $insurance->holder->name]) . ',' }}</p>
                            
                            @foreach (__('frontend.motor.payment_success_page.messages') as $message)
                                <p>{!! str_replace(':insurer', 'Insurer', $message) !!}</p>
                            @endforeach                    
                        </div>
                    </div>
                    <div class="col-6">
                        <table class="table table-bordered">
                            <tr>
                                <th class="text-center py-3 w-50">{{ __('frontend.motor.payment_success_page.insurance_company') }}</th>
                                <td class="text-center py-3">{{ $insurance->product->insurance_company->name }}</td>
                            </tr>
                            <tr>
                                <th class="text-center py-3">{{ __('frontend.motor.payment_success_page.transaction_number') }}</th>
                                <td class="text-center py-3">{{ $insurance->insurance_code }}</td>
                            </tr>
                            <tr>
                                <th class="text-center py-3">{{ __('frontend.motor.payment_success_page.total_premium') }}</th>
                                <td class="text-center py-3">{{ 'RM ' . number_format($insurance->amount, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <p class="fw-bold text-center">{{ __('frontend.motor.payment_success_page.you_might_interested') }}</p>
                        <div class="row">
                            <div class="col">
                                <a href="">
                                    <img src="{{ asset('images/icons/covid.png') }}" alt="COVID-19 Insurance" width="100" />
                                </a>
                            </div>
                            <div class="col">
                                <a href="{{ config('setting.redirects.travel') }}">
                                    <img src="{{ asset('images/icons/travel.png') }}" alt="Travel Insurance" width="100" />
                                </a>
                            </div>
                            <div class="col">
                                <a href="{{ config('setting.redirects.motor_extended') }}">
                                    <img src="{{ asset('images/icons/extended-motor.png') }}" alt="Motor Extended Warranty Insurance" width="100" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection