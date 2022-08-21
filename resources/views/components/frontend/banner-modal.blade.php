<div class="modal fade rounded" tabindex="-1" id="landing-banner" {{ $static ? 'data-bs-backdrop="static"' : '' }}>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded">
            <div class="container px-0">
                <div class="row">
                    <div class="col-12">
                        <div class="modal-body p-0">
                            <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Close"></button>
                            <img src="{{ $image }}" alt="Banner Image" class="img-fluid rounded">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>