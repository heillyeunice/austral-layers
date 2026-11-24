<div class="quote-modal" id="quote-modal" aria-hidden="true">
    <div class="quote-backdrop" data-close-quote></div>
    <aside class="quote-panel" role="dialog" aria-modal="true" aria-labelledby="quote-title">
        <button class="modal-close" type="button" data-close-quote aria-label="Close quote form">×</button>
        <p class="eyebrow"><span></span> Have something in mind?</p>
        <h2 id="quote-title">{{ $siteSettings['inquiry_heading_first'] }}<br>{{ $siteSettings['inquiry_heading_second'] }}</h2>
        <p class="quote-intro">{{ $siteSettings['inquiry_description'] }}</p>
        @if ($errors->any())
            <div class="form-errors" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('quote.store') }}" class="quote-form" enctype="multipart/form-data">
            @csrf
            <div class="field-row">
                <label>Name<input type="text" name="name" value="{{ old('name') }}" required></label>
                <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
            </div>
            <label>Company <span>(optional)</span><input type="text" name="company" value="{{ old('company') }}"></label>
            <label>Social media profile link
                <input type="text" inputmode="url" autocomplete="url" autocapitalize="none" spellcheck="false" name="social_media_link" value="{{ old('social_media_link') }}" maxlength="2048" placeholder="www.facebook.com/your.profile" required>
                <small>Required — share the profile where you’d like our staff to contact you. We’ll add https:// if it’s missing.</small>
            </label>
            <div class="field-row">
                <label>What do you need?<select name="service" required><option value="" disabled {{ old('service') ? '' : 'selected' }}>Select one</option><option {{ old('service') === 'Custom 3D Modeling' ? 'selected' : '' }}>Custom 3D Modeling</option><option {{ old('service') === '3D Printing' ? 'selected' : '' }}>3D Printing</option><option {{ old('service') === 'Products & Personalization' ? 'selected' : '' }}>Products &amp; Personalization</option><option {{ old('service') === 'Not sure yet' ? 'selected' : '' }}>Not sure yet</option></select></label>
                <label>Quantity<select name="quantity" required><option value="" disabled {{ old('quantity') ? '' : 'selected' }}>Select one</option><option {{ old('quantity') === '1–5 pieces' ? 'selected' : '' }}>1–5 pieces</option><option {{ old('quantity') === '6–20 pieces' ? 'selected' : '' }}>6–20 pieces</option><option {{ old('quantity') === 'More than 20' ? 'selected' : '' }}>More than 20</option><option {{ old('quantity') === 'Not sure yet' ? 'selected' : '' }}>Not sure yet</option></select></label>
            </div>
            <label>Project brief<textarea name="message" rows="4" required>{{ old('message') }}</textarea></label>
            <div class="upload-control">
                <label class="upload-button" for="quote-inspiration"><span>Upload inspiration <small>(optional)</small></span><b>+</b></label>
                <input id="quote-inspiration" type="file" name="inspiration[]" accept="image/jpeg,image/png,image/gif,image/bmp,image/webp,image/avif,image/heic,image/heif,application/pdf" multiple aria-describedby="inspiration-help inspiration-status">
                <p class="upload-help" id="inspiration-help">JPG, PNG, GIF, BMP, WEBP, AVIF, HEIC/HEIF or PDF · Up to 5 files, 20 MB each.</p>
                <p class="upload-status" id="inspiration-status" role="status" aria-live="polite">No files selected.</p>
            </div>
            <button class="submit-button" type="submit">Send enquiry <span>↗</span></button>
        </form>
    </aside>
</div>
