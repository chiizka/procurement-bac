<x-app-layout>
  <x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <h2 class="font-semibold text-xl text-gray-800">RFQ {{ $rfq->rfq_no }}</h2>
        <x-status-chip :status="$rfq->status->value" />
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('rfqs.show', $rfq) }}" class="text-sm text-gray-600 hover:underline">Back to RFQ</a>
        <x-primary-button type="button" onclick="window.print()">Print / Save as PDF</x-primary-button>
      </div>
    </div>
  </x-slot>

  <style>
    .rfq-preview { overflow-x: auto; padding-bottom: 8px; }
    .rfq-paper {
      box-sizing: border-box;
      width: 8.5in;
      min-height: 14in;
      margin-inline: auto;
      padding: 11mm 5mm 12mm 8mm;
      background: white;
      color: #111;
      font: 8.03pt/1.2 "Times New Roman", serif;
      print-color-adjust: exact;
      overflow-wrap: anywhere;
    }
    .rfq-paper strong, .rfq-paper th { font-weight: bold; }
    /* The workbook prints at 73%. The fixed spacing below uses its scaled row heights. */
    .rfq-masthead { position: relative; min-height: 64pt; text-align: center; }
    .rfq-letterhead { font: 8.76pt/10.5pt Arial, sans-serif; }
    .rfq-letterhead div:nth-child(4) { font-style: italic; }
    .rfq-letterhead div:last-child { color: #0563c1; text-decoration: underline; }
    .rfq-masthead img { position: absolute; object-fit: contain; }
    .rfq-dole-logo { left: 11%; top: -4mm; width: 28mm; height: 24mm; }
    .rfq-bagong-logo { left: 75%; top: -1.5mm; width: 17mm; height: 17mm; }
    .rfq-bureau-logo { right: 3%; top: 2.7mm; width: 26mm; height: 13mm; }
    .rfq-paper h1 {
      min-height: 13.14pt;
      margin: 6.75pt 0 12.05pt;
      text-align: center;
      font-size: 8.76pt;
      font-weight: bold;
      text-decoration: underline;
    }
    .rfq-reference { margin-left: 69.3%; }
    .rfq-reference div { display: grid; grid-template-columns: 41% 1fr; min-height: 11pt; }
    .rfq-reference div:first-child { min-height: 13.14pt; }
    .rfq-reference dt, .rfq-reference dd { font-weight: bold; }
    .rfq-reference dd { text-align: center; border-bottom: 1px solid; }
    .rfq-instructions { margin-top: 11.5pt; }
    .rfq-instructions p { margin: 0; }
    .rfq-instructions p:first-child { min-height: 18.07pt; font-size: 8.76pt; }
    .rfq-instructions p + p { text-indent: 2em; }
    .rfq-instructions .rfq-invitation { margin-top: 11.5pt; min-height: 45.45pt; }
    .rfq-brand-instruction { margin-top: 13.69pt !important; min-height: 27.38pt; background: #92d050; }
    .rfq-budget-instruction { margin-top: 13.69pt !important; min-height: 13.69pt; }
    .rfq-eligibility { margin-top: 13.69pt !important; min-height: 24.09pt; }
    .rfq-chair { margin: 33.94pt 0 9.86pt auto; width: 35.9%; text-align: center; }
    .rfq-chair strong { display: block;  font-size: 8.76pt; text-decoration: underline; }
    .rfq-chair em { display: block; min-height: 5pt; }
    .rfq-purpose { display: flex; min-height: 9.86pt; white-space: pre-line; border: 1px solid; }
    .rfq-purpose strong { flex: 0 0 8.6%; padding-inline: 1pt; }
    .rfq-purpose span { text-decoration: underline; }
    .rfq-items { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .rfq-items th, .rfq-items td { border: .75pt solid; padding: 1pt 1.5pt; vertical-align: middle; }
    .rfq-items th { text-align: center; }
    .rfq-items thead tr:first-child { height: 10.4pt; }
    .rfq-items thead tr:last-child { height: 21.35pt; }
    .rfq-items .rfq-center { text-align: center; }
    .rfq-items .rfq-amount { text-align: right; font-variant-numeric: tabular-nums; }
    .rfq-description { white-space: pre-wrap; }
    .rfq-item-row { height: 27pt; }
    .rfq-lot-row { height: 14.78pt; }
    .rfq-end-row { height: 15.88pt; }
    .rfq-terms { font-weight: bold; }
    .rfq-delivery-area { height: 23.54pt; }
    .rfq-price-validity { height: 13.14pt; }
    .rfq-total { height: 14.78pt; }
    .rfq-items .rfq-total th { text-align: right; border-inline-end: .75pt solid; }
    .rfq-items .rfq-total td { text-align: center; }
    .rfq-footer { display: grid; grid-template-columns: 69.3% 30.7%; }
    .rfq-payment { padding-right: 2mm; }
    .rfq-footer p, .rfq-payment div { min-height: 10.95pt; }
    .rfq-footer .rfq-payment-note { max-width: 67%; min-height: 43.8pt; padding-top: 2pt; margin-bottom: 5pt; }
    .rfq-payment .rfq-bank-field {
      display: grid;
      grid-template-columns: 29% minmax(0, 1fr);
      margin-right: 100px;
      align-items: center;
      min-height: 15pt;
    }
    .rfq-bank-line { align-self: stretch; margin-bottom: 1pt; border-bottom: .75pt solid; }
    .rfq-tax-options { display: flex; gap: 3mm; margin: 10.95pt 0 10.95pt 20%; }
    .rfq-tax-option { display: inline-flex; align-items: center; gap: 1.5mm; white-space: nowrap; }
    .rfq-tax-box { display: inline-block; width: 5.5mm; height: 5.5mm; border: .75pt solid; }
    .rfq-payment-ref { display: grid; grid-template-columns: 17% minmax(0, 1fr); font-weight: bold; }
    .rfq-payment-value { border-bottom: .75pt solid; text-align: center; }
    .rfq-supplier p { border-top: .75pt solid; margin-top: 21.9pt; text-align: center; }
    .rfq-supplier p:first-child { margin-top: 32.85pt; }
    @page { size: legal portrait; margin: 11mm 5mm 12mm 8mm; }
    @media print {
      body > div { min-height: 0 !important; background: white !important; }
      body > div > nav, body > div > header { display: none; }
      .rfq-print-content { max-width: none; padding: 0 !important; }
      .rfq-preview { overflow: visible; padding: 0; }
      .rfq-paper { width: auto; min-height: 0; padding: 0; box-shadow: none !important; }
      .rfq-masthead, .rfq-chair, .rfq-items tr, .rfq-footer { break-inside: avoid; }
      .rfq-purpose { break-after: avoid; }
    }
  </style>

  <div class="rfq-print-content max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="rfq-preview" role="region" aria-label="Printable RFQ preview" tabindex="0">
      <article class="rfq-paper shadow-sm" aria-labelledby="rfq-title">
        <div class="rfq-masthead">
          <img class="rfq-dole-logo" src="{{ asset('images/dole_bicol_logo.png') }}" alt="DOLE Bicol">
          <div class="rfq-letterhead">
            <div>Republic of the Philippines</div>
            <div><strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong></div>
            <div>Regional Office No. 5</div>
            <div>ORD: 0981-461-8788 TSSD: 0963-206-0008 IMSD: 0912-330-4751</div>
            <div>ro5@dole.gov.ph</div>
          </div>
          <img class="rfq-bagong-logo" src="{{ asset('images/bagong_pilipinas_logo.png') }}" alt="Bagong Pilipinas">
          <img class="rfq-bureau-logo" src="{{ asset('images/bureau_veritas_logo.png') }}" alt="Bureau Veritas">
        </div>
        <h1 id="rfq-title">REQUEST FOR QUOTATION</h1>
        <dl class="rfq-reference">
          <div><dt>RFQ No.</dt><dd>{{ $rfq->rfq_no }}</dd></div>
          <div><dt>Date:</dt><dd>{{ $rfq->rfq_date?->format('F d, Y') }}</dd></div>
        </dl>

        <div class="rfq-instructions">
          <p><strong>GREETINGS:</strong></p>
          <p class="rfq-invitation">Please quote your best offer, tax included, on the items mentioned below and submit your sealed quotation/bid to DOLE RO5 or email at bacdolero5@gmail.com on or before <strong>{{ $rfq->deadline_at?->format('F d, Y h:i A') ?? '________________' }}</strong> at which time and date, all submitted quotations/bids will be opened, UNLESS, all suppliers requested to quote submit their RFQ earlier than the cut-off time.</p>
          <p class="rfq-brand-instruction">The supplier shall state the brand of each item being offered. Failure to do so may result in the disqualification of offer, in whole or in part, for non-responsiveness. Likewise, the payment details <strong><u>MUST</strong></u> be duly accomplished.</p>
          <p class="rfq-budget-instruction">Offers beyond the approved budget shall be automatically rejected.</p>
          <p class="rfq-eligibility">Interested suppliers are required to submit their valid PhilGEPS Registration Number and valid Mayor's / Business Permit upon submission of quotation. We reserve the right to reject any or all bids/quotations.</p>
        </div>

        <div class="rfq-chair">
          <strong>{{ $rfq->bac_chairperson }}</strong>
          <em>BAC Chairperson</em>
        </div>

        <p class="rfq-purpose"><strong>PURPOSE:</strong><span>{{ $rfq->purpose }}</span></p>
        <table class="rfq-items" aria-label="RFQ items and supplier proposal">
          <colgroup>
            <col style="width:8.6%"><col style="width:7.9%"><col style="width:40.3%"><col style="width:7.9%">
          </colgroup>
          <colgroup><col style="width:7.9%"><col style="width:11.6%"></colgroup>
          <colgroup><col style="width:7.9%"><col style="width:7.9%"></colgroup>
          <thead>
            <tr>
              <th rowspan="2" scope="col">Qty.</th>
              <th rowspan="2" scope="col">Unit</th>
              <th rowspan="2" scope="col">Description</th>
              <th rowspan="2" scope="col">Brand of Quoted Item/s</th>
              <th colspan="2" scope="colgroup">ABC</th>
              <th colspan="2" scope="colgroup">PROPOSAL</th>
            </tr>
            <tr>
              <th scope="col">Unit Cost</th><th scope="col">Total Cost</th>
              <th scope="col">Unit Cost</th><th scope="col">Total Cost</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($rfq->items as $item)
              <tr @class(['rfq-item-row' => $item->line_type === \App\Enums\LineType::Item, 'rfq-lot-row' => $item->line_type !== \App\Enums\LineType::Item])>
                @if ($item->line_type === \App\Enums\LineType::Item)
                  <td class="rfq-center">{{ $item->quantity !== null ? $item->quantity + 0 : '' }}</td>
                  <td class="rfq-center">{{ $item->unit }}</td>
                  @php
                    [$descriptionTitle, $descriptionDetails] = array_pad(
                        preg_split('/\r\n|\n|\r/', (string) $item->description, 2),
                        2,
                        null,
                    );
                  @endphp
                  <td class="rfq-description"><strong>{{ $descriptionTitle }}</strong>@if ($descriptionDetails !== null)<br>{{ $descriptionDetails }}@endif</td>
                  <td></td>
                  <td class="rfq-amount">{{ $item->unit_cost !== null ? '₱' . number_format($item->unit_cost, 2) : '' }}</td>
                  <td class="rfq-amount">{{ $item->amount !== null ? '₱' . number_format($item->amount, 2) : '' }}</td>
                  <td></td><td></td>
                @else
                  <td></td><td></td>
                  <td class="rfq-description"><strong>{{ $item->description }}</strong></td>
                  <td></td><td></td><td></td><td></td><td></td>
                @endif
              </tr>
            @endforeach
            @if ($rfq->items->isNotEmpty())
              <tr class="rfq-end-row"><td></td><td></td><td class="rfq-center"><strong>**nothing follows**</strong></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endif
          </tbody>
          <tbody>
            <tr class="rfq-end-row" aria-hidden="true"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr class="rfq-end-row rfq-terms">
              <td></td><td></td><td>Contract Duration: {{ $rfq->contract_duration }}</td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr class="rfq-delivery-area rfq-terms">
              <td></td><td></td><td>Delivery Area: {{ $rfq->delivery_area }}</td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr class="rfq-price-validity rfq-terms">
              <td></td><td></td><td>Price validity until: ________________</td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr class="rfq-total">
              <th colspan="3" scope="row">TOTAL</th>
              <td></td>
              <td colspan="2" class="rfq-amount"><strong>&#8369;{{ number_format($rfq->total_amount, 2) }}</strong></td>
              <td></td><td></td>
            </tr>
          </tbody>
        </table>

        <div class="rfq-footer">
          <div class="rfq-payment">
            <p class="rfq-payment-note"><strong>Note:</strong> Payment shall be made through Land Bank’s LDDAP-ADA/Bank Transfer facility, within thirty (30) days after submission of Billing and User Acceptance of the product. Bank Transfer fee shall be charged against the creditor’s account.</p>
            <div><strong>Payment Details:</strong></div>
            <div class="rfq-bank-field"><span>Banking Details:</span><span class="rfq-bank-line" aria-hidden="true"></span></div>
            <div class="rfq-bank-field"><span>Account No.:</span><span class="rfq-bank-line" aria-hidden="true"></span></div>
            <div class="rfq-bank-field"><span>Account Name:</span><span class="rfq-bank-line" aria-hidden="true"></span></div>
            <div class="rfq-bank-field"><span>Branch:</span><span class="rfq-bank-line" aria-hidden="true"></span></div>
            <div class="rfq-bank-field"><span>TIN No.:</span><span class="rfq-bank-line" aria-hidden="true"></span></div>
            <div class="rfq-tax-options">
              <span class="rfq-tax-option"><span class="rfq-tax-box" aria-hidden="true"></span> VAT</span>
              <span class="rfq-tax-option"><span class="rfq-tax-box" aria-hidden="true"></span> Non-VAT</span>
            </div>
            <div class="rfq-payment-ref">
              <span>PR No.:</span><span class="rfq-payment-value">{{ $rfq->purchaseRequest?->pr_no }}</span>
            </div>
            <div class="rfq-payment-ref">
              <span>End User:</span><span class="rfq-payment-value">{{ $rfq->purchaseRequest?->end_user }}</span>
            </div>
          </div>
          <div class="rfq-supplier">
            <p>Name of Company</p>
            <p>PhilGEPS Registration Number</p>
            <p>Address and Telephone No.</p>
            <p>Name of Authorized Person/Head<br>Signature Over Printed Name</p>
          </div>
        </div>
      </article>
    </div>
  </div>
</x-app-layout>
