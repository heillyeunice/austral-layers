<?php

namespace Tests\Feature;

use App\Models\PrinterPreset;
use App\Models\PrintQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_is_available_only_to_business_staff(): void
    {
        $this->get(route('admin.business.print-calculator.index'))
            ->assertRedirect(route('admin.login'));

        $customer = $this->makeUser(null);
        $this->actingAs($customer)
            ->get(route('admin.business.print-calculator.index'))
            ->assertForbidden();

        $admin = $this->makeUser(User::ROLE_ADMIN);
        $this->actingAs($admin)
            ->get(route('admin.business.print-calculator.index'))
            ->assertOk()
            ->assertSee('3D print cost calculator')
            ->assertSee('Packaging fee')
            ->assertSee('₱30')
            ->assertSee('₱50')
            ->assertSee('business-document-order-summary');
    }

    public function test_quotation_pdf_uses_packaging_before_failure_and_profit(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $preset = PrinterPreset::query()->create([
            'brand' => 'Example',
            'model' => 'Test printer',
            'power_watts' => 1000,
            'price' => 6000,
            'lifespan_hours' => 3000,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.business.print-calculator.quotation'), [
                ...$this->validInputs(),
                'printer_preset_id' => $preset->id,
                'printer_brand' => 'Example',
                'printer_model' => 'Test printer',
                'packaging_fee' => '30',
                'failure_rate' => 0,
                'profit_margin' => 0,
            ]);

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="3d-print-quotation-1.pdf"');
        $this->assertSame('%PDF', substr($response->getContent(), 0, 4));
        $this->assertDatabaseHas('print_quotations', [
            'item_name' => 'Key holder',
            'printer_preset_id' => $preset->id,
            'packaging_fee' => 30,
            'subtotal' => 198,
            'failure_rate' => 30,
            'profit_margin' => 200,
            'failure_cost' => 59.4,
            'profit_amount' => 514.8,
            'final_price' => 772.2,
            'view_mode' => 'internal',
        ]);
        $this->assertSame(1, PrintQuotation::query()->count());
        $this->view('admin.business.print-calculator.quotation', [
            'quotation' => PrintQuotation::query()->firstOrFail(),
        ])
            ->assertSee('Internal cost breakdown')
            ->assertSee('Electricity rate');
        $this->get(route('admin.business.print-calculator.quotation.download', PrintQuotation::query()->firstOrFail()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_quote_validation_rejects_unsupported_packaging_and_zero_printer_lifespan(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $this->actingAs($admin)
            ->from(route('admin.business.print-calculator.index'))
            ->post(route('admin.business.print-calculator.quotation'), [
                ...$this->validInputs(),
                'packaging_fee' => '20',
                'printer_lifespan_hours' => '0',
            ])
            ->assertRedirect(route('admin.business.print-calculator.index'))
            ->assertSessionHasErrors(['packaging_fee', 'printer_lifespan_hours']);

        $this->assertDatabaseCount('print_quotations', 0);
    }

    public function test_calculator_can_prefill_a_new_order_with_the_fixed_price(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->post(route('admin.business.print-calculator.order'), [
                ...$this->validInputs(),
                'failure_rate' => 0,
                'profit_margin' => 0,
            ])
            ->assertRedirect(route('admin.business.orders.create'))
            ->assertSessionHasInput('total_price', '655.20')
            ->assertSessionHasInput('customer_name', 'Test customer')
            ->assertSessionHasInput('item_name', 'Key holder')
            ->assertSessionHas('business_saved');

        $this->get(route('admin.business.orders.create'))
            ->assertOk()
            ->assertSee('value="655.20"', false)
            ->assertSee('Filament: PLA')
            ->assertSee('Packaging: included.')
            ->assertDontSee('200%');
        $this->assertDatabaseCount('business_orders', 0);
    }

    public function test_client_view_omits_internal_cost_inputs_and_breakdown(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $this->actingAs($admin)
            ->post(route('admin.business.print-calculator.quotation'), [
                ...$this->validInputs(),
                'view_mode' => 'client',
            ])
            ->assertOk();

        $this->view('admin.business.print-calculator.quotation', [
            'quotation' => PrintQuotation::query()->firstOrFail(),
        ])
            ->assertSee('ORDER SUMMARY')
            ->assertSee('50% Down Payment')
            ->assertSee('Total quotation amount')
            ->assertDontSee('Internal cost breakdown')
            ->assertDontSee('Electricity rate')
            ->assertDontSee('Markup (')
            ->assertDontSee('200%');
    }

    public function test_printer_presets_can_be_added_and_removed(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $this->actingAs($admin)
            ->get(route('admin.business.print-calculator.printers'))
            ->assertOk()
            ->assertSee('Saved printers');

        $this->post(route('admin.business.print-calculator.printers.store'), [
            'brand' => 'Example',
            'model' => 'Model 1',
            'power_watts' => 220,
            'price' => 12000,
            'lifespan_hours' => 5000,
        ])->assertRedirect(route('admin.business.print-calculator.printers'));

        $preset = PrinterPreset::query()->firstOrFail();
        $this->assertDatabaseHas('printer_presets', ['id' => $preset->id, 'model' => 'Model 1']);

        $this->put(route('admin.business.print-calculator.printers.update', $preset), [
            'brand' => 'Example',
            'model' => 'Model 2',
            'power_watts' => 240,
            'price' => 12500,
            'lifespan_hours' => 5500,
        ])->assertRedirect(route('admin.business.print-calculator.printers'));
        $this->assertDatabaseHas('printer_presets', ['id' => $preset->id, 'model' => 'Model 2']);

        $this->delete(route('admin.business.print-calculator.printers.destroy', $preset))
            ->assertRedirect(route('admin.business.print-calculator.printers'));
        $this->assertDatabaseMissing('printer_presets', ['id' => $preset->id]);
    }

    private function validInputs(): array
    {
        return [
            'customer_name' => 'Test customer',
            'item_name' => 'Key holder',
            'filament_type' => 'PLA',
            'weight_grams' => 100,
            'filament_cost_per_kg' => 1000,
            'print_hours' => 1,
            'print_minutes' => 30,
            'electricity_rate' => 10,
            'printer_power_watts' => 1000,
            'printer_price' => 6000,
            'printer_lifespan_hours' => 3000,
            'labor_rate' => 100,
            'labor_hours' => 0,
            'labor_minutes' => 30,
            'packaging_fee' => '0',
            'view_mode' => 'internal',
        ];
    }

    private function makeUser(?string $role): User
    {
        $user = new User;
        $user->forceFill([
            'name' => ucfirst($role ?? 'regular').' User',
            'email' => ($role ?? 'regular').'-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ])->save();

        return $user;
    }
}
