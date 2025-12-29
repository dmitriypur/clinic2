<x-app-layout>
    <section class="py-10 lg:py-24 bg-surface">
        <div class="container">
            <div class="text-center pb-4 text-lg">@lang('Error') 404</div>
            <h1 class="font-semibold text-center text-2xl md:text-4xl text-heading mb-6 lg:mb-10">
                @lang('Not Found')
            </h1>

            <div class="text-center">
                <p>К сожалению, такой страницы не существует на нашем сайте.</p>
                <p class="mb-4">Возможно вы ввели
                    неправильный адрес или страница была удалена с сервера.</p>
                <p>Можете перейти на <a href="{{ home_route() }}"
                                        class="text-interactive underline hover:no-underline text-sm lg:text-base">главную
                        страницу</a> сайта
                </p>
            </div>

        </div>
    </section>
    @section('title', __('Not Found'))
    @section('code', '404')
    @section('message', __('Not Found'))
</x-app-layout>
