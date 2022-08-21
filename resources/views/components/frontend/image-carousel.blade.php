<div id="{{ $id }}" class="carousel slide"  data-bs-ride="carousel" data-bs-interval="{{ $interval }}">
    <div class="carousel-inner">
        @foreach ($images as $index => $item)
            <div class="carousel-item{{ $index === 0 ? ' active' : '' }}">
                <div class="carousel-image" style="background-image: url('{{ 'images' . str_replace([public_path('images'), '\\'], ['', '/'], $item) }}')"></div>
            </div>
        @endforeach
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