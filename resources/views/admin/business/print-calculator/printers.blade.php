@extends('layouts.app', [
    'pageTitle' => 'Printer presets — Austral Layers',
    'pageDescription' => 'Manage printer power, purchase price, and expected lifespan presets.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page business-print-calculator-page">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Business management</p>
                <h1>Printer presets</h1>
                <p>Presets save time in the calculator. Values are still editable for each quotation.</p>
            </div>
            <a class="quote-button staff-content-action" href="{{ route('admin.business.print-calculator.index') }}">Back to calculator <span>←</span></a>
        </div>

        @if (session('business_saved'))
            <p class="business-print-notice" role="status">{{ session('business_saved') }}</p>
        @endif
        @if ($errors->any())
            <div class="business-print-errors" role="alert">
                <strong>Please check the printer preset details.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="business-print-preset-form" method="post" action="{{ route('admin.business.print-calculator.printers.store') }}">
            @csrf
            <div>
                <h2>Add a printer</h2>
                <p>Enter the manufacturer’s power rating and your purchase price and expected lifespan.</p>
            </div>
            <div class="business-print-fields">
                <label><span>Brand</span><input name="brand" value="{{ old('brand') }}" maxlength="120" required></label>
                <label><span>Model</span><input name="model" value="{{ old('model') }}" maxlength="150" required></label>
                <label><span>Power (watts)</span><input type="number" name="power_watts" value="{{ old('power_watts') }}" min="0.01" step="0.01" required></label>
                <label><span>Purchase price (₱)</span><input type="number" name="price" value="{{ old('price') }}" min="0" step="0.01" required></label>
                <label><span>Expected lifespan (hours)</span><input type="number" name="lifespan_hours" value="{{ old('lifespan_hours') }}" min="0.01" step="0.01" required></label>
            </div>
            <button class="quote-button" type="submit">Add printer preset <span>+</span></button>
        </form>

        <section class="business-print-presets" aria-labelledby="saved-printers-heading">
            <div class="business-print-presets-heading">
                <h2 id="saved-printers-heading">Saved printers</h2>
                <span>{{ $printerPresets->count() }} {{ \Illuminate\Support\Str::plural('preset', $printerPresets->count()) }}</span>
            </div>
            @forelse ($printerPresets as $preset)
                <form class="business-print-preset-row" method="post" action="{{ route('admin.business.print-calculator.printers.update', $preset) }}">
                    @csrf
                    @method('put')
                    <label><span>Brand</span><input name="brand" value="{{ $preset->brand }}" maxlength="120" required></label>
                    <label><span>Model</span><input name="model" value="{{ $preset->model }}" maxlength="150" required></label>
                    <label><span>Power (watts)</span><input type="number" name="power_watts" value="{{ $preset->power_watts }}" min="0.01" step="0.01" required></label>
                    <label><span>Purchase price (₱)</span><input type="number" name="price" value="{{ $preset->price }}" min="0" step="0.01" required></label>
                    <label><span>Lifespan (hours)</span><input type="number" name="lifespan_hours" value="{{ $preset->lifespan_hours }}" min="0.01" step="0.01" required></label>
                    <div class="business-print-preset-actions">
                        <button class="quote-button" type="submit">Save changes</button>
                        <button
                            class="business-print-remove"
                            type="submit"
                            form="remove-printer-{{ $preset->id }}"
                        >Remove</button>
                    </div>
                </form>
                <form id="remove-printer-{{ $preset->id }}" method="post" action="{{ route('admin.business.print-calculator.printers.destroy', $preset) }}" data-confirm-action="Remove this printer preset? Existing quotations will be kept.">
                    @csrf
                    @method('delete')
                </form>
            @empty
                <p class="business-print-empty">No printer presets yet. You can enter printer values manually in the calculator until you add one.</p>
            @endforelse
        </section>
    </section>
@endsection
