<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import services and prices from JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = storage_path('services_import.json');

        if (!File::exists($path)) {
            $this->error("File not found: {$path}");
            return;
        }

        $data = json_decode(File::get($path), true);

        if (!$data) {
            $this->error("Invalid JSON or empty file");
            return;
        }

        foreach ($data as $categoryData) {
            $categoryName = $categoryData['category_name'];
            
            // Find parent category
            $category = Service::withoutGlobalScope('city')
                ->whereNull('parent_id')
                ->where('title', $categoryName)
                ->first();

            if (!$category) {
                $this->warn("Category not found: {$categoryName}. Skipping...");
                continue;
            }

            $this->info("Processing category: {$categoryName}");

            foreach ($categoryData['services'] as $serviceData) {
                $serviceName = $serviceData['name'];
                $price = $serviceData['price'];
                $oldPrice = $serviceData['old_price'] ?? null;
                $priceFrom = $serviceData['price_from'] ?? false;
                $citySlug = $serviceData['city'] ?? null;
                
                // Find or create service
                $service = Service::withoutGlobalScope('city')->firstOrCreate(
                    [
                        'parent_id' => $category->id,
                        'title' => $serviceName,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'is_active' => true,
                        'sort_order' => 0,
                    ]
                );

                // Determine city_id
                $cityId = null;
                if ($citySlug) {
                    $city = City::where('slug', $citySlug)->first();
                    if ($city) {
                        $cityId = $city->id;
                    } else {
                        $this->warn("City not found for slug: {$citySlug}. Setting as global price (null).");
                    }
                }

                // Update or create price
                ServicePrice::updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'city_id' => $cityId,
                    ],
                    [
                        'price' => $price,
                        'old_price' => $oldPrice,
                        'price_from' => $priceFrom,
                    ]
                );
            }
        }

        $this->info('Import completed successfully.');
    }
}
