<div id="{{ $id }}" class="modal fade rounded" tabindex="-1" {{ $backdropStatic ? 'data-bs-backdrop="static" data-bs-keyboard=false' : '' }}>
    <div class="{{ "modal-dialog modal-dialog-centered {$maxWidth}" }}">
        <div class="modal-content">
            <div class="{{ "modal-header {$headerClass}" }}">
                <h5 class="modal-title">{{ $title }}</h5>
                @if (!$notClosable)
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                @endif
            </div>
            <div class="modal-body">
                {{ $body }}
            </div>
            @if (!empty($footer))
                {{ $footer }}
            @else
                <div class="modal-footer text-end">
                    <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">{{ __('frontend.button.close') }}</button>
                    <button type="button" id="btn-continue-modal" class="btn btn-primary text-white rounded">{{ __('frontend.button.continue') }}</button>
                </div>
            @endif
        </div>
    </div>
</div>