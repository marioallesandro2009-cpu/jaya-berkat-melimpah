{{-- Signature edge: the photograph melts into the next (cream) section through three layers of swell. Each layer is a
     sine-like path (period divides 1200, so the -50% drift loops without a seam), drifts at its own speed and bobs; the
     front layer carries a thin foam line. The layers roll in once when the edge scrolls into view (class .wave gets
     .is-visible from main.js). CSS: public/css/style.css, "Signature transition". --}}
@php
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
    @foreach ($layers as [$period, $amp, $base, $phase, $class])
        <div class="wave-l {{ $class }}">
            <svg viewBox="0 0 2400 160" preserveAspectRatio="none" focusable="false">
                <path class="wave-fill" d="{{ $path($period, $amp, $base, $phase) }} L2400,160 L0,160 Z"/>
                @if ($class === 'wave-l3')
                    <path class="wave-foam" d="{{ $path($period, $amp, $base, $phase) }}"/>
                @endif
            </svg>
        </div>
    @endforeach
</div>
