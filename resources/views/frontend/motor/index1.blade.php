@extends('frontend.layouts.app')

@section('title', implode(' | ', [config('app.name'), __('frontend.motor.get_quote'), __('frontend.motor.insure_motor'), __('frontend.motor.car_insurance')]))

@section('meta_description', __('frontend.motor.meta_description'))

@section('content')
    <section id="motor">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 p-0">
                    <div class="header">
                        <div class="container">
                            <div class="row text-center justify-content-center">
                                <div class="col-11 col-lg-7 tag-line">
                                    <h1 class="title text-uppercase text-white aos-init" data-aos="fade-right" data-aos-duration="1000">{{ __('frontend.motor.compare_and_buy') }}</h1>
                                    <p class="text-white aos-init" data-aos="fade-right" data-aos-duration="2000">{{ __('frontend.motor.compare_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 p-0">
                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                            <form action="{{ route('motor.index') }}?p={{ $renewal }}&t={{ $timestamp }}" method="post">
                                @csrf
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Welcome to instaPol</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                <label class="form-label">Please key in the last <b>4 digit</b> of owner's NRIC number of to continue</label>
                                <input type="text" class="form-control" name="ic" id="id-number" placeholder="Last 4 Digit NRIC Number" required>
                                <div id="emailHelp" class="form-text">Without spaces / dashes [-]</div>
                                @if($errors->any())
                                    <p class="text-danger">{{$errors->first()}}</p>
                                @endif
                            </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="d-grid gap-2 d-grid gap-2 col-6 mx-auto">
                                        <button type="submit" class="btn btn-success text-white btn-block">Next</button>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Modal -->
                    <div class="bg-light pt-4">
                        <x-make-easy-section />
                    </div>
                    <section id="partners">
                        <div class="container-fluid text-center">
                            <div class="row">
                                <div class="col-12 p-0">
                                    <h3 class="text-uppercase text-white fw-bold py-3">{{ __('frontend.motor.partner') }}</h3>
                                </div>
                            </div>
                            <x-instapol-motor-insurer :insurers="$insurers" />
                        </div>
                    </section>
                    <x-instapol-features />
                </div>
            </div>
        </div>
    </section>
@endsection

@push('after-scripts')
<script>
    $(document).ready(function($) {
        $('#exampleModal').modal('show'); 
        new Inputmask({mask: '9999'}).mask('#id-number');
    });

    $("#exampleModal").on("hidden.bs.modal", function () {
        window.location = "{{ route('motor.index') }}";
    });
</script>

@endpush
