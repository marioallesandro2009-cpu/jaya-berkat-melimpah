// The hero's fishing boat: public/models/fishing-boat.glb (see docs/image-credits.md), optimised to about
// 11 draw calls, 68k triangles and 1.6 MB (a 0.9 MB, 35k-triangle variant for phones).
// It is scaled to a fixed length, laid along +x with the bow forward, settled to its waterline, and given
// the foam, wake and contact shadow of ./waterfx.js. Glass and lenses are turned from "transmission" (which
// makes three.js render the whole scene a second time) into plain alpha: the same look for a fraction of the cost.
import { Box3, MathUtils, Group, Vector3 } from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { createWaterFx } from './waterfx.js';

const LENGTH = 9.5;          // world units, bow to stern
const WATERLINE = 0.82;      // model units above the keel where the hull meets the water

export function loadBoat({ buffer, waveHeight }) {
    return new Promise((resolve, reject) => {
        new GLTFLoader().parse(buffer, '', (gltf) => {
            try {
                const ship = gltf.scene;
                const raw = new Box3().setFromObject(ship).getSize(new Vector3());
                const s = LENGTH / raw.x;
                const pivot = new Group();
                pivot.add(ship);
                ship.scale.setScalar(s);
                pivot.updateMatrixWorld(true);

                // centre on midships; the keel is at y = 0, so sink the hull to its waterline
                const box = new Box3().setFromObject(ship);
                ship.position.x -= (box.min.x + box.max.x) / 2;
                ship.position.y -= WATERLINE * s;

                ship.traverse((o) => {
                    if (!o.isMesh) return;
                    o.castShadow = true;
                    o.receiveShadow = true;
                    const m = o.material;
                    m.envMapIntensity = 1.2;
                    if (m.transmission > 0) {                    // glazing and lenses
                        m.transmission = 0;
                        m.transparent = true;
                        m.depthWrite = false;
                        m.opacity = /glaz/i.test(m.name) ? 0.42 : 0.92;
                        m.roughness = Math.min(m.roughness, 0.12);
                    }
                });

                // waterline outline: pointed bow, flat transom, beam taken from the model
                const size = new Box3().setFromObject(ship).getSize(new Vector3());
                const half = size.z / 2 * 0.86, x0 = -LENGTH / 2 + 0.55, x1 = LENGTH / 2;
                const wl = Array.from({ length: 40 }, (_, i) => {
                    const u = i / 39, x = x0 + (x1 - x0) * u;
                    const bow = MathUtils.smoothstep(u, 0.45, 1);
                    return { x, w: half * (1 - Math.pow(bow, 1.7)) * (0.82 + 0.18 * MathUtils.smoothstep(u, 0, 0.15)), t: MathUtils.clamp((u - 0.55) / 0.45, 0, 1) };
                });
                const fx = createWaterFx(pivot, waveHeight, { wl, sternX: x0, beam: half, shadow: [0.2, LENGTH * 1.12, size.z * 2.1] });
                resolve({ group: pivot, fx: fx.group, update() {}, updateFx: fx.update });
            } catch (e) {
                reject(e);
            }
        }, reject);
    });
}
