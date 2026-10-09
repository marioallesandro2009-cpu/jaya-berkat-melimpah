// Minifies the site's own CSS and JS (public/css/style.css, public/js/main.js) into
// public/css/style.min.css and public/js/main.min.js, and bundles the 3D hero:
//   resources/js/ocean3d.js       (three.js WebGPU build + the vendored water library) -> public/js/ocean3d.min.js
//   resources/js/lite/ocean3d.js  (hand-written WebGL sea, much lighter)               -> public/js/ocean3d-lite.min.js
// Run it on the laptop before committing:
//
//     npm install   (once)
//     npm run build
//
// The layout uses a .min file only while it is at least as new as its source, so forgetting to
// run this never serves stale assets; it only serves the larger source file. The server needs no Node.
// The ocean3d bundles have no unminified twin: they are the only built copies, so commit them.
import { build } from 'esbuild';

const bundle = (entry, outfile) => build({ entryPoints: [entry], outfile, bundle: true, minify: true, format: 'iife', target: 'es2020', legalComments: 'none', logLevel: 'info' });

await Promise.all([
    build({ entryPoints: ['public/css/style.css'], outfile: 'public/css/style.min.css', minify: true, legalComments: 'none', logLevel: 'info' }),
    build({ entryPoints: ['public/js/main.js'], outfile: 'public/js/main.min.js', minify: true, target: 'es2017', legalComments: 'none', logLevel: 'info' }),
    bundle('resources/js/ocean3d.js', 'public/js/ocean3d.min.js'),
    bundle('resources/js/lite/ocean3d.js', 'public/js/ocean3d-lite.min.js'),
]);
