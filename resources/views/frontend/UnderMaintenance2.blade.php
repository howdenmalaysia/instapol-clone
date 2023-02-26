@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.about.about_us'))

@section('content')
    <section id="about-us" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
      				We'll be back soon!                
		</div>
            </x-slot>
    
            <x-slot name="content">
                <div class="text-center px-3 pt-4 pb-4 fw-bold">
                    <p><h4>instaPol motor portal is under maintenance. </h4>
  
                    <p><h4>We will be right back soon, thank you for staying with us.</h4></p>
                    
                </div>

            </x-slot>
        </x-static-page>
    </section>
@endsection