<?php

namespace Tests\Unit;

use App\Support\BookingPriceForm;
use App\Support\BookingPriceSchedule;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingPriceScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 5)->startOfDay());
    }

    public function test_legacy_price_is_preserved_until_the_scheduled_start(): void
    {
        $original = ['price' => '2500', 'price_child' => '1500', 'other' => 'keep'];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $data['_booking_price_editor']['price']['starts_on'] = '2026-11-01';

        $saved = BookingPriceSchedule::save($data, $original);

        $this->assertSame('2500', BookingPriceSchedule::price($saved, 'price', '2026-10-31'));
        $this->assertSame('3000', BookingPriceSchedule::price($saved, 'price', '2026-11-01'));
        $this->assertSame('3000', BookingPriceSchedule::price($saved, 'price', '2027-01-01'));
        $this->assertSame('1500', $saved['price_child']);
        $this->assertSame('keep', $saved['other']);
        $this->assertArrayNotHasKey('_booking_price_editor', $saved);
    }

    public function test_saving_other_fields_does_not_manage_empty_or_existing_prices(): void
    {
        foreach ([[], ['price' => '2500', 'price_child' => '']] as $original) {
            $this->assertSame($original, BookingPriceSchedule::save(BookingPriceSchedule::hydrate($original), $original));
        }
    }

    public function test_new_price_without_a_previous_price_is_absent_before_start(): void
    {
        $data = BookingPriceSchedule::hydrate([]);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $data['_booking_price_editor']['price']['starts_on'] = '2026-11-01';
        $saved = BookingPriceSchedule::save($data, []);
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-10-31'));
        $this->assertSame('3000', BookingPriceSchedule::price($saved, 'price', '2026-11-01'));
    }

    public function test_end_is_inclusive_and_does_not_resurrect_the_old_price(): void
    {
        $original = ['price' => '2500'];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $data['_booking_price_editor']['price']['starts_on'] = '2026-11-01';
        $data['_booking_price_editor']['price']['ends_on'] = '2026-11-30';
        $saved = BookingPriceSchedule::save($data, $original);
        $this->assertSame('3000', BookingPriceSchedule::price($saved, 'price', '2026-11-30'));
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-12-01'));
    }

    public function test_pending_price_can_be_edited_and_cancelled_without_losing_original_price(): void
    {
        $saved = $this->scheduledPrice();
        $data = BookingPriceSchedule::hydrate($saved);
        $this->assertSame('2500', $data['_booking_price_editor']['price']['amount']);
        $this->assertSame('3000', $data['_booking_price_editor']['price']['scheduled'][0]['price']);
        $data['_booking_price_editor']['price']['scheduled'][0] = [
            'price' => '3200', 'starts_on' => '2026-11-05', 'ends_on' => null,
        ];
        $edited = BookingPriceSchedule::save($data, $saved);
        $this->assertSame('2500', BookingPriceSchedule::price($edited, 'price', '2026-11-01'));
        $this->assertSame('3200', BookingPriceSchedule::price($edited, 'price', '2026-11-05'));

        $data = BookingPriceSchedule::hydrate($edited);
        $data['_booking_price_editor']['price']['scheduled'] = [];
        $cancelled = BookingPriceSchedule::save($data, $edited);
        $this->assertSame('2500', BookingPriceSchedule::price($cancelled, 'price', '2026-12-01'));
    }

    public function test_clear_price_removes_pending_change_and_preserves_history(): void
    {
        $original = $this->scheduledPrice();
        $original['price_periods']['price'][] = ['price' => '4000', 'starts_on' => '2026-11-08', 'ends_on' => null];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['action'] = 'clear';
        $data['_booking_price_editor']['price']['amount'] = null;
        $saved = BookingPriceSchedule::save($data, $original);
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-10-05'));
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-11-01'));
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-11-08'));
        $this->assertSame('2500', BookingPriceSchedule::price($saved, 'price', '2026-10-04'));
        $this->assertNull(BookingPriceSchedule::pending($saved, 'price'));
    }

    public function test_clearing_an_already_empty_field_records_explicit_site_control(): void
    {
        $data = BookingPriceSchedule::hydrate([]);
        $data['_booking_price_editor']['price']['action'] = 'clear';
        $saved = BookingPriceSchedule::save($data, []);
        $this->assertArrayHasKey('price', $saved['price_periods']);
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-11-01'));
    }

    public function test_start_is_required_for_a_new_price(): void
    {
        $data = BookingPriceSchedule::hydrate([]);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $data['_booking_price_editor']['price']['starts_on'] = null;
        $this->expectException(ValidationException::class);
        BookingPriceSchedule::save($data, []);
    }

    public function test_end_cannot_precede_start(): void
    {
        $data = BookingPriceSchedule::hydrate([]);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $data['_booking_price_editor']['price']['starts_on'] = '2026-11-01';
        $data['_booking_price_editor']['price']['ends_on'] = '2026-10-31';
        $this->expectException(ValidationException::class);
        BookingPriceSchedule::save($data, []);
    }

    public function test_additional_pending_price_keeps_previous_and_replaces_it_on_its_start(): void
    {
        $original = $this->scheduledPrice();
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['amount'] = '3500';
        $data['_booking_price_editor']['price']['starts_on'] = '2026-11-08';
        $saved = BookingPriceSchedule::save($data, $original);
        $this->assertSame('2500', BookingPriceSchedule::price($saved, 'price', '2026-10-31'));
        $this->assertSame('3000', BookingPriceSchedule::price($saved, 'price', '2026-11-07'));
        $this->assertSame('3500', BookingPriceSchedule::price($saved, 'price', '2026-11-08'));
    }

    public function test_many_unsorted_prices_replace_previous_and_can_return_to_an_earlier_amount(): void
    {
        $original = ['price' => '2000'];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['scheduled'] = [
            ['price' => '2000', 'starts_on' => '2026-10-12', 'ends_on' => null],
            ['price' => '3000', 'starts_on' => '2026-10-06', 'ends_on' => '2026-12-01'],
            ['price' => '4000', 'starts_on' => '2026-10-07', 'ends_on' => null],
        ];
        $saved = BookingPriceSchedule::save($data, $original);
        foreach (['2026-10-05' => '2000', '2026-10-06' => '3000', '2026-10-07' => '4000', '2026-10-11' => '4000', '2026-10-12' => '2000', '2027-01-01' => '2000'] as $date => $price) {
            $this->assertSame($price, BookingPriceSchedule::price($saved, 'price', $date));
        }
        $data = BookingPriceSchedule::hydrate($saved);
        array_splice($data['_booking_price_editor']['price']['scheduled'], 1, 1);
        $cancelled = BookingPriceSchedule::save($data, $saved);
        $this->assertSame('3000', BookingPriceSchedule::price($cancelled, 'price', '2026-10-11'));
        $this->assertSame('2000', BookingPriceSchedule::price($cancelled, 'price', '2026-10-12'));
    }

    public function test_two_prices_cannot_start_on_the_same_day(): void
    {
        $data = BookingPriceSchedule::hydrate([]);
        $data['_booking_price_editor']['price']['scheduled'] = [
            ['price' => '2000', 'starts_on' => '2026-10-06', 'ends_on' => null],
            ['price' => '3000', 'starts_on' => '2026-10-06', 'ends_on' => null],
        ];
        $this->expectException(ValidationException::class);
        BookingPriceSchedule::save($data, []);
    }

    public function test_current_price_change_preserves_later_scheduled_prices(): void
    {
        $original = $this->scheduledPrice();
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['amount'] = '2700';
        $saved = BookingPriceSchedule::save($data, $original);
        $this->assertSame('2700', BookingPriceSchedule::price($saved, 'price', '2026-10-05'));
        $this->assertSame('3000', BookingPriceSchedule::price($saved, 'price', '2026-11-01'));
        $this->assertSame('2500', BookingPriceSchedule::price($saved, 'price', '2026-10-04'));
    }

    public function test_calendar_day_change_activates_price_without_rewriting_storage(): void
    {
        $original = $this->scheduledPrice();
        $original['price_periods']['price'][] = ['price' => '4000', 'starts_on' => '2026-11-08', 'ends_on' => null];
        $this->travelTo(now()->setDate(2026, 11, 1)->startOfDay());
        $data = BookingPriceSchedule::hydrate($original);
        $this->assertSame('3000', $data['_booking_price_editor']['price']['amount']);
        $this->assertCount(1, $data['_booking_price_editor']['price']['scheduled']);
        $this->assertSame('4000', $data['_booking_price_editor']['price']['scheduled'][0]['price']);
        $data['other'] = 'edited';
        $saved = BookingPriceSchedule::save($data, $original);
        $this->assertSame($original['price_periods'], $saved['price_periods']);
        $this->assertSame('2500', $saved['price']);
    }

    public function test_stale_schedule_only_edit_cannot_overwrite_a_changed_price(): void
    {
        $original = $this->scheduledPrice();
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['scheduled'][0]['price'] = '4000';
        $original['price_periods']['price'][1]['price'] = '3500';
        $this->expectException(ValidationException::class);
        BookingPriceSchedule::save($data, $original);
    }

    public function test_first_prices_can_be_scheduled_with_an_empty_current_price(): void
    {
        $data = BookingPriceSchedule::hydrate([]);
        $data['_booking_price_editor']['price']['scheduled'] = [
            ['price' => '2000', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-07'],
            ['price' => '3000', 'starts_on' => '2026-10-12', 'ends_on' => null],
        ];
        $saved = BookingPriceSchedule::save($data, []);
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-10-05'));
        $this->assertSame('2000', BookingPriceSchedule::price($saved, 'price', '2026-10-07'));
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-10-08'));
        $this->assertSame('3000', BookingPriceSchedule::price($saved, 'price', '2026-10-12'));
    }

    public function test_stale_editor_cannot_overwrite_another_price_change(): void
    {
        $original = ['price' => '2500'];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $this->expectException(ValidationException::class);
        BookingPriceSchedule::save($data, ['price' => '2700']);
    }

    public function test_same_amount_can_be_given_an_end_date_explicitly(): void
    {
        $original = ['price' => '2500'];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['action'] = 'edit_period';
        $data['_booking_price_editor']['price']['ends_on'] = '2026-11-30';
        $saved = BookingPriceSchedule::save($data, $original);
        $this->assertSame('2500', BookingPriceSchedule::price($saved, 'price', '2026-11-30'));
        $this->assertNull(BookingPriceSchedule::price($saved, 'price', '2026-12-01'));
        $this->assertSame('2026-11-30', BookingPriceSchedule::hydrate($saved)['_booking_price_editor']['price']['current_ends_on']);
    }

    public function test_doctor_legacy_one_category_price_is_kept_before_a_future_change(): void
    {
        $original = ['price' => '2500'];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price_child']['amount'] = '1500';
        $data['_booking_price_editor']['price_child']['starts_on'] = '2026-11-01';
        $saved = BookingPriceSchedule::save($data, $original, 'data.extra', true);
        $this->assertSame('2500', BookingPriceSchedule::price($saved, 'price_child', '2026-10-31'));
        $this->assertSame('1500', BookingPriceSchedule::price($saved, 'price_child', '2026-11-01'));
    }

    public function test_branch_reordering_and_new_branch_do_not_mix_price_history(): void
    {
        $original = [
            ['external_id' => 'a', 'price' => '2500'],
            ['external_id' => 'b', 'price' => '1800'],
        ];
        $data = BookingPriceForm::hydrateCity(['branches' => $original]);
        $data['branches'] = array_reverse($data['branches']);
        $data['branches'][0]['_booking_price_editor']['price']['amount'] = '2000';
        $data['branches'][0]['_booking_price_editor']['price']['starts_on'] = '2026-11-01';
        $data['branches'][] = BookingPriceSchedule::hydrate(['external_id' => 'c']);
        $data['branches'][2]['_booking_price_editor']['price']['amount'] = '1500';
        $data['branches'][2]['_booking_price_editor']['price']['starts_on'] = '2026-11-01';
        $saved = BookingPriceForm::saveCity($data, $original)['branches'];

        $this->assertSame('b', $saved[0]['external_id']);
        $this->assertSame('1800', BookingPriceSchedule::price($saved[0], 'price', '2026-10-31'));
        $this->assertSame('2000', BookingPriceSchedule::price($saved[0], 'price', '2026-11-01'));
        $this->assertSame('2500', $saved[1]['price']);
        $this->assertNull(BookingPriceSchedule::price($saved[2], 'price', '2026-10-31'));
        $this->assertSame('1500', BookingPriceSchedule::price($saved[2], 'price', '2026-11-01'));
    }

    public function test_stale_branch_cannot_overwrite_changed_price_history(): void
    {
        $original = [['external_id' => 'a', 'price' => '2500']];
        $data = BookingPriceForm::hydrateCity(['branches' => $original]);
        $original[0]['price'] = '3000';

        $this->expectException(ValidationException::class);
        BookingPriceForm::saveCity($data, $original);
    }

    private function scheduledPrice(): array
    {
        $original = ['price' => '2500'];
        $data = BookingPriceSchedule::hydrate($original);
        $data['_booking_price_editor']['price']['amount'] = '3000';
        $data['_booking_price_editor']['price']['starts_on'] = '2026-11-01';

        return BookingPriceSchedule::save($data, $original);
    }
}
