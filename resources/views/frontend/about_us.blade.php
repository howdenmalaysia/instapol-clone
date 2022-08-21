@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.about.about_us'))

@section('content')
    <section id="about-us" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                    {{ __('frontend.about_page.about') }}
                    <x-instapol-logo />
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="text-center px-3 pt-4 pb-4 fw-bold">
                    <p>{{ __('frontend.about_page.instant_policy') . ' ' . __('frontend.about_page.instapol_is_quick') }}</p>
                    <p>{{ __('frontend.about_page.line_2') }}</p>
                    <p>{{ __('frontend.about_page.line_3') }}</p>
                    <p>{{ __('frontend.about_page.powered_by') }}</p>
                    <h4 class="text-primary fw-bold">{{ __('frontend.about_page.about_hbgm') }}</h4>
                    <x-howden-logo width="250" class="my-4" />
                    <p>{{ __('frontend.about_page.line_6') }}</p>
                    <p>{{ __('frontend.about_page.line_7') }}</p>
                    <p>{{ __('frontend.about_page.line_8') }}</p>
                    <p>
                        {{ __('frontend.about_page.visit') . ' ' . __('frontend.general.the') }}
                        <a href="">{{ __('frontend.about_page.interested') }}</a>
                    </p>
                    <p>
                        <a href="{{ route('howden_website') }}">{{ __('frontend.about_page.hbg_website') }}</a>
                        {{ __('frontend.general.and') }}
                        <a href="">{{ __('frontend.about_page.get_in_touch') }}</a>
                    </p>
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection