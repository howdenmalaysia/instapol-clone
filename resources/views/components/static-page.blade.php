<div class="container-fluid header"></div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col">
            <x-text-content>
                <x-slot name="title">
                    {{ $title }}
                </x-slot>
                <x-slot name="content">
                    {{ $content }}
                </x-slot>
            </x-text-content>
        </div>
    </div>
</div>