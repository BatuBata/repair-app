import { harness, assert, sleep, out, tmp, readFileSync, writeFileSync } from './browser-lib.mjs';
import { execFileSync } from 'node:child_process';
const h = await harness('chrome-a2', 360);
const { browser, page: p, goto, click, tap, date, key, wait, login, shot } = h;
const cases = [], layouts = [], taps = [];
const save = () => writeFileSync(out + 'chrome-results.json', JSON.stringify({ cases, layouts, taps, consoleErrors: p.consoleErrors }, null, 2));
const sample = async () => {
  await goto('/laporan');
  await date('input[wire\\:model\\.live=from]', '2026-11-11');
  await date('input[wire\\:model\\.live=to]', '2026-11-11');
  await wait(() => document.body.innerText.includes('Rp735.000'), undefined, 'sample report');
};
const audit = async (mode, width) => {
  const result = await p.eval(width => {
    const anchors = [...document.querySelectorAll('.report-number a')].map(a => {
      const r = a.getBoundingClientRect(), style = getComputedStyle(a);
      const eye = a.closest('tr').querySelector('.report-detail').getBoundingClientRect();
      return { href: a.getAttribute('href'), text: a.textContent, width: r.width, height: r.height, display: style.display, minWidth: style.minWidth, minHeight: style.minHeight, eyeLeftOfAnchor: eye.x < r.x, eyeWidth: eye.width, eyeHeight: eye.height };
    });
    return { requestedWidth: width, innerWidth, scrollWidth: document.documentElement.scrollWidth, anchors, body: document.body.innerText };
  }, width);
  assert.ok(result.scrollWidth <= width + 1);
  assert.ok(result.anchors.length >= 2);
  if (width < 1024) assert.ok(result.anchors.every(a => a.width >= 44 && a.height >= 44 && a.eyeLeftOfAnchor && a.eyeWidth >= 44 && a.eyeHeight >= 44));
  else assert.ok(result.anchors.every(a => a.display === 'inline' && a.height < 44));
  const expected = mode === 'sample' ? ['Rp2.600.000', 'Rp1.800.000', 'Rp800.000', 'Rp65.000', 'Rp735.000'] : [];
  for (const value of expected) assert.ok(result.body.includes(value), value);
  if (mode !== 'sample') assert.ok(result.body.includes('36.893.488.'));
  delete result.body; layouts.push({ mode, ...result });
  await shot('reports-' + mode + '-' + width); save();
};
try {
  await login('t023_admin');
  for (const width of [360, 768, 1280]) {
    await p.setViewport({ width, height: width === 768 ? 1024 : 900 });
    await sample(); await audit('sample', width);
    // Eye still opens a dialog, closes back to its trigger, and Tab reaches the link.
    const eyeId = await p.eval(() => document.querySelector('.report-detail').id);
    await click('.report-detail'); await wait(() => !!document.querySelector('dialog[open]'), undefined, 'report detail dialog');
    await shot('report-detail-' + width, false);
    await click('dialog[open] .section-head button');
    await wait(id => !document.querySelector('dialog[open]') && document.activeElement.id === id, eyeId, 'focus restore');
    await key('Tab'); assert.ok(await p.eval(() => document.activeElement.matches('.report-number a')));
    cases.push({ id: 'eye-dialog-focus-tab', width, eyeId, pass: true });
    if (width < 1024) for (const kind of ['service', 'sale']) {
      await sample();
      const selector = '.report-number a[href*="/' + (kind === 'service' ? 'tts' : 'penjualan') + '/"]';
      const proof = await p.eval(async selector => {
        const a = document.querySelector(selector); a.scrollIntoView({ block: 'center' });
        await new Promise(r => setTimeout(r, 100));
        const r = a.getBoundingClientRect(), range = document.createRange(); range.selectNodeContents(a);
        const text = range.getBoundingClientRect(); const x = r.x + r.width / 2, y = Math.min(r.bottom - 6, text.bottom + 10);
        const hit = document.elementFromPoint(x, y);
        a.addEventListener('click', event => sessionStorage.setItem('t023-a2-tap', JSON.stringify({ trusted: event.isTrusted, x: event.clientX, y: event.clientY })), { once: true });
        return { href: a.getAttribute('href'), anchor: { left: r.left, top: r.top, width: r.width, height: r.height, bottom: r.bottom }, text: { top: text.top, bottom: text.bottom, height: text.height }, x, y, hitIsAnchor: hit === a || a.contains(hit) };
      }, selector);
      assert.ok(proof.y > proof.text.bottom + 2 && proof.y < proof.anchor.bottom && proof.hitIsAnchor);
      await shot('tap-added-area-' + kind + '-' + width, false);
      await tap({ x: proof.x, y: proof.y });
      const expected = new URL(proof.href).pathname;
      await wait(expected => location.pathname === expected, expected, 'added-area navigation');
      proof.event = await p.eval(() => JSON.parse(sessionStorage.getItem('t023-a2-tap')));
      assert.equal(proof.event.trusted, true); proof.actualPath = await p.eval(() => location.pathname);
      taps.push({ kind, width, pass: true, ...proof }); save();
    }
  }
  // A1 copy is negative; inspect the fixed report under those same large values.
  for (const width of [360, 768, 1280]) {
    await p.setViewport({ width, height: width === 768 ? 1024 : 900 });
    await goto('/laporan'); await audit('large-negative', width);
  }
  execFileSync('/Users/muzib/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/bin/python3', [process.cwd() + '/docs/evidence/T023/A2/positive-fixture.py', tmp]);
  for (const width of [360, 768, 1280]) {
    await p.setViewport({ width, height: width === 768 ? 1024 : 900 });
    await goto('/laporan'); await audit('large-positive', width);
  }
  const assets = await h.assets();
  assert.equal(p.consoleErrors.length, 0);
  writeFileSync(out + 'chrome-assets.json', JSON.stringify(assets, null, 2));
  cases.push({ id: 'F01-fixed-with-trusted-added-area-taps', count: taps.length, pass: true });
  save(); console.log(JSON.stringify({ pass: true, layouts: layouts.length, trustedTaps: taps.length, cases: cases.length, assets: assets.length }));
} catch (error) {
  save(); writeFileSync(out + 'chrome-failure.json', JSON.stringify({ error: error.stack, cases, layouts, taps }, null, 2));
  await shot('chrome-failure', false); console.error(error); process.exitCode = 1;
} finally { await browser.close(); }
