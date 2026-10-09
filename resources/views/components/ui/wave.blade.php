{{-- Signature edge: the photograph melts into the next (cream) section through three layers of swell. Each layer is a
     sine-like path (period divides 1200, so the -50% drift loops without a seam), drifts at its own speed and bobs; the
     front layer carries a thin foam line. The crest of every layer is feathered: its fill is a vertical gradient that is
     transparent at the top of the wave and reaches full strength lower down, so the edge reads as spray and mist, not a
     razor line. The layers roll in once when the edge scrolls into view (class .wave gets .is-visible from main.js).
     CSS: public/css/style.css, "Signature transition". Colours come from --wc / --wa (colour, strength) set per layer there. --}}
@php
    $uid = 'wv'.substr(md5(uniqid('', true)), 0, 6);

    // [period, amplitude, baseline y, phase (in half periods), layer class]
    $layers = [[1200, 26, 78, 0, 'wave-l1'], [600, 17, 96, 1, 'wave-l2'], [400, 11, 112, 0, 'wave-l3']];

    $path = function (int $period, int $amp, int $base, int $phase): string {
        $half = intdiv($period, 2);
        $d = "M0,{$base}";
        $sign = $phase % 2 === 0 ? -1 : 1;

        for ($x = 0; $x < 2400; $x += $half) {
            $d .= ' Q'.($x + intdiv($half, 2)).','.($base + $sign * 2 * $amp).' '.($x + $half).','.$base;
            $sign = -$sign;
        }

        return $d;
    };
@endphp
<div class="wave" aria-hidden="true">
    @foreach ($layers as $i => [$period, $amp, $base, $phase, $class])
        <div class="wave-l {{ $class }}">
            <svg viewBox="0 0 2400 160" preserveAspectRatio="none" focusable="false">
                <defs>
                    <linearGradient id="{{ $uid }}-{{ $i }}" gradientUnits="userSpaceOnUse" x1="0" x2="0" y1="{{ $base - $amp - 14 }}" y2="{{ min($base + $amp + 60, 160) }}">
                        <stop offset="0" style="stop-color: var(--wc); stop-opacity: 0"/>
                        <stop offset="0.28" style="stop-color: var(--wc); stop-opacity: calc(var(--wa) * 0.55)"/>
                        <stop offset="0.55" style="stop-color: var(--wc); stop-opacity: var(--wa)"/>
                        <stop offset="1" style="stop-color: var(--wc); stop-opacity: var(--wa)"/>
                    </linearGradient>
                </defs>
                <path class="wave-fill" fill="url(#{{ $uid }}-{{ $i }})" d="{{ $path($period, $amp, $base, $phase) }} L2400,160 L0,160 Z"/>
                @if ($class === 'wave-l3')
                    <path class="wave-foam" d="{{ $path($period, $amp, $base, $phase) }}"/>
                @endif
            </svg>
        </div>
    @endforeach
</div>
