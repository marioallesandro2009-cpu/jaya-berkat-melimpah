{{-- Inline styles on purpose: the Filament panel does not compile the site's Tailwind classes. --}}
@php
    $dot = ['good' => '#10b981', 'warn' => '#f59e0b', 'bad' => '#ef4444', 'info' => '#9ca3af'];
    $box = [
        'good' => 'background: rgb(16 185 129 / .12); color: #059669;',
        'warn' => 'background: rgb(245 158 11 / .14); color: #d97706;',
        'bad' => 'background: rgb(239 68 68 / .12); color: #dc2626;',
    ];
@endphp
<div style="font-size: .875rem; line-height: 1.35;">
    <div style="display: flex; justify-content: space-between; gap: 1rem; border-radius: .5rem; padding: .75rem 1rem; font-weight: 600; {{ $box[$result['tone']] }}">
        <span>Skor SEO</span>
        <span>{{ $result['score'] }}/100 - {{ $result['label'] }}</span>
    </div>
    <p style="margin: .5rem 0 0; font-size: .75rem; opacity: .6;">~{{ $result['minutes'] }} menit baca</p>

    @foreach ($result['groups'] as $group)
        <div style="margin-top: 1rem;">
            <p style="margin: 0; font-size: .7rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; opacity: .6;">{{ $group['title'] }}</p>
            <ul style="margin: .4rem 0 0; padding: 0; list-style: none;">
                @foreach ($group['checks'] as [$status, $text])
                    <li style="display: flex; align-items: flex-start; gap: .5rem; margin-top: .35rem;">
                        <span style="flex: none; width: .5rem; height: .5rem; margin-top: .4rem; border-radius: 9999px; background: {{ $dot[$status] }};"></span>
                        <span>{{ $text }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
