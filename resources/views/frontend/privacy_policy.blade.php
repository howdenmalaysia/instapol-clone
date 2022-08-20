@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.about.privacy_policy'))

@section('content')
    <section id="privacy-policy" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                    {{ __('frontend.navbar.about.privacy_policy') }}
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="px-3 pt-4 pb-4">
                    <p class="text-center">{{ __('frontend.privacy_policy_page.line_1') }}</p>
                    <p class="text-center">{{ __('frontend.privacy_policy_page.line_2') }}</p>

                    @foreach (__('frontend.privacy_policy_page.items') as $index => $item)
                        <div class="mb-3">
                            
                            <p class="text-primary fw-bold mb-1">{{ "{$index}. {$item['title']}" }}</p>
                            @foreach ($item['description'] as $subindex => $desc)
                                <div class="row">
                                    <div class="col-1 text-end">
                                        {{ "{$index}.{$subindex}." }}
                                    </div>
                                    <div class="col-11 px-0">
                                        {{ $desc }}
                                    </div>
                                </div>
                                @if ($subindex === 1 && !empty($item['list']))
                                        @foreach ($item['list'] as $number => $details)
                                            <div class="row ps-5">
                                                <div class="col-1 text-end">
                                                    {{ $number . '.' }}
                                                </div>
                                                <div class="col-11">
                                                    {{ $details }}
                                                </div>
                                            </div>
                                        @endforeach
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                    <x-howden-address />
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection