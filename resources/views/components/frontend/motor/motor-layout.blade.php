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