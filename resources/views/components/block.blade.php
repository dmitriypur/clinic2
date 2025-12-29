<section
    class="flex flex-col scroll-mt-16 scroll-smooth lg:scroll-mt-28 {{ $className }} {{ !$block->show_on_mobile ? 'md-down:visually-hidden' : '' }} {{ $block->hide_on_desctop ? 'md:visually-hidden' : '' }}"
    {{ $block->anchor ? 'id='. $block->anchor : null }}>

    @if ($block->show_breadcrumbs)
        <div class="py-10">
            <div class="container">
                <ol class="flex space-x-2 font-medium" itemscope
                    itemtype="https://schema.org/BreadcrumbList">
                    <li itemprop="itemListElement" itemscope
                        itemtype="https://schema.org/ListItem">
                        <a itemprop="item"
                           href="{{ home_route() }}"
                           class="text-interactive hover:text-interactive-hovered hover:underline">
                            <span itemprop="name">Главная</span>
                        </a>
                        <meta itemprop="position" content="1"/>
                    </li>
                    <li>/</li>
                    <li class="flex text-action-primary"
                        itemprop="itemListElement" itemscope
                        itemtype="https://schema.org/ListItem">
                        <span itemprop="name">{{ $breadcrumbsTitle }}</span>
                        <meta itemprop="position" content="2"/>
                    </li>
                </ol>
            </div>
        </div>
    @endif
    @if ($block->show_page_title)
        <div class="container">
            <h1 class="font-semibold text-2xl md:text-4xl text-center text-heading mb-6 {{ $headingMarginClassName }}">
                {{ $pageTitle }}
            </h1>
        </div>
    @endif

    @switch($block->type)
        @case(\App\Enums\BlockType::MAIN_PAGE_STATIC_BLOCK)
            <x-block.main/>
            @break

        @case(\App\Enums\BlockType::TEXT_WITH_IMAGE)
            <div class="container">
                <x-block.text-with-image :block="$block"/>
            </div>
            @break

        @case(\App\Enums\BlockType::TEXT_WITH_IMAGE_ALT)
            <div class="container">
                <x-block.text-with-image-alt :block="$block"/>
            </div>
            @break

        @case(\App\Enums\BlockType::ELEMENTS_ITEM_COLUMN)
            <div class="container">
                <x-block.elements-item-column :block="$block"
                                              :subdued="$subdued"/>
            </div>
            @break

        @case(\App\Enums\BlockType::ELEMENTS_ITEM_ROW)
            <div class="container">
                <x-block.elements-item-row :block="$block" :subdued="$subdued"/>
            </div>
            @break

        @case(\App\Enums\BlockType::DOCTORS_ALT)
            <div class="container">
                <x-block.doctors-alt :block="$block"/>
            </div>
            @break

        @case(\App\Enums\BlockType::CONTACTS)
            <x-block.contacts :block="$block"/>
            @break

        @case(\App\Enums\BlockType::CAROUSEL)
            <x-block.carousel :block="$block"/>
            @break

        @case(\App\Enums\BlockType::TAGS)
            <x-block.tags :block="$block"/>
            @break

        @case(\App\Enums\BlockType::HTML)
            <x-block.html :block="$block"/>
            @break

        @case(\App\Enums\BlockType::VIDEO)
            <x-block.video :block="$block"/>
            @break

        @case(\App\Enums\BlockType::PRICE_LIST)
            <x-block.price-list
                :block="$block"
                :title="$pageTitle"
                :description="$pageDescription"
            />
            @break

        @case(\App\Enums\BlockType::FULL_PRICE_LIST)
            <x-block.full-price-list :block="$block"/>
            @break

        @case(\App\Enums\BlockType::CALL_TO_ACTION)
            <x-block.call-to-action :block="$block"/>
            @break

        @case(\App\Enums\BlockType::LICENSES)
            <x-block.licenses :block="$block"/>
            @break

        @case(\App\Enums\BlockType::FAQ)
            <x-block.faq :block="$block"/>
            @break

        @case(\App\Enums\BlockType::AUTHOR)
            <x-block.author :block="$block"/>
            @break

        @case(\App\Enums\BlockType::TEXT_SUBDUED)
            <x-block.text-subdued :block="$block"/>
            @break

        @case(\App\Enums\BlockType::PHOTO)
            <x-block.photo :block="$block"/>
            @break

        @case(\App\Enums\BlockType::HTML_CODE)
            <x-block.html-code :block="$block"/>
            @break

        @case(\App\Enums\BlockType::UTP)
            <div class="container">
                <x-block.utp :block="$block"/>
            </div>
            @break

        @case(\App\Enums\BlockType::WELCOME)
            <x-block.welcome :block="$block"/>
            @break

        @case(\App\Enums\BlockType::GRID_CAROUSEL)
            <x-block.grid-carousel :block="$block"/>
            @break

        @case(\App\Enums\BlockType::VIDEO_CAROUSEL)
            <x-block.video-carousel :block="$block"/>
            @break

        @case(\App\Enums\BlockType::POINTS)
            <x-block.points :block="$block"/>
            @break

        @case(\App\Enums\BlockType::ADVANTAGES)
            <x-block.advantages :block="$block"/>
            @break

        @case(\App\Enums\BlockType::VIDEO_NEW)
            <x-block.video-new :block="$block"/>
            @break

        @case(\App\Enums\BlockType::TEXT_WITH_CHART)
            <x-block.text-with-chart :block="$block"/>
            @break

        @case(\App\Enums\BlockType::CARD_COATING)
            <x-block.card-coating :block="$block"/>
            @break

        @case(\App\Enums\BlockType::HOW_TO_ORDER)
            <x-block.how-to-order :block="$block"/>
            @break

        @case(\App\Enums\BlockType::TEXT_BLOCKS)
            <x-block.text-blocks :block="$block"/>
            @break

        @case(\App\Enums\BlockType::REVIEWS_ALT)
            <x-block.reviews-alt :block="$block"/>
            @break

        @case(\App\Enums\BlockType::GUARANTEE)
            <x-block.guarantee :block="$block"/>
            @break

        @case(\App\Enums\BlockType::PROMOTIONS)
            <x-block.promotions :block="$block"/>
            @break

        @case(\App\Enums\BlockType::SERVICES_BLOCK)
            <x-block.services-block :block="$block"/>
            @break

        @case(\App\Enums\BlockType::CARDS_ITEM_ROW)
            <x-block.cards-row :block="$block" :subdued="$subdued"/>
            @break

        @case(\App\Enums\BlockType::BANNER_WITH_BUTTON)
            <x-banner.with-btn :block="$block"/>
            @break

        @case(\App\Enums\BlockType::BANNER_NIGHT_LENSES)
            <x-night-lenses.banner :block="$block"/>
            @break

        @case(\App\Enums\BlockType::NIGHT_LENSES_PICTURES)
            <x-night-lenses.pictures :block="$block"/>
            @break

        @case(\App\Enums\BlockType::NIGHT_LENSES_SELECTION)
            <x-night-lenses.selection :block="$block"/>
            @break

        @case(\App\Enums\BlockType::SEVERAL_COLS)
            <x-block.several-cols :block="$block"/>
            @break

        @case(\App\Enums\BlockType::BANNER_APPOINTMENT)
            <x-banner.appointment :block="$block"/>
            @break

        @case(\App\Enums\BlockType::CARDS_SLIDER)
            @if(isset($block->payload['is_blog']) && $block->payload['is_blog'] === true)
                <x-block.blog-slider :block="$block"/>
            @else
                <x-block.cards-slider :block="$block"/>
            @endif
            @break

        @case(\App\Enums\BlockType::CARDS_FEATURE)
            <x-block.cards-feature :block="$block"/>
            @break

        @case(\App\Enums\BlockType::SELECT_LENSES_SELECTION)
            <x-block.select-lenses :block="$block"/>
            @break

        @case(\App\Enums\BlockType::PICTURE)
            <x-block.picture :block="$block"/>
            @break

        @case(\App\Enums\BlockType::POST_TEXT)
            <x-block.post-text :block="$block"/>
            @break

        @case(\App\Enums\BlockType::BANNER_CORRECTION)
            <x-banner.correction :block="$block"/>
            @break

        @case(\App\Enums\BlockType::BANNER_MYOPIA)
            <x-banner.myopia :block="$block"/>
            @break

        @case(\App\Enums\BlockType::CARDS_BORDER)
            <x-block.cards-border :block="$block"/>
            @break

        @case(\App\Enums\BlockType::LIST_WITH_IMAGE)
            <x-block.list-with-image :block="$block"/>
            @break

        @case(\App\Enums\BlockType::BANNERS_GRID)
            <x-banner.banners-grid :block="$block"/>
            @break

        @case(\App\Enums\BlockType::ADVANTAGES_SLIDER)
            <x-block.advantages-slider :block="$block"/>
            @break

        @case(\App\Enums\BlockType::DETAILS)
            <x-block.details :block="$block"/>
            @break

        @case(\App\Enums\BlockType::UNIVERSAL_TEXT_BLOCK)
            <x-block.universal-block :block="$block"/>
            @break

        @case(\App\Enums\BlockType::GRID_CONTACTS)
            <x-block.contacts-info :block="$block"/>
            @break

        @case(\App\Enums\BlockType::LIST_TEXT_WITH_LINK)
            <x-block.list-text-with-link :block="$block"/>
            @break

    @endswitch
</section>
