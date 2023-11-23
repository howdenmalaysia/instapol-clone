<div id="{{ $id }}" class="carousel slide"  data-bs-ride="carousel" data-bs-interval="{{ $interval }}">
<div class="carousel-inner">
        <div class="carousel-item active">
            <a href="https://howdenvirtualrun.com/" target="_blank">
                <img class="carousel-image img-fluid d-none d-md-block" src="{{ asset('images/banner/virtual.jpg') }}" />
                <img class="carousel-image img-fluid d-block d-md-none" src="{{ asset('images/banner/mobile/virtual.png') }}" />
            </a>
        </div>
        <div class="carousel-item">
            <a href="https://howden-bike.instapol.my/landing" target="_blank">
                <img class="carousel-image img-fluid d-none d-md-block" src="{{ asset('images/banner/bicycle.png') }}" />
                <img class="carousel-image img-fluid d-block d-md-none" src="{{ asset('images/banner/mobile/bicycle.png') }}" />
            </a>
        </div>
        <div class="carousel-item">
            <a href="https://instapol.my/motor" target="_blank">
                <img class="carousel-image img-fluid d-none d-md-block" src="{{ asset('images/banner/compare.png') }}" />
                <img class="carousel-image img-fluid d-block d-md-none" src="{{ asset('images/banner/mobile/virtual.png') }}" />
            </a>
        </div>
        <div class="carousel-item">
            <img class="carousel-image img-fluid d-none d-md-block" src="{{ asset('images/banner/main.png') }}" />
            <img class="carousel-image img-fluid d-block d-md-none" src="{{ asset('images/banner/mobile/virtual.png') }}" />
        </div>
        <div class="carousel-item">
            <a href="https://instapol.my/motor" target="_blank">
                <img class="carousel-image img-fluid d-none d-md-block" src="{{ asset('images/banner/roadtax_2.jpg') }}" />
                <img class="carousel-image img-fluid d-block d-md-none" src="{{ asset('images/banner/mobile/roadtax_2.jpg') }}" />
            </a>
        </div>
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
