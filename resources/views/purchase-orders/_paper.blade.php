@php
    $fmt = fn ($n) => number_format((float) $n, 2);
    $penalty = 'In case of failure to make the full delivery within the time specified above, a penalty as liquidated damages, of one-tenth (1/10) of one percent for every day of delay shall be imposed on the undelivered item/s. In case payment has been made and the penalty is owing, but was inadvertently not deducted from the payment, the supplier shall refund the amount of penalty.';
    $deductions = $po->deductions ?? [];
@endphp
<style>
    .po-paper { box-sizing: border-box; width: 210mm; min-height: 297mm; padding: 10mm; margin: 0 auto; background: white; color: #111; font: 9pt/1.3 'Times New Roman', Times, serif; overflow-wrap: anywhere; print-color-adjust: exact; }
    .po-appendix { text-align: right; font-style: italic; font-size: 9pt; }
    .po-paper h1 { text-align: center; font-size: 15pt; font-weight: bold; margin: 2pt 0 0; }
    .po-entity { text-align: center; font-size: 9pt; margin-bottom: 3pt; }
    .po-draft { text-align: center; font-weight: bold; margin: 0 0 3pt; }
    .po-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .po-table td { padding: 2pt 3pt; vertical-align: middle; }
    .po-box td { border: 0; }
    .po-top td { border-top: 1.2pt solid #111; }
    .po-frame { border: 1.2pt solid #111; }
    .po-frame > .po-table > tbody > tr > td { border-left: 1.2pt solid #111; }
    .po-block { border: 1.2pt solid #111; border-top: 0; }
    .po-bold { font-weight: bold; }
    .po-u { text-decoration: underline; }
    .po-line { border-bottom: .6pt solid #111; }
    .po-items td { border-left: 1.2pt solid #111; border-right: 1.2pt solid #111; padding: 3pt 4pt; }
    .po-items thead th { border: 1.2pt solid #111; padding: 5pt 3pt; font-weight: bold; text-align: center; font-size: 8.5pt; }
    .po-items tbody tr:last-child td { border-bottom: 1.2pt solid #111; }
    .po-center { text-align: center; }
    .po-right { text-align: right; font-variant-numeric: tabular-nums; }
    .po-desc { white-space: pre-line; }
    .po-words { border: 1.2pt solid #111; border-top: 0; display: grid; grid-template-columns: 42mm 1fr; font-weight: bold; font-style: italic; }
    .po-words > div { padding: 5pt 4pt; }
    .po-words > div:last-child { text-align: center; }
    .po-penalty { padding: 8pt 4pt 8pt; text-indent: 8mm; text-align: justify; border-left: 1.2pt solid #111; border-right: 1.2pt solid #111; margin: 0; }
    .po-sign { display: grid; grid-template-columns: 1fr 1fr; border: 1.2pt solid #111; border-top: 0; border-bottom: 0; padding: 4pt 4pt 6pt; break-inside: avoid; }
    .po-sign > div { text-align: center; padding: 0 6pt; }
    .po-sign .po-who { margin-top: 14pt; font-weight: bold; text-decoration: underline; text-transform: uppercase; min-height: 11pt; }
    .po-sign .po-label { text-align: left; }
    .po-date { margin: 14pt auto 0; width: 55%; border-top: .6pt solid #111; }
    .po-foot { display: grid; grid-template-columns: 1fr 1fr; border: 1.2pt solid #111; break-inside: avoid; min-height: 28mm; }
    .po-foot > div { padding: 4pt 4pt; }
    .po-foot > div + div { border-left: 1.2pt solid #111; }
    .po-foot .po-field { margin: 0 0 3pt; font-weight: bold; }
    .po-foot .po-field span { display: inline-block; min-width: 38mm; border-bottom: .6pt solid #111; }
    .po-acct { margin-top: 9mm; text-align: center; }
    .po-acct strong { display: block; text-decoration: underline; text-transform: uppercase; min-height: 11pt; }
    @page { size: A4 portrait; margin: 10mm; }
    @media print {
        body > div { min-height: 0 !important; background: white !important; }
        body > div > nav, body > div > header { display: none !important; }
        .po-preview { overflow: visible; padding: 0 !important; }
        .po-paper { width: auto; min-height: 0; padding: 0; }
        .po-items tr { break-inside: avoid; }
    }
</style>
<article class="po-paper" aria-label="Purchase Order document">
    <div class="po-appendix">Appendix 61</div>
    <h1>PURCHASE ORDER</h1>
    <div class="po-entity">DOLE RO5</div>
    @if ($po->status === \App\Enums\DocStatus::Draft)<p class="po-draft">DRAFT</p>@endif

    <div class="po-frame">
        <table class="po-table">
            <colgroup><col style="width:13%"><col style="width:37%"><col style="width:16%"><col style="width:34%"></colgroup>
            <tbody>
                <tr><td style="border-left:0">Supplier :</td><td class="po-u">{{ $po->supplier_name }}</td><td>P.O. No.</td><td class="po-bold">: {{ $po->po_no }}</td></tr>
                <tr><td style="border-left:0">Address :</td><td class="po-u">{{ $po->supplier_address }}</td><td>Date</td><td>: {{ $po->po_date->format('F j, Y') }}</td></tr>
                <tr><td style="border-left:0">TIN :</td><td class="po-u">{{ $po->supplier_tin }}@if (filled($po->supplier_contact_no)) Contact No.: {{ $po->supplier_contact_no }}@endif</td><td>Mode of Procurement</td><td>: {{ $po->procurement_mode }}</td></tr>
            </tbody>
        </table>
    </div>
    <div class="po-block" style="padding:3pt 4pt 4pt">
        <div>Gentlemen:</div>
        <div style="padding-left:12mm">Please furnish this office the following articles subject to the terms and conditions contained herein:</div>
    </div>
    <div class="po-block" style="border-top:1.2pt solid #111">
        <table class="po-table">
            <colgroup><col style="width:17%"><col style="width:36%"><col style="width:16%"><col style="width:31%"></colgroup>
            <tbody>
                <tr style="height:10mm"><td>Place of Delivery:</td><td class="po-bold">{{ $po->place_of_delivery }}</td><td>Delivery Term:</td><td class="po-bold">{{ $po->delivery_term }}</td></tr>
                <tr style="height:10mm"><td>Date of Delivery :</td><td class="po-bold">{{ $po->date_of_delivery }}</td><td>Payment Term:</td><td class="po-bold">{{ $po->payment_term }}</td></tr>
            </tbody>
        </table>
    </div>

    <table class="po-table po-items" style="margin-top:0">
        <colgroup><col style="width:11%"><col style="width:8%"><col style="width:36%"><col style="width:10%"><col style="width:15%"><col style="width:20%"></colgroup>
        <thead><tr><th>Stock/ Property No.</th><th>Unit</th><th>Description</th><th>Quantity</th><th>Unit Cost</th><th>Amount</th></tr></thead>
        <tbody>
            @foreach ($po->items as $item)
                @if ($item->line_type === \App\Enums\LineType::Item)
                    <tr><td class="po-center">{{ $item->stock_no ?: $loop->iteration }}</td><td class="po-center">{{ $item->unit }}</td><td class="po-desc">{{ $item->description }}</td><td class="po-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td><td class="po-right">{{ $fmt($item->unit_cost) }}</td><td class="po-right">{{ $fmt($item->amount) }}</td></tr>
                @else
                    <tr><td></td><td></td><td class="po-desc {{ $item->line_type === \App\Enums\LineType::LotHeader ? 'po-bold' : '' }}">{{ $item->description }}</td><td></td><td></td><td></td></tr>
                @endif
            @endforeach
            <tr><td></td><td></td><td class="po-center po-bold" style="padding:6pt 4pt">***Nothing follows***</td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td class="po-bold">Total:</td><td class="po-right po-bold">{{ $fmt($po->total_amount) }}</td></tr>
            @if (filled($po->warranty_security))
                <tr><td></td><td></td><td></td><td colspan="2" style="font-style:italic">{{ $po->warranty_security }}</td><td></td></tr>
            @endif
            @foreach ($deductions as $row)
                <tr><td></td><td></td><td></td><td style="font-style:italic">{{ $row['label'] }}</td><td class="po-right" style="font-style:italic">{{ $row['rate'] }}</td><td class="po-right">{{ $fmt($row['amount']) }}</td></tr>
            @endforeach
            @if (count($deductions))
                <tr><td></td><td></td><td></td><td></td><td></td><td class="po-right po-bold">{{ $fmt($po->deductions_total) }}</td></tr>
            @endif
            <tr><td></td><td></td><td></td><td></td><td class="po-bold" style="padding-top:8pt">Amount Due:</td><td class="po-right po-bold" style="padding-top:8pt">{{ $fmt($po->amount_due) }}</td></tr>
        </tbody>
    </table>
    <div class="po-words"><div>(Total Amount in Words)</div><div>{{ \App\Support\PoOptions::amountInWords($po->amount_due) }}</div></div>
    <p class="po-penalty">{{ $penalty }}</p>
    <div class="po-sign">
        <div>
            <div class="po-label">Conforme:</div>
            <div class="po-who" style="margin-top:16pt">{{ $po->supplier_name }}</div>
            <div>Signature over Printed Name of Supplier</div>
            <div class="po-date">Date</div>
        </div>
        <div>
            <div class="po-label">Very truly yours,</div>
            <div class="po-who" style="margin-top:16pt">{{ $po->approver_name ?: '' }}</div>
            <div>{{ $po->approver_position ?: '' }}</div>
        </div>
    </div>
    <div class="po-foot">
        <div>
            <p class="po-field">Fund Cluster : <span></span></p>
            <p class="po-field">Funds Available : <span></span></p>
            <div class="po-acct"><strong>{{ $po->accountant_name ?: '' }}</strong><div>Accountant III</div></div>
        </div>
        <div>
            <p class="po-field">ORS/BURS No. : <span></span></p>
            <p class="po-field">Date of the ORS/BURS : <span></span></p>
            <p class="po-field">Amount : <span></span></p>
        </div>
    </div>
</article>
