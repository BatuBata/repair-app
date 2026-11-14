import { harness, assert, sleep, out, readFileSync, writeFileSync } from './browser-lib.mjs';
const h = await harness('edge-a2', 1280);
const { browser, page: p, goto, click, fill, date, key, wait, login, logout, shot } = h;
const cases = [], pdfs = [];
const version = await browser.send('Browser.getVersion');
const ua = await p.eval(() => navigator.userAgent);
const runtime = { binary: process.env.CHROME_PATH, version, navigatorUserAgent: ua, headless: true, profileIsolated: true, globalInstall: false };
writeFileSync(out + 'edge-runtime.json', JSON.stringify(runtime, null, 2));
const save = () => writeFileSync(out + 'edge-results.json', JSON.stringify({ runtime, cases, pdfs, consoleErrors: p.consoleErrors }, null, 2));
const record = (id, evidence = {}) => { cases.push({ id, pass: true, ...evidence }); save(); };
const pdf = async (path, name) => {
  const result = await p.eval(async path => {
    const response = await fetch(path); let data = '';
    for (const byte of new Uint8Array(await response.arrayBuffer())) data += String.fromCharCode(byte);
    return { status: response.status, type: response.headers.get('Content-Type'), cache: response.headers.get('Cache-Control'), robots: response.headers.get('X-Robots-Tag'), data: btoa(data) };
  }, path);
  assert.equal(result.status, 200); assert.match(result.type, /application\/pdf/);
  const bytes = Buffer.from(result.data, 'base64'); assert.equal(bytes.subarray(0, 5).toString(), '%PDF-');
  writeFileSync(out + name + '.pdf', bytes); delete result.data;
  pdfs.push({ name, ...result, bytes: bytes.length }); save();
};
try {
  assert.match(process.env.CHROME_PATH, /Microsoft Edge/); assert.match(ua, /Edg\/157\./); assert.equal(version.product, 'Edg/157.0.4341.57');
  record('actual-Microsoft-Edge-runtime', { userAgentContainsEdg: true });
  await login('t023_admin'); assert.equal(await p.eval(() => location.pathname), '/');
  await wait(() => !!document.querySelector('.dashboard-page'), undefined, 'dashboard');
  await shot('edge-dashboard'); record('admin-login-launcher-dashboard');
  await click('#tab-profit');
  await wait(() => document.querySelector('#tab-profit')?.getAttribute('aria-selected') === 'true', undefined, 'chart profit');
  await click('.chart-plot'); await key('End'); await sleep(100);
  const tip = await p.eval(() => [...document.querySelectorAll('[role=tooltip]')].filter(e => getComputedStyle(e).display !== 'none').map(e => ({ text: e.innerText, opacity: getComputedStyle(e).opacity })));
  assert.equal(tip.length, 1); assert.equal(tip[0].opacity, '1');
  await shot('edge-chart-tooltip', false); record('dashboard-chart-tab-keyboard-tooltip', { tip });
  await click('a[href$="/laporan"]'); await wait(() => location.pathname === '/laporan', undefined, 'dashboard report link');
  await date('input[wire\\:model\\.live=from]', '2026-11-11');
  await date('input[wire\\:model\\.live=to]', '2026-11-11');
  await wait(() => document.body.innerText.includes('Rp735.000'), undefined, 'PRD sample');
  for (const value of ['Rp2.600.000', 'Rp1.800.000', 'Rp800.000', 'Rp65.000', 'Rp735.000']) assert.ok(await p.eval(value => document.body.innerText.includes(value), value));
  await shot('edge-report-sample'); record('report-actual-date-inputs-PRD-sample');
  await click('.report-detail'); await wait(() => !!document.querySelector('dialog[open]'), undefined, 'report dialog');
  await shot('edge-report-dialog', false); await click('dialog[open] .section-head button');
  await wait(() => !document.querySelector('dialog[open]'), undefined, 'close report dialog');
  await click('.report-transactions .tabs button:nth-child(3)');
  await wait(() => document.querySelector('.report-transactions .tabs button:nth-child(3)')?.getAttribute('aria-pressed') === 'true' && document.querySelectorAll('.report-table tbody tr').length === 1, undefined, 'sale filter');
  await click('.report-number a'); await wait(() => location.pathname === '/penjualan/1', undefined, 'sale navigation');
  assert.match(await p.eval(() => document.body.innerText), /Rp1\.500\.000/);
  await shot('edge-sale'); record('report-detail-filter-navigation-sale');
  await goto('/tts/7/rincian'); assert.ok(await p.eval(() => !!document.querySelector('#invoice-form')));
  assert.match(await p.eval(() => document.body.innerText), /Rp1\.125\.000/);
  await shot('edge-invoice'); record('representative-invoice-form-no-save');
  await pdf('/tts/7/pdf', 'edge-admin-a5'); record('admin-PDF-HTTP');
  await goto('/tts/baru'); await click('#tts-form button[type=submit]');
  await wait(() => document.querySelectorAll('[aria-invalid=true]').length >= 4, undefined, 'TTS validation');
  await shot('edge-tts-required'); record('TTS-four-required-validation-no-create');
  await logout(); record('admin-logout'); await login('t023_tech');
  assert.equal(await p.eval(() => location.pathname), '/tts/baru'); await goto('/mulai');
  assert.equal(await p.eval(() => location.pathname), '/tts/baru'); record('technician-login-launcher');
  const guards = [];
  for (const path of ['/', '/laporan', '/stok', '/penjualan', '/tts/7/rincian', '/tts/7/pdf']) {
    const status = await p.eval(async path => (await fetch(path)).status, path); assert.equal(status, 403); guards.push({ path, status });
  }
  await goto('/tts/7'); const technician = await p.eval(() => document.body.innerText);
  for (const amount of ['1.125.000', '700.000', '125.000']) assert.ok(!technician.includes(amount));
  await shot('edge-technician-detail'); record('technician-guards-and-price-privacy', { guards });
  await logout(); const ids = JSON.parse(readFileSync('/private/tmp/t023-a2-3of99won/ids.json'));
  await goto('/t/' + ids.invoice_token); const text = await p.eval(() => document.body.innerText);
  assert.ok(text.includes('Rp1.100.000')); assert.ok(!text.includes('6281234567890') && !text.includes('700.000') && !text.includes('Alamat privat QA'));
  await shot('edge-public-tracking'); await pdf('/t/' + ids.invoice_token + '/pdf', 'edge-public-a5');
  record('anonymous-tracking-public-PDF-privacy');
  const assets = await h.assets(); writeFileSync(out + 'edge-assets.json', JSON.stringify(assets, null, 2));
  const assetFailures = h.responses.filter(r => r.status >= 400 && ['Stylesheet', 'Script', 'Font', 'Image'].includes(r.type));
  assert.deepEqual(assetFailures, []); assert.equal(p.consoleErrors.length, 0);
  assert.ok(h.responses.some(r => r.url.includes('app-CDn1G0_W.css') && r.status === 200));
  record('frozen-final-assets-and-console', { assets: assets.length, assetFailures });
  save(); console.log(JSON.stringify({ verdict: 'pass', browser: 'Microsoft Edge 157.0.4341.57', cases: cases.length, pdfs: pdfs.length, assets: assets.length }));
} catch (error) {
  save(); writeFileSync(out + 'edge-failure.json', JSON.stringify({ error: error.stack, runtime, cases, pdfs, responses: h.responses }, null, 2));
  await shot('edge-failure', false); console.error(error); process.exitCode = 1;
} finally { await browser.close(); }
