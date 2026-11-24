<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>3D Print Quotation — Austral Layers</title>
    <style>
        @page { margin: 36px 42px; }
        body { color: #202b28; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; }
        .header { border-bottom: 2px solid #263d36; margin-bottom: 25px; padding-bottom: 15px; }
        .brand { color: #263d36; font-size: 16px; font-weight: bold; letter-spacing: 1.5px; }
        .title { color: #263d36; font-size: 22px; font-weight: bold; margin: 18px 0 2px; }
        .muted { color: #68746f; }
        .parties { margin: 18px 0 22px; width: 100%; }
        .parties td { vertical-align: top; width: 50%; }
        .section-title { border-bottom: 1px solid #d5dcd8; color: #263d36; font-size: 11px; font-weight: bold; letter-spacing: .6px; margin: 22px 0 8px; padding-bottom: 6px; text-transform: uppercase; }
        .details, .costs { border-collapse: collapse; width: 100%; }
        .details td, .costs td { border-bottom: 1px solid #e8ece9; padding: 8px 4px; }
        .details td:first-child, .costs td:first-child { color: #58655f; }
        .details td:last-child, .costs td:last-child { font-weight: bold; text-align: right; }
        .total { background: #263d36; color: #fff; margin-top: 20px; padding: 15px 18px; }
        .total span { font-size: 10px; letter-spacing: .6px; text-transform: uppercase; }
        .total strong { display: block; font-size: 22px; margin-top: 4px; }
        .terms { color: #58655f; margin-top: 24px; }
        .footer { border-top: 1px solid #d5dcd8; color: #68746f; font-size: 9px; margin-top: 32px; padding-top: 10px; text-align: center; }
        .invoice { color: #171b1a; font-size: 11px; }
        .invoice-header { border-collapse: collapse; width: 100%; }
        .invoice-header td { border-bottom: 1px solid #d5d9d7; color: #7a4030; font-size: 10px; font-weight: bold; letter-spacing: 1px; padding: 0 0 16px; }
        .invoice-header td:nth-child(2) { text-align: center; }
        .invoice-header td:last-child { text-align: right; }
        .invoice-parties { border-collapse: collapse; margin: 28px 0; width: 100%; }
        .invoice-parties td { vertical-align: top; width: 50%; }
        .invoice-parties small { color: #6a7370; font-size: 10px; }
        .invoice-parties h2 { font-family: DejaVu Serif, serif; font-size: 19px; font-weight: normal; margin: 8px 0; }
        .invoice-parties p { color: #555e5b; font-size: 11px; margin: 5px 0; }
        .invoice-summary { background: #f7f9f8; border: 1px solid #d5d9d7; margin: 22px 0; padding: 17px; }
        .invoice-summary-intro { color: #343c39; font-size: 12px; line-height: 1.6; margin: 0 0 12px; }
        .invoice-summary h2 { font-size: 13px; letter-spacing: .6px; margin: 0 0 10px; }
        .invoice-summary table { border-collapse: collapse; width: 100%; }
        .invoice-summary th, .invoice-summary td { border-bottom: 1px solid #dfe4e1; padding: 7px 4px; text-align: left; vertical-align: top; }
        .invoice-summary th { color: #58635f; font-size: 10px; width: 38%; }
        .invoice-summary td { color: #171b1a; font-size: 11px; }
        .invoice-terms { color: #343c39; font-size: 11px; line-height: 1.6; }
        .invoice-terms p { margin: 12px 0; }
        .invoice-terms ul { margin: 10px 0; padding-left: 20px; }
        .invoice-terms li { margin: 5px 0; }
        .invoice-footer { border-top: 1px solid #d5d9d7; margin-top: 20px; padding-top: 15px; text-align: right; }
        .invoice-footer span { color: #58635f; font-size: 11px; margin-right: 20px; }
        .invoice-footer strong { color: #171b1a; font-family: DejaVu Serif, serif; font-size: 21px; font-weight: normal; }
    </style>
</head>
<body>
    @if ($quotation->view_mode === 'client')
        @php($half = round((float) $quotation->final_price / 2, 2))
        <article class="invoice">
            <table class="invoice-header">
                <tr>
                    <td>AUSTRAL LAYERS</td>
                    <td>3D PRINT QUOTATION</td>
                    <td>PREPARED {{ strtoupper($quotation->created_at->format('M j, Y')) }}</td>
                </tr>
            </table>
            <table class="invoice-parties">
                <tr>
                    <td>
                        <small>Prepared for</small>
                        <h2>{{ $quotation->customer_name ?: 'Valued customer' }}</h2>
                    </td>
                    <td>
                        <small>Quotation date</small>
                        <h2>{{ $quotation->created_at->format('F j, Y') }}</h2>
                    </td>
                </tr>
            </table>
            <section class="invoice-summary">
                <p class="invoice-summary-intro">Here’s a summary of your order for confirmation. 😊</p>
                <h2>ORDER SUMMARY</h2>
                <table>
                    <tr><th>Order Name</th><td>{{ $quotation->item_name }}</td></tr>
                    <tr><th>Filament</th><td>{{ $quotation->filament_type }}</td></tr>
                    <tr><th>Material used</th><td>{{ number_format((float) $quotation->weight_grams, 2) }} g</td></tr>
                    <tr><th>Estimated print time</th><td>{{ $quotation->print_hours }} h {{ $quotation->print_minutes }} min</td></tr>
                    <tr><th>Total Amount</th><td>₱{{ number_format((float) $quotation->final_price, 2) }}</td></tr>
                    <tr><th>50% Down Payment</th><td>₱{{ number_format($half, 2) }}</td></tr>
                    <tr><th>Remaining Balance after down payment</th><td>₱{{ number_format($half, 2) }} + shipping fee</td></tr>
                </table>
            </section>
            <section class="invoice-terms">
                <p>Please review all the details and let us know if everything is correct.</p>
                <p>Just a quick reminder of our payment terms:</p>
                <ul>
                    <li>A 50% down payment is required to officially confirm and process your order.</li>
                    <li>The remaining 50% balance must be settled before we ship or dispatch your order.</li>
                    <li>Production will proceed once the down payment has been received.</li>
                </ul>
                <p>Once payment is sent, kindly send us a screenshot of your payment confirmation.</p>
                <p>Thank you for choosing Austral Layers! We appreciate your trust and support. 💙</p>
            </section>
            <footer class="invoice-footer">
                <span>Total quotation amount</span>
                <strong>₱{{ number_format((float) $quotation->final_price, 2) }}</strong>
            </footer>
        </article>
    @else
    <header class="header">
        <div class="brand">AUSTRAL LAYERS</div>
        <h1 class="title">3D PRINT QUOTATION</h1>
        <div class="muted">Prepared {{ $quotation->created_at->format('F j, Y') }}</div>
    </header>

    <table class="parties">
        <tr>
            <td>
                <div class="muted">Prepared for</div>
                <strong>{{ $quotation->customer_name ?: 'Valued customer' }}</strong>
            </td>
            <td>
                <div class="muted">Item</div>
                <strong>{{ $quotation->item_name }}</strong>
            </td>
        </tr>
    </table>

    <h2 class="section-title">Print details</h2>
    <table class="details">
        <tr><td>Filament</td><td>{{ $quotation->filament_type }}</td></tr>
        <tr><td>Material used</td><td>{{ number_format((float) $quotation->weight_grams, 2) }} g</td></tr>
        <tr><td>Estimated print time</td><td>{{ $quotation->print_hours }} h {{ $quotation->print_minutes }} min</td></tr>
        @if ($quotation->printer_brand || $quotation->printer_model)
            <tr><td>Printer</td><td>{{ trim(($quotation->printer_brand ?? '').' '.($quotation->printer_model ?? '')) }}</td></tr>
        @endif
        @if ($quotation->view_mode === 'internal')
            <tr><td>Filament price</td><td>₱{{ number_format((float) $quotation->filament_cost_per_kg, 2) }} / kg</td></tr>
            <tr><td>Electricity rate</td><td>₱{{ number_format((float) $quotation->electricity_rate, 4) }} / kWh</td></tr>
            <tr><td>Printer power</td><td>{{ number_format((float) $quotation->printer_power_watts, 2) }} W</td></tr>
            <tr><td>Printer purchase price</td><td>₱{{ number_format((float) $quotation->printer_price, 2) }}</td></tr>
            <tr><td>Printer lifespan</td><td>{{ number_format((float) $quotation->printer_lifespan_hours, 2) }} hours</td></tr>
            <tr><td>Labor rate</td><td>₱{{ number_format((float) $quotation->labor_rate, 2) }} / hour</td></tr>
            <tr><td>Hands-on labor time</td><td>{{ $quotation->labor_hours }} h {{ $quotation->labor_minutes }} min</td></tr>
            <tr><td>Packaging fee</td><td>₱{{ number_format((float) $quotation->packaging_fee, 2) }}</td></tr>
            <tr><td>Failure rate</td><td>{{ number_format((float) $quotation->failure_rate, 2) }}%</td></tr>
            <tr><td>Markup</td><td>{{ number_format((float) $quotation->profit_margin, 2) }}%</td></tr>
        @endif
    </table>

    @if ($quotation->view_mode === 'internal')
        <h2 class="section-title">Internal cost breakdown</h2>
        <table class="costs">
            <tr><td>Material cost</td><td>₱{{ number_format((float) $quotation->material_cost, 2) }}</td></tr>
            <tr><td>Electricity</td><td>₱{{ number_format((float) $quotation->electricity_cost, 2) }}</td></tr>
            <tr><td>Printer wear and tear</td><td>₱{{ number_format((float) $quotation->printer_wear_cost, 2) }}</td></tr>
            <tr><td>Labor</td><td>₱{{ number_format((float) $quotation->labor_cost, 2) }}</td></tr>
            <tr><td>Packaging fee</td><td>₱{{ number_format((float) $quotation->packaging_fee, 2) }}</td></tr>
            <tr><td>Subtotal</td><td>₱{{ number_format((float) $quotation->subtotal, 2) }}</td></tr>
            <tr><td>Failure allowance ({{ number_format((float) $quotation->failure_rate, 2) }}%)</td><td>₱{{ number_format((float) $quotation->failure_cost, 2) }}</td></tr>
            <tr><td>Markup ({{ number_format((float) $quotation->profit_margin, 2) }}%)</td><td>₱{{ number_format((float) $quotation->profit_amount, 2) }}</td></tr>
        </table>
    @endif

    <div class="total">
        <span>{{ $quotation->view_mode === 'internal' ? 'Recommended selling price' : 'Quotation total' }}</span>
        <strong>₱{{ number_format((float) $quotation->final_price, 2) }}</strong>
    </div>

    <footer class="footer">Thank you for choosing Austral Layers.</footer>
    @endif
</body>
</html>
