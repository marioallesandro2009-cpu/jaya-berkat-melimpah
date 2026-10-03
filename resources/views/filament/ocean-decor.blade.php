{{-- Scene behind the admin login card (CSS in public/css/admin-ocean.css; shown only on the login page):
     light rays from the surface, fish swimming across, three layers of swell, bubbles. The small script moves
     the layers a little with the pointer and "dives" when the form is submitted. Decorative only. --}}
@php
    $wave = 'M0 150 C150 90 300 210 450 150 S750 90 900 150 S1050 210 1200 150 V300 H0Z';
@endphp
<div class="ocean-decor" aria-hidden="true" data-ocean>
    <div class="o-rays"><span></span><span></span><span></span><span></span><span></span></div>

    <div class="o-par o-par--far">
        <div class="o-fish o-fish--a"><x-ui.fish id="login-fish-a" /></div>
        <div class="o-fish o-fish--b"><x-ui.fish id="login-fish-b" /></div>
        <div class="o-fish o-fish--c"><x-ui.fish id="login-fish-c" /></div>
    </div>

    <div class="o-par o-par--mid">
        @foreach (range(1, 9) as $n)
            <i class="o-bubble o-bubble--{{ $n }}"></i>
        @endforeach
    </div>

    @foreach ([1, 2, 3] as $n)
        <div class="o-layer o-l{{ $n }}">
            <div class="o-swell">
                <div class="o-drift">
                    <svg viewBox="0 0 2400 300" preserveAspectRatio="none" focusable="false"><path d="{{ $wave }}"/><path transform="translate(1200 0)" d="{{ $wave }}"/></svg>
                </div>
            </div>
        </div>
    @endforeach

    <div class="o-dive"></div>
</div>
<script>
    (function () {
        var scene = document.querySelector('[data-ocean]');
        if (!scene || !document.querySelector('.fi-simple-layout')) return;

        // "dive" while the login form is being sent (the page then redirects, or shows the error and surfaces again)
        document.addEventListener('click', function (e) {
            if (e.target.closest('.fi-simple-layout button[type="submit"]')) {
                document.body.classList.add('is-diving');
                setTimeout(function () { document.body.classList.remove('is-diving'); }, 1800);
            }
        });

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        // the water follows the pointer a little (individual layers move by different amounts, see CSS)
        var tx = 0, ty = 0, x = 0, y = 0, idle = 0;
        window.addEventListener('pointermove', function (e) { tx = e.clientX / window.innerWidth - 0.5; ty = e.clientY / window.innerHeight - 0.5; idle = 0; }, { passive: true });
        (function loop() {
            x += (tx - x) * 0.05; y += (ty - y) * 0.05;
            if (Math.abs(tx - x) > 0.0005 || Math.abs(ty - y) > 0.0005 || idle++ < 5) {
                scene.style.setProperty('--px', x.toFixed(4));
                scene.style.setProperty('--py', y.toFixed(4));
            }
            window.requestAnimationFrame(loop);
        })();
    })();
</script>
