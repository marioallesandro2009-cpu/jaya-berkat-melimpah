{{-- Floating WhatsApp button, bottom right on every public page. Only rendered when a WhatsApp number is set
     (Pengaturan Situs > Umum); the message that is already typed in is also set there. CSS: "WhatsApp button". --}}
@props(['settings'])
@if ($settings['whatsappChatUrl'] ?? null)
    <a class="wa-float" href="{{ $settings['whatsappChatUrl'] }}" target="_blank" rel="noopener" aria-label="{{ __('Chat on WhatsApp') }}" data-no-transition>
        <svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.04 3C9 3 3.3 8.7 3.3 15.7c0 2.3.6 4.4 1.7 6.3L3 29l7.2-1.9a12.7 12.7 0 0 0 5.8 1.5C23 28.6 28.7 22.9 28.7 15.9S23.1 3 16.04 3Zm0 23.3c-1.9 0-3.7-.5-5.3-1.5l-.4-.2-4.3 1.1 1.2-4.2-.3-.4a10.4 10.4 0 0 1-1.6-5.5c0-5.8 4.7-10.5 10.6-10.5 5.8 0 10.5 4.7 10.5 10.5s-4.8 10.7-10.4 10.7Zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.6 8.6 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7.1-.2.1-.4 0-.5l-1-2.3c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4a3.4 3.4 0 0 0-1 2.5c0 1.5 1.1 2.9 1.2 3.1.2.2 2.2 3.4 5.3 4.7 2 .8 2.7.9 3.7.7.6-.1 1.9-.8 2.1-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.2-.6-.4Z"/></svg>
        <span class="wa-float-label">{{ __('WhatsApp') }}</span>
    </a>
@endif
