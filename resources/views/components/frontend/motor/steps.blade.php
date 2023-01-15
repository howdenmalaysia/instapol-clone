<section id="steps">
    <div class="container">
        <div class="row bg-white rounded position-relative mx-4 pt-4 pb-5 white-glow">
            <div class="bs-stepper d-flex justify-content-center pb-4">
                <div class="col-12 col-lg-10">
                    <div class="bs-stepper-header">
                        @foreach (array_keys(__('frontend.motor.steps')) as $index => $key)
                            <div class={{ "step" . ($index === $currentStep ? ' active' : '')}}>
                                <button type="button" class="step-trigger p-0" role="tab" {{ --$currentStep <= $index ? '' : 'disabled' }} data-index="{{ $index }}">
                                    <span class="bg-primary bs-stepper-circle align-items-center">{{ ++$index }}</span>
                                    <span class="position-absolute stepper-label">{{ __("frontend.motor.steps.{$key}") }}</span>
                                </button>
                            </div>
                            @if ($index !== count(array_keys(__('frontend.motor.steps'))))
                                <div class="line"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>