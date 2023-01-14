<section {{ $attributes->merge(['class' => 'pt-0']) }}>
    <div class="container-fluid header d-flex flex-column justify-content-center">
        <x-steps current-step="{{ $currentStep }}" />
    </div>
    
    <section id="content" class="nm-15">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card rounded white-glow">
                        <div class="card-body px-4 py-5">
                            {{ $content }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</section>

@push('after-scripts')
    <script>
        $(() => {
            $('.step-trigger').on('click', (e) => {
                let step = $(e.target).parent('button').data('index') + 1;

                switch(step) {
                    case 1: {
                        location.href = "{{ route('motor.vehicle-details') }}";
                        break;
                    }
                    case 2: {
                        location.href = "{{ route('motor.compare') }}";
                        break;
                    }
                    case 3: {
                        location.href = "{{ route('motor.add-ons') }}"
                        break;
                    }
                    case 4: {
                        location.href = "{{ route('motor.policy-holder') }}"
                        break;
                    }
                }
            });
        });
    </script>
@endpush