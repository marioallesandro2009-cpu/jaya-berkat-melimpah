{{-- Detailed decorative fish, head to the right. Drawn in currentColor so CSS sets tone and opacity.
     $id must be unique on the page (it names the clip path). The tail, fins and gill animate in CSS. --}}
@props(['id'])
<svg {{ $attributes->merge(['viewBox' => '0 0 180 90', 'focusable' => 'false', 'aria-hidden' => 'true']) }}>
    <defs>
        <clipPath id="{{ $id }}-body"><path d="M18 46 C 38 16, 96 8, 142 34 C 150 38, 154 42, 156 46 C 154 50, 150 54, 142 58 C 96 82, 38 76, 18 46 Z" /></clipPath>
    </defs>
    {{-- tail fin --}}
    <g class="fish-tail">
        <path d="M24 46 C 12 38, 6 24, 2 10 C 14 16, 24 22, 34 33 Z" fill="currentColor" fill-opacity=".34" />
        <path d="M24 46 C 12 54, 6 68, 2 82 C 14 76, 24 70, 34 59 Z" fill="currentColor" fill-opacity=".34" />
        <path d="M26 46 C 16 40, 10 30, 7 20 M26 46 C 14 46, 8 46, 5 46 M26 46 C 16 52, 10 62, 7 72" fill="none" stroke="currentColor" stroke-opacity=".5" stroke-width="1" stroke-linecap="round" />
    </g>
    {{-- dorsal fin and spines --}}
    <g class="fish-dorsal">
        <path d="M58 22 C 66 6, 84 2, 108 14 C 100 18, 96 22, 92 26 C 82 22, 70 22, 58 22 Z" fill="currentColor" fill-opacity=".3" />
        <path d="M66 18 L 70 24 M76 12 L 79 22 M88 11 L 89 22 M99 14 L 98 24" fill="none" stroke="currentColor" stroke-opacity=".5" stroke-width="1" stroke-linecap="round" />
    </g>
    {{-- belly fins --}}
    <path class="fish-pelvic" d="M74 66 C 78 78, 90 84, 98 82 C 92 76, 90 70, 90 64 Z" fill="currentColor" fill-opacity=".3" />
    <path d="M52 62 C 50 72, 54 78, 60 80 C 58 74, 60 68, 64 64 Z" fill="currentColor" fill-opacity=".24" />
    {{-- body: base tone, lighter belly, scales, stripes --}}
    <path d="M18 46 C 38 16, 96 8, 142 34 C 150 38, 154 42, 156 46 C 154 50, 150 54, 142 58 C 96 82, 38 76, 18 46 Z" fill="currentColor" fill-opacity=".28" />
    <g clip-path="url(#{{ $id }}-body)">
        <path d="M0 52 C 50 44, 110 50, 170 56 L170 90 L0 90 Z" fill="currentColor" fill-opacity=".16" />
        <g fill="none" stroke="currentColor" stroke-opacity=".32" stroke-width=".9">
            @foreach ([34, 46, 58] as $row => $y)
                @foreach (range(0, 8) as $col)
                    <path d="M{{ 36 + $col * 11 + ($row % 2) * 5 }} {{ $y - 5 }} a 5.5 5.5 0 0 1 0 11" />
                @endforeach
            @endforeach
        </g>
        <g fill="none" stroke="currentColor" stroke-opacity=".34" stroke-width="3" stroke-linecap="round">
            <path d="M62 14 C 66 30, 66 52, 60 76" />
            <path d="M84 12 C 88 30, 88 54, 82 78" />
            <path d="M106 18 C 110 32, 110 50, 104 70" />
        </g>
    </g>
    {{-- outline, lateral line, gill, mouth, eye --}}
    <path d="M18 46 C 38 16, 96 8, 142 34 C 150 38, 154 42, 156 46 C 154 50, 150 54, 142 58 C 96 82, 38 76, 18 46 Z" fill="none" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2" />
    <path d="M30 44 C 66 38, 108 40, 140 44" fill="none" stroke="currentColor" stroke-opacity=".5" stroke-width=".9" stroke-dasharray="2 3" />
    <path class="fish-gill" d="M122 28 C 114 36, 114 56, 124 64" fill="none" stroke="currentColor" stroke-opacity=".6" stroke-width="1.4" stroke-linecap="round" />
    <path d="M155 47 C 150 49, 146 49, 142 48" fill="none" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2" stroke-linecap="round" />
    <circle cx="140" cy="41" r="5" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-opacity=".7" stroke-width="1.2" />
    <circle cx="141" cy="41" r="2.4" fill="currentColor" fill-opacity=".9" />
    <circle cx="142" cy="40" r=".8" fill="#fff" fill-opacity=".9" />
    {{-- pectoral fin --}}
    <path class="fish-pectoral" d="M116 52 C 106 56, 98 64, 96 72 C 106 70, 116 64, 122 56 Z" fill="currentColor" fill-opacity=".34" stroke="currentColor" stroke-opacity=".5" stroke-width=".8" />
</svg>
