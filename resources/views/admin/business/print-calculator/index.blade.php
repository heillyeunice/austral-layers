@extends('layouts.app', [
    'pageTitle' => '3D print calculator — Austral Layers',
    'pageDescription' => 'Calculate 3D printing costs and create printable quotations.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page business-print-calculator-page">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Business management</p>
                <h1>3D print cost calculator</h1>
                <p>Estimate the full production cost and create a printable customer quotation.</p>
            </div>
            <a class="quote-button staff-content-action" href="{{ route('admin.business.print-calculator.printers') }}">Manage printer presets <span>↗</span></a>
        </div>

        @if ($errors->any())
            <div class="business-print-errors" role="alert">
                <strong>Please review the following errors.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('business_saved'))
            <p class="business-print-notice" role="status">{{ session('business_saved') }}</p>
        @endif

        <div class="business-print-workspace" data-print-calculator data-failure-rate="{{ $failureRate }}" data-markup="{{ $markup }}">
            <fieldset class="business-print-mode">
                <legend>Preview mode</legend>
                <label>
                    <input type="radio" name="view_mode" value="internal" form="print-calculator-form" @checked(old('view_mode', 'internal') === 'internal') data-print-view>
                    <span>Internal costing</span>
                </label>
                <label>
                    <input type="radio" name="view_mode" value="client" form="print-calculator-form" @checked(old('view_mode') === 'client') data-print-view>
                    <span>Client view</span>
                </label>
            </fieldset>

        <div class="business-print-layout">
            <form id="print-calculator-form" class="business-print-form" method="post" action="{{ route('admin.business.print-calculator.quotation') }}">
                @csrf
                <section class="business-print-section" aria-labelledby="print-quote-details">
                    <div class="business-print-section-heading">
                        <span>01</span>
                        <div>
                            <h2 id="print-quote-details">Quotation details</h2>
                            <p>Identify the customer and the item being priced.</p>
                        </div>
                    </div>
                    <div class="business-print-fields">
                        <label>
                            <span>Item name</span>
                            <input name="item_name" value="{{ old('item_name') }}" maxlength="180" required>
                        </label>
                        <label>
                            <span>Customer name <small>Optional</small></span>
                            <input name="customer_name" value="{{ old('customer_name') }}" maxlength="150">
                        </label>
                    </div>
                </section>

                <section class="business-print-section" aria-labelledby="print-material-cost">
                    <div class="business-print-section-heading">
                        <span>02</span>
                        <div>
                            <h2 id="print-material-cost">Material</h2>
                            <p>Enter the used weight and your filament’s purchase cost.</p>
                        </div>
                    </div>
                    <div class="business-print-fields">
                        <label>
                            <span>Filament type</span>
                            <select name="filament_type" required>
                                @foreach ($filamentTypes as $filamentType)
                                    <option value="{{ $filamentType }}" @selected(old('filament_type', 'PLA') === $filamentType)>{{ $filamentType }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Weight used (grams)</span>
                            <input type="number" name="weight_grams" value="{{ old('weight_grams') }}" min="0.01" max="9999999999.99" step="0.01" required>
                        </label>
                        <label>
                            <span>Filament cost per kilogram (₱)</span>
                            <input type="number" name="filament_cost_per_kg" value="{{ old('filament_cost_per_kg', 0) }}" min="0" max="9999999999.99" step="0.01" required>
                        </label>
                    </div>
                </section>

                <section class="business-print-section" aria-labelledby="print-machine-cost">
                    <div class="business-print-section-heading">
                        <span>03</span>
                        <div>
                            <h2 id="print-machine-cost">Printer and electricity</h2>
                            <p>Choose a saved printer or enter values manually. Preset values remain editable.</p>
                        </div>
                    </div>
                    <div class="business-print-fields">
                        <label>
                            <span>Printer brand</span>
                            <select name="printer_brand" data-printer-brand>
                                <option value="">Manual entry</option>
                                @foreach ($printerPresets->pluck('brand')->unique()->sort() as $brand)
                                    <option value="{{ $brand }}" @selected(old('printer_brand') === $brand)>{{ $brand }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Printer model</span>
                            <select name="printer_model" data-printer-model>
                                <option value="">Select a model</option>
                                @foreach ($printerPresets as $preset)
                                    <option
                                        value="{{ $preset->model }}"
                                        data-brand="{{ $preset->brand }}"
                                        data-preset-id="{{ $preset->id }}"
                                        data-power="{{ $preset->power_watts }}"
                                        data-price="{{ $preset->price }}"
                                        data-lifespan="{{ $preset->lifespan_hours }}"
                                        @selected(old('printer_model') === $preset->model && old('printer_brand') === $preset->brand)
                                    >{{ $preset->model }}</option>
                                @endforeach
                            </select>
                        </label>
                        <input type="hidden" name="printer_preset_id" value="{{ old('printer_preset_id') }}" data-printer-preset-id>
                        <label>
                            <span>Print time</span>
                            <span class="business-print-time-inputs">
                                <input type="number" name="print_hours" value="{{ old('print_hours', 0) }}" min="0" max="9999" step="1" aria-label="Print time hours" required>
                                <span>hours</span>
                                <input type="number" name="print_minutes" value="{{ old('print_minutes', 0) }}" min="0" max="59" step="1" aria-label="Print time minutes" required>
                                <span>minutes</span>
                            </span>
                        </label>
                        <label>
                            <span>Electricity rate (₱ / kWh)</span>
                            <input type="number" name="electricity_rate" value="{{ old('electricity_rate', 0) }}" min="0" max="99999999.9999" step="0.0001" required>
                        </label>
                        <label>
                            <span>Printer power (watts)</span>
                            <input type="number" name="printer_power_watts" value="{{ old('printer_power_watts') }}" min="0.01" max="99999999.99" step="0.01" data-printer-power required>
                        </label>
                        <label>
                            <span>Printer purchase price (₱)</span>
                            <input type="number" name="printer_price" value="{{ old('printer_price', 0) }}" min="0" max="9999999999.99" step="0.01" data-printer-price required>
                        </label>
                        <label>
                            <span>Expected lifespan (hours)</span>
                            <input type="number" name="printer_lifespan_hours" value="{{ old('printer_lifespan_hours') }}" min="0.01" max="9999999999.99" step="0.01" data-printer-lifespan required>
                        </label>
                    </div>
                </section>

                <section class="business-print-section" aria-labelledby="print-labor-cost">
                    <div class="business-print-section-heading">
                        <span>04</span>
                        <div>
                            <h2 id="print-labor-cost">Labor and packaging</h2>
                            <p>The packaging amount is added to the subtotal before failure and profit.</p>
                        </div>
                    </div>
                    <div class="business-print-fields">
                        <label>
                            <span>Labor rate (₱ / hour)</span>
                            <input type="number" name="labor_rate" value="{{ old('labor_rate', 0) }}" min="0" max="9999999999.99" step="0.01" required>
                        </label>
                        <label>
                            <span>Hands-on labor time</span>
                            <span class="business-print-time-inputs">
                                <input type="number" name="labor_hours" value="{{ old('labor_hours', 0) }}" min="0" max="9999" step="1" aria-label="Labor time hours" required>
                                <span>hours</span>
                                <input type="number" name="labor_minutes" value="{{ old('labor_minutes', 0) }}" min="0" max="59" step="1" aria-label="Labor time minutes" required>
                                <span>minutes</span>
                            </span>
                        </label>
                        <label>
                            <span>Packaging fee</span>
                            <select name="packaging_fee" required>
                                <option value="0" @selected(old('packaging_fee', '0') === '0')>₱0</option>
                                <option value="30" @selected(old('packaging_fee') === '30')>₱30</option>
                                <option value="50" @selected(old('packaging_fee') === '50')>₱50</option>
                            </select>
                        </label>
                    </div>
                </section>

                <section class="business-print-section" aria-labelledby="print-risk-profit">
                    <div class="business-print-section-heading">
                        <span>05</span>
                        <div>
                            <h2 id="print-risk-profit">Failure risk and profit</h2>
                            <p>The failure allowance and markup are fixed for consistent order pricing.</p>
                        </div>
                    </div>
                    <div class="business-print-fields">
                        <p class="business-print-fixed-rate"><span>Failure allowance</span><strong>{{ $failureRate }}% · fixed</strong></p>
                        <p class="business-print-fixed-rate"><span>Markup</span><strong>{{ $markup }}% · fixed</strong></p>
                    </div>
                </section>

                <div class="business-print-actions">
                    <button class="quote-button business-print-submit" type="submit">Download quotation PDF <span>↓</span></button>
                    <button class="business-print-order-submit" type="submit" formaction="{{ route('admin.business.print-calculator.order') }}">Use costing to create an order <span>↗</span></button>
                </div>
            </form>

            <aside class="business-print-result" aria-label="Live quotation result">
                <p class="eyebrow"><span></span> Live estimate</p>
                <p class="business-print-total-label">Recommended selling price</p>
                <strong class="business-print-total" data-print-final-price aria-live="polite">₱0.00</strong>
                <p class="business-print-description" data-print-item-name>Enter an item name to preview the quotation.</p>
                <dl class="business-print-breakdown" data-print-breakdown>
                    <div><dt>Material</dt><dd data-print-cost="material_cost">₱0.00</dd></div>
                    <div><dt>Electricity</dt><dd data-print-cost="electricity_cost">₱0.00</dd></div>
                    <div><dt>Printer wear</dt><dd data-print-cost="printer_wear_cost">₱0.00</dd></div>
                    <div><dt>Labor</dt><dd data-print-cost="labor_cost">₱0.00</dd></div>
                    <div><dt>Packaging</dt><dd data-print-cost="packaging_fee">₱0.00</dd></div>
                    <div><dt>Subtotal</dt><dd data-print-cost="subtotal">₱0.00</dd></div>
                    <div><dt>Failure cost</dt><dd data-print-cost="failure_cost">₱0.00</dd></div>
                    <div><dt>Markup</dt><dd data-print-cost="profit_amount">₱0.00</dd></div>
                </dl>
            </aside>
        </div>

            <article class="business-document business-document-invoice business-print-client-preview" aria-label="Client quotation preview" data-print-client-preview hidden>
                <header>
                    <strong>AUSTRAL LAYERS</strong>
                    <strong>3D PRINT QUOTATION</strong>
                    <span>Prepared {{ now()->format('M j, Y') }}</span>
                </header>
                <div class="business-document-parties">
                    <div>
                        <small>Prepared for</small>
                        <h2 data-print-client-customer>Valued customer</h2>
                    </div>
                    <div>
                        <small>Quotation date</small>
                        <h2>{{ now()->format('M j, Y') }}</h2>
                    </div>
                </div>
                <section class="business-document-order-summary">
                    <p>Here’s a summary of your order for confirmation. 😊</p>
                    <h2>ORDER SUMMARY</h2>
                    <dl>
                        <div><dt>Order Name</dt><dd data-print-client-item>—</dd></div>
                        <div><dt>Filament</dt><dd data-print-client-filament>PLA</dd></div>
                        <div><dt>Material used</dt><dd data-print-client-weight>—</dd></div>
                        <div><dt>Estimated print time</dt><dd data-print-client-duration>—</dd></div>
                        <div><dt>Total Amount</dt><dd data-print-client-total>₱0.00</dd></div>
                        <div><dt>50% Down Payment</dt><dd data-print-client-half>₱0.00</dd></div>
                        <div><dt>Remaining Balance after down payment</dt><dd data-print-client-balance>₱0.00 + shipping fee</dd></div>
                    </dl>
                </section>
                <section class="business-document-terms">
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
                <footer>
                    <span>Total quotation amount</span>
                    <strong data-print-client-footer-total>₱0.00</strong>
                </footer>
            </article>
        </div>

        @if ($recentQuotations->isNotEmpty())
            <section class="business-print-history" aria-labelledby="recent-print-quotations">
                <div class="business-print-presets-heading">
                    <h2 id="recent-print-quotations">Recent quotations</h2>
                    <span>Last {{ $recentQuotations->count() }}</span>
                </div>
                @foreach ($recentQuotations as $quotation)
                    <div class="business-print-history-row">
                        <div>
                            <strong>{{ $quotation->item_name }}</strong>
                            <span>{{ $quotation->customer_name ?: 'No customer name' }} · {{ $quotation->created_at->format('M j, Y') }} · {{ $quotation->view_mode === 'internal' ? 'Internal' : 'Client' }} view</span>
                        </div>
                        <strong>₱{{ number_format((float) $quotation->final_price, 2) }}</strong>
                        <a href="{{ route('admin.business.print-calculator.quotation.download', $quotation) }}">Download PDF <span>↓</span></a>
                    </div>
                @endforeach
            </section>
        @endif
    </section>
@endsection
