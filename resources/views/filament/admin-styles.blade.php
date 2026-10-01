{{-- Small admin-only CSS (no custom Filament theme build). Placeholder text in Filament's
     inputs (global search, table search, form fields) is gray-400 = 2.6:1 on white; AA needs
     4.5:1, so it uses gray-500 (about 4.8:1) in light mode. Dark mode keeps Filament's own colour. --}}
<style>
    html:not(.dark) .fi-input::placeholder,
    html:not(.dark) textarea.fi-input::placeholder {
        color: var(--gray-500, #71717a);
        opacity: 1;
    }
</style>
