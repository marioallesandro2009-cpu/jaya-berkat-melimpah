// Browser smoke test: loads the real site in headless Chromium and checks what Pest cannot see.
//
//   BASE_URL=http://127.0.0.1:8000 node tests/e2e/smoke.mjs        (npm run test:e2e)
//   CHROME_PATH=/path/to/chrome   use an installed Chrome instead of Playwright's Chromium
//
// For every public page, at phone and desktop width: no uncaught JavaScript error, no console error, exactly
// one <h1>, no raw template text on screen ("@if", "{{": the symptom of a broken Blade view), and no horizontal
// scroll. On the home page also: the 3D hero comes alive (software WebGL is fine), falls back to the photograph
// when its model cannot be loaded, and stays off with reduced motion. Exits 1 on the first failed run.
import { chromium } from 'playwright-core';

const BASE = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const PAGES = ['/', '/id', '/company', '/products', '/products/tuna/yellowfin-tuna', '/products/yellowfin-tuna-whole', '/news', '/photo-credits'];
const WIDTHS = [390, 1440];
const GL_ARGS = ['--no-sandbox', '--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'];

const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH || undefined, headless: true, args: GL_ARGS });
const failures = [];
const fail = (where, what) => { failures.push(`${where}: ${what}`); console.log(`  FAIL  ${where}: ${what}`); };
const ok = (where) => console.log(`  ok    ${where}`);

async function open(path, { width = 1440, reducedMotion = 'no-preference', block } = {}) {
    const ctx = await browser.newContext({ viewport: { width, height: 900 }, reducedMotion });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(`uncaught: ${e.message}`));
    page.on('console', (m) => { if (m.type() === 'error' && !(block && /404/.test(m.text()))) errors.push(`console: ${m.text().slice(0, 160)}`); });
    if (block) await page.route(block, (r) => r.fulfill({ status: 404, body: 'blocked by the test' }));
    const res = await page.goto(BASE + path, { waitUntil: 'load', timeout: 90000 });
    return { ctx, page, errors, status: res?.status() };
}

// 1. every page, both widths
for (const path of PAGES) {
    for (const width of WIDTHS) {
        const where = `${path} @${width}`;
        let run;
        try { run = await open(path, { width }); } catch (e) { fail(where, `did not load (${e.message.split('\n')[0]})`); continue; }
        const { ctx, page, errors, status } = run;
        await page.waitForTimeout(1200);
        const facts = await page.evaluate(() => ({
            h1: document.querySelectorAll('h1').length,
            raw: /@(if|elseif|else|endif|foreach|endforeach|php)\b|\{\{|\}\}/.test(document.body.innerText),
            overflow: document.documentElement.scrollWidth - innerWidth,
        }));
        const bad = [];
        if (status !== 200) bad.push(`HTTP ${status}`);
        if (facts.h1 !== 1) bad.push(`${facts.h1} <h1>`);
        if (facts.raw) bad.push('raw template text on screen');
        if (facts.overflow > 0) bad.push(`horizontal scroll (+${facts.overflow}px)`);
        bad.push(...errors);
        bad.length ? fail(where, bad.join('; ')) : ok(where);
        await ctx.close();
    }
}

// 2. the 3D hero (home page only)
{
    const where = '3D hero comes alive';
    const { ctx, page, errors } = await open('/');
    try {
        await page.waitForFunction(() => document.querySelector('[data-ocean3d].is-live'), null, { timeout: 90000 });
        const n = await page.evaluate(() => document.querySelectorAll('[data-ocean3d] canvas').length);
        errors.length ? fail(where, errors.join('; ')) : n === 1 ? ok(where) : fail(where, `${n} canvases`);
    } catch {
        fail(where, 'never became live within 90 s (a deploy without public/js/ocean3d.min.js, or a script error)');
    }
    await ctx.close();
}
{
    const where = '3D hero falls back to the photo when the model is missing';
    const { ctx, page, errors } = await open('/', { block: '**/models/*.glb*' });
    await page.waitForTimeout(6000);
    const s = await page.evaluate(() => ({ live: !!document.querySelector('[data-ocean3d].is-live'), canvas: document.querySelectorAll('[data-ocean3d] canvas').length, h1: document.querySelectorAll('h1').length, photo: !!document.querySelector('.hero-media img') }));
    s.live || s.canvas || !s.photo || s.h1 !== 1 || errors.length ? fail(where, JSON.stringify({ ...s, errors })) : ok(where);
    await ctx.close();
}
{
    const where = '3D hero stays off with reduced motion';
    const { ctx, page, errors } = await open('/', { reducedMotion: 'reduce' });
    await page.waitForTimeout(3000);
    const requested = await page.evaluate(() => performance.getEntriesByType('resource').some((r) => r.name.includes('/models/')));
    const canvas = await page.evaluate(() => document.querySelectorAll('[data-ocean3d] canvas').length);
    requested || canvas || errors.length ? fail(where, JSON.stringify({ requested, canvas, errors })) : ok(where);
    await ctx.close();
}

await browser.close();
if (failures.length) {
    console.log(`\n${failures.length} check(s) failed.`);
    process.exit(1);
}
console.log('\nAll browser checks passed.');
