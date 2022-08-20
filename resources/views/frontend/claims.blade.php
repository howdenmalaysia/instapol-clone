@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.claims'))

@section('content')
    <section id="claims" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                    {{ __('frontend.navbar.claims') }}
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="p-4">
                    <p>{{ __('frontend.claims_page.description') }}</p>
                    
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('frontend.claims_page.contact_us') }}</th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>{{ __('frontend.claims_page.customer_service_careline') }}</th>
                            <td>
                                <a href="tel:{{ config('setting.customer_service.number') }}">{{ config('setting.customer_service.number') }}</a>
                                <p class="mb-0 mt-3">{{ __('frontend.claims_page.operating_hours') }}</p>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('frontend.claims_page.whatsapp') . ' ' . __('frontend.claims_page.no') }}</th>
                            <td>
                                <a href="{{ config('setting.whatsapp.url') . config('setting.whatsapp.number') }}">{{ config('setting.whatsapp.number') }}</a>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('frontend.claims_page.whatsapp') . ' ' . __('frontend.claims_page.link') }}</th>
                            <td>
                                <a href="{{ config('setting.whatsapp.link') }}">{{ config('setting.whatsapp.link') }}</a>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('frontend.claims_page.scan_qr') }}</th>
                            <td>
                                <img src="{{ asset('images/whatsapp-qr.png') }}" alt="instaPol WhatsApp QR Code" width="120">
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('frontend.claims_page.email_us') }}</th>
                            <td>
                                <a href="mailto:{{ config('setting.customer_service.email') }}">{{ config('setting.customer_service.email') }}</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection