@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.about.terms_and_conditions'))

@section('content')
    <section id="terms-and-conditions" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                    {{ __('frontend.navbar.about.terms_and_conditions') }}
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="p-4">
                    @foreach (__('frontend.terms_and_conditions_page') as $category => $item)
                        <div class="my-4">
                            <h6 class="text-primary fw-bold mt-5 mb-3">{{ $item['title'] }}</h6>
                            @foreach ($item['description'] as $index => $description)
                                <p>{!! $description !!}</p>
    
                                @if ($category === 'instapol' && $index === count($item['description']) - 1)
                                    <x-howden-address />
                                @else
                                    @if ($category === 'not_allowed_to_do' && $index === 0)
                                        @foreach ($item['exception'] as $number => $exception)
                                            <p class="mb-0">{{ "({$number}) {$exception}"}}</p>
                                        @endforeach
                                    @else
                                        @if ($category === 'limitation_of_liability' && $index === 0)
                                            @foreach ($item['exception'] as $number => $exception)
                                                <p class="mb-0">{{ "({$number}) {$exception}"}}</p>
                                            @endforeach
    
                                            <ul>
                                                @foreach ($item['liability'] as $liability)
                                                    <li>{{ $liability }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection