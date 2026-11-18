{{--
    PLACEHOLDER for the RFQ print layout (friend: replace everything below).
    Mirrors purchase-requests/printPR.blade.php. Route: GET /rfqs/{rfq}/print  (name: rfqs.print)
    Variable: $rfq (Rfq) with items and purchaseRequest already loaded. See RFQ_PRINT_FIELDS.md.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>RFQ {{ $rfq->rfq_no }}</title>
</head>
<body>
    <h2>REQUEST FOR QUOTATION</h2>
    <p>RFQ No. {{ $rfq->rfq_no }} &mdash; {{ $rfq->rfq_date->format('F d, Y') }}</p>
    <p>PURPOSE: {{ $rfq->purpose }}</p>

    <table border="1" cellpadding="4" cellspacing="0">
        @foreach ($rfq->items as $item)
            @if ($item->line_type === \App\Enums\LineType::Item)
                <tr>
                    <td>{{ $item->quantity + 0 }}</td>
                    <td>{{ $item->unit }}</td>
                    <td style="white-space: pre-line">{{ $item->description }}</td>
                    <td align="right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td align="right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @else
                <tr><td colspan="5"><b>{{ $item->description }}</b></td></tr>
            @endif
        @endforeach
        <tr><td colspan="4" align="right">TOTAL</td><td align="right">{{ number_format($rfq->total_amount, 2) }}</td></tr>
    </table>

    <p>Contract Duration: {{ $rfq->contract_duration }}</p>
    <p>Delivery Area: {{ $rfq->delivery_area }}</p>
    <p>PR No: {{ $rfq->purchaseRequest->pr_no }} &nbsp; End User: {{ $rfq->purchaseRequest->end_user }}</p>
    <p>{{ $rfq->bac_chairperson }}<br>BAC Chairperson</p>
</body>
</html>
