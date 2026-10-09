// Hero ocean in 3D: the FFT sea and sky of baditaflorin/threejs-water-free (MIT, vendored in ./vendor) with a
// glTF fishing boat (./boat.js) that floats on it. The boat is settled on the very wave field the GPU draws
// (water.getHeight), so it rises, pitches and rolls with the swell and can never look sunk.
// Bundled with three.js (tree-shaken) by `npm run build` -> public/js/ocean3d.min.js. A lighter hand-written
// WebGL version of the same hero lives in ./lite (public/js/ocean3d-lite.min.js).
//
// Built to cost as little as possible:
//  * the model is fetched at low priority as soon as the script runs; the scene is built only after the page
//    has loaded (the photo is the LCP image and stays until a boat is on the water);
//  * the library's "low" quality (64x64 FFT on the CPU, no refraction, no animated clouds, no micro-normals),
//    ~11 draw calls for the boat, pixel ratio capped and lowered automatically if frames run slow, shadows on
//    desktop only, 30 fps on phones, rendering stops when the hero is off screen;
//  * WebGPU where the browser has it, the library's WebGL2 backend otherwise;
//  * skipped entirely with reduced motion or data-saver; if anything fails the photo hero simply stays.
import {
    DirectionalLight, Group, HemisphereLight, PerspectiveCamera, PMREMGenerator, Scene, Vector2, Vector3, WebGPURenderer,
} from 'three/webgpu';
import { Water } from './vendor/threejs-water-free/src/index.js';
import { resolvePreset } from './vendor/threejs-water-free/src/presets.js';
import { QUALITY_LEVELS } from './vendor/threejs-water-free/src/quality.js';
import { loadBoat } from './boat.js';

const host = document.querySelector('[data-ocean3d]');
const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
// weak devices and slow links keep the photograph: the 3D hero is about 2.7 MB of downloads and a continuous GPU load
const conn = navigator.connection || {};
const weak = (navigator.deviceMemory && navigator.deviceMemory < 4) || (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) || /(^|-)2g|3g/.test(conn.effectiveType || '');
const saveData = conn.saveData || weak;
const small = matchMedia('(max-width: 720px)').matches;

// start the download right away, at low priority, so it is ready by the time the scene is
const modelUrl = host && (small ? host.dataset.modelSm : host.dataset.model);
const modelBuffer = modelUrl && !reduced && !saveData
    ? fetch(modelUrl, { priority: 'low' }).then((r) => { if (!r.ok) throw new Error(`model ${r.status}`); return r.arrayBuffer(); })
    : null;
modelBuffer?.catch(() => {});   // handled in boot(); keeps an early failure from showing as unhandled

// Where the sun stands (degrees; azimuth 270 is straight ahead of the camera). The camera looks a little up on
// wide screens and down on phones, so the sun is lower in the sky on phones to stay in frame.
const SUN_LANDSCAPE = { el: 21, az: 292 };
const SUN_PORTRAIT = { el: 5, az: 268 };

// The sun's glow, drawn as two page elements over the canvas (a soft halo and the bright core): still, so the sky is
// calm. Only its place changes: update() puts it on the projected sun, which drifts a little with the camera.
function createSunFx(el) {
    const div = (cls, parent) => { const d = document.createElement('div'); d.className = cls; parent.appendChild(d); return d; };
    const root = div('sun-fx', el);
    const at = div('sun-at', root);
    div('sun-glow', at); div('sun-core', at);
    return {
        update(x, y, shown) {
            root.style.visibility = shown ? 'visible' : 'hidden';
            if (shown) at.style.transform = `translate3d(${x.toFixed(1)}px, ${y.toFixed(1)}px, 0)`;
        },
    };
}

// The library's "clear" preset, re-tuned for a premium deep-blue sea: three FFT cascades whose tiles are not
// multiples of each other and whose winds cross (a long swell, a cross-sea and fine ripples), so no repeating
// pattern shows near the horizon; three swells from different directions; deeper water and sky; less crest
// foam and a tighter, weaker sun glint.
function oceanPreset() {
    const DEG = Math.PI / 180;
    const cascade = (patchSize, windSpeed, dir, waveHeight, fetch, gamma, shortWaves, foamThreshold, choppiness, seed) => (
        { size: 64, patchSize, windSpeed, windDir: dir * DEG, fetch, gamma, waveHeight, choppiness, shortWaves, foamThreshold, seed });
    const swell = (dir, wavelength, amplitude, steepness) => ({ direction: [Math.cos(dir * DEG), Math.sin(dir * DEG)], wavelength, amplitude, steepness, speed: 1 });
    const p = structuredClone(resolvePreset('clear'));
    p.sun = { ...p.sun, elevation: SUN_LANDSCAPE.el, azimuth: SUN_LANDSCAPE.az, intensity: 0.8 };   // in front of the camera, in view, above the boat
    p.sky = { ...p.sky, zenithColor: 0x1450a8, horizonColor: 0x8db8de, groundColor: 0x1d3f5c, rayleigh: 0.12, exposure: 1.0 };
    p.ocean.cascades = [
        cascade(400, 8, 40, 0.78, 100000, 3.3, 0.4, 0.93, 1.0, 3),
        cascade(151, 7, 78, 0.3, 30000, 2.6, 0.25, 0.9, 1.15, 11),
        cascade(83, 5.6, 112, 0.12, 12000, 2.0, 0.06, 0.88, 1.2, 23),   // fine ripples, kept long enough not to alias near the horizon
    ];
    p.ocean.gerstner = [swell(40, 140, 0.22, 0.35), swell(96, 95, 0.1, 0.3), swell(-20, 210, 0.14, 0.3)];
    Object.assign(p.water, { waterColor: 0x062f4a, absorptionColor: [0.3, 0.07, 0.04], sssColor: 0x127a73, sssStrength: 0.35, crestFoam: 0.22, foamScale: 1.2, sunShininess: 900 });
    p.fog = { color: 0x062f4a, density: 0.01 };
    return p;
}

async function boot(el) {
    const renderer = new WebGPURenderer({ antialias: !small && (devicePixelRatio || 1) < 1.5, powerPreference: 'low-power' });
    let dead = false;
    const fail = (e) => {
        if (dead) return;
        dead = true;
        console.warn('[ocean3d] unavailable, keeping the photo hero:', e);
        try { renderer.dispose(); } catch (err) { /* nothing to free yet */ }
        el.replaceChildren();
        el.classList.remove('is-live');
        el.closest('.hero')?.classList.remove('has-3d');
    };

    try {
        await renderer.init();
    } catch (e) {
        fail(e);
        return;
    }

    let pixelRatio = Math.min(devicePixelRatio || 1, small ? 1.25 : 1.5);
    renderer.setPixelRatio(pixelRatio);
    renderer.shadowMap.enabled = !small;
    el.appendChild(renderer.domElement);

    const scene = new Scene();
    const camera = new PerspectiveCamera(48, 1, 0.1, 14000);

    // --- sea and sky (the library) -----------------------------------------------------------------
    // the library's "low" quality, plus animated clouds on screens big enough to afford them
    const water = new Water({ preset: oceanPreset(), quality: { ...QUALITY_LEVELS.low, clouds: !small } });
    scene.add(water.object);
    const waveHeight = (x, z) => water.getHeight(x, z);
    const sunDir = water.skyUniforms.sunDirection.value;   // live: setSun() updates it in place

    // --- reflections: the sky itself, captured once, so paint, steel and glass reflect it -------------
    let envLight = 0;
    const skyParent = water.sky.parent, skyPos = water.sky.position.clone();
    try {
        const pmrem = new PMREMGenerator(renderer);
        const skyScene = new Scene();
        skyScene.add(water.sky);                       // borrowed for the capture, given back below
        water.sky.position.set(0, 0, 0);
        scene.environment = pmrem.fromScene(skyScene, 0, 0.1, 9000).texture;
        scene.environmentIntensity = 0.8;
        pmrem.dispose();
        envLight = 1;
    } catch (e) {
        console.warn('[ocean3d] no sky reflections:', e);
    } finally {
        skyParent.add(water.sky);
        water.sky.position.copy(skyPos);
    }

    // --- light -------------------------------------------------------------------------------------
    scene.add(new HemisphereLight(0xdceaf5, 0x3d6a85, envLight ? 0.7 : 1.1));
    // the visible sun stands behind the boat as seen from the page, which would leave its near side in shade: a key
    // light from the camera's upper left lights it (and casts the shadows), and a warm rim light from the real
    // sun edges the hull and rails
    const key = new DirectionalLight(0xfffaf2, 3.1);
    const keyDir = new Vector3(-0.45, 0.6, 0.65).normalize();
    key.castShadow = !small;
    key.shadow.mapSize.set(1024, 1024);
    Object.assign(key.shadow.camera, { left: -9, right: 9, top: 9, bottom: -9, near: 5, far: 140 });
    key.shadow.bias = -0.0005;
    key.shadow.normalBias = 0.04;
    scene.add(key, key.target);
    const rim = new DirectionalLight(0xffe7bd, 1.3);
    scene.add(rim, rim.target);
    const sunFx = createSunFx(el);
    const sunBase = { ...SUN_LANDSCAPE };
    const sunPoint = new Vector3();

    // --- the boat: the moving root, with the loaded boat hanging under it --------------------------
    const boat = new Group();
    scene.add(boat);
    let vessel = null, boatSize = 1;
    const bp = { x: 5.8, z: -4, yaw: 3.5 };

    // sample the swell under the hull's four corners, then settle the boat on it
    const fwd = new Vector3(), side = new Vector3();
    function floatBoat(t) {
        const cy = Math.cos(bp.yaw), sy = Math.sin(bp.yaw);
        fwd.set(cy, 0, -sy);              // where the bow points
        side.set(sy, 0, cy);              // starboard
        const L = 4.2 * boatSize, W = 1.2 * boatSize;
        const hb = waveHeight(bp.x + fwd.x * L, bp.z + fwd.z * L);
        const hs = waveHeight(bp.x - fwd.x * L, bp.z - fwd.z * L);
        const hp = waveHeight(bp.x - side.x * W, bp.z - side.z * W);
        const hr = waveHeight(bp.x + side.x * W, bp.z + side.z * W);
        const hc = waveHeight(bp.x, bp.z);
        boat.position.set(bp.x, (hb + hs + hp + hr + hc * 2) / 6 + 0.05, bp.z);
        boat.rotation.order = 'YZX';
        boat.rotation.set((hp - hr) / (2 * W) * 0.32, bp.yaw + Math.sin(t * 0.25) * 0.04, Math.atan2(hb - hs, 2 * L) * 0.45);
    }

    // --- sizing, pointer, scroll -------------------------------------------------------------------
    const pointer = new Vector2(0, 0), eased = new Vector2(0, 0);
    let scrollK = 0, visible = true, raf = 0, time = 0, last = performance.now(), lastDraw = 0;
    let camY = 3.2, lookY = 6.6, camZ = 16;

    function resize() {
        const w = el.clientWidth || 1, h = el.clientHeight || 1;
        renderer.setPixelRatio(pixelRatio);
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        const portrait = w / h < 1;
        camera.fov = portrait ? 66 : 48;
        camera.updateProjectionMatrix();
        camY = portrait ? 3.6 : 3.2;
        lookY = portrait ? -11 : 6.6;
        camZ = portrait ? 18 : 16;
        Object.assign(sunBase, portrait ? SUN_PORTRAIT : SUN_LANDSCAPE);
        if (portrait) {
            Object.assign(bp, { x: 1.0, z: -16, yaw: 3.45 });   // near the horizon, large: it fills the width above the headline
            boatSize = 1.9;
        } else {
            // keep the boat on the right third whatever the window width: scale and x follow the visible half-width
            bp.z = -4; bp.yaw = 3.5;
            const halfW = Math.tan((camera.fov * Math.PI) / 360) * (camZ - bp.z) * camera.aspect;
            bp.x = halfW * 0.45;
            boatSize = Math.min(Math.max(halfW * 0.102, 0.95), 1.5);
        }
        boat.scale.setScalar(boatSize);
    }
    resize();
    addEventListener('resize', resize, { passive: true });

    if (!small) {
        addEventListener('pointermove', (e) => {
            pointer.set((e.clientX / innerWidth - 0.5) * 2, (e.clientY / innerHeight - 0.5) * 2);
        }, { passive: true });
    }
    addEventListener('scroll', () => {
        scrollK = Math.min(Math.max(scrollY / (el.clientHeight || 1), 0), 1);
    }, { passive: true });

    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        if (visible && vessel && !raf && !document.hidden) loop();
    }).observe(el);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && visible && vessel && !raf) loop();
    });

    // if frames run slow, trade resolution for speed (a soft sea is fine; a stuttering one is not)
    let slowSum = 0, slowN = 0, frames = 0;
    function adapt(rawDt) {
        if (++frames < 40) return;                       // ignore shader compilation and warm-up
        slowSum += rawDt; slowN++;
        if (slowN < 60) return;
        const avg = slowSum / slowN;
        slowSum = 0; slowN = 0;
        if (avg > (small ? 0.045 : 0.026) && pixelRatio > 0.8) {
            pixelRatio = Math.max(0.75, pixelRatio * 0.8);
            resize();
            renderer.render(scene, camera);          // setSize clears the canvas: draw again at once, no blank frame
        }
    }

    const look = new Vector3();
    function frame(now) {
        const rawDt = (now - last) / 1000, dt = Math.min(rawDt, 0.05);
        last = now;
        time += dt;
        eased.lerp(pointer, 1 - Math.pow(0.001, dt));
        camera.position.set(eased.x * 1.8, camY - eased.y * 0.5 - scrollK * 1.2, camZ);
        look.set(eased.x * -1.4, lookY + scrollK * 1.5, -20);
        camera.lookAt(look);
        water.update(time, camera);
        floatBoat(time);
        boat.updateMatrixWorld(true);
        vessel.update(time);
        vessel.updateFx(time);
        // the sun drifts a little, so the sky and the glitter on the water are never still
        water.setSun(sunBase.el + Math.sin(time * 0.05) * 1.1, sunBase.az + Math.sin(time * 0.031) * 2);
        key.position.copy(boat.position).addScaledVector(keyDir, 60);
        key.target.position.copy(boat.position);
        rim.position.copy(boat.position).addScaledVector(sunDir, 60);
        rim.target.position.copy(boat.position);
        sunPoint.copy(sunDir).multiplyScalar(2000).add(camera.position).project(camera);
        sunFx.update((sunPoint.x * 0.5 + 0.5) * el.clientWidth, (0.5 - sunPoint.y * 0.5) * el.clientHeight, sunPoint.z < 1 && Math.abs(sunPoint.x) < 1.6 && Math.abs(sunPoint.y) < 1.6);
        renderer.render(scene, camera);
        adapt(rawDt);
    }
    function loop() {
        raf = requestAnimationFrame((now) => {
            raf = 0;
            if (dead || !visible || document.hidden) return;
            if (small && now - lastDraw < 32) { loop(); return; }     // 30 fps on phones: half the battery
            lastDraw = now;
            frame(now);
            loop();
        });
    }

    // nothing is drawn (or revealed) until the boat is ready; if it fails, the photo hero just stays
    try {
        const buffer = await modelBuffer;
        const v = await loadBoat({ buffer, waveHeight });
        if (dead) return;
        vessel = v;
        boat.add(v.group);
        scene.add(v.fx);
        last = performance.now();
        frame(last);
        requestAnimationFrame(() => {
            el.classList.add('is-live');
            el.closest('.hero')?.classList.add('has-3d');
        });
        loop();
    } catch (e) {
        fail(e);
    }
}

// last, so every constant above exists when the scene is built; after load + idle, so the page (and its
// LCP photo) never compete with it
if (host && modelBuffer) {
    const start = () => boot(host).catch((e) => {
        console.warn('[ocean3d] scene failed, keeping the photo hero:', e);
        host.replaceChildren();
        host.classList.remove('is-live');
        host.closest('.hero')?.classList.remove('has-3d');
    });
    const later = () => ('requestIdleCallback' in window ? requestIdleCallback(start, { timeout: 1500 }) : setTimeout(start, 200));
    if (document.readyState === 'complete') later(); else addEventListener('load', later, { once: true });
}
