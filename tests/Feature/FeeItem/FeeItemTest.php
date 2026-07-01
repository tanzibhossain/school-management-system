<?php

namespace Tests\Feature\FeeItem;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeDiscount;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeItemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private AcademicYear $year;
    private SchoolClass $class;
    private FeeCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school   = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin    = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year     = AcademicYear::create([
            'school_id'  => $this->school->id,
            'year'       => '2026',
            'is_current' => true,
        ]);

        $this->class    = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 1']);
        $this->category = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Academic']);
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    public function test_admin_can_create_fee_category(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/fee-categories', ['name' => 'Transport'])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Transport']);

        $this->assertDatabaseHas('fee_categories', [
            'name'      => 'Transport',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_admin_can_create_fee_item(): void
    {
        $response = $this->withToken($this->token())
            ->postJson('/api/v2/fee-items', [
                'category_id'      => $this->category->id,
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'name'             => 'Tuition Fee',
                'amount'           => 5000,
                'frequency'        => 'monthly',
                'due_day'          => 10,
                'is_mandatory'     => true,
            ]);

        $response->assertCreated()->assertJsonFragment(['name' => 'Tuition Fee', 'amount' => '5000.00']);

        $this->assertDatabaseHas('fee_items', [
            'name'             => 'Tuition Fee',
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
        ]);
    }

    public function test_null_class_id_item_applies_to_all_classes(): void
    {
        FeeItem::create([
            'school_id'        => $this->school->id,
            'category_id'      => $this->category->id,
            'academic_year_id' => $this->year->id,
            'class_id'         => null,
            'name'             => 'School Development Fee',
            'amount'           => 1000,
            'frequency'        => 'yearly',
            'is_mandatory'     => true,
            'is_active'        => true,
        ]);

        // A class-specific query must include null-class_id items
        $items = FeeItem::where('school_id', $this->school->id)
            ->where('academic_year_id', $this->year->id)
            ->where(function ($q): void {
                $q->whereNull('class_id')->orWhere('class_id', $this->class->id);
            })
            ->get();

        $this->assertCount(1, $items);
        $this->assertEquals('School Development Fee', $items->first()->name);
    }

    public function test_admin_can_create_fee_discount(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/fee-discounts', [
                'name'       => 'Sibling Discount',
                'type'       => 'percentage',
                'value'      => 10,
                'max_amount' => 500,
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Sibling Discount', 'type' => 'percentage']);
    }

    public function test_percentage_discount_respects_cap(): void
    {
        $discount = FeeDiscount::create([
            'school_id'  => $this->school->id,
            'name'       => 'Merit Scholarship',
            'type'       => 'percentage',
            'value'      => 20,
            'max_amount' => 800,
            'is_active'  => true,
        ]);

        // 20% of 5000 = 1000, capped at 800
        $this->assertEquals(800.0, $discount->calculate(5000));

        // 20% of 2000 = 400, under cap
        $this->assertEquals(400.0, $discount->calculate(2000));
    }

    public function test_fixed_discount_cannot_exceed_fee_amount(): void
    {
        $discount = FeeDiscount::create([
            'school_id' => $this->school->id,
            'name'      => 'Staff Child',
            'type'      => 'fixed',
            'value'     => 3000,
            'is_active' => true,
        ]);

        // Discount is capped at the fee amount
        $this->assertEquals(1500.0, $discount->calculate(1500));
        $this->assertEquals(2500.0, $discount->calculate(2500));
    }

    public function test_duplicate_fee_items_to_next_year(): void
    {
        FeeItem::create([
            'school_id'        => $this->school->id,
            'category_id'      => $this->category->id,
            'academic_year_id' => $this->year->id,
            'name'             => 'Tuition Fee',
            'amount'           => 5000,
            'frequency'        => 'monthly',
            'is_mandatory'     => true,
            'is_active'        => true,
        ]);

        $nextYear = AcademicYear::create([
            'school_id'  => $this->school->id,
            'year'       => '2027',
            'is_current' => false,
        ]);

        $this->withToken($this->token())
            ->postJson('/api/v2/fee-items/duplicate', [
                'from_academic_year_id' => $this->year->id,
                'to_academic_year_id'   => $nextYear->id,
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => '1 fee items duplicated.']);

        $this->assertDatabaseHas('fee_items', [
            'name'             => 'Tuition Fee',
            'academic_year_id' => $nextYear->id,
        ]);
    }

    public function test_fee_item_list_is_filterable_by_year(): void
    {
        FeeItem::create([
            'school_id' => $this->school->id, 'category_id' => $this->category->id,
            'academic_year_id' => $this->year->id, 'name' => 'Fee Y1',
            'amount' => 1000, 'frequency' => 'monthly', 'is_mandatory' => true, 'is_active' => true,
        ]);

        $nextYear = AcademicYear::create([
            'school_id'  => $this->school->id,
            'year'       => '2027',
            'is_current' => false,
        ]);

        FeeItem::create([
            'school_id' => $this->school->id, 'category_id' => $this->category->id,
            'academic_year_id' => $nextYear->id, 'name' => 'Fee Y2',
            'amount' => 1000, 'frequency' => 'monthly', 'is_mandatory' => true, 'is_active' => true,
        ]);

        $response = $this->withToken($this->token())
            ->getJson("/api/v2/fee-items?academic_year_id={$this->year->id}")
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Fee Y1', $names);
        $this->assertNotContains('Fee Y2', $names);
    }
}
