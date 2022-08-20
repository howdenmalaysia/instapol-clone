@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.about.cookie_policy'))

@section('content')
    <section id="cookie-policy" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                    {{ __('frontend.navbar.about.cookie_policy') }}
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="px-3 pt-4 pb-4">
                    @foreach (__('frontend.cookie_policy_page') as $category => $item)
                        <h6 class="text-primary fw-bold">{{ $item['title'] }}</h6>
                        @foreach ($item['description'] as $description)
                            <p>{!! $description !!}</p>
                        @endforeach
                        
                        @if ($category === 'types_of_cookies')
                            <table class="table table-bordered table-striped text-center">
                                <thead>
                                    <tr class="text-primary table-secondary">
                                        <th>{{ $item['source'] }}</th>
                                        <th>{{ $item['cookie_name'] }}</th>
                                        <th>{{ $item['purpose'] }}</th>
                                        <th>{{ $item['essential'] }}</th>
                                        <th>{{ $item['cookie_category'] }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-start">
                                    @foreach ($item['cookies'] as $source => $details)
                                        <tr>
                                            <td>{{ ucwords($source) }}</td>
                                            <td>
                                                @foreach (explode(',', $details['name']) as $cookies_name)
                                                    <p class="mb-0">{{ $cookies_name }}</p>
                                                @endforeach
                                            </td>
                                            <td>{{ $details['purpose'] }}</td>
                                            <td>{{ $details['essential'] }}</td>
                                            <td>{{ $details['necessary'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            @if ($category === 'changing_browser_settings')
                                <ul>
                                    @foreach ($item['browsers'] as $platform)
                                        <li>
                                            <a href="{{ $platform['url'] }}" class="text-info">{{ $item['cookies_settings'] . ' ' . $platform['name'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    @endforeach
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection