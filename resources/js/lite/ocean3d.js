// Hero ocean in 3D: a procedural sky, a Gerstner-wave sea that hazes into the horizon, and a fishing boat
// (glTF, see ./boat.js) that floats on it. The boat's buoyancy is computed from the very same wave
// functions as the water shader, so it rises, pitches and rolls with the swell and can never look sunk.
// Bundled with three.js (tree-shaken) by `npm run build` -> public/js/ocean3d.min.js.
//
// Built to cost as little as possible:
//  * the model is fetched at low priority as soon as the script runs, the scene is built only after the
//    page has loaded (the photo is the LCP image and stays until a boat is on the water);
//  * ~11 draw calls, pixel ratio capped (and lowered automatically if frames run slow), no MSAA on dense
//    screens, shadows on desktop only, 30 fps on phones, rendering stops when the hero is off screen;
//  * skipped entirely with reduced motion or data-saver; if anything fails the photo hero simply stays.
// Water colours and the Fresnel/sun-glint constants follow the "clear" preset of baditaflorin/threejs-water-free (MIT).
import {
    BackSide, Color, DirectionalLight, Group, HemisphereLight, Mesh, PMREMGenerator, PerspectiveCamera, PlaneGeometry,
    Scene, ShaderMaterial, SphereGeometry, Vector2, Vector3, WebGLRenderer,
} from 'three';
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

// [dir x, dir z, steepness, wavelength] -- shared by the shader (as vec4) and by the boat's buoyancy
const WAVES = [[1.0, 0.6, 0.20, 22], [0.8, 1.0, 0.16, 13], [-0.4, 1.0, 0.12, 7], [0.2, -1.0, 0.07, 3.6]];
const SPEED = 0.55;
const Z0 = -40;     // the water plane is shifted this far along -z

function waveHeight(x, z, t) {
    const gx = x, gz = z - Z0;
    let h = 0;
    for (const [dx, dz, s, len] of WAVES) {
        const l = Math.hypot(dx, dz), k = (Math.PI * 2) / len, c = Math.sqrt(9.8 / k);
        h += (s / k) * Math.sin(k * ((dx / l) * gx + (dz / l) * gz - c * t * SPEED));
    }
    return h;
}

// the shaders write colours as authored (no sRGB encoding), so hex values go in unconverted
const raw = (hex) => new Color().setRGB(((hex >> 16) & 255) / 255, ((hex >> 8) & 255) / 255, (hex & 255) / 255);

function boot(el) {
    let renderer;
    try {
        renderer = new WebGLRenderer({ antialias: !small && (devicePixelRatio || 1) < 1.5, powerPreference: 'low-power' });
    } catch (e) {
        return;
    }
    let pixelRatio = Math.min(devicePixelRatio || 1, small ? 1.25 : 1.5);
    renderer.setPixelRatio(pixelRatio);
    renderer.shadowMap.enabled = !small;
    el.appendChild(renderer.domElement);

    const scene = new Scene();
    const camera = new PerspectiveCamera(48, 1, 0.1, 400);
    const sunDir = new Vector3(0.5, 0.2, -0.8).normalize();

    const uniforms = {
        uTime: { value: 0 },
        uDeep: { value: raw(0x07485f) },
        uShallow: { value: raw(0x1b7f96) },
        uSss: { value: raw(0x1aa07f) },
        uSky: { value: raw(0x7aaed0) },
        uHorizon: { value: raw(0xa8cde8) },
        uZenith: { value: raw(0x174f9c) },
        uSunColor: { value: raw(0xfff6e8) },
        uSun: { value: sunDir },
    };

    // --- sky -------------------------------------------------------------------------------------
    const sky = new Mesh(
        new SphereGeometry(300, 24, 12),
        new ShaderMaterial({
            uniforms, side: BackSide, depthWrite: false,
            vertexShader: 'varying vec3 vW; void main(){ vec4 w = modelMatrix * vec4(position,1.0); vW = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }',
            fragmentShader: `
                uniform vec3 uSun, uHorizon, uZenith, uDeep, uSunColor; uniform float uTime; varying vec3 vW;
                float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
                float noise(vec2 p){ vec2 i = floor(p), f = fract(p); f = f*f*(3.0-2.0*f);
                    return mix(mix(hash(i), hash(i+vec2(1,0)), f.x), mix(hash(i+vec2(0,1)), hash(i+vec2(1,1)), f.x), f.y); }
                float fbm(vec2 p){ float v = 0.0, a = 0.5; for (int i = 0; i < 3; i++) { v += a * noise(p); p *= 2.07; a *= 0.5; } return v; }
                void main(){
                    vec3 d = normalize(vW - cameraPosition);
                    float h = max(d.y, 0.0);
                    vec3 col = mix(uHorizon, uZenith, pow(h, 0.45));
                    float s = max(dot(d, uSun), 0.0);
                    col += uSunColor * (pow(s, 2500.0) * 1.4 + pow(s, 14.0) * 0.09);
                    // clouds: a flat layer projected onto the sky, brighter on the sun side
                    vec2 uv = (d.xz / (h + 0.22) * 0.9 + vec2(uTime * 0.012, 0.0)) * 1.7;
                    float n = fbm(uv);
                    float cl = smoothstep(0.50, 0.82, n) * smoothstep(0.0, 0.2, h);
                    vec3 cc = mix(vec3(0.70, 0.78, 0.88), vec3(1.0, 0.99, 0.96), smoothstep(0.45, 0.95, n + (noise(uv + uSun.xz * 0.2) - 0.5) * 0.4));
                    col = mix(col, cc, cl * 0.9);
                    col = mix(col, uDeep * 1.3, smoothstep(0.0, 0.3, -d.y));
                    gl_FragColor = vec4(col, 1.0);
                }`,
        }),
    );
    sky.renderOrder = -1;
    sky.frustumCulled = false;
    scene.add(sky);

    // --- sea -------------------------------------------------------------------------------------
    const seg = small ? 90 : 160;
    const water = new Mesh(
        new PlaneGeometry(260, 180, seg, Math.round(seg * 0.7)),
        new ShaderMaterial({
            uniforms,
            vertexShader: `
                uniform float uTime;
                varying vec3 vPos; varying vec3 vNormal; varying float vCrest;
                vec3 gerstner(vec4 w, vec2 p, inout vec3 tangent, inout vec3 binormal) {
                    float k = 6.28318 / w.w;
                    float c = sqrt(9.8 / k);
                    vec2 d = normalize(w.xy);
                    float f = k * (dot(d, p) - c * uTime * ${SPEED});
                    float a = w.z / k;
                    tangent  += vec3(-d.x * d.x * (w.z * sin(f)), d.x * (w.z * cos(f)), -d.x * d.y * (w.z * sin(f)));
                    binormal += vec3(-d.x * d.y * (w.z * sin(f)), d.y * (w.z * cos(f)), -d.y * d.y * (w.z * sin(f)));
                    return vec3(d.x * a * cos(f), a * sin(f), d.y * a * cos(f));
                }
                void main() {
                    vec2 g = vec2(position.x, -position.y);
                    vec3 tangent = vec3(1.0, 0.0, 0.0);
                    vec3 binormal = vec3(0.0, 0.0, 1.0);
                    vec3 off = vec3(0.0);
                    ${WAVES.map(([x, z, s, l]) => `off += gerstner(vec4(${x.toFixed(2)}, ${z.toFixed(2)}, ${s.toFixed(2)}, ${l.toFixed(1)}), g, tangent, binormal);`).join('\n                    ')}
                    vec3 world = vec3(g.x + off.x, off.y, g.y + (${Z0.toFixed(1)}) + off.z);
                    vNormal = normalize(cross(binormal, tangent));
                    vCrest = off.y;
                    vPos = world;
                    gl_Position = projectionMatrix * viewMatrix * vec4(world, 1.0);
                }`,
            fragmentShader: `
                uniform vec3 uDeep, uShallow, uSss, uSky, uSun, uHorizon, uSunColor;
                uniform float uTime;
                varying vec3 vPos; varying vec3 vNormal; varying float vCrest;
                float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
                float noise(vec2 p) {
                    vec2 i = floor(p), f = fract(p); f = f * f * (3.0 - 2.0 * f);
                    return mix(mix(hash(i), hash(i + vec2(1, 0)), f.x), mix(hash(i + vec2(0, 1)), hash(i + vec2(1, 1)), f.x), f.y);
                }
                void main() {
                    vec3 n = normalize(vNormal);
                    n.xz += (vec2(noise(vPos.xz * 1.5 + uTime * 0.35), noise(vPos.xz * 1.5 - uTime * 0.28)) - 0.5) * 0.2;
                    n = normalize(n);
                    vec3 toCam = cameraPosition - vPos;
                    float dist = length(toCam);
                    vec3 view = toCam / dist;
                    float crest = clamp(vCrest * 1.5 + 0.35, 0.0, 1.0);
                    vec3 base = mix(uDeep, uShallow, clamp(vCrest * 1.6 + 0.4, 0.0, 1.0)) + uSss * crest * crest * 0.15;   // light through thin crests
                    float F = 0.02 + 0.98 * pow(1.0 - max(dot(n, view), 0.0), 5.0);                                           // Schlick, F0 = 0.02 (water)
                    vec3 col = mix(base, uSky, clamp(F * 0.95, 0.0, 1.0));
                    vec3 refl = reflect(-uSun, n);
                    col += uSunColor * pow(max(dot(refl, view), 0.0), 520.0) * 1.5;                                          // sun glitter
                    float foam = smoothstep(0.6, 0.98, vCrest * 2.3 + noise(vPos.xz * 0.9 + uTime * 0.2) * 0.55);
                    col = mix(col, vec3(0.92, 0.97, 0.97), foam * 0.55);
                    // haze: the far sea melts into the horizon colour of the sky
                    float fog = 1.0 - exp(-pow(dist * 0.016, 1.7));
                    gl_FragColor = vec4(mix(col, uHorizon, clamp(fog, 0.0, 1.0)), 1.0);
                }`,
        }),
    );
    water.frustumCulled = false;
    scene.add(water);

    // --- image-based light: the sky itself, so paint, steel and glass reflect it ---------------------
    const pmrem = new PMREMGenerator(renderer);
    const envScene = new Scene();
    envScene.add(new Mesh(sky.geometry, sky.material));
    scene.environment = pmrem.fromScene(envScene, 0, 0.1, 1000).texture;
    scene.environmentIntensity = 0.6;
    pmrem.dispose();

    // --- light -----------------------------------------------------------------------------------
    scene.add(new HemisphereLight(0xcfe8f5, 0x1a4a5e, 0.35));
    const sun = new DirectionalLight(0xfff6e8, 2.0);
    sun.castShadow = !small;
    sun.shadow.mapSize.set(1024, 1024);
    Object.assign(sun.shadow.camera, { left: -9, right: 9, top: 9, bottom: -9, near: 5, far: 140 });
    sun.shadow.bias = -0.0005;
    sun.shadow.normalBias = 0.04;
    scene.add(sun, sun.target);
    // the sun stands behind the boat as seen from the page, so a soft fill from the camera side keeps its near side readable
    const fill = new DirectionalLight(0xdfeaff, 0.8);
    fill.position.set(-8, 10, 18);
    scene.add(fill);

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
        const hb = waveHeight(bp.x + fwd.x * L, bp.z + fwd.z * L, t);
        const hs = waveHeight(bp.x - fwd.x * L, bp.z - fwd.z * L, t);
        const hp = waveHeight(bp.x - side.x * W, bp.z - side.z * W, t);
        const hr = waveHeight(bp.x + side.x * W, bp.z + side.z * W, t);
        const hc = waveHeight(bp.x, bp.z, t);
        boat.position.set(bp.x, (hb + hs + hp + hr + hc * 2) / 6 + 0.05, bp.z);
        boat.rotation.order = 'YZX';
        boat.rotation.set((hp - hr) / (2 * W) * 0.32, bp.yaw + Math.sin(t * 0.25) * 0.04, Math.atan2(hb - hs, 2 * L) * 0.45);
    }

    // --- sizing, pointer, scroll -----------------------------------------------------------------
    const pointer = new Vector2(0, 0), eased = new Vector2(0, 0);
    let scrollK = 0, visible = true, raf = 0, dead = false, last = performance.now(), lastDraw = 0;
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
        lookY = portrait ? -7.5 : 6.6;
        camZ = portrait ? 18 : 16;
        if (portrait) {
            Object.assign(bp, { x: 1.2, z: -13, yaw: 3.45 });
            boatSize = 1.05;
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
        }
    }

    const look = new Vector3();
    function frame(now) {
        const rawDt = (now - last) / 1000, dt = Math.min(rawDt, 0.05);
        last = now;
        uniforms.uTime.value += dt;
        const t = uniforms.uTime.value;
        eased.lerp(pointer, 1 - Math.pow(0.001, dt));
        camera.position.set(eased.x * 1.8, camY - eased.y * 0.5 - scrollK * 1.2, camZ);
        look.set(eased.x * -1.4, lookY + scrollK * 1.5, -20);
        camera.lookAt(look);
        sky.position.copy(camera.position);
        floatBoat(t);
        boat.updateMatrixWorld(true);
        vessel.update(t);
        vessel.updateFx(t);
        sun.position.copy(boat.position).addScaledVector(sunDir, 60);
        sun.target.position.copy(boat.position);
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
    function fail(e) {
        console.warn('[ocean3d] boat model unavailable, keeping the photo hero:', e);
        dead = true;
        renderer.dispose();
        el.replaceChildren();
        el.classList.remove('is-live');
        el.closest('.hero')?.classList.remove('has-3d');
    }
    modelBuffer.then((buffer) => loadBoat({ buffer, waveHeight })).then((v) => {
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
    }).catch(fail);
}

// last, so every constant above exists when the scene is built; after load + idle, so the page (and its
// LCP photo) never compete with it
if (host && modelBuffer) {
    const start = () => {
        try {
            boot(host);
        } catch (e) {
            console.warn('[ocean3d] scene failed, keeping the photo hero:', e);
            host.replaceChildren();
            host.classList.remove('is-live');
            host.closest('.hero')?.classList.remove('has-3d');
        }
    };
    const later = () => ('requestIdleCallback' in window ? requestIdleCallback(start, { timeout: 1500 }) : setTimeout(start, 200));
    if (document.readyState === 'complete') later(); else addEventListener('load', later, { once: true });
}
