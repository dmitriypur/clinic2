<?php

namespace App\Console\Commands;

use App\Clinic;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportServicesFromLegacySourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-services-legacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import services and prices from legacy source (Clinic::prices())';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import from legacy source...');

        $legacyData = Clinic::prices();

        if (empty($legacyData)) {
            $this->error('No data received from legacy source.');
            return;
        }

        foreach ($legacyData as $categoryData) {
            $categoryName = $categoryData['name'];
            $categoryUuid = $categoryData['uid'] ?? Str::uuid();
            
            // 1. Find or create parent category
            // We try to find by existing UUID first (if we have it in DB) or by Title.
            // Since we are migrating, let's trust the Title for now if UUID mismatch or missing.
            
            $category = Service::withoutGlobalScope('city')
                ->whereNull('parent_id')
                ->where(function($q) use ($categoryName, $categoryUuid) {
                     $q->where('title', $categoryName)
                       ->orWhere('uuid', $categoryUuid);
                })
                ->first();

            if (!$category) {
                $this->info("Creating new category: {$categoryName}");
                $category = Service::create([
                    'title' => $categoryName,
                    'uuid' => $categoryUuid,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            } else {
                 $this->info("Updating category: {$categoryName}");
                 // Optional: update UUID if it was different? 
                 // Let's keep existing DB data safe, maybe just ensure it's active.
                 $category->update(['is_active' => true]);
            }

            if (!empty($categoryData['items'])) {
                foreach ($categoryData['items'] as $item) {
                    $serviceName = $item['item'];
                    $serviceUuid = $item['uid'] ?? Str::uuid();
                    $priceValue = (int) ($item['price1'] ?? 0);
                    $oldPriceValue = (int) ($item['price2'] ?? 0);
                    
                    // 2. Find or create child service
                    $service = Service::withoutGlobalScope('city')
                        ->where('parent_id', $category->id)
                        ->where(function($q) use ($serviceName, $serviceUuid) {
                            $q->where('title', $serviceName)
                              ->orWhere('uuid', $serviceUuid);
                        })
                        ->first();

                    if (!$service) {
                        $this->info("  Creating service: {$serviceName}");
                        $service = Service::create([
                            'parent_id' => $category->id,
                            'title' => $serviceName,
                            'uuid' => $serviceUuid,
                            'is_active' => true,
                            'sort_order' => 0,
                        ]);
                    } else {
                        // $this->info("  Found service: {$serviceName}");
                    }

                    // 3. Update price (Global price, city_id = null)
                    // Assuming legacy source sends global prices or Moscow prices as default.
                    // Based on previous context, we treat this as base price.
                    
                    ServicePrice::updateOrCreate(
                        [
                            'service_id' => $service->id,
                            'city_id' => null, // Global price
                        ],
                        [
                            'price' => $priceValue,
                            'old_price' => $oldPriceValue > 0 ? $oldPriceValue : null,
                            'price_from' => false, 
                        ]
                    );
                }
            }
        }

        $this->info('Import completed successfully.');
    }
}
