<div id="{{ $id }}" class="carousel slide"  data-bs-ride="carousel" data-bs-interval="{{ $interval }}">
    <div class="carousel-inner">
        @foreach ($images as $index => $item)
            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                <img class="carousel-image img-fluid d-none d-md-block" src="{{ asset('images' . str_replace([public_path('images'), '\\'], ['', '/'], $item)) }}" />
                @if (file_exists($item))
                    <img class="carousel-image img-fluid d-block d-md-none" src="{{ asset('images/banner/mobile' . str_replace([public_path('images/banner'), '\\'], ['', '/'], $item)) }}" />
                @endif
            </div>
        @endforeach
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#{{ $id }}" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#{{ $id }}" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
