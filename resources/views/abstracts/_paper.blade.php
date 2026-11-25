@php
    $roleLabels = ['member' => 'BAC Member', 'vice_chairperson' => 'BAC Vice-Chairperson', 'chairperson' => 'BAC Chairperson'];
    $signatures = collect($abstract->signatories ?? [])->filter(fn ($sg) => filled($sg['name'] ?? null));
@endphp
<style>
    .abstract-paper { box-sizing: border-box; width: 210mm; min-height: 297mm; padding: 10mm; margin: 0 auto; background: white; color: #111; font: 7pt/1.25 Arial, Helvetica, sans-serif; overflow-wrap: anywhere; print-color-adjust: exact; }
    .abstract-masthead { display: grid; grid-template-columns: 22mm 86mm 20mm 27mm; gap: 3mm; align-items: center; width: 164mm; max-width: 100%; margin-left: auto; min-height: 26mm; }
    .abstract-masthead img { display: block; width: 100%; height: 24mm; object-fit: contain; }
    .abstract-masthead > img:first-child { transform: scale(1.4); }
    .abstract-letterhead { text-align: center; font-size: 9pt; line-height: 1.3; }
    .abstract-letterhead div:nth-child(2), .abstract-letterhead div:nth-child(3) { font-size: 9pt; font-weight: bold; white-space: nowrap; }
    .abstract-letterhead div:nth-child(4) { font-size: 8pt; white-space: nowrap; }
    .abstract-letterhead div:last-child { margin-top: 2pt; }
    .abstract-letterhead div:first-child, .abstract-letterhead div:nth-child(4) { font-style: italic; }
    .abstract-letterhead div:last-child { color: #0563c1; text-decoration: underline; }
    .abstract-paper h1 { text-align: center; font-size: 12pt; font-weight: bold; margin: 10pt 0 3pt; }
    .abstract-form-body { border: 1.2pt solid #111; }
    .abstract-section { margin: 0; padding: 1pt 2pt; border-top: 1.2pt solid #111; border-bottom: .6pt solid #111; font-weight: bold; }
    .abstract-form-body > .abstract-section:first-child { border-top: 0; }
    .abstract-particulars { margin: 0; padding: 1pt 2pt; }
    .abstract-particulars div { margin: 0; min-height: 9.4pt; white-space: pre-line; }
    .abstract-particular-value { font-weight: bold; text-decoration: underline; }
    .abstract-grid { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7pt; }
    .abstract-grid tbody tr { height: 63.3pt; }
    .abstract-grid th, .abstract-grid td { border: .6pt solid #111; padding: 4pt 2pt; vertical-align: middle; }
    .abstract-grid th { font-size: 6.7pt; padding: 1pt 1pt; font-weight: bold; text-align: center; }
    .abstract-grid tbody tr:last-child { height: 51.3pt; }
    .abstract-grid thead tr:first-child { height: 16.6pt; }
    .abstract-grid thead tr:last-child { height: 10.6pt; }
    .abstract-grid tbody td:last-child { text-align: center; font-weight: bold; }
    .abstract-grid td.center { text-align: center; }
    .abstract-grid .abstract-check { margin-right: 0; }
    .abstract-grid .money { text-align: right; font-variant-numeric: tabular-nums; }
    .abstract-note { margin: 0; padding: 1pt 2pt; border-bottom: .6pt solid #111; font-size: 7pt; }
    .abstract-check { display: inline-block; width: 5pt; height: 5pt; border: .6pt solid #111; vertical-align: baseline; margin-right: 4pt; text-align: center; font: bold 5pt/5pt Arial; }
    .abstract-recommendation { margin: 0; }
    .abstract-recommendation > p { margin: 0; padding: 1pt 2pt; border-bottom: .6pt solid #111; }
    .abstract-award { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .abstract-award td { height: 19.1pt; padding: 2pt; border-right: .6pt solid #111; border-bottom: .6pt solid #111; text-align: center; vertical-align: middle; }
    .abstract-award td:last-child { border-right: 0; }
    .abstract-failure { display: grid; grid-template-columns: 1fr 1fr; gap: 0; font-size: 7pt; }
    .abstract-failure > div { padding: 2pt; min-height: 18.1pt; }
    .abstract-failure > div + div { border-left: .6pt solid #111; }
    .abstract-resolved { border-top: 1.2pt solid #111; padding: 3pt 3pt 5pt; }
    .abstract-resolved > p { margin: 0; font-weight: bold; }
    .abstract-grid tr > :first-child { border-left: 0; }
    .abstract-grid tr > :last-child { border-right: 0; }
    .abstract-grid thead tr:first-child > th { border-top: 0; }
    .abstract-grid tbody tr:last-child > td { border-bottom: 0; }
    .abstract-signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 26pt 20pt; margin-top: 21.1pt; }
    .abstract-signature { text-align: center; break-inside: avoid; min-height: 31.6pt; }
    .abstract-signature strong { display: block; text-transform: uppercase; }
    .abstract-chair { margin: 10.6pt auto 0; width: 75%; }
    .abstract-approval { margin: 0; padding-bottom: 6pt; break-inside: avoid; }
    .abstract-approval > p { margin: 0; padding: 1pt 2pt; border-top: 1.2pt solid #111; border-bottom: 1.2pt solid #111; text-align: center; font-style: italic; }
    .abstract-approval-options .abstract-check { width: 16pt; height: 16pt; }
    .abstract-approval-options { display: flex; justify-content: space-around; gap: 12pt; margin: 13.1pt 0 27pt; }
    .abstract-hope { text-align: center; }
    .abstract-hope strong { display: block; text-transform: uppercase; }
    @page { size: A4 portrait; margin: 10mm; }
    @media print {
        body > div { min-height: 0 !important; background: white !important; }
        body > div > nav, body > div > header { display: none !important; }
        .abstract-preview { overflow: visible; padding: 0 !important; }
        .abstract-paper { width: auto; min-height: 0; padding: 0; }
        .abstract-grid thead { display: table-header-group; }
        .abstract-grid tr, .abstract-award, .abstract-failure { break-inside: avoid; }
        .abstract-section { break-after: avoid; }
    }
</style>
<article class="abstract-paper" aria-label="Abstract of Quotations document">
    <div class="abstract-masthead">
        <img src="{{ asset('images/dole_bicol_logo.png') }}" alt="DOLE Bicol">
        <div class="abstract-letterhead">
        <div>Republic of the Philippines</div><div><strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong></div><div>Regional Office No. 5</div>
        <div>ORD: 0981-461-8788 TSSD: 0963-206-0008 IMSD: 0912-330-4751</div><div>ro5@dole.gov.ph</div>
        </div>
        <img src="{{ asset('images/bagong_pilipinas_logo.png') }}" alt="Bagong Pilipinas">
        <img src="{{ asset('images/bureau_veritas_logo.png') }}" alt="Bureau Veritas">
    </div>
    <h1>ABSTRACT OF QUOTATION/S</h1>
    @if ($abstract->status === \App\Enums\DocStatus::Draft)<p style="text-align:center;font-weight:bold">DRAFT</p>@endif
    <div class="abstract-form-body">
    <h2 class="abstract-section">I. Particulars</h2>
    <div class="abstract-particulars">
        <div><strong>Project Name:</strong> <span class="abstract-particular-value">{{ $abstract->project_name }}</span></div>
        <div><strong>Project Location:</strong> <span class="abstract-particular-value">{{ $abstract->project_location }}</span></div>
        <div><strong>Implementing Office:</strong> <span class="abstract-particular-value">{{ $abstract->implementing_office }}</span></div>
        <div><strong>Method of Procurement:</strong> <span class="abstract-particular-value">{{ $abstract->procurement_mode }}</span></div>
        <div><strong>Approved Budget for the Contract:</strong> <span class="abstract-particular-value">PhP {{ number_format($abstract->abc, 2) }}</span></div>
        <div><strong>Deadline of Submission of Bid:</strong> <span class="abstract-particular-value">{{ $abstract->bid_deadline_at?->format('F j, Y h:i A') ?? '-' }}</span></div>
    </div>
    <h2 class="abstract-section">II. Abstract of Quotations/Canvass</h2>
    <p class="abstract-note" style="padding-left:6mm"><u>Evaluation of Document/s Required to be Submitted Within the Deadline Specified in RFQ</u></p>
    <p class="abstract-note" style="padding-left:6mm">TWG Report No. {{ $twg->twg_no }} &nbsp; Date: {{ $twg->twg_date->format('F j, Y') }}</p>
    <table class="abstract-grid">
        <colgroup><col style="width:22.3%"><col style="width:13%"><col style="width:5.25%"><col style="width:5.35%"><col style="width:4.6%"><col style="width:4.6%"><col style="width:4.95%"><col style="width:4.6%"><col style="width:12%"><col style="width:5.15%"><col style="width:18.2%"></colgroup>
        <thead><tr><th rowspan="2">Participating Bidder/s</th><th rowspan="2">Date and Time of Receipt</th><th colspan="2">Eligibility Requirements</th><th colspan="2">Technical Requirements</th><th colspan="2">Financial Requirements</th><th rowspan="2">Bid Amount</th><th rowspan="2">Rank</th><th rowspan="2">Remarks</th></tr>
            <tr>@for ($i = 0; $i < 3; $i++)<th>Pass</th><th>Fail</th>@endfor</tr>
        </thead><tbody>
            @foreach ($twg->suppliers as $supplier)
                @php($evaluation = ($abstract->evaluations ?? [])[$supplier->id] ?? [])
                @php($total = $abstract->bidAmountFor($supplier))
                <tr><td>{{ $supplier->name }}</td><td class="center">{{ filled($evaluation['received_at'] ?? null) ? \Carbon\Carbon::parse($evaluation['received_at'])->format('F j, Y h:i A') : '-' }}</td>
                    @foreach (['eligibility', 'technical', 'financial'] as $requirement)
                        <td class="center"><span class="abstract-check" role="img" aria-label="{{ ucfirst($requirement) }} pass {{ ($evaluation[$requirement] ?? '') === 'pass' ? 'checked' : 'unchecked' }}">{{ ($evaluation[$requirement] ?? '') === 'pass' ? 'X' : '' }}</span></td><td class="center"><span class="abstract-check" role="img" aria-label="{{ ucfirst($requirement) }} fail {{ ($evaluation[$requirement] ?? '') === 'fail' ? 'checked' : 'unchecked' }}">{{ ($evaluation[$requirement] ?? '') === 'fail' ? 'X' : '' }}</span></td>
                    @endforeach
                    <td class="money">{{ $total === null ? '-' : number_format($total, 2) }}</td><td class="center">{{ $evaluation['rank'] ?? '' }}</td><td style="white-space:pre-line">{{ $evaluation['remarks'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h2 class="abstract-section">III. Recommendation</h2>
    <div class="abstract-recommendation">
        <p><span class="abstract-check">{{ $abstract->recommendation === 'award' ? 'X' : '' }}</span>Recommend to Award Contract</p>
        <table class="abstract-award">
            <colgroup><col style="width:22.3%"><col style="width:23.6%"><col style="width:15%"><col style="width:39.1%"></colgroup>
            <tbody>
                <tr>
                    <td rowspan="2">Single Calculated and Responsive Quotation:</td>
                    <td rowspan="2" style="font-weight:bold">{{ $abstract->award_description }}</td>
                    <td rowspan="2">Contract Price Award<br>(In words and figures)</td>
                    <td style="font-weight:bold">@if ($abstract->award_amount !== null){{ \App\Support\AmountInWords::pesos($abstract->award_amount) }}@endif</td>
                </tr>
                <tr><td style="font-weight:bold">@if ($abstract->award_amount !== null)PhP {{ number_format($abstract->award_amount, 2) }}@endif</td></tr>
            </tbody>
        </table>
        <p><span class="abstract-check">{{ $abstract->recommendation === 'failure' ? 'X' : '' }}</span>Declaration of Failure under Section 35 of the Revised IRR of RA 9184</p>
        <div class="abstract-failure">
            <div><span class="abstract-check">{{ $abstract->failure_reason === 'ineligible' ? 'X' : '' }}</span>All prospective bidders are declared ineligible [Section 35(b)]</div>
            <div><span class="abstract-check">{{ $abstract->failure_reason === 'noncompliant' ? 'X' : '' }}</span>All bids failed to comply with all the bid requirements or fail post qualification [Section 35.1(c)]</div>
        </div>
    </div>
    <section class="abstract-resolved">
    <p>Resolved by:</p>
    <div class="abstract-signatures">
        @foreach ($signatures->where('role', '!=', 'chairperson') as $sg)
            <div class="abstract-signature"><strong>{{ $sg['name'] }}</strong><div>{{ $sg['position'] ?? '' }}</div><div>{{ $roleLabels[$sg['role']] }}</div></div>
        @endforeach
    </div>
    @foreach ($signatures->where('role', 'chairperson') as $sg)
        <div class="abstract-signature abstract-chair"><strong>{{ $sg['name'] }}</strong><div>{{ $sg['position'] ?? '' }}</div><div>BAC Chairperson</div></div>
    @endforeach
    </section>
    <div class="abstract-approval">
        <p>*Proceed only if recommended for award contract*</p>
        <div class="abstract-approval-options"><span><span class="abstract-check"></span>Approved</span><span><span class="abstract-check"></span>Disapproved</span><span><span class="abstract-check"></span>Others: ___________________</span></div>
        <div class="abstract-hope"><strong>{{ $abstract->hope_name ?: '________________________________' }}</strong><div>Head of the Procuring Entity</div></div>
    </div>
    </div>
</article>
