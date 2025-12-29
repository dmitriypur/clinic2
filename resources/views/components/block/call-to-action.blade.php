<div class="py-10 md:py-[76px] orange-gr-nohover">

    <div class="container relative flex flex-col md:block">
        @if (!$block->title_hidden)
            <h2 class="order-0 text-2xl md:text-3xl text-white font-semibold {{ !empty($block->payload['add_fox2']) ? 'text-center' : 'text-center md:text-left' }} relative z-20">
                {{ $block->title }}</h2>
        @endif
        @if(!empty($block->payload['subtitle']))
            <p class="order-2 text-white {{ !empty($block->payload['add_fox2']) ? 'text-center mx-auto' : 'text-center md:text-left' }} mt-5 max-w-2xl">
                {{ $block->payload['subtitle'] }}</p>
        @else
            <p class="order-2 text-white {{ !empty($block->payload['add_fox2']) ? 'text-center mx-auto' : 'text-center md:text-left' }} mt-5 max-w-2xl">
                Оставьте ваши контакты, мы перезвоним вам и подтвердим запись</p>
        @endif
        @if(!empty($block->payload['add_fox2']))
            <div
                class="accessibility:hidden order-1 mx-auto w-32 h-32 mt-10 rounded-full bg-white md:bg-transparent relative [&_img]:absolute [&_img]:left-8 [&_img]:bottom-0 [&_img]:w-28 md:[&_img]:w-48 md:[&_img]:relative md:absolute md:-top-10 md:-left-10 w-[140px] md:w-[215px]">
                <img src="{{asset('images/fireworks2.webp')}}" alt="Корги с рупором" width="192" height="250">
            </div>
        @endif
        @if(!empty($block->payload['add_fox']))
            <div
                class="{{ empty($block->payload['add_fox2']) ? 'mx-auto mt-10 md:-top-20 md:right-10' : 'hidden md:-top-40 md:right-0' }} accessibility:hidden order-1 md:block md:absolute w-[140px] md:w-[215px]">
                <img src="{{asset('images/fireworks.webp')}}" alt="Милая корги в очках" width="215" height="288">
            </div>
        @endif
        <div class="order-3 pt-4 md:pt-8 relative z-20">
            <call-to-action :fox="{{ !empty($block->payload['add_fox2']) ? '1' : '2' }}" :name="callbackModalName"
                            :phone="callbackModalPhone"
                            target="bloki-otpravka-formy"></call-to-action>
        </div>
    </div>
</div>
