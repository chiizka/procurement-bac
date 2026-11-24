<x-app-layout>
  <x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <h2 class="font-semibold text-xl text-gray-800">TWG Report {{ $twg->twg_no }}</h2>
        <x-status-chip :status="$twg->status->value" />
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('twg-reports.show', $twg) }}" class="text-sm text-gray-600 hover:underline">Back to report</a>
        <x-primary-button type="button" onclick="window.print()">Print / Save as PDF</x-primary-button>
      </div>
    </div>
  </x-slot>

  @php
    $rfq = $twg->rfq;
    $suppliers = $twg->suppliers;
    $cols = max($suppliers->count(), 1);               // at least one (empty) supplier column
    $supplierWidth = round(50 / $cols, 2);             // suppliers share the right half of the grid
    $priceMaps = $suppliers->mapWithKeys(fn ($s) => [$s->id => $s->quotes->keyBy('rfq_item_id')]);
    $itemNo = 0;
  @endphp

  <style>
    .twg-preview { overflow-x: auto; padding-bottom: 8px; }
    .twg-paper {
      box-sizing: border-box;
      width: 210mm;
      min-height: 297mm;
      margin-inline: auto;
      padding: 10mm 8mm 12mm 10mm;
      background: white;
      color: #111;
      font: 8pt/1.3 Arial, Helvetica, sans-serif;
      print-color-adjust: exact;
      overflow-wrap: anywhere;
    }
    .twg-paper strong, .twg-paper th { font-weight: bold; }

    /* letterhead (same as the RFQ) */
    .twg-masthead { position: relative; min-height: 58pt; text-align: center; }
    .twg-letterhead { font: 8pt/10.5pt Arial, sans-serif; }
    .twg-letterhead div:nth-child(1), .twg-letterhead div:nth-child(4) { font-style: italic; }
    .twg-letterhead div:last-child { color: #0563c1; text-decoration: underline; }
    .twg-masthead img { position: absolute; object-fit: contain; }
    .twg-dole-logo { left: 18%; top: 1mm; width: 20mm; height: 18mm; }
    .twg-bagong-logo { left: 71%; top: 0; width: 14mm; height: 14mm; }
    .twg-bureau-logo { right: 7%; top: 1mm; width: 20mm; height: 12mm; }

    .twg-paper h1 { margin: 18pt 0 12pt; text-align: center; font-size: 9pt; font-weight: bold; }
    .twg-reference { margin-left: 62%; font-weight: bold; }
    .twg-reference div { min-height: 12pt; }

    .twg-info { display: grid; grid-template-columns: 22% 1fr; margin: 12pt 0 14pt; row-gap: 5pt; }
    .twg-info dt { font-weight: bold; }
    .twg-info dd { margin: 0; white-space: pre-line; }

    /* quotation grid */
    .twg-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .twg-grid th, .twg-grid td { border: .75pt solid #111; padding: 1.5pt 3pt; vertical-align: middle; height: 15pt; }
    .twg-grid th { text-align: center; }
    .twg-grid .twg-desc { white-space: pre-wrap; }
    .twg-grid .twg-center { text-align: center; }
    .twg-grid .twg-amount { text-align: right; font-variant-numeric: tabular-nums; }
    .twg-grid .twg-noquote { text-align: center; font-style: italic; color: #1f4e9c; }
    .twg-grid .twg-label { text-align: right; font-weight: bold; }
    .twg-grid tfoot td { font-weight: bold; }

    .twg-found { margin: 8pt 0 0; text-align: justify; }
    .twg-findings { margin-top: 26pt; }
    .twg-finding { display: flex; align-items: flex-end; gap: 6pt; min-height: 14pt; margin: 0 0 4pt; }
    .twg-finding .twg-scope { flex: 0 0 16%; text-align: right; }
    .twg-finding .twg-supplier { flex: 0 0 27%; text-align: center; border-bottom: .75pt solid #111; padding-bottom: 1pt; }
    .twg-finding span:last-child { flex: 1 1 0; }

    .twg-signatures { display: grid; grid-template-columns: 1.45fr 1fr; column-gap: 6mm; margin-top: 22pt; }
    .twg-signatures .twg-sign-row { display: flex; flex-wrap: wrap; gap: 6mm 8mm; }
    .twg-signatory { margin-top: 30pt; min-width: 46mm; }
    .twg-signatory .twg-name { font-weight: bold; text-decoration: underline; }
    .twg-signatory .twg-position { font-weight: bold; }

    @page { size: A4 portrait; margin: 10mm 8mm 12mm 10mm; }
    @media print {
      body > div { min-height: 0 !important; background: white !important; }
      body > div > nav, body > div > header { display: none; }
      .twg-print-content { max-width: none; padding: 0 !important; }
      .twg-preview { overflow: visible; padding: 0; }
      .twg-paper { width: auto; min-height: 0; padding: 0; box-shadow: none !important; }
      .twg-masthead, .twg-grid tr, .twg-signatures, .twg-finding { break-inside: avoid; }
      .twg-grid thead { display: table-header-group; }
    }
  </style>

  <div class="twg-print-content max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="twg-preview" role="region" aria-label="Printable TWG evaluation report preview" tabindex="0">
      <article class="twg-paper shadow-sm" aria-labelledby="twg-title">

        <div class="twg-masthead">
          <img class="twg-dole-logo" src="{{ asset('images/dole_bicol_logo.png') }}" alt="DOLE Bicol">
          <div class="twg-letterhead">
            <div>Republic of the Philippines</div>
            <div><strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong></div>
            <div><strong>Regional Office No. 5</strong></div>
            <div>ORD: 0981-461-8788 TSSD: 0963-206-0008 IMSD: 0912-330-4751</div>
            <div>ro5@dole.gov.ph</div>
          </div>
          <img class="twg-bagong-logo" src="{{ asset('images/bagong_pilipinas_logo.png') }}" alt="Bagong Pilipinas">
          <img class="twg-bureau-logo" src="{{ asset('images/bureau_veritas_logo.png') }}" alt="Bureau Veritas">
        </div>

        <h1 id="twg-title">TWG EVALUATION REPORT</h1>
        <div class="twg-reference">
          <div>TWG Report No. {{ $twg->twg_no }}</div>
          <div>Date: {{ $twg->twg_date?->format('F d, Y') }}</div>
        </div>

        <dl class="twg-info">
          <dt>Name of Procuring Agency:</dt><dd>Department of Labor and Employment Regional Office No. 5</dd>
          <dt>Name of Project:</dt><dd>{{ $twg->project_name }}</dd>
          <dt>Approved Budget for the Contract:</dt><dd>PhP{{ number_format($twg->abc, 2) }}</dd>
          <dt>Method of Procurement:</dt><dd>{{ $twg->procurement_mode }}</dd>
          <dt>PhilGEPS Reference Number:</dt><dd>{{ $twg->philgeps_ref }}</dd>
          <dt>Date of PhilGEPS Posting:</dt><dd>{{ $twg->philgeps_posted }}</dd>
          <dt>Number of Quotations Received:</dt><dd>{{ $twg->quotationsLabel() }}</dd>
          <dt>Criteria for Evaluation:</dt><dd>{{ $twg->criteria }}</dd>
          <dt>Details of Quotations:</dt><dd>Given Below</dd>
        </dl>

        <table class="twg-grid" aria-label="Details of quotations">
          <colgroup>
            <col style="width:7%"><col style="width:9%"><col style="width:34%">
            @for ($c = 0; $c < $cols; $c++)<col style="width:{{ $supplierWidth }}%">@endfor
          </colgroup>
          <thead>
            <tr>
              <th rowspan="2" scope="col">Item No.</th>
              <th rowspan="2" scope="col">Quantity/<br>Unit</th>
              <th rowspan="2" scope="col">Item/s Description</th>
              <th colspan="{{ $cols }}" scope="colgroup">NAME OF SUPPLIER</th>
            </tr>
            <tr>
              @forelse ($suppliers as $s)
                <th scope="col">{{ $s->name }}</th>
              @empty
                <th scope="col">&nbsp;</th>
              @endforelse
            </tr>
          </thead>
          <tbody>
            @foreach ($rfq->items as $item)
              @if ($item->line_type === \App\Enums\LineType::Item)
                @php
                  $itemNo++;
                  $lines = array_values(array_filter(
                      preg_split('/\r\n|\n|\r/', (string) $item->description),
                      fn ($l) => trim($l) !== ''
                  ));
                  $span = max(count($lines), 1);
                @endphp
                @for ($r = 0; $r < $span; $r++)
                  <tr>
                    <td class="twg-center">{{ $r === 0 ? $itemNo : '' }}</td>
                    <td class="twg-center">{{ $r === 0 && $item->quantity !== null ? rtrim(rtrim(number_format($item->quantity, 2), '0'), '.').' '.$item->unit : '' }}</td>
                    <td class="twg-desc">@if ($r === 0)<strong>{{ $lines[0] ?? '' }}</strong>@else{{ $lines[$r] }}@endif</td>
                    @forelse ($suppliers as $s)
                      @php($quote = $priceMaps[$s->id]->get($item->id))
                      @if ($quote)
                        <td class="twg-amount">{{ $r === 0 ? number_format(round((float) $item->quantity * (float) $quote->unit_price, 2), 2) : '' }}</td>
                      @elseif ($r === 0)
                        <td class="twg-noquote" rowspan="{{ $span }}">No Quotation</td>
                      @endif
                    @empty
                      <td></td>
                    @endforelse
                  </tr>
                @endfor
              @elseif ($item->line_type === \App\Enums\LineType::LotHeader)
                <tr>
                  <td></td><td></td>
                  <td class="twg-desc"><strong>{{ $item->description }}</strong></td>
                  @for ($c = 0; $c < $cols; $c++)<td></td>@endfor
                </tr>
              @else
                <tr>
                  <td></td><td></td>
                  <td class="twg-desc"><em>{{ $item->description }}</em></td>
                  @for ($c = 0; $c < $cols; $c++)<td></td>@endfor
                </tr>
              @endif
            @endforeach

            @if ($rfq->items->isNotEmpty())
              <tr>
                <td></td><td></td>
                <td class="twg-center"><strong>**nothing follows**</strong></td>
                @for ($c = 0; $c < $cols; $c++)<td></td>@endfor
              </tr>
            @endif
            <tr aria-hidden="true"><td></td><td></td><td></td>@for ($c = 0; $c < $cols; $c++)<td></td>@endfor</tr>
          </tbody>
          <tfoot>
            <tr>
              <td></td><td></td><td class="twg-label">Total</td>
              @forelse ($suppliers as $s)
                @php($grand = $s->grandTotal($rfq->items))
                <td class="twg-amount">{{ $grand === null ? '-' : number_format($grand, 2) }}</td>
              @empty
                <td></td>
              @endforelse
            </tr>
            <tr>
              <td></td><td></td><td class="twg-label">Remarks</td>
              @forelse ($suppliers as $s)
                <td class="twg-amount">{{ $s->remarks }}</td>
              @empty
                <td></td>
              @endforelse
            </tr>
            <tr aria-hidden="true"><td></td><td></td><td></td>@for ($c = 0; $c < $cols; $c++)<td></td>@endfor</tr>
          </tfoot>
        </table>

        <p class="twg-found">Based on the above price quotations offered by different leading dealers/suppliers, the TWG found that:</p>

        <div class="twg-findings">
          @foreach ($twg->findings ?? [] as $f)
            <p class="twg-finding">
              <span class="twg-scope">{{ $f['scope'] ?? '' }}</span>
              <strong class="twg-supplier">{{ $f['supplier'] ?? '' }}</strong>
              <span>{{ $f['text'] ?? '' }}</span>
            </p>
          @endforeach
        </div>

        <div class="twg-signatures">
          @foreach (['evaluated' => 'Evaluated by:', 'reviewed' => 'Reviewed by:'] as $group => $label)
            <div>
              <div>{{ $label }}</div>
              <div class="twg-sign-row">
                @foreach ($twg->signatoriesIn($group) as $sg)
                  <div class="twg-signatory">
                    <div class="twg-name">{{ $sg['name'] }}</div>
                    <div class="twg-position">{{ $sg['position'] ?? '' }}</div>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>

      </article>
    </div>
  </div>
</x-app-layout>
