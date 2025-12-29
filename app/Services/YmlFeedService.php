<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PageType;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class YmlFeedService
{
    private string $siteName;
    private string $companyName;
    private string $siteUrl;
    private GeneralSettings $generalSettings;
    private CityService $cityService;
    private ?City $city;
    private ?string $shopEmail;

    public function __construct(GeneralSettings $generalSettings, CityService $cityService)
    {
        $this->generalSettings = $generalSettings;
        $this->cityService = $cityService;
        $this->city = $this->cityService->getCurrentCity() ?? $this->cityService->getDefaultCity();

        $defaultName = config('app.name', 'Клиника зрения');
        $this->siteName = $generalSettings->site_name !== '' ? $generalSettings->site_name : $defaultName;
        $this->companyName = $defaultName;
        $this->siteUrl = rtrim((string) config('app.url'), '/');
        $this->shopEmail = $this->resolveEmail($this->city->email ?? null)
            ?? $this->resolveEmail(config('mail.from.address'));
    }

    public function generateDoctorsFeed(?City $city = null): string
    {
        if ($city) {
            $this->cityService->setCurrentCity($city);
            $this->city = $city;
            $this->shopEmail = $this->resolveEmail($this->city->email ?? null)
                ?? $this->resolveEmail(config('mail.from.address'));
        }

        $generatedAt = now()->format('Y-m-d H:i');

        $doctors = Doctor::query()
            ->with([
                'reviews' => static function ($query) {
                    $query->latest()->limit(5);
                },
            ])
            ->get();

        $services = Page::query()
            ->where('type', PageType::Services)
            ->where('active', true)
            ->orderBy('sorting')
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><yml_catalog/>');

        $xml->addAttribute('date', $generatedAt);

        $shop = $xml->addChild('shop');
        $this->addChildIfNotEmpty($shop, 'name', $this->sanitizeText($this->siteName));
        $this->addChildIfNotEmpty($shop, 'company', $this->sanitizeText($this->companyName));
        $this->addChildIfNotEmpty($shop, 'url', $this->siteUrl);
        $this->addChildIfNotEmpty($shop, 'picture', $this->getLogoUrl());
        if ($this->shopEmail !== null) {
            $this->addChildIfNotEmpty($shop, 'email', $this->shopEmail);
        }

        $this->addDoctors($shop->addChild('doctors'), $doctors);
        $this->addClinics($shop->addChild('clinics'));
        $this->addServices($shop->addChild('services'), $services);
        $this->addOffers($shop->addChild('offers'), $doctors, $services);

        return $xml->asXML();
    }

    /**
     * @param \SimpleXMLElement $doctorsElement
     * @param iterable<Doctor> $doctors
     */
    private function addDoctors(\SimpleXMLElement $doctorsElement, iterable $doctors): void
    {
        foreach ($doctors as $doctor) {
            $doctorElement = $doctorsElement->addChild('doctor');
            $doctorElement->addAttribute('id', 'doctor_' . $doctor->id);
            $doctorUrl = $this->ensureAbsoluteUrl($doctor->url);

            $this->addChildIfNotEmpty($doctorElement, 'internal_id', $doctor->uuid ?? (string) $doctor->id);
            $this->addChildIfNotEmpty($doctorElement, 'name', $this->sanitizeText($doctor->full_name));
            $description = $this->sanitizeText($doctor->excerpt) ?? $this->sanitizeText($doctor->bio);
            $this->addChildIfNotEmpty($doctorElement, 'description', $description);

            $nameParts = $this->splitNameParts($doctor->name);
            $this->addChildIfNotEmpty($doctorElement, 'first_name', $this->sanitizeText($nameParts['first'] ?? null));
            $this->addChildIfNotEmpty($doctorElement, 'surname', $this->sanitizeText($doctor->surname));
            $this->addChildIfNotEmpty($doctorElement, 'patronymic', $this->sanitizeText($nameParts['patronymic'] ?? null));

            $experienceYears = $this->extractYearsFromSeniority($doctor->extra['seniority'] ?? null);
            if ($experienceYears !== null) {
                $this->addChildIfNotEmpty($doctorElement, 'experience_years', (string) $experienceYears);

                if ($experienceYears > 0) {
                    $careerStartDate = now()->copy()->subYears($experienceYears)->startOfYear()->format('Y-01-01');
                    $this->addChildIfNotEmpty($doctorElement, 'career_start_date', $careerStartDate);
                }
            }

            $this->addChildIfNotEmpty($doctorElement, 'degree', $this->sanitizeText($doctor->extra['degree'] ?? null));
            $this->addChildIfNotEmpty($doctorElement, 'rank', $this->sanitizeText($doctor->extra['rank'] ?? null));
            $this->addChildIfNotEmpty($doctorElement, 'category', $this->sanitizeText($doctor->extra['category'] ?? null));

            if (isset($doctor->extra['education']) && is_array($doctor->extra['education'])) {
                foreach ($doctor->extra['education'] as $education) {
                    $organization = $this->sanitizeText($education['title'] ?? null);

                    if (isset($education['educational_institution']) && is_array($education['educational_institution'])) {
                        foreach ($education['educational_institution'] as $institution) {
                            $educationElement = $doctorElement->addChild('education');
                            $this->addChildIfNotEmpty($educationElement, 'organization', $organization);
                            $this->addChildIfNotEmpty($educationElement, 'finish_year', $this->sanitizeText($institution['year'] ?? null));
                            $this->addChildIfNotEmpty($educationElement, 'type', $this->sanitizeText($institution['level'] ?? null));
                            $this->addChildIfNotEmpty($educationElement, 'specialization', $this->sanitizeText($institution['specialty'] ?? null));
                        }
                    } else {
                        $educationElement = $doctorElement->addChild('education');
                        $this->addChildIfNotEmpty($educationElement, 'organization', $organization);
                    }
                }
            }

            if (isset($doctor->extra['professional_development']) && is_array($doctor->extra['professional_development'])) {
                foreach ($doctor->extra['professional_development'] as $cert) {
                    $certificateElement = $doctorElement->addChild('certificate');
                    $this->addChildIfNotEmpty($certificateElement, 'organization', $this->sanitizeText($cert['place'] ?? null));
                    $this->addChildIfNotEmpty($certificateElement, 'finish_year', $this->sanitizeText($cert['year'] ?? null));
                    $this->addChildIfNotEmpty($certificateElement, 'name', $this->sanitizeText($cert['title'] ?? null));
                }
            }

            $jobOrganization = $this->sanitizeText($this->companyName);
            $jobPosition = $this->sanitizeText($doctor->speciality);
            if ($jobOrganization !== null || $jobPosition !== null) {
                $jobElement = $doctorElement->addChild('job');
                $this->addChildIfNotEmpty($jobElement, 'organization', $jobOrganization);
                $this->addChildIfNotEmpty($jobElement, 'position', $jobPosition);
            }

            $reviews = $doctor->reviews;
            $this->addChildIfNotEmpty($doctorElement, 'reviews_total_count', (string) $reviews->count());

            foreach ($reviews as $review) {
                $reviewElement = $doctorElement->addChild('review');
                $this->addChildIfNotEmpty($reviewElement, 'date', $review->created_at->format('Y-m-d H:i:s'));
                $this->addChildIfNotEmpty($reviewElement, 'checked', $this->boolToXmlValue(true));
                $this->addChildIfNotEmpty($reviewElement, 'used_in_rating', $this->boolToXmlValue(true));
                $author = $this->sanitizeText($review->name);
                $this->addChildIfNotEmpty($reviewElement, 'author', $author);
                $this->addChildIfNotEmpty($reviewElement, 'author_id', $author !== null ? Str::slug($author) : null);
                if ($doctorUrl !== null) {
                    $this->addChildIfNotEmpty($reviewElement, 'url', $doctorUrl . '#reviews');
                }
                $this->addChildIfNotEmpty($reviewElement, 'comment', $this->sanitizeText($review->body));

                if ($review->rating !== null) {
                    $this->addChildIfNotEmpty($reviewElement, 'grade', (string) $review->rating);
                }
            }
        }
    }

    private function addClinics(\SimpleXMLElement $clinicsElement): void
    {
        $clinicElement = $clinicsElement->addChild('clinic');
        $clinicElement->addAttribute('id', 'clinic_1');

        $this->addChildIfNotEmpty($clinicElement, 'url', $this->siteUrl);
        $this->addChildIfNotEmpty($clinicElement, 'picture', $this->getLogoUrl());
        $this->addChildIfNotEmpty($clinicElement, 'name', $this->sanitizeText($this->companyName));
        $this->addChildIfNotEmpty($clinicElement, 'city', $this->sanitizeText($this->city->name ?? null));
        $this->addChildIfNotEmpty($clinicElement, 'address', $this->sanitizeText($this->city->address ?? null));
        $this->addChildIfNotEmpty($clinicElement, 'email', $this->resolveEmail($this->city->email ?? null));
        $this->addChildIfNotEmpty($clinicElement, 'phone', $this->sanitizeText($this->city->phone ?? null));
        $this->addChildIfNotEmpty($clinicElement, 'internal_id', config('zrenie-clinic.clinic_uuid'));
    }

    /**
     * @param \SimpleXMLElement $servicesElement
     * @param iterable<Page> $services
     */
    private function addServices(\SimpleXMLElement $servicesElement, iterable $services): void
    {
        foreach ($services as $service) {
            $serviceElement = $servicesElement->addChild('service');
            $serviceElement->addAttribute('id', 'service_' . $service->id);

            $this->addChildIfNotEmpty($serviceElement, 'name', $this->sanitizeText($service->title));
            $this->addChildIfNotEmpty($serviceElement, 'description', $this->sanitizeText($service->body_html));
            $this->addChildIfNotEmpty($serviceElement, 'internal_id', (string) $service->id);
            $this->addChildIfNotEmpty($serviceElement, 'url', $this->ensureAbsoluteUrl($service->getUrl()));

            $seo = $service->seo ?? [];
            if (is_array($seo) && isset($seo['gov_id']) && $seo['gov_id'] !== '') {
                $this->addChildIfNotEmpty($serviceElement, 'gov_id', $seo['gov_id']);
            }
        }
    }

    /**
     * @param \SimpleXMLElement $offersElement
     * @param iterable<Doctor> $doctors
     * @param iterable<Page> $services
     */
    private function addOffers(\SimpleXMLElement $offersElement, iterable $doctors, iterable $services): void
    {
        $offerId = 1;

        foreach ($doctors as $doctor) {
            $doctorUrl = $this->ensureAbsoluteUrl($doctor->url);
            $serviceIndex = 0;

            foreach ($services as $service) {
                $offerElement = $offersElement->addChild('offer');
                $offerElement->addAttribute('id', 'offer_' . $offerId);

                // Цена
                if ($basePrice = $this->extractServicePrice($service)) {
                    $this->addChildIfNotEmpty($offerElement, 'price', $basePrice);
                }

                // Добавляем URL врача
                $this->addChildIfNotEmpty($offerElement, 'url', $doctorUrl);
                $this->addChildIfNotEmpty($offerElement, 'oms', $this->boolToXmlValue(false));
                $this->addChildIfNotEmpty($offerElement, 'online_schedule', $this->boolToXmlValue(false));
                $this->addChildIfNotEmpty($offerElement, 'appointment', $this->boolToXmlValue(true));

//                $priceElement = $offerElement->addChild('price');
//                $priceElement->addChild('base_price', htmlspecialchars('2000'));
//                $priceElement->addChild('currency', htmlspecialchars('RUB'));

                // ✅ Явное добавление service и clinic, чтобы не потерялись
                $serviceElement = $offerElement->addChild('service');
                $serviceElement->addAttribute('id', 'service_' . $service->id);
                $serviceElement->addChild('name', htmlspecialchars($service->title ?? '', ENT_XML1, 'UTF-8'));

                $clinicElement = $offerElement->addChild('clinic');
                $clinicElement->addAttribute('id', 'clinic_1');

                // Врач
                $doctorElement = $clinicElement->addChild('doctor');
                $doctorElement->addAttribute('id', 'doctor_' . $doctor->id);

                $this->addChildIfNotEmpty($doctorElement, 'speciality', $doctor->speciality);
                $this->addChildIfNotEmpty($doctorElement, 'children_appointment', $this->boolToXmlValue(false));
                $this->addChildIfNotEmpty($doctorElement, 'adult_appointment', $this->boolToXmlValue(true));
                $this->addChildIfNotEmpty($doctorElement, 'house_call', $this->boolToXmlValue(false));
                $this->addChildIfNotEmpty($doctorElement, 'telemed', $this->boolToXmlValue(false));
                $this->addChildIfNotEmpty($doctorElement, 'is_base_service', $this->boolToXmlValue($serviceIndex === 0));

                $offerId++;
                $serviceIndex++;
            }
        }
    }

    private function extractServicePrice(Page $service): ?string
    {
        $seo = $service->seo ?? [];
        if (!is_array($seo)) {
            return null;
        }

        $price = $seo['price'] ?? $seo['base_price'] ?? null;

        if ($price === null || $price === '') {
            return null;
        }

        return (string) $price;
    }

    private function extractYearsFromSeniority(?string $seniority): ?int
    {
        if ($seniority === null || $seniority === '') {
            return null;
        }

        if (preg_match('/(\d+)/u', $seniority, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function saveFeedToFile(string $content): string
    {
        $this->deleteOldFeeds();

        $filename = 'doctors_feed.xml';

        Storage::disk('public')->put($filename, $content);

        return $filename;
    }

    private function deleteOldFeeds(): void
    {
        $feedFiles = Storage::disk('public')->files();
        $feedFiles = array_filter($feedFiles, static function ($file) {
            return str_starts_with($file, 'doctors_feed_') && str_ends_with($file, '.xml');
        });

        Storage::disk('public')->delete('doctors_feed.xml');

        foreach ($feedFiles as $file) {
            Storage::disk('public')->delete($file);
        }
    }

    private function addChildIfNotEmpty(\SimpleXMLElement $parent, string $name, ?string $value): ?\SimpleXMLElement
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(["&nbsp;", "\u{00A0}"], ' ', $value);
        $trimmed = trim($normalized);
        if ($trimmed === '') {
            return null;
        }

        return $parent->addChild($name, htmlspecialchars($trimmed, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    }

    private function sanitizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = strip_tags($decoded);

        $clean = Str::of($plain)
            ->replace("\u{00A0}", ' ')
            ->replace(["\r\n", "\n", "\r"], ' ')
            ->squish()
            ->value();

        return $clean === '' ? null : $clean;
    }

    /**
     * @return array{first: ?string, patronymic: ?string}
     */
    private function splitNameParts(?string $name): array
    {
        $parts = array_values(array_filter(preg_split('/\s+/u', (string) $name) ?: []));

        return [
            'first' => $parts[0] ?? null,
            'patronymic' => $parts[1] ?? null,
        ];
    }

    private function boolToXmlValue(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    private function ensureAbsoluteUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return $this->siteUrl . '/' . ltrim($url, '/');
    }

    private function getLogoUrl(): ?string
    {
        return $this->ensureAbsoluteUrl(asset('images/logo.svg'));
    }

    private function resolveEmail(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $email = trim($value);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
