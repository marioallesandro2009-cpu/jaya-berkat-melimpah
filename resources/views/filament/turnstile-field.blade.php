{{-- Turnstile widget for a Filament form. wire:ignore keeps Livewire from redrawing it; the token
     goes into the field's state, and "turnstile-reset" (sent after a failed attempt) asks for a
     fresh one, because a token can only be used once. --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            token: $wire.$entangle(@js($getStatePath())),
            widget: null,
            draw() {
                this.widget = window.turnstile.render(this.$refs.box, {
                    sitekey: @js(\App\Support\Turnstile::siteKey()),
                    callback: (value) => { this.token = value },
                    'expired-callback': () => { this.token = null },
                    'error-callback': () => { this.token = null },
                });
            },
            load() {
                if (window.turnstile) return this.draw();
                const script = document.createElement('script');
                script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
                script.async = true;
                script.onload = () => this.draw();
                document.head.appendChild(script);
            },
        }"
        x-init="load()"
        x-on:turnstile-reset.window="token = null; window.turnstile && widget !== null && window.turnstile.reset(widget)"
    >
        <div x-ref="box" wire:ignore></div>
    </div>
</x-dynamic-component>
