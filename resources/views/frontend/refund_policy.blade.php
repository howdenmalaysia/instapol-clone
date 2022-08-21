@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.about.refund_policy'))

@section('content')
    <section id="refund-policy" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                    {{ __('frontend.navbar.about.refund_policy') }}
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="px-3 py-4">
                    @foreach (__('frontend.refund_policy_page') as $category => $item)
                        <h6 class="text-primary fw-bold">{{ $item['title'] }}</h6>
                        @foreach ($item['description'] as $number => $description)
                            @if (is_numeric($number))
                                <p>{{ $description }}</p>
                            @else
                                <div class="row">
                                    <div class="col-1 text-end">
                                        {{ "{$number}." }}
                                    </div>
                                    <div class="col-11 px-0">
                                        <p>{!! $description !!}</p>

                                        @if ($number === 'i')
                                            <table class="table table-bordered table-striped text-center">
                                                <thead>
                                                    <tr class="text-primary table-secondary">
                                                        <th>{{ $item['period_of_insurance'] }}</th>
                                                        <th>{{ $item['refund_premium'] }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($item['refund_table'] as $details)
                                                        <tr>
                                                            <td>{{ $details['title'] }}</td>
                                                            <td>{{ $details['description'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection