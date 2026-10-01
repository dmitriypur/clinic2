<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorUuidIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_lookup_is_indexed_without_requiring_unique_or_non_null_uuids(): void
    {
        $index = collect(Schema::getIndexes('doctors'))
            ->first(fn (array $index): bool => $index['columns'] === ['uuid']);

        $this->assertNotNull($index, 'Doctor UUID lookups need an index.');
        $this->assertFalse($index['unique']);

        $uuid = '00000000-0000-4000-8000-000000000001';
        foreach ([$uuid, $uuid, null, null] as $value) {
            DB::table('doctors')->insert([
                'uuid' => $value,
                'name' => 'Synthetic',
                'surname' => 'Doctor',
                'speciality' => 'Synthetic speciality',
                'job_title' => 'Synthetic job',
                'bio' => 'Synthetic bio',
            ]);
        }

        $this->assertSame(2, DB::table('doctors')->where('uuid', $uuid)->count());
        $this->assertSame(2, DB::table('doctors')->whereNull('uuid')->count());
        $this->assertSame(0, DB::table('doctors')->where('uuid', 'missing')->count());
    }
}
