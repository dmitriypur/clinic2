<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AddUlidToAllDoctors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-ulid-to-all-doctors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add ULID to doctors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doctors = Doctor::query()->whereNull('ulid')->get();

        $doctors->each(fn($doctor) => $doctor->forceFill(['ulid' => Str::ulid()])->save());
    }
}
