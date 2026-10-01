{{-- Inline styles on purpose: the Filament panel does not compile the site's Tailwind classes. --}}
@if ($empty)
    <div role="alert" style="display: flex; gap: .75rem; align-items: flex-start; border-radius: .5rem; padding: .75rem 1rem; font-size: .875rem; line-height: 1.4; background: rgb(245 158 11 / .14); color: #b45309; border: 1px solid rgb(245 158 11 / .4);">
        <span aria-hidden="true" style="font-weight: 700;">!</span>
        <span>
            <strong>Belum ada penerima leads diatur.</strong>
            Notifikasi sementara jatuh ke email publik{{ filled($fallback ?? null) ? ' ('.$fallback.')' : ' (juga belum diisi, jadi tidak ada email yang terkirim)' }}.
            Isi daftar penerima di Pengaturan Situs &rsaquo; tab Kontak.
        </span>
    </div>
@endif
