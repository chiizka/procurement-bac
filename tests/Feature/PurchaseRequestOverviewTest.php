<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function pr(array $attributes = []): PurchaseRequest
    {
        return PurchaseRequest::create(array_merge([
            'pr_no' => 'PR-'.uniqid(), 'pr_date' => '2026-11-24',
            'entity_name' => 'DOLE RO5', 'office_section' => 'BAC',
            'purpose' => 'Office supplies', 'status' => 'created',
            'created_by' => User::factory()->create()->id,
        ], $attributes));
    }

    private function rfq(PurchaseRequest $pr, string $number, string $status = 'created')
    {
        return $pr->rfqs()->create([
            'rfq_no' => $number, 'rfq_date' => '2026-11-24',
            'purpose' => 'Supplies', 'status' => $status, 'created_by' => $pr->created_by,
        ]);
    }

    private function twg($rfq, string $status = 'draft')
    {
        return $rfq->twgReport()->create([
            'twg_no' => 'TWG-'.$rfq->rfq_no, 'twg_date' => '2026-11-24',
            'project_name' => 'Supplies', 'abc' => 100, 'procurement_mode' => 'SVP',
            'criteria' => 'SCRQ', 'status' => $status, 'created_by' => $rfq->created_by,
        ]);
    }

    public function test_overview_links_directly_to_documents_and_respects_rfq_lock(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $pr = $this->pr();
        $rfq = $this->rfq($pr, 'RFQ-1');
        $twg = $this->twg($rfq);
        $this->get(route('purchase-requests.show', $pr))->assertOk()
            ->assertSee('Key details')->assertSee('Documents')->assertSee('PR details and items')
            ->assertSee('Coming soon')->assertSee('Continue draft')->assertSee('Locked by TWG report')
            ->assertSee(route('rfqs.print', $rfq), false)
            ->assertSee(route('twg-reports.print', $twg), false)
            ->assertSee(route('twg-reports.edit', $twg), false)
            ->assertDontSee(route('rfqs.edit', $rfq), false);
        $this->get(route('rfqs.edit', $rfq))->assertForbidden();
    }

    public function test_drafts_and_group_members_do_not_offer_ineligible_creation(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Bac]));
        $draft = $this->pr(['status' => 'draft']);
        $this->get(route('purchase-requests.show', $draft))->assertOk()
            ->assertSee('Continue draft')->assertDontSee(route('purchase-requests.rfqs.create', $draft), false);
        $group = $this->pr(['is_group' => true]);
        $member = $this->pr(['group_id' => $group->id]);
        $this->get(route('purchase-requests.show', $member))->assertOk()
            ->assertSee('Open the group overview')->assertDontSee(route('purchase-requests.edit', $member), false)
            ->assertDontSee(route('purchase-requests.rfqs.create', $member), false);
        $rfq = $this->rfq($group, 'GROUP-DRAFT', 'draft');
        $this->get(route('purchase-requests.show', $group))->assertOk()
            ->assertSee('Locked by RFQs')->assertDontSee(route('purchase-requests.edit', $group), false)
            ->assertDontSee(route('rfqs.twg-reports.create', $rfq), false);
    }

    public function test_group_shows_each_rfq_and_unstarted_lot_and_counts_only_created_reports(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $pr = $this->pr(['is_group' => true]);
        $pr->items()->create(['sort_order' => 1, 'line_type' => 'lot_header', 'description' => 'Unstarted lot']);
        $first = $this->rfq($pr, 'LOT-A');
        $second = $this->rfq($pr, 'LOT-B');
        $this->twg($first, 'created');
        $this->twg($second);
        $deleted = $this->rfq($pr, 'DELETED');
        $this->twg($deleted, 'created');
        $deleted->delete();
        $this->get(route('purchase-requests.index'))->assertOk()->assertSee('TWG 1/2');
        $this->get(route('purchase-requests.show', $pr))->assertOk()
            ->assertSee('LOT-A')->assertSee('LOT-B')->assertSee('Unstarted lot')->assertSee('Create RFQ')
            ->assertDontSee('DELETED');
    }

    public function test_procurement_has_previews_without_write_actions(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Procurement]));
        $pr = $this->pr();
        $rfq = $this->rfq($pr, 'READ-ONLY');
        $twg = $this->twg($rfq);
        $this->get(route('purchase-requests.show', $pr))->assertOk()->assertSee('Read-only')
            ->assertSee(route('twg-reports.print', $twg), false)
            ->assertDontSee(route('twg-reports.edit', $twg), false)
            ->assertDontSee(route('purchase-requests.edit', $pr), false)
            ->assertDontSee(route('purchase-requests.rfqs.create', $pr), false);
    }

    public function test_created_rfq_without_report_offers_twg_creation(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Bac]));
        $pr = $this->pr();
        $rfq = $this->rfq($pr, 'READY');
        $this->get(route('purchase-requests.show', $pr))->assertOk()
            ->assertSee(route('rfqs.twg-reports.create', $rfq), false)
            ->assertSee(route('rfqs.edit', $rfq), false)->assertSee('Not started');
    }
}
