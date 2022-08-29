<section id="info">
    <div class="body-container p-5">
        @foreach (__('frontend.home_page.info') as $index => $info)
            <x-benefits :image-path='asset("images/info_{$index}.png")' :title="$info['title']" :description="$info['description']" />
        @endforeach
    </div>
</section>