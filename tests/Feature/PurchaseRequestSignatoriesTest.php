<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestSignatoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_signatories_are_saved_updated_and_printed(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $data = [
            'action' => 'draft', 'pr_no' => 'TEST-SIGN', 'pr_date' => '2026-11-23',
            'entity_name' => 'DOLE RO5', 'office_section' => 'BAC', 'purpose' => 'Supplies',
            'end_user' => 'End User', 'requested_by' => 'Requester',
            'requested_by_position' => 'Officer', 'certified_funds_available' => 'Certifier',
            'certified_funds_available_position' => 'Accountant',
            'approved_by' => 'Approver', 'approved_by_position' => 'Director',
        ];
        $this->post(route('purchase-requests.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $pr = PurchaseRequest::where('pr_no', 'TEST-SIGN')->firstOrFail();
        foreach (array_diff_key($data, ['action' => true]) as $field => $value) {
            $this->assertEquals($value, $field === 'pr_date' ? $pr->pr_date->format('Y-m-d') : $pr->$field);
        }
        $this->get('/purchase-requests/'.$pr->id.'/print')->assertOk()
            ->assertSee('Requester')->assertSee('Accountant')->assertSee('Director')
            ->assertDontSee('JOAN B. REBUTICA')->assertDontSee('ANGELICA JOY L. NAPOLIS');
        foreach (['requested_by', 'requested_by_position', 'certified_funds_available', 'certified_funds_available_position', 'approved_by', 'approved_by_position'] as $field) {
            $data[$field] = '';
        }
        $this->put(route('purchase-requests.update', $pr), $data)->assertSessionHasNoErrors()->assertRedirect();
        $pr->refresh();
        $this->assertNull($pr->requested_by);
        $this->assertNull($pr->certified_funds_available);
        $this->assertNull($pr->approved_by_position);
        $this->assertSame('End User', $pr->end_user);
        $this->get('/purchase-requests/'.$pr->id.'/print')->assertOk()->assertDontSee('Requester')->assertDontSee('Director');
    }
}
