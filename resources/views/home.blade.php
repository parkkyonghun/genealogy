@section('title')
    &vert; {{ __('app.home') }}
@endsection

<x-app-layout>
    <x-slot name="heading">
        <div class="text-center">
            <h1 class="text-2xl font-bold">មរតក</h1>
            <p class="text-lg mt-1">សុភមង្គល លី ថារិទ្ធ</p>
        </div>
    </x-slot>

    <div class="w-full p-2 space-y-5">
        <div class="pb-10 dark:text-neutral-200">
            <div class="flex flex-col items-center pt-6 sm:pt-0">
                <div>
                    <x-authentication-card-logo />
                </div>

                <div class="w-full p-6 mt-6 overflow-hidden prose bg-secondary rounded shadow-md sm:max-w-5xl font-koulen">
                    {!! $home !!}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
