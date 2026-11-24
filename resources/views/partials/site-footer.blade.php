<footer class="site-footer">
    <div class="footer-main">
        <div class="footer-about">
            <a class="footer-brand" href="{{ route('home') }}" aria-label="Austral Layers home"><img src="{{ asset($siteSettings['logo_path']) }}" alt="Austral Layers"></a>
            <p>Custom 3D printing &amp; modeling studio.<br><strong>{{ $siteSettings['brand_tagline'] }}</strong></p>
        </div>
        <section class="social-qrs" aria-labelledby="social-qrs-title">
            <h2 id="social-qrs-title">Find us online <span>Scan to follow</span></h2>
            <div class="social-qr-grid">
                <figure>
                    <img class="social-qr-code" src="/images/facebook-qr.png" alt="QR code for Austral Layers on Facebook">
                    <figcaption><a class="social-link" href="https://www.facebook.com/profile.php?id=61595552973378" target="_blank" rel="noopener noreferrer" aria-label="Visit Austral Layers on Facebook"><img src="/images/facebook-icon.png" alt=""><span>Facebook</span></a></figcaption>
                </figure>
                <figure>
                    <img class="social-qr-code" src="/images/instagram-qr.png" alt="QR code for Austral Layers on Instagram">
                    <figcaption><a class="social-link" href="https://www.instagram.com/austral_layers" target="_blank" rel="noopener noreferrer" aria-label="Visit Austral Layers on Instagram"><img src="/images/instagram-icon.png" alt=""><span>Instagram</span></a></figcaption>
                </figure>
                <figure>
                    <img class="social-qr-code" src="/images/tiktok-qr.png" alt="QR code for Austral Layers on TikTok">
                    <figcaption><a class="social-link" href="https://www.tiktok.com/@austral_layers" target="_blank" rel="noopener noreferrer" aria-label="Visit Austral Layers on TikTok"><img src="/images/tiktok-icon.png" alt=""><span>TikTok</span></a></figcaption>
                </figure>
            </div>
        </section>
        <div class="footer-contact">
            <p>Have an idea in mind?</p>
            <button type="button" data-open-quote>Let’s make it <span>↗</span></button>
            <a href="mailto:australlayers@gmail.com">australlayers@gmail.com</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p class="copyright">
            © {{ date('Y') }} Austral Layers
            <span class="footer-staff-links" aria-label="Staff sign in">
                <a href="{{ route('admin.login') }}">Admin</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('superadmin.login') }}">SuperAdmin</a>
            </span>
        </p>
        <p>Designed and made one layer at a time.</p>
    </div>
</footer>
