<?php

declare(strict_types=1);

namespace App\Services\Schema;

use App\Contracts\Services\Schema\Schema as Contract;
use App\Models\Doctor;
use App\Models\Page;
use App\Settings\GeneralSettings;
use DateTimeInterface;
use Illuminate\Support\Str;
use Spatie\SchemaOrg\FAQPage;
use Spatie\SchemaOrg\LocalBusiness;
use Spatie\SchemaOrg\MedicalOrganization;
use Spatie\SchemaOrg\MedicalScholarlyArticle;
use Spatie\SchemaOrg\Offer;
use Spatie\SchemaOrg\Physician;
use Spatie\SchemaOrg\PostalAddress;
use Spatie\SchemaOrg\Product;
use Spatie\SchemaOrg\Review;
use Spatie\SchemaOrg\Schema as Builder;

class Schema implements Contract
{
    public function localBusiness(GeneralSettings $settings): LocalBusiness
    {
        $city = app(\App\Services\CityService::class)->getCurrentCity();

        return Builder::localBusiness()
            ->name($settings->site_name)
            ->telephone($city->phone ?? '')
            ->url(url('/'))
            ->image(asset('images/logo.svg'))
            ->logo(asset('images/logo.svg'))
            ->openingHours(explode('; ', $city->schedule ?? ''))
            ->geo(
                Builder::geoCoordinates()
                    ->latitude(Str::of($city->coordinates ?? '')->before(',')->trim())
                    ->longitude(Str::of($city->coordinates ?? '')->after(',')->trim())
            )
            ->address($this->address($settings));
    }

    public function medicalOrganization(GeneralSettings $settings): MedicalOrganization
    {
        $city = app(\App\Services\CityService::class)->getCurrentCity();
        $socials = $city->social_links ?? [];

        return Builder::medicalOrganization()
            ->name($settings->site_name)
            ->alternateName(Str::slug($settings->site_name, ' '))
            ->telephone($city->phone ?? '')
            ->email($city->email ?? '')
            ->url(url('/'))
            ->image(asset('images/logo.svg'))
            ->logo(asset('images/logo.svg'))
            ->aggregateRating(
                Builder::aggregateRating()
                    ->bestRating('5')
                    ->worstRating('1')
                    ->ratingCount('35')
                    ->ratingValue('4.6')
            )
            ->sameAs(array_filter([
                $socials['youtube'] ?? null,
                $socials['telegram'] ?? null
            ]))
            ->address($this->address($settings));
    }

    public function product(string $name, string $description, array $prices): Product
    {
        $offers = [];
        foreach ($prices as $key => $price) {
            $offers[$key] = $this->offer($price['item'], $price['price1']);
        }
        return Builder::product()
            ->name($name)
            ->description($description)
            ->image(asset('images/logo.svg'))
            ->offers($offers);
    }

    /**
     * @param Page $page
     * @param Doctor|null $author
     */
    public function medicalScholarlyArticle(Page $page, ?Doctor $author = null): MedicalScholarlyArticle
    {
        // Если автор не передан, пробуем получить из блока, иначе null
        if (!$author) {
            $author = $page->authorBlock ? $page->authorBlock->author : null;
        }

        return Builder::medicalScholarlyArticle()
            ->audience(
                Builder::audience()
                    ->url('https://schema.org/Clinician')
            )
            ->name($page->seo['title'] ?? $page->title)
            ->headline($page->title)
            ->image($page->pictureBlock ? $page->pictureBlock->getImageUrl('default') : asset('images/logo.svg'))
            ->datePublished($page->created_at->format('d.m.Y'))
            ->author(
                $author
                    ? Builder::person()
                    ->name($author->full_name)
                    ->url($author->url)
                    ->jobTitle($author->speciality)
                    : null
            );
    }

    public function physician(Doctor $doctor, ?GeneralSettings $settings = null): Physician
    {
        $city = app(\App\Services\CityService::class)->getCurrentCity();
        $phone = $city->phone ?? '';

        $schema = Builder::physician()
            ->name($doctor->full_name)
            ->url($doctor->url)
            ->image($doctor->getFirstMediaUrl())
            ->description($doctor->speciality)
            ->address($this->address($settings)) // Keeping argument for compatibility but ignoring inside
            ->telephone($phone)
            ->contactPoint(
                Builder::contactPoint()
                    ->telephone($phone)
                    ->contactType('Customer Support')
            );

        if (!empty($city->schedule)) {
            $schema->openingHours(explode('; ', $city->schedule));
        }
        
        if (!empty($city->coordinates)) {
             // coordinates format: "lat, lon" or "lat,lon"
             $coords = explode(',', $city->coordinates);
             if (count($coords) === 2) {
                 $lat = trim($coords[0]);
                 $lon = trim($coords[1]);
                 $schema->hasMap("https://yandex.ru/maps/?pt={$lon},{$lat}&z=16&l=map");
             }
        }

        return $schema;
    }

    public function offer(string $name, string|int $price): Offer
    {
        return Builder::offer()
            ->name($name)
            ->price((string)$price)
            ->priceCurrency('RUB');
    }

    public function review(
        string $name,
        string $text,
        int $rating,
        string $author,
        DateTimeInterface $publishedAt,
    ): Review {
        return Builder::review()
            ->name($name)
            ->text($text)
            ->reviewRating(Builder::rating()
                ->bestRating(5)
                ->ratingValue($rating)
                ->worstRating(1)
            )
            ->author(Builder::person()->name($author))
            ->datePublished($publishedAt);
    }

    public function faq(array $data): FAQPage
    {
        $entities = collect($data)->map(fn($item) => [
            Builder::question()
                ->name($item['question'])
                ->acceptedAnswer(Builder::answer()->text(strip_tags($item['answer_html'])))
        ])->toArray();

        return Builder::fAQPage()
            ->mainEntity($entities);
    }

    protected function address(?GeneralSettings $settings = null): PostalAddress
    {
        $city = app(\App\Services\CityService::class)->getCurrentCity();
        
        return Builder::postalAddress()
            ->addressLocality($city->name ?? '')
            ->postalCode($city->postal_code ?? '')
            ->streetAddress($city->address ?? '')
            ->addressCountry('Россия');
    }

    public function videoItem(string $name, string $text, string $thumb, string $date, string $url)
    {
        return Builder::videoObject()
            ->name($name)
            ->description($text)
            ->thumbnailUrl($thumb ?: asset('images/logo.svg'))
            ->uploadDate($date)
            ->duration('PT1M33S')
            ->contentUrl($url);
    }

    public function videos($block, $videos)
    {
        $items = [];
        foreach ($videos as $item => $video) {
            $items[$item] = Builder::listItem()
                ->position($item + 1)
                ->item(
                    Builder::videoObject()
                        ->name($block->title . ' - видео ' . ($item + 1))
                        ->description($block->title . ' - видео ' . ($item + 1))
                        ->thumbnailUrl(asset('images/logo.svg'))
                        ->uploadDate($video->created_at)
                        ->duration('PT1M33S')
                        ->contentUrl($video->getUrl())
                );
        }
        return Builder::itemList()
            ->itemListElement($items);
    }

    // public function breadcrumbList(array $breadcrumbs): BreadcrumbList
    // {
    //     return Builder::breadcrumbList()->itemListElement([
    //         collect($breadcrumbs)->map(function ($item, $index) {
    //             return Builder::listItem()
    //                 ->name($item['content'])
    //                 ->item($item['url'])
    //                 ->position($index + 1);
    //         })->toArray(),
    //     ]);
    // }
}
