<?php

namespace App\Console\Commands;

use App\Services\DoctorImportFromBookingApiService;
use Illuminate\Console\Command;

class ImportDoctorsFromBookingApiCommand extends Command
{
    protected $signature = 'app:import-doctors-from-booking-api';

    protected $description = 'Import doctors from booking API by uuid (skip existing)';

    public function handle(DoctorImportFromBookingApiService $service): int
    {
        $this->info('Starting doctors import from booking API...');

        $stats = $service->import();

        $this->newLine();
        $this->info('Import finished.');
        $this->line('Cities total: ' . $stats['cities_total']);
        $this->line('Cities processed: ' . $stats['cities_processed']);
        $this->line('Allowed clinics processed: ' . $stats['clinics_allowed_processed']);
        $this->line('Doctors received: ' . $stats['doctors_received']);
        $this->line('Created: ' . $stats['created']);
        $this->line('Skipped (existing uuid): ' . $stats['skipped_existing']);
        $this->line('Skipped (missing external_id): ' . $stats['skipped_missing_external_id']);
        $this->line('Skipped (invalid external_id): ' . $stats['skipped_invalid_external_id']);
        $this->line('Skipped (duplicate in API payload): ' . $stats['skipped_duplicate_in_api']);

        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->warn('Errors:');
            foreach ($stats['errors'] as $error) {
                $this->line('- ' . $error);
            }
        }

        return self::SUCCESS;
    }
}
