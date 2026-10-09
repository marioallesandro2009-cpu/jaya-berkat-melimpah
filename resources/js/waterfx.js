// Waterline foam, a stern wake and a soft contact shadow for the hero's boat. They are world-space strips
// that follow the swell: every frame their vertices are placed from the boat's matrix and the water's own
// height function, so they sit on the surface instead of floating beside it. Plain MeshBasicMaterial with
// per-vertex alpha, so they work with the WebGPU renderer too (which has no ShaderMaterial).
import {
    BufferAttribute, BufferGeometry, DoubleSide, Group, Mesh, MeshBasicMaterial, PlaneGeometry, Vector3,
} from 'three';

const REF_BEAM = 1.25;   // the half-beam the foam widths were tuned for; other hulls scale from it

// outline = { wl: [{ x, w, t }] waterline stations stern -> bow (t: 0 amidships .. 1 at the stem),
//             sternX, beam (half-beam), shadow: [centre x, length, width] }
export function createWaterFx(boat, waveHeight, outline) {
    const group = new Group();
    const basic = () => new MeshBasicMaterial({ vertexColors: true, transparent: true, depthWrite: false, side: DoubleSide, fog: false });
    const foamMat = basic(), shadowMat = basic();
    // rgba per vertex: foam is near-white, the shadow a deep teal
    const rgba = (n, r, g, b, alphaOf) => {
        const a = new Float32Array(n * 4);
        for (let i = 0; i < n; i++) a.set([r, g, b, alphaOf(i)], i * 4);
        return new BufferAttribute(a, 4);
    };

    // waterline outline (port stern -> bow -> starboard stern), with an outer edge pushed away from the hull
    const local = [], wl = outline.wl, sc = outline.beam / REF_BEAM;
    const loop = [...wl.map((p) => ({ ...p, s: -1 })), ...wl.slice().reverse().map((p) => ({ ...p, s: 1 }))];
    loop.forEach((p) => {
        const off = (0.14 + 0.24 * p.t) * sc;
        local.push({ x: p.x, z: p.s * Math.max(p.w - 0.03, 0), a: 1, k: p.t }, { x: p.x + 0.7 * off * p.t, z: p.s * (p.w + off), a: 0, k: p.t });
    });
    const ringGeo = new BufferGeometry();
    ringGeo.setAttribute('position', new BufferAttribute(new Float32Array(local.length * 3), 3));
    ringGeo.setAttribute('color', rgba(local.length, 0.95, 0.985, 0.985, (i) => Math.min(local[i].a * (0.55 + 0.6 * local[i].k), 1) * 0.8));
    const idx = [];
    for (let k = 0; k < loop.length - 1; k++) { const a = k * 2; idx.push(a, a + 1, a + 2, a + 1, a + 3, a + 2); }
    ringGeo.setAttribute('uv', new BufferAttribute(new Float32Array(local.length * 2), 2));   // the material expects one, even unused
    ringGeo.setIndex(idx);

    // wake: a widening strip trailing astern, with brighter edges
    const K = 20, C = 9, LEN = 7.5, wakeLocal = [], wakeA = [], widx = [];
    for (let k = 0; k < K; k++) {
        const s = (k / (K - 1)) * LEN, fade = Math.pow(1 - s / LEN, 1.5), hw = outline.beam * 0.56 + 0.17 * s;
        for (let c = 0; c < C; c++) {
            const cc = (c / (C - 1)) * 2 - 1;
            wakeLocal.push({ x: outline.sternX - 0.1 - s, z: cc * hw });
            wakeA.push(0.7 * fade * ((1 - Math.pow(Math.abs(cc), 1.6)) * 0.55 + 0.6 * Math.exp(-Math.pow((Math.abs(cc) - 0.8) / 0.16, 2))));
        }
        if (k < K - 1) for (let c = 0; c < C - 1; c++) { const a = k * C + c; widx.push(a, a + 1, a + C, a + 1, a + C + 1, a + C); }
    }
    const wakeGeo = new BufferGeometry();
    wakeGeo.setAttribute('position', new BufferAttribute(new Float32Array(wakeLocal.length * 3), 3));
    wakeGeo.setAttribute('color', rgba(wakeLocal.length, 0.95, 0.985, 0.985, (i) => Math.min(wakeA[i], 1)));
    wakeGeo.setAttribute('uv', new BufferAttribute(new Float32Array(wakeLocal.length * 2), 2));
    wakeGeo.setIndex(widx);

    // contact shadow under the hull: alpha falls off radially across a small grid
    const shadowGeo = new PlaneGeometry(1, 1, 12, 6);
    const shadowLocal = [], pos = shadowGeo.attributes.position;
    for (let i = 0; i < pos.count; i++) shadowLocal.push({ x: pos.getX(i) * outline.shadow[1] + outline.shadow[0], z: pos.getY(i) * outline.shadow[2] });
    shadowGeo.setAttribute('color', rgba(pos.count, 0.01, 0.07, 0.1, (i) => {
        const d = Math.hypot(pos.getX(i) * 2, pos.getY(i) * 2);
        const t = Math.min(Math.max((1 - d) / 0.75, 0), 1);
        return t * t * (3 - 2 * t) * 0.34;
    }));

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
            fill(shadowGeo, shadowLocal, t, 0.03);
            fill(ringGeo, local, t, 0.06);
            fill(wakeGeo, wakeLocal, t, 0.05);
        },
    };
}
