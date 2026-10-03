@props(['section' => null, 'products' => [], 'settings'])
@php
    use App\Http\Middleware\GuardContactForm;
    use App\Support\Links;
    use App\Support\Turnstile;

    $errors = session('errors')?->getBag('contact');
    $status = session('contact_status');
    $failure = session('contact_error');
    $field = fn (string $name): ?string => $errors?->first($name);
    $texts = $settings['texts'];
@endphp
<section id="contact" class="section section--secondary">
    <div class="wrap contact">
        <div class="contact-intro reveal">
            <h2>{{ $section['title'] ?? '' }}</h2>
            @if ($section['body'] ?? null)
                <p class="body-text" style="margin-top: var(--space-sm);">{{ $section['body'] }}</p>
            @endif
            <ul class="contact-direct">
                @if ($settings['whatsappUrl'])
                    <li><a href="{{ $settings['whatsappUrl'] }}" target="_blank" rel="noopener">{{ __('WhatsApp') }}: {{ $settings['whatsappNumber'] }}</a></li>
                @endif
                @if ($settings['email'])
                    <li><a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a></li>
                @endif
            </ul>
        </div>

        <form class="inquiry reveal" id="inquiry-form" method="post" action="{{ Links::contactAction() }}" novalidate
              data-sending="{{ $texts['form_sending'] }}" data-error="{{ $texts['contact_error'] }}" data-check="{{ __('Please check the form.') }}">
            @csrf
            {{-- Honeypot: invisible to people, bots fill it in (see GuardContactForm). --}}
            <div class="hp" aria-hidden="true"><label>Website <input type="text" name="{{ GuardContactForm::HONEYPOT }}" tabindex="-1" autocomplete="off"></label></div>

            <div class="field-row">
                <div class="field">
                    <label for="f-name">{{ $texts['form_name'] }}</label>
                    <input id="f-name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}" @if ($field('name')) aria-invalid="true" aria-describedby="e-name" @endif>
                    <p class="field-error" id="e-name">{{ $field('name') }}</p>
                </div>
                <div class="field">
                    <label for="f-company">{{ $texts['form_company'] }}</label>
                    <input id="f-company" name="company" type="text" autocomplete="organization" required value="{{ old('company') }}" @if ($field('company')) aria-invalid="true" aria-describedby="e-company" @endif>
                    <p class="field-error" id="e-company">{{ $field('company') }}</p>
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="f-email">{{ $texts['form_email'] }}</label>
                    <input id="f-email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" @if ($field('email')) aria-invalid="true" aria-describedby="e-email" @endif>
                    <p class="field-error" id="e-email">{{ $field('email') }}</p>
                </div>
                <div class="field">
                    <label for="f-country">{{ $texts['form_country'] }}</label>
                    <input id="f-country" name="country" type="text" autocomplete="country-name" required value="{{ old('country') }}" @if ($field('country')) aria-invalid="true" aria-describedby="e-country" @endif>
                    <p class="field-error" id="e-country">{{ $field('country') }}</p>
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="f-phone">{{ $texts['form_phone'] }} <span class="opt">({{ $texts['form_optional'] }})</span></label>
                    <input id="f-phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" @if ($field('phone')) aria-invalid="true" aria-describedby="e-phone" @endif>
                    <p class="field-error" id="e-phone">{{ $field('phone') }}</p>
                </div>
                <div class="field">
                    <label for="f-product">{{ $texts['form_product'] }}</label>
                    <select id="f-product" name="product" required @if ($field('product')) aria-invalid="true" aria-describedby="e-product" @endif>
                        <option value="">{{ $texts['form_product_select'] }}</option>
                        @foreach ($products as $product)
                            <option value="{{ $product['value'] }}" @selected(old('product', request('product')) === $product['value'])>{{ $product['name'] }}</option>
                        @endforeach
                        <option value="Other / multiple" @selected(old('product') === 'Other / multiple')>{{ $texts['form_product_other'] }}</option>
                    </select>
                    <p class="field-error" id="e-product">{{ $field('product') }}</p>
                </div>
            </div>
            {{-- Filled by the inquiry list script (several products, one quote); hidden when the list is empty. --}}
            <div class="inquiry-picked" data-inquiry-picked hidden>
                <p class="inquiry-picked-title">{{ $texts['inquiry_items'] }}</p>
                <ul class="inquiry-picked-list"></ul>
                <input type="hidden" name="items" id="f-items" value="">
            </div>
            <div class="field">
                <label for="f-volume">{{ $texts['form_volume'] }} <span class="opt">({{ $texts['form_optional'] }})</span></label>
                <input id="f-volume" name="volume" type="text" placeholder="{{ $texts['form_volume_placeholder'] }}" value="{{ old('volume') }}">
            </div>
            <div class="field">
                <label for="f-message">{{ $texts['form_message'] }} <span class="opt">({{ $texts['form_optional'] }})</span></label>
                <textarea id="f-message" name="message" rows="4">{{ old('message') }}</textarea>
            </div>

            @if (Turnstile::enabled())
                <div class="cf-turnstile" data-sitekey="{{ Turnstile::siteKey() }}"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer @if ($nonce = \Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ $nonce }}" @endif></script>
            @endif

            <p class="form-status {{ $failure ? 'is-error' : '' }}" id="form-status" role="status" aria-live="polite">{{ $failure ?: $status }}</p>
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">{{ $texts['form_submit'] }} <span class="arrow" aria-hidden="true">→</span></button>
                @if ($settings['whatsappUrl'])
                    <a href="{{ $settings['whatsappUrl'] }}" class="link-arrow" target="_blank" rel="noopener">{{ $texts['form_whatsapp'] }} <span class="arrow" aria-hidden="true">→</span></a>
                @endif
            </div>
        </form>
    </div>
</section>
