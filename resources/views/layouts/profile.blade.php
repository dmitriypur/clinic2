<x-app-layout>
    <section class="bg-surface pb-16 lg:pt-16">
        <div class="container">
            <div class="lg:grid grid-cols-12 gap-8">
                <div class="col-span-3">
                    <div class="-mx-5 lg:mx-0 lg:rounded-lg border-2 p-6">
                        <nav>
                            <ul class="space-y-4">
                                <li>
                                    <x-profile-link route="profile.show">
                                        <x-slot:icon>
                                            <x-icon-user/>
                                        </x-slot:icon>
                                        Персональные данные
                                    </x-profile-link>
                                </li>
                                <li>
                                    <x-profile-link route="profile.bonuses">
                                        <x-slot:icon>
                                            <x-icon-bonus/>
                                        </x-slot:icon>
                                        Бонусная программа
                                    </x-profile-link>
                                </li>
                                <li>
                                    <x-profile-link route="profile.history">
                                        <x-slot:icon>
                                            <x-icon-calendar/>
                                        </x-slot:icon>
                                        Записи и история приёма
                                    </x-profile-link>
                                </li>
                                {{--                                <li>--}}
                                {{--                                    <x-profile-link route="profile.notifications">--}}
                                {{--                                        <x-slot:icon>--}}
                                {{--                                            <x-icon-chat/>--}}
                                {{--                                        </x-slot:icon>--}}
                                {{--                                        Уведомления--}}
                                {{--                                    </x-profile-link>--}}
                                {{--                                </li>--}}
                                <li>
                                    <form method="post" action="{{ route('logout') }}">
                                        {{ csrf_field() }}
                                        <button
                                            type="submit"
                                            class="leading-none text-interactive border-b border-transparent hover:border-interactive font-medium ml-[34px]"
                                        >Выйти
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                <div class="col-span-9 mt-8 lg:mt-0">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
