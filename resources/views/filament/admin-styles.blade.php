{{-- Admin-only CSS. admin-ocean.css is the "deep water" skin (palette and fonts of the site, plain CSS on top of
     Filament's fi-* classes, no theme build). The block below is a small AA fix: placeholder text in Filament's
     inputs is gray-400 = 2.6:1 on white; AA needs 4.5:1, so it uses gray-500 (about 4.8:1) in light mode. --}}
<link rel="stylesheet" href="{{ asset('css/admin-ocean.css') }}?v={{ @filemtime(public_path('css/admin-ocean.css')) ?: 1 }}">
<style>
    html:not(.dark) .fi-input::placeholder,
    html:not(.dark) textarea.fi-input::placeholder {
        color: var(--gray-500, #71717a);
        opacity: 1;
    }
</style>
