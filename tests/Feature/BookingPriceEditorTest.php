<?php

namespace Tests\Feature;

use App\Filament\Resources\CityResource\Pages\EditCity;
use App\Filament\Resources\DoctorResource\Pages\CreateDoctor;
use App\Filament\Resources\DoctorResource\Pages\EditDoctor;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Staff;
use App\Services\BookingSiteDoctorsService;
use App\Support\BookingPriceSchedule;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BookingPriceEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 5)->startOfDay());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = Staff::query()->create(['name' => 'Manager', 'email' => 'prices@example.test', 'password' => 'password']);
        $permissions = ['view_doctor', 'update_doctor', 'view_any_doctor', 'view_city', 'update_city', 'view_any_city'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'staff');
        }
        $staff->givePermissionTo($permissions);
        $this->actingAs($staff, 'staff');
        DB::connection()->getPdo()->sqliteCreateFunction('JSON_UNQUOTE', static fn ($value) => $value, 1);
        // The existing contact migration mixes dropColumn and additions; SQLite's
        // table rebuild loses the additions. Supply those fields only in memory.
        foreach (['phone', 'email', 'address', 'postal_code', 'coordinates', 'schedule', 'metro', 'social_links', 'utm_phones', 'special_schedule', 'special_schedule_title', 'header_scripts', 'body_scripts'] as $column) {
            if (! Schema::hasColumn('cities', $column)) {
                Schema::table('cities', fn ($table) => $table->text($column)->nullable());
            }
        }
        if (! Schema::hasColumn('cities', 'show_special_schedule')) {
            Schema::table('cities', fn ($table) => $table->boolean('show_special_schedule')->default(false));
        }
    }

    public function test_doctor_form_schedules_price_and_booking_payload_refreshes(): void
    {
        $doctor = $this->doctor();
        $service = app(BookingSiteDoctorsService::class);
        $service->getPayloadByUuids([$doctor->uuid]);

        Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->assertFormSet(['extra._booking_price_editor.price.amount' => '2500'])
            ->fillForm(['extra._booking_price_editor.price.amount' => '3000', 'extra._booking_price_editor.price.starts_on' => '2026-11-01'])
            ->call('save')
            ->assertHasNoFormErrors();

        $extra = $doctor->fresh()->extra;
        $this->assertSame('2500', BookingPriceSchedule::price($extra, 'price', '2026-10-31'));
        $this->assertSame('3000', BookingPriceSchedule::price($extra, 'price', '2026-11-01'));
        $payload = $service->getPayloadByUuids([$doctor->uuid]);
        $this->assertSame('3000', data_get($payload, 'data.0.extra.price_periods.price.1.price'));
        $this->assertArrayNotHasKey('_booking_price_editor', $extra);
    }

    public function test_doctor_form_can_save_without_prices_and_without_required_dates(): void
    {
        $doctor = $this->doctor([]);
        Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->fillForm(['job_title' => 'Новая должность'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertArrayNotHasKey('price_periods', $doctor->fresh()->extra);
    }

    public function test_doctor_form_rejects_missing_start_and_invalid_end(): void
    {
        $doctor = $this->doctor();
        Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->fillForm(['extra._booking_price_editor.price.amount' => '3000', 'extra._booking_price_editor.price.starts_on' => null])
            ->call('save')
            ->assertHasFormErrors(['extra._booking_price_editor.price.starts_on']);
        $this->assertArrayNotHasKey('price_periods', $doctor->fresh()->extra);
    }

    public function test_invalid_price_does_not_create_a_redirect_or_save_other_changes(): void
    {
        $doctor = $this->doctor();
        Livewire::test(EditDoctor::class, ['record' => $doctor->id])->fillForm([
            'handle' => 'changed-before-invalid-price',
            'redirect' => true,
            'extra._booking_price_editor.price.amount' => 'invalid',
        ])->call('save')->assertHasFormErrors(['extra._booking_price_editor.price.amount']);
        $this->assertSame($doctor->handle, $doctor->fresh()->handle);
        $this->assertDatabaseCount('redirections', 0);
    }

    public function test_clear_price_also_discards_an_unfinished_new_schedule_row(): void
    {
        $doctor = $this->doctor();
        Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->callFormComponentAction('extra._booking_price_editor.price.scheduled', 'add')
            ->callFormComponentAction('extra._booking_price_editor.price.clear_priceAction', 'clear_price')
            ->call('save')->assertHasNoFormErrors();
        $this->assertNull(BookingPriceSchedule::price($doctor->fresh()->extra, 'price'));
    }

    public function test_a_second_open_doctor_form_cannot_overwrite_the_first_schedule(): void
    {
        $doctor = $this->doctor();
        $first = Livewire::test(EditDoctor::class, ['record' => $doctor->id]);
        $second = Livewire::test(EditDoctor::class, ['record' => $doctor->id]);
        $first->fillForm([
            'extra._booking_price_editor.price.amount' => '3000',
            'extra._booking_price_editor.price.starts_on' => '2026-10-06',
        ])->call('save')->assertHasNoFormErrors();
        $second->fillForm([
            'extra._booking_price_editor.price.amount' => '4000',
            'extra._booking_price_editor.price.starts_on' => '2026-10-07',
        ])->call('save')->assertHasFormErrors(['extra._booking_price_editor.price.amount']);
        $this->assertSame('3000', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-10-07'));
    }

    public function test_doctor_actions_edit_cancel_and_clear_a_pending_price(): void
    {
        $doctor = $this->doctor();
        $data = BookingPriceSchedule::hydrate($doctor->extra);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $data['_booking_price_editor']['price']['starts_on'] = '2026-11-01';
        $doctor->update(['extra' => BookingPriceSchedule::save($data, $doctor->extra)]);

        $component = Livewire::test(EditDoctor::class, ['record' => $doctor->id]);
        $rows = $component->get('data.extra._booking_price_editor.price.scheduled');
        $key = array_key_first($rows);
        $component->fillForm(["extra._booking_price_editor.price.scheduled.{$key}.price" => '3200'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('3200', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-11-01'));

        Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->fillForm(['extra._booking_price_editor.price.scheduled' => []])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('2500', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-11-01'));

        Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->callFormComponentAction('extra._booking_price_editor.price.clear_priceAction', 'clear_price')
            ->callFormComponentAction('extra._booking_price_editor.price_child.clear_priceAction', 'clear_price')
            ->call('save')->assertHasNoFormErrors();
        $this->assertNull(BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-11-01'));
        $this->assertNull(BookingPriceSchedule::price($doctor->fresh()->extra, 'price_child', '2026-11-01'));
    }

    public function test_new_doctor_can_have_a_future_price(): void
    {
        Permission::findOrCreate('create_doctor', 'staff');
        auth('staff')->user()->givePermissionTo('create_doctor');

        Livewire::test(CreateDoctor::class)->fillForm([
            'uuid' => (string) Str::uuid(), 'surname' => 'Новый', 'name' => 'Врач',
            'speciality' => 'Офтальмолог', 'job_title' => 'Врач', 'excerpt' => 'Описание', 'bio' => '<p>Информация</p>',
            'extra._booking_price_editor.price.amount' => '3000',
            'extra._booking_price_editor.price.starts_on' => '2026-11-01',
        ])->call('create')->assertHasNoFormErrors();

        $doctor = Doctor::withoutGlobalScopes()->where('surname', 'Новый')->firstOrFail();
        $this->assertNull(BookingPriceSchedule::price($doctor->extra, 'price', '2026-10-31'));
        $this->assertSame('3000', BookingPriceSchedule::price($doctor->extra, 'price', '2026-11-01'));
    }

    public function test_doctor_repeater_adds_multiple_prices_and_can_cancel_just_one(): void
    {
        $doctor = $this->doctor(['price' => '2000']);
        $path = 'extra._booking_price_editor.price.scheduled';
        $component = Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->callFormComponentAction($path, 'add');
        $key = array_key_first($component->get('data.'.$path));
        $component->assertFormSet([$path.'.'.$key.'.starts_on' => '2026-10-06'])
            ->fillForm([$path.'.'.$key.'.price' => '3000'])
            ->callFormComponentAction($path, 'add');
        $keys = array_keys($component->get('data.'.$path));
        $component->fillForm([
            $path.'.'.$keys[1].'.price' => '2000',
            $path.'.'.$keys[1].'.starts_on' => '2026-10-12',
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('2000', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-10-05'));
        $this->assertSame('3000', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-10-11'));
        $this->assertSame('2000', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-10-12'));

        $rows = $component->get('data.'.$path);
        $component->callFormComponentAction($path, 'delete', arguments: ['item' => array_key_first($rows)])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('2000', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-10-11'));
        $this->assertCount(1, array_values($component->get('data.'.$path)));
    }

    public function test_branch_repeater_adds_prices_without_a_current_local_price(): void
    {
        $city = City::query()->create(['name' => 'Москва', 'slug' => 'moskva', 'active' => true, 'phone' => '79990000000', 'branches' => [[
            'name' => 'Филиал', 'external_id' => 'branch-1',
        ]]]);
        $component = Livewire::test(EditCity::class, ['record' => $city->id]);
        $branchKey = array_key_first($component->get('data.branches'));
        $path = "branches.{$branchKey}._booking_price_editor.price.scheduled";
        $component->callFormComponentAction($path, 'add');
        $key = array_key_first($component->get('data.'.$path));
        $component->fillForm([$path.'.'.$key.'.price' => '2000'])
            ->callFormComponentAction($path, 'add');
        $keys = array_keys($component->get('data.'.$path));
        $component->fillForm([
            $path.'.'.$keys[1].'.price' => '3000',
            $path.'.'.$keys[1].'.starts_on' => '2026-10-12',
        ])->call('save')->assertHasNoFormErrors();
        $branch = $city->fresh()->branches[0];
        $this->assertNull(BookingPriceSchedule::price($branch, 'price', '2026-10-05'));
        $this->assertSame('2000', BookingPriceSchedule::price($branch, 'price', '2026-10-11'));
        $this->assertSame('3000', BookingPriceSchedule::price($branch, 'price', '2026-10-12'));

        $component = Livewire::test(EditCity::class, ['record' => $city->id]);
        $branchKey = array_key_first($component->get('data.branches'));
        $path = "branches.{$branchKey}._booking_price_editor.price.scheduled";
        $rows = $component->get('data.'.$path);
        $keys = array_keys($rows);
        $component->fillForm([$path.'.'.$keys[1].'.starts_on' => '2026-10-06'])
            ->call('save')->assertHasFormErrors([$path.'.'.$keys[1].'.starts_on']);
        $this->assertSame($branch['price_periods'], $city->fresh()->branches[0]['price_periods']);
    }

    public function test_repeater_rejects_duplicate_start_dates_and_invalid_end(): void
    {
        $doctor = $this->doctor();
        $path = 'extra._booking_price_editor.price.scheduled';
        Livewire::test(EditDoctor::class, ['record' => $doctor->id])->fillForm([$path => [
            'first' => ['price' => '2000', 'starts_on' => '2026-10-06', 'ends_on' => null],
            'second' => ['price' => '3000', 'starts_on' => '2026-10-06', 'ends_on' => null],
        ]])->call('save')->assertHasFormErrors([$path.'.second.starts_on']);
        Livewire::test(EditDoctor::class, ['record' => $doctor->id])->fillForm([$path => [
            'first' => ['price' => '2000', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-05'],
        ]])->call('save')->assertHasFormErrors([$path.'.first.ends_on']);
        $this->assertArrayNotHasKey('price_periods', $doctor->fresh()->extra);
    }

    public function test_typing_a_price_after_clear_action_creates_the_entered_price(): void
    {
        $doctor = $this->doctor();
        Livewire::test(EditDoctor::class, ['record' => $doctor->id])
            ->callFormComponentAction('extra._booking_price_editor.price.clear_priceAction', 'clear_price')
            ->set('data.extra._booking_price_editor.price.amount', '3000')
            ->fillForm(['extra._booking_price_editor.price.starts_on' => '2026-11-01'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('3000', BookingPriceSchedule::price($doctor->fresh()->extra, 'price', '2026-11-01'));
    }

    public function test_branch_clear_action_blocks_api_and_can_be_reset_before_save(): void
    {
        $city = City::query()->create(['name' => 'Москва', 'slug' => 'moskva', 'active' => true, 'phone' => '79990000000', 'branches' => [[
            'name' => 'Филиал', 'external_id' => 'branch-1', 'price' => '1800',
        ]]]);
        $component = Livewire::test(EditCity::class, ['record' => $city->id]);
        $key = array_key_first($component->get('data.branches'));
        $path = "branches.{$key}._booking_price_editor.price";
        $component->callFormComponentAction($path.'.clear_priceAction', 'clear_price')
            ->callFormComponentAction($path.'.reset_price_inputAction', 'reset_price_input')
            ->assertFormSet([$path.'.amount' => '1800'])
            ->callFormComponentAction($path.'.clear_priceAction', 'clear_price')
            ->call('save')->assertHasNoFormErrors();
        $branch = $city->fresh()->branches[0];
        $this->assertNull(BookingPriceSchedule::price($branch, 'price', '2026-10-05'));
        $result = app(\App\Services\BookingBranchEnrichmentService::class)->enrichBranches([
            ['external_id' => 'branch-1', 'price' => '9999'],
        ], $city->fresh());
        $this->assertNull($result[0]['price']);
        $this->assertArrayHasKey('price', $result[0]['price_periods']);
    }

    public function test_branch_form_preserves_other_branch_data_when_scheduling_price(): void
    {
        $city = City::query()->create(['name' => 'Москва', 'slug' => 'moskva', 'active' => true, 'phone' => '79990000000', 'branches' => [[
            'name' => 'Филиал', 'external_id' => 'branch-1', 'address' => 'Адрес', 'price' => '1800', 'price_child' => '1200',
        ]]]);

        $component = Livewire::test(EditCity::class, ['record' => $city->id]);
        $branches = $component->get('data.branches');
        $key = array_key_first($branches);
        $component->fillForm([
            "branches.{$key}._booking_price_editor.price.amount" => '2100',
            "branches.{$key}._booking_price_editor.price.starts_on" => '2026-11-01',
        ])->call('save')->assertHasNoFormErrors();

        $branch = $city->fresh()->branches[0];
        $this->assertSame('1800', BookingPriceSchedule::price($branch, 'price', '2026-10-31'));
        $this->assertSame('2100', BookingPriceSchedule::price($branch, 'price', '2026-11-01'));
        $this->assertSame('1200', $branch['price_child']);
        $this->assertSame('Адрес', $branch['address']);
        $this->assertArrayNotHasKey('_booking_price_editor', $branch);
        $this->assertArrayNotHasKey('_booking_price_source', $branch);
    }

    private function doctor(?array $extra = null): Doctor
    {
        return Doctor::query()->create([
            'uuid' => (string) Str::uuid(), 'surname' => 'Тестов', 'name' => 'Врач',
            'speciality' => 'Офтальмолог', 'job_title' => 'Врач', 'excerpt' => 'Описание',
            'bio' => '<p>Информация</p>', 'active' => true, 'publish' => true, 'to_terminate' => false,
            'extra' => $extra ?? ['price' => '2500', 'price_child' => '1500'],
        ]);
    }
}
