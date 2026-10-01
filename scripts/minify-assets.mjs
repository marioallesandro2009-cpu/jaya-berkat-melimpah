// Minifies the site's own CSS and JS (public/css/style.css, public/js/main.js) into
// public/css/style.min.css and public/js/main.min.js. Run it on the laptop before committing:
//
//     npm install   (once)
//     npm run build
//
// The layout uses a .min file only while it is at least as new as its source, so forgetting to
// run this never serves stale assets; it only serves the larger source file. The server needs no Node.
import { build } from 'esbuild';

await Promise.all([
    build({ entryPoints: ['public/css/style.css'], outfile: 'public/css/style.min.css', minify: true, legalComments: 'none', logLevel: 'info' }),
    build({ entryPoints: ['public/js/main.js'], outfile: 'public/js/main.min.js', minify: true, target: 'es2017', legalComments: 'none', logLevel: 'info' }),
]);
