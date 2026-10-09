// Waterline foam, a stern wake and a soft contact shadow for the hero's boat. They are world-space strips
// that follow the swell: every frame their vertices are placed from the boat's matrix and the same wave
// function the water shader uses, so they sit on the surface instead of floating beside it.
import {
    BufferAttribute, BufferGeometry, DoubleSide, Group, Mesh, PlaneGeometry, ShaderMaterial, Vector3,
} from 'three';

const REF_BEAM = 1.25;   // the half-beam the foam widths were tuned for; other hulls scale from it

// outline = { wl: [{ x, w, t }] waterline stations stern -> bow (t: 0 amidships .. 1 at the stem),
//             sternX, beam (half-beam), shadow: [centre x, length, width] }
export function createWaterFx(boat, waveHeight, outline) {
    const group = new Group();
    const noise = `
        float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
        float noise(vec2 p) { vec2 i = floor(p), f = fract(p); f = f * f * (3.0 - 2.0 * f);
            return mix(mix(hash(i), hash(i + vec2(1, 0)), f.x), mix(hash(i + vec2(0, 1)), hash(i + vec2(1, 1)), f.x), f.y); }`;
    const foamMat = new ShaderMaterial({
        uniforms: { uTime: { value: 0 } }, transparent: true, depthWrite: false, side: DoubleSide,
        vertexShader: 'attribute float aA; attribute vec2 aUv; varying float vA; varying vec2 vUv; void main() { vA = aA; vUv = aUv; gl_Position = projectionMatrix * viewMatrix * vec4(position, 1.0); }',
        fragmentShader: `uniform float uTime; varying float vA; varying vec2 vUv; ${noise}
            void main() { float n = noise(vUv + vec2(-uTime * 0.5, uTime * 0.12)) * 0.65 + noise(vUv * 2.3 + vec2(-uTime * 0.9, 0.0)) * 0.35;
                gl_FragColor = vec4(0.95, 0.985, 0.985, clamp(vA * (0.35 + 1.0 * n), 0.0, 1.0) * 0.85); }`,
    });
    const shadowMat = new ShaderMaterial({
        transparent: true, depthWrite: false,
        vertexShader: 'varying vec2 vUv; void main() { vUv = uv; gl_Position = projectionMatrix * viewMatrix * vec4(position, 1.0); }',
        fragmentShader: 'varying vec2 vUv; void main() { float d = length((vUv - 0.5) * vec2(2.0, 2.0)); gl_FragColor = vec4(0.01, 0.07, 0.1, smoothstep(1.0, 0.25, d) * 0.34); }',
    });

    // waterline outline (port stern -> bow -> starboard stern), with an outer edge pushed away from the hull
    const local = [], wl = outline.wl, sc = outline.beam / REF_BEAM;
    const loop = [...wl.map((p) => ({ ...p, s: -1 })), ...wl.slice().reverse().map((p) => ({ ...p, s: 1 }))];
    loop.forEach((p) => {
        const off = (0.14 + 0.24 * p.t) * sc;
        local.push({ x: p.x, z: p.s * Math.max(p.w - 0.03, 0), a: 1, k: p.t }, { x: p.x + 0.7 * off * p.t, z: p.s * (p.w + off), a: 0, k: p.t });
    });
    const ringGeo = new BufferGeometry();
    ringGeo.setAttribute('position', new BufferAttribute(new Float32Array(local.length * 3), 3));
    ringGeo.setAttribute('aA', new BufferAttribute(new Float32Array(local.map((p) => p.a * (0.7 + 0.8 * p.k))), 1));
    ringGeo.setAttribute('aUv', new BufferAttribute(new Float32Array(local.flatMap((p, i) => [(i >> 1) * 0.35, p.a * 2])), 2));
    const idx = [];
    for (let k = 0; k < loop.length - 1; k++) { const a = k * 2; idx.push(a, a + 1, a + 2, a + 1, a + 3, a + 2); }
    ringGeo.setIndex(idx);

    // wake: a widening strip trailing astern, with brighter edges
    const K = 20, C = 9, LEN = 7.5, wakeLocal = [], wakeA = [], wakeUv = [], widx = [];
    for (let k = 0; k < K; k++) {
        const s = (k / (K - 1)) * LEN, fade = Math.pow(1 - s / LEN, 1.5), hw = outline.beam * 0.56 + 0.17 * s;
        for (let c = 0; c < C; c++) {
            const cc = (c / (C - 1)) * 2 - 1;
            wakeLocal.push({ x: outline.sternX - 0.1 - s, z: cc * hw });
            wakeA.push(0.5 * fade * ((1 - Math.pow(Math.abs(cc), 1.6)) * 0.55 + 0.6 * Math.exp(-Math.pow((Math.abs(cc) - 0.8) / 0.16, 2))));
            wakeUv.push(s * 1.1, cc * 2.5);
        }
        if (k < K - 1) for (let c = 0; c < C - 1; c++) { const a = k * C + c; widx.push(a, a + 1, a + C, a + 1, a + C + 1, a + C); }
    }
    const wakeGeo = new BufferGeometry();
    wakeGeo.setAttribute('position', new BufferAttribute(new Float32Array(wakeLocal.length * 3), 3));
    wakeGeo.setAttribute('aA', new BufferAttribute(new Float32Array(wakeA), 1));
    wakeGeo.setAttribute('aUv', new BufferAttribute(new Float32Array(wakeUv), 2));
    wakeGeo.setIndex(widx);

    // contact shadow under the hull
    const shadowGeo = new PlaneGeometry(1, 1, 12, 6);
    const shadowLocal = [];
    for (let i = 0; i < shadowGeo.attributes.position.count; i++) {
        shadowLocal.push({ x: shadowGeo.attributes.position.getX(i) * outline.shadow[1] + outline.shadow[0], z: shadowGeo.attributes.position.getY(i) * outline.shadow[2] });
    }

    for (const [g, m, order] of [[shadowGeo, shadowMat, 1], [ringGeo, foamMat, 2], [wakeGeo, foamMat, 2]]) {
        const mesh = new Mesh(g, m);
        mesh.frustumCulled = false;
        mesh.renderOrder = order;
        group.add(mesh);
    }

    const v = new Vector3();
    const fill = (geo, pts, t, lift) => {
        const a = geo.attributes.position.array;
        pts.forEach((p, i) => {
            v.set(p.x, 0, p.z).applyMatrix4(boat.matrixWorld);
            a[i * 3] = v.x; a[i * 3 + 1] = waveHeight(v.x, v.z, t) + lift; a[i * 3 + 2] = v.z;
        });
        geo.attributes.position.needsUpdate = true;
    };
    return {
        group,
        update(t) {
            foamMat.uniforms.uTime.value = t;
            fill(shadowGeo, shadowLocal, t, 0.03);
            fill(ringGeo, local, t, 0.06);
            fill(wakeGeo, wakeLocal, t, 0.05);
        },
    };
}
