<x-app-layout>
  <x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <h2 class="font-semibold text-xl text-gray-800">PR {{ $pr->pr_no }}</h2>
        <x-status-chip :status="$pr->status->value" />
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('purchase-requests.show', $pr) }}" class="text-sm text-gray-600 hover:underline">Back to request</a>
        <x-primary-button type="button" onclick="window.print()">Print / Save as PDF</x-primary-button>
      </div>
    </div>
  </x-slot>

  <style>
    .pr-preview { overflow-x: auto; padding-bottom: 8px; }
    .pr-paper {
      box-sizing: border-box;
      width: 297mm;
      min-height: 210mm;
      margin-inline: auto;
      padding: 12mm;
      background: white;
      color: #111;
      font: 10.5pt/1.35 "Times New Roman", serif;
    }
    .pr-paper h1 { margin: 0 0 1.1em; text-align: center; font-size: 1.53em; font-weight: bold; }
    .identity { display: grid; grid-template-columns: 76.5% 23.5%; font-weight: bold; }
    .identity span { padding: .25em .4em .45em; }
    .identity span:last-child { padding-left: 1.2em; }
    .pr-paper table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .pr-paper th, .pr-paper td { padding: .35em; vertical-align: middle; }
    .request { border: 2px solid; }
    .request th, .request td { border: 1px solid; border-inline-width: 2px; }
    .request thead th, .request thead td, .purpose td { border-bottom: 2px solid; }
    .request tbody td, .request tfoot td { height: 2.05em; }
    .request .item td { height: 4.25em; }
    .request .purpose td { height: 3.5em; border-top: 2px solid; }
    .description, .purpose td { white-space: pre-line; overflow-wrap: anywhere; }
    .signatures td, .request td, .identity span { overflow-wrap: anywhere; }
    .center { text-align: center; }
    .amount { text-align: right; white-space: nowrap; }
    .signatures { font-family: Arial, sans-serif; }
    .signatures th, .signatures td { border: 1px solid; height: 2.1em; text-align: center; }
    @page { size: A4 landscape; margin: 12mm; }
    @media print {
      body > div { min-height: 0 !important; background: white !important; }
      body > div > nav, body > div > header { display: none; }
      .pr-print-content { max-width: none; padding: 0 !important; }
      .pr-preview { overflow: visible; padding: 0; }
      .pr-paper { width: auto; min-height: 0; padding: 0; box-shadow: none !important; }
      .pr-paper tr, .signatures { break-inside: avoid; }
      .request tfoot { display: table-row-group; }
    }
  </style>
  <div class="pr-print-content max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="pr-preview" role="region" aria-label="Printable Purchase Request preview" tabindex="0">
      <article class="pr-paper shadow-sm" aria-labelledby="form-title">
        <h1 id="form-title">PURCHASE REQUEST</h1>
        <div class="identity">
          <span>Entity Name: &nbsp; {{ $pr->entity_name }}</span>
          <span>Fund Cluster: &nbsp; {{ $pr->fund_cluster }}</span>
        </div>
        <table class="request" aria-label="Purchase request details">
          <colgroup>
            <col style="width:11%"><col style="width:14%"><col style="width:44%">
            <col style="width:7.5%"><col style="width:9.5%"><col style="width:14%">
          </colgroup>
          <thead>
            <tr>
              <td class="center" colspan="2">Office/Section:</td>
              <td colspan="2"><strong>PR No.: {{ $pr->pr_no }}</strong></td>
              <td rowspan="2" colspan="2"><strong>Date:</strong> {{ $pr->pr_date?->format("M d, Y") }}</td>
            </tr>
            <tr>
              <td class="center" colspan="2"><strong>{{ $pr->office_section }}</strong></td>
              <td colspan="2"><strong>Responsibility Center Code:</strong> {{ $pr->responsibility_center_code }}</td>
            </tr>
            <tr>
              <th scope="col">Stock/<br>Property No.</th>
              <th scope="col">Unit</th>
              <th scope="col">Item Description</th>
              <th scope="col">Quantity</th>
              <th scope="col">Unit Cost</th>
              <th scope="col">Total Cost</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($pr->items as $item)
              @if ($item->line_type === \App\Enums\LineType::Item)
                <tr class="item">
                  <td>{{ $item->stock_no }}</td>
                  <td class="center">{{ $item->unit }}</td>
                  <td class="description"><strong>{{ $item->description }}</strong></td>
                  <td class="center">{{ $item->quantity !== null ? number_format($item->quantity, 2) : '' }}</td>
                  <td class="amount">{{ $item->unit_cost !== null ? number_format($item->unit_cost, 2) : '' }}</td>
                  <td class="amount">{{ $item->amount !== null ? number_format($item->amount, 2) : '' }}</td>
                </tr>
              @elseif ($item->line_type === \App\Enums\LineType::LotHeader)
                <tr><td colspan="6" class="description"><strong>{{ $item->description }}</strong></td></tr>
              @else
                <tr><td></td><td colspan="5" class="description"><em>{{ $item->description }}</em></td></tr>
              @endif
            @endforeach
            @if ($pr->items->isNotEmpty())
              <tr><td></td><td></td><td class="center">*nothing follows*</td><td></td><td></td><td></td></tr>
            @endif
            @for ($row = $pr->items->count(); $row < 6; $row++)
              <tr aria-hidden="true"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
          </tbody>
          <tfoot>
            <tr><td></td><td></td><td><strong>Total</strong></td><td></td><td></td><td class="amount">{{ number_format($pr->total_amount, 2) }}</td></tr>
            <tr class="purpose"><td colspan="6">Purpose: {{ $pr->purpose }}</td></tr>
          </tfoot>
        </table>
        <table class="signatures" aria-label="Signatories">
          <colgroup><col style="width:25%"><col style="width:44%"><col style="width:31%"></colgroup>
          <thead>
            <tr><th scope="col">Requested by:</th><th scope="col">Certified Funds Available:</th><th scope="col">Approved by:</th></tr>
          </thead>
          <tbody>
            <tr aria-hidden="true"><td></td><td></td><td></td></tr>
            <tr><td><strong>{{ $pr->requested_by }}</strong></td><td><strong>{{ $pr->certified_funds_available }}</strong></td><td><strong>{{ $pr->approved_by }}</strong></td></tr>
            <tr><td>{{ $pr->requested_by_position }}</td><td>{{ $pr->certified_funds_available_position }}</td><td>{{ $pr->approved_by_position }}</td></tr>
            <tr aria-hidden="true"><td></td><td></td><td></td></tr>
          </tbody>
        </table>
      </article>
    </div>
  </div>
</x-app-layout>
