<div id="{{ $id }}" class="carousel slide"  data-bs-ride="carousel" data-bs-interval="{{ $interval }}">
    <div class="carousel-inner">
        @foreach ($images as $index => $item)
            <div class="carousel-item{{ $index === 0 ? ' active' : '' }} d-none d-md-block">
                <img class="carousel-image img-fluid" src="{{ asset('images' . str_replace([public_path('images'), '\\'], ['', '/'], $item)) }}" />
            </div>
        @endforeach

        @if (!empty($mobile))
            @foreach ($mobile as $index => $item)
                <div class="carousel-item{{ $index === 0 ? ' active' : '' }} d-block d-md-none">
                    <img class="carousel-image img-fluid" src="{{ asset('images' . str_replace([public_path('images'), '\\'], ['', '/'], $item)) }}" />
                </div>
            @endforeach
        @endif
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#promo-carousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#promo-carousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
