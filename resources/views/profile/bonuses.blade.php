<x-profile-layout>
    <h1 class="font-semibold text-2xl md:text-4xl text-center md:text-left text-heading">Бонусная программа</h1>

    <div class="flex gap-8 w-full pt-8">
        <div class="flex-none -ml-[50%] lg:m-0">
            <img src="{{ asset('images/bonus_card.png') }}" srcset="{{ asset('images/bonus_card@2x.png') }} 2x"
                class="w-[333px]" />
        </div>
        <div class="w-full flex flex-col space-y-6 pt-16 lg:pt-0">
            <div>
                <p class="font-medium">Бонусный баланс:</p>
                <p class="text-2xl text-action-primary font-bold">{{ $user->bonuses }} руб.</p>
            </div>

            <div class="space-y-6 hidden lg:block">
                <div class="w-12 h-0.5 bg-[#E1E5E7]"></div>
                <div class="flex space-x-2">
                    <span class="fill-[#FFCB13] w-4">
                        <x-icon-spark />
                    </span>
                    <span>Вы можете использовать при оплате услуг/товаров
                        <br />до <span class="font-semibold">20%</span> бонусами от стоимости.</span>
                </div>
            </div>
        </div>
    </div>
</x-profile-layout>
