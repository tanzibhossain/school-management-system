<?php

namespace Tests\Feature\Transport;

use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Payment\Services\InvoiceService;

class TransportBillingTest extends TransportTestCase
{
    public function test_transport_fee_bills_only_riders_not_the_whole_class(): void
    {
        // A normal class-wide fee so invoices always have at least one line.
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'is_active' => true]);
        FeeItem::create([
            'school_id' => $this->school->id,
            'category_id' => $cat->id,
            'academic_year_id' => $this->year->id,
            'class_id' => null,
            'name' => 'Monthly Tuition',
            'amount' => 100,
            'frequency' => 'monthly',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        [$routeId] = $this->routeWithVehicle(capacity: 10, fare: 30);

        $rider = $this->makeStudent();
        $classmate = $this->makeStudent();

        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId, 'student_id' => $rider->id,
        ], $this->auth())->assertStatus(201);

        $invoices = app(InvoiceService::class);

        $riderInvoice = $invoices->generate(
            $this->school->id, $this->year->id, 1, $rider->id, $this->class->id, null, '2026-02-10', $this->admin->id
        );
        $classmateInvoice = $invoices->generate(
            $this->school->id, $this->year->id, 1, $classmate->id, $this->class->id, null, '2026-02-10', $this->admin->id
        );

        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $riderInvoice->id, 'name' => 'Monthly Tuition']);

        $riderNames = $riderInvoice->items->pluck('name')->all();
        $classmateNames = $classmateInvoice->items->pluck('name')->all();

        $this->assertTrue(collect($riderNames)->contains(fn ($n) => str_starts_with($n, 'Transport: ')));
        $this->assertFalse(collect($classmateNames)->contains(fn ($n) => str_starts_with($n, 'Transport: ')));

        // Rider billed 130, classmate 100.
        $this->assertEquals(130.0, (float) $riderInvoice->amount_due);
        $this->assertEquals(100.0, (float) $classmateInvoice->amount_due);
    }
}
