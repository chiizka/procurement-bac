<?php

namespace Tests\Feature;

use App\Enums\DocStatus;
use App\Enums\Role;
use App\Models\AbstractOfQuotation;
use App\Models\PurchaseRequest;
use App\Models\TwgReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbstractOfQuotationTest extends TestCase
{
    use RefreshDatabase;

    private function source(string $status = 'created'): TwgReport
    {
        $user = User::factory()->create(['role' => Role::Bac]);
        $this->actingAs($user);
        $pr = PurchaseRequest::create(['pr_no' => 'PR-'.uniqid(), 'pr_date' => '2026-11-24', 'entity_name' => 'DOLE RO5', 'office_section' => 'CPO', 'purpose' => 'Satellite internet', 'status' => 'created', 'created_by' => $user->id]);
        $rfq = $pr->rfqs()->create(['rfq_no' => 'RFQ-'.uniqid(), 'rfq_date' => '2026-11-24', 'purpose' => 'Satellite internet', 'total_amount' => 102000, 'status' => 'created', 'created_by' => $user->id]);
        $item = $rfq->items()->create(['sort_order' => 1, 'line_type' => 'item', 'description' => 'Subscription', 'unit' => 'month', 'quantity' => 12, 'unit_cost' => 8500, 'amount' => 102000]);
        $twg = $rfq->twgReport()->create(['twg_no' => 'TWG-'.uniqid(), 'twg_date' => '2026-11-24', 'project_name' => 'Satellite internet', 'abc' => 102000, 'procurement_mode' => 'Small Value Procurement', 'criteria' => 'SCRQ', 'status' => $status, 'created_by' => $user->id]);
        $supplier = $twg->suppliers()->create(['sort_order' => 1, 'name' => 'Quoted Supplier', 'remarks' => 'SCRQ']);
        $supplier->quotes()->create(['rfq_item_id' => $item->id, 'unit_price' => 8000]);
        $twg->suppliers()->create(['sort_order' => 2, 'name' => 'No Quote Supplier', 'remarks' => 'Failed (No Quotation)']);
        return $twg;
    }

    private function data(TwgReport $twg, array $changes = []): array
    {
        $id = $twg->suppliers()->first()->id;
        return array_replace([
            'action' => 'draft', 'abstract_no' => 'ABS-'.uniqid(), 'abstract_date' => '2026-11-24',
            'project_name' => 'Satellite internet', 'project_location' => 'Catanduanes', 'implementing_office' => 'CPO',
            'bid_deadline_at' => '2026-11-24T09:00',
            'evaluations' => [$id => ['received_at' => '2026-11-24T08:19', 'eligibility' => 'pass', 'technical' => 'pass', 'financial' => 'fail', 'rank' => 1, 'remarks' => 'Manual remarks']],
            'recommendation' => 'award', 'award_description' => 'Responsive quotation', 'award_amount' => '96000.84',
            'signatories' => [['name' => 'BAC Member One', 'position' => 'Officer', 'role' => 'member'], ['name' => 'BAC Chair', 'position' => 'Director', 'role' => 'chairperson']],
            'hope_name' => 'Head Name',
        ], $changes);
    }

    public function test_draft_flow_uses_twg_totals_and_locks_source_then_delete_unlocks_it(): void
    {
        $twg = $this->source();
        $this->get(route('twg-reports.abstracts.create', $twg))->assertOk()->assertSee('No Quotation')->assertSee('value="96000"', false);
        $data = $this->data($twg, ['abc' => 1, 'procurement_mode' => 'Tampered']);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors()->assertRedirect();
        $abstract = AbstractOfQuotation::firstOrFail();
        $this->assertEquals('102000.00', $abstract->abc);
        $this->assertSame('Small Value Procurement', $abstract->procurement_mode);
        $this->get(route('abstracts.show', $abstract))->assertOk()->assertSee('Manual remarks')->assertSee('96,000.00')->assertSee('No Quote Supplier');
        $this->get(route('abstracts.edit', $abstract))->assertOk()->assertSee('BAC Member One');
        $this->get(route('abstracts.print', $abstract))->assertOk()->assertSee('Ninety-Six Thousand Pesos and 84/100 Only')->assertSee('Head Name')->assertSee('Approved')->assertSee('Disapproved');
        $this->get(route('twg-reports.edit', $twg))->assertForbidden();
        $this->put(route('twg-reports.update', $twg), [])->assertForbidden();
        $this->get(route('purchase-requests.show', $twg->rfq->purchase_request_id))->assertOk()->assertSee('Locked by Abstract')->assertSee(route('abstracts.print', $abstract), false);
        $this->delete(route('abstracts.destroy', $abstract))->assertRedirect();
        $this->get(route('twg-reports.edit', $twg))->assertOk();
    }

    public function test_only_created_twg_can_start_an_abstract_and_duplicates_return_existing(): void
    {
        $twg = $this->source('draft');
        $this->get(route('twg-reports.abstracts.create', $twg))->assertForbidden();
        $this->post(route('twg-reports.abstracts.store', $twg), $this->data($twg))->assertForbidden();
        $twg->update(['status' => 'created']);
        $data = $this->data($twg);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertRedirect();
        $abstract = AbstractOfQuotation::firstOrFail();
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertRedirect(route('abstracts.show', $abstract));
        $this->get(route('twg-reports.abstracts.create', $twg))->assertRedirect(route('abstracts.show', $abstract));
        $this->assertDatabaseCount('abstracts', 1);
    }

    public function test_created_number_and_status_stay_locked_and_only_drafts_can_be_deleted(): void
    {
        $twg = $this->source();
        $data = $this->data($twg, ['action' => 'created']);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors()->assertRedirect();
        $abstract = AbstractOfQuotation::firstOrFail();
        $this->put(route('abstracts.update', $abstract), array_replace($data, ['abstract_no' => 'CHANGED', 'action' => 'draft', 'recommendation' => 'failure', 'failure_reason' => 'noncompliant']))->assertSessionHasNoErrors()->assertRedirect();
        $abstract->refresh();
        $this->assertSame($data['abstract_no'], $abstract->abstract_no);
        $this->assertSame(DocStatus::Created, $abstract->status);
        $this->assertNull($abstract->award_amount);
        $this->assertNull($abstract->award_description);
        $this->delete(route('abstracts.destroy', $abstract))->assertForbidden();
        $this->get(route('abstracts.print', $abstract))->assertOk()->assertDontSee('96000.84');
    }

    public function test_invalid_supplier_keys_requirements_ranks_and_duplicate_numbers_are_rejected(): void
    {
        $twg = $this->source();
        $bad = $this->data($twg, ['evaluations' => [999999 => ['rank' => 1]]]);
        $this->post(route('twg-reports.abstracts.store', $twg), $bad)->assertSessionHasErrors('evaluations');
        $id = $twg->suppliers()->first()->id;
        $bad['evaluations'] = [$id => ['rank' => 0, 'eligibility' => 'invalid']];
        $this->post(route('twg-reports.abstracts.store', $twg), $bad)->assertSessionHasErrors(["evaluations.$id.rank", "evaluations.$id.eligibility"]);
        $data = $this->data($twg);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors();
        $other = $this->source();
        $this->post(route('twg-reports.abstracts.store', $other), $this->data($other, ['abstract_no' => $data['abstract_no']]))->assertSessionHasErrors('abstract_no');
    }

    public function test_procurement_can_view_but_cannot_write_abstracts(): void
    {
        $twg = $this->source();
        $data = $this->data($twg);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors();
        $abstract = AbstractOfQuotation::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => Role::Procurement]));
        $this->get(route('abstracts.show', $abstract))->assertOk()->assertDontSee('Delete draft');
        $this->get(route('abstracts.print', $abstract))->assertOk();
        $this->get(route('abstracts.edit', $abstract))->assertForbidden();
        $this->put(route('abstracts.update', $abstract), $data)->assertForbidden();
        $this->delete(route('abstracts.destroy', $abstract))->assertForbidden();
        $this->get(route('twg-reports.abstracts.create', $twg))->assertForbidden();
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertForbidden();
    }
    public function test_incomplete_recommendation_can_be_drafted_but_not_marked_created(): void
    {
        $twg = $this->source();
        $data = $this->data($twg, ['award_description' => '', 'award_amount' => '', 'signatories' => []]);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors();
        $abstract = AbstractOfQuotation::firstOrFail();
        $this->put(route('abstracts.update', $abstract), array_replace($data, ['action' => 'created']))
            ->assertSessionHasErrors(['award_description', 'award_amount']);
        $this->assertSame(DocStatus::Draft, $abstract->fresh()->status);
    }

    public function test_cleared_remarks_deadline_and_signatories_stay_blank_after_saving(): void
    {
        $twg = $this->source();
        $id = $twg->suppliers()->first()->id;
        $data = $this->data($twg, ['bid_deadline_at' => '', 'signatories' => [], 'evaluations' => [$id => ['remarks' => '']]]);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors();
        $abstract = AbstractOfQuotation::firstOrFail();
        $response = $this->get(route('abstracts.edit', $abstract))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('', $xpath->query('//textarea[@name="evaluations['.$id.'][remarks]"]')->item(0)->textContent);
        $this->assertSame('', $xpath->query('//input[@name="bid_deadline_at"]')->item(0)->getAttribute('value'));
        $this->assertSame([], $abstract->signatories);
    }

    public function test_bid_amount_edits_are_saved_on_created_abstract_without_changing_twg(): void
    {
        $twg = $this->source();
        $id = $twg->suppliers()->first()->id;
        $data = $this->data($twg, ['action' => 'created']);
        $data['evaluations'][$id]['bid_amount'] = '87500.25';
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors();
        $abstract = AbstractOfQuotation::firstOrFail();
        $this->assertSame('87500.25', $abstract->evaluations[$id]['bid_amount']);
        $this->assertSame('8000.00', $twg->suppliers()->first()->quotes()->first()->unit_price);
        $this->get(route('abstracts.print', $abstract))->assertOk()->assertSee('87,500.25')
            ->assertSee('Eligibility pass checked')->assertSee('Eligibility fail unchecked')
            ->assertSee('images/dole_bicol_logo.png', false)->assertSee('images/bagong_pilipinas_logo.png', false)
            ->assertSee('images/bureau_veritas_logo.png', false)->assertDontSee('Abstract No.');
        $data['evaluations'][$id]['bid_amount'] = '0';
        $this->put(route('abstracts.update', $abstract), $data)->assertSessionHasNoErrors();
        $this->assertSame('0.00', $abstract->fresh()->evaluations[$id]['bid_amount']);
        $data['evaluations'][$id]['bid_amount'] = '';
        $this->put(route('abstracts.update', $abstract), $data)->assertSessionHasNoErrors();
        $this->assertNull($abstract->fresh()->evaluations[$id]['bid_amount']);
        $this->get(route('abstracts.edit', $abstract))->assertOk()->assertSee('value=""', false);
    }

    public function test_invalid_bid_amounts_are_rejected_and_legacy_totals_still_load(): void
    {
        $twg = $this->source();
        $id = $twg->suppliers()->first()->id;
        $data = $this->data($twg);
        foreach (['-1', '1.234', '1000000000000', 'invalid'] as $amount) {
            $data['evaluations'][$id]['bid_amount'] = $amount;
            $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasErrors("evaluations.$id.bid_amount");
        }
        unset($data['evaluations'][$id]['bid_amount']);
        $this->post(route('twg-reports.abstracts.store', $twg), $data)->assertSessionHasNoErrors();
        $abstract = AbstractOfQuotation::firstOrFail();
        $this->assertSame('96000.00', $abstract->evaluations[$id]['bid_amount']);
        $abstract->update(['evaluations' => [$id => ['eligibility' => 'pass']]]);
        $this->get(route('abstracts.print', $abstract))->assertOk()->assertSee('96,000.00');
        $this->get(route('abstracts.edit', $abstract))->assertOk()->assertSee('value="96000"', false)->assertSee('type="checkbox"', false);
    }

}
