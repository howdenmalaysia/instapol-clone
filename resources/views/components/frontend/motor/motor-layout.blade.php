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
            $('.step-btn').on('click', (e) => {
                let step = $(e.target).data('index')++;
                let url = '';

                switch(step) {
                    case 1: {
                        url = "{{ route('motor.vehicle-details') }}";
                        break;
                    }
                    case 2: {
                        url = "{{ route('motor.compare') }}";
                        break;
                    }
                    case 3: {
                        url = "{{ route('motor.add-ons') }}"
                        break;
                    }
                    case: 4 {
                        url = "{{ route('motor.policy-holder') }}"
                        break;
                    }
                }

                window.location.href = url;
            });
        });
    </script>
@endpush