import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';

const base = 'http://127.0.0.1:8190';
const out = new URL('./screens/', import.meta.url).pathname;
mkdirSync(out, { recursive: true });
const browser = await Browser.launch({ width: 1280, height: 900 });
const page = await browser.newPage({ width: 1280, height: 900 });
const waitFor = async (fn, timeout = 10000) => {
  const end = Date.now() + timeout;
  while (Date.now() < end) {
    if (await page.eval(fn)) return;
    await sleep(100);
  }
  const state = await page.eval(() => ({ url: location.pathname, total: document.querySelector('.expense-total')?.textContent, dialog: document.querySelector('.expenses-page dialog[open]')?.innerText, form: !!document.querySelector('.expense-form') }));
  throw new Error(`Timed out awaiting UI state: ${JSON.stringify(state)}`);
};
const pointFor = async (selector, text = '') => page.eval(({ selector, text }) => {
  const el = [...document.querySelectorAll(selector)].find((node) => (!text || node.textContent.trim().includes(text)) && node.getClientRects().length && getComputedStyle(node).visibility === 'visible');
  if (!el) return null;
  el.scrollIntoView({ block: 'center', inline: 'center' });
  const r = el.getBoundingClientRect();
  return { x: r.x + r.width / 2, y: r.y + r.height / 2 };
}, { selector, text });
const click = async (selector, text = '') => {
  const point = await pointFor(selector, text);
  assert.ok(point, `No visible target ${selector} ${text}`);
  await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', ...point, button: 'left', clickCount: 1 });
  await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', ...point, button: 'left', clickCount: 1 });
};
const press = async (key, code = key, modifiers = 0) => {
  await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, modifiers });
  await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, modifiers });
};
const typeText = async (selector, value) => {
  await click(selector);
  await page.eval((selector) => document.querySelector(selector).select(), selector);
  await page.send('Input.insertText', { text: value });
};
const typeDate = async (selector, value) => {
  await click(selector);
  await page.eval(({ selector, value }) => { document.querySelector(selector).value = value; }, { selector, value });
  await press('ArrowUp');
  await press('ArrowDown');
  assert.equal(await page.eval((selector) => document.querySelector(selector).value, selector), value);
};
const clearDate = async (selector) => {
  await click(selector);
  await press('Backspace');
  assert.equal(await page.eval((selector) => document.querySelector(selector).value, selector), '');
};
const resize = async (width) => {
  await page.setViewport({ width, height: 900, dsf: 1, mobile: width < 640 });
  await sleep(250);
  const metrics = await page.eval(() => ({
    width: innerWidth,
    pageWidth: document.documentElement.scrollWidth,
    fontSize: getComputedStyle(document.querySelector('.expenses-page') || document.querySelector('main') || document.body).fontSize,
    inputFonts: [...document.querySelectorAll('.expenses-page input')].map((el) => getComputedStyle(el).fontSize),
    smallTargets: [...document.querySelectorAll('.expenses-page button, .expenses-page a, .expenses-page input, .expenses-page select')]
      .filter((el) => el.getClientRects().length).map((el) => ({ text: el.textContent.trim(), height: Math.round(el.getBoundingClientRect().height) }))
      .filter((el) => el.height < 44),
  }));
  assert.ok(metrics.pageWidth <= width, `Horizontal overflow at ${width}px: ${metrics.pageWidth}px`);
  return metrics;
};
const screenshot = (name) => page.screenshot({ path: `${out}/${name}.png`, format: 'png', fullPage: true });
const results = { screenshots: [], measurements: {}, sameDay: null, range: null, editedAmount: null, focusAfterCancel: null, deleteConfirmed: false, technicianStatus: null, consoleErrors: [] };
try {
  await page.goto(`${base}/login`);
  await typeText('#username', 'qa-expenses');
  await typeText('#password', process.env.T014_LOGIN_PASSWORD);
  await click('[data-test="login-button"]');
  await waitFor(() => location.pathname === '/');
  await page.goto(`${base}/pengeluaran`);
  await waitFor(() => document.querySelector('.expenses-page h1')?.textContent === 'Pengeluaran');

  // Prove a same-day range and a two-endpoint range against the synthetic rows.
  await typeDate('.expense-filters label:first-child input[type="date"]', '2026-11-13');
  await typeDate('.expense-filters label:last-child input[type="date"]', '2026-11-13');
  await waitFor(() => document.querySelector('.expense-total')?.textContent.includes('Rp150.000'));
  results.sameDay = await page.eval(() => ({ total: document.querySelector('.expense-total').textContent, rows: document.querySelector('.expense-list tbody').textContent }));
  assert.match(results.sameDay.total, /Rp150\.000/);
  assert.ok(results.sameDay.rows.includes('T014 visual QA'));
  assert.ok(!results.sameDay.rows.includes('T014 batas awal'));
  await typeDate('.expense-filters label:first-child input[type="date"]', '2026-11-12');
  await waitFor(() => document.querySelector('.expense-total')?.textContent.includes('Rp160.000'));
  results.range = await page.eval(() => ({ total: document.querySelector('.expense-total').textContent, rows: document.querySelector('.expense-list tbody').textContent }));
  assert.match(results.range.total, /Rp160\.000/);
  assert.ok(results.range.rows.includes('T014 visual QA') && results.range.rows.includes('T014 batas awal'));
  assert.ok(!results.range.rows.includes('T014 setelah rentang'));
  await typeDate('.expense-filters label:first-child input[type="date"]', '2026-11-14');
  await waitFor(() => document.querySelector('#to-error')?.textContent.includes('Sampai tanggal harus sama'));
  assert.match(await page.eval(() => document.querySelector('.expense-total')?.textContent), /Rp0/);
  await clearDate('.expense-filters label:first-child input[type="date"]');
  await waitFor(() => document.querySelector('#from-error')?.textContent.includes('Dari tanggal wajib'));
  await typeDate('.expense-filters label:first-child input[type="date"]', '2026-11-12');
  await waitFor(() => document.querySelector('.expense-total')?.textContent.includes('Rp160.000'));

  for (const width of [360, 768, 1280]) {
    results.measurements[`list-${width}`] = await resize(width);
    await screenshot(`list-populated-${width}`);
    results.screenshots.push(`list-populated-${width}.png`);
  }
  await resize(1280);
  await click('.expenses-page button', 'Tambah pengeluaran');
  await waitFor(() => document.querySelector('.expense-form'));
  for (const width of [360, 768, 1280]) {
    results.measurements[`form-${width}`] = await resize(width);
    await screenshot(`form-${width}`);
    results.screenshots.push(`form-${width}.png`);
  }
  await resize(1280);
  await click('.expense-form button', 'Batal');
  await waitFor(() => !document.querySelector('.expense-form'));

  // The shared menu is portalled to body, so invoke its Livewire method and verify the edit is persisted.
  const rowAction = await page.eval(() => [...document.querySelectorAll('.expense-list tr')].find((tr) => tr.textContent.includes('T014 visual QA'))?.querySelector('.act-btn')?.getAttribute('aria-controls'));
  assert.ok(rowAction);
  await click(`.expenses-page [aria-controls="${rowAction}"]`);
  await click(`[role="menuitem"]`, 'Ubah');
  await waitFor(() => document.querySelector('.expense-form'));
  await typeText('.expense-form input[data-money]', '175000');
  await waitFor(() => document.querySelector('.expense-form input[data-money]')?.value === '175.000');
  await click('.expense-form button', 'Simpan');
  await waitFor(() => document.querySelector('.expense-list tbody')?.textContent.includes('Rp175.000'));
  results.editedAmount = execFileSync('/usr/bin/sqlite3', [process.env.T014_DB, "SELECT amount FROM expenses WHERE description='T014 visual QA' ORDER BY id LIMIT 1"]).toString().trim();
  assert.equal(results.editedAmount, '175000');

  // Cancel is scoped to this page's dialog and must return focus to the visible row trigger.
  await click(`.expenses-page [aria-controls="${rowAction}"]`);
  await click('[role="menuitem"]', 'Hapus');
  await waitFor(() => document.querySelector('.expenses-page dialog[open]'));
  await click('.expenses-page dialog button', 'Batal');
  await waitFor(() => !document.querySelector('.expenses-page dialog[open]'));
  results.focusAfterCancel = await page.eval((id) => document.activeElement?.getAttribute('aria-controls'), rowAction);
  assert.equal(results.focusAfterCancel, rowAction);

  await click(`.expenses-page [aria-controls="${rowAction}"]`);
  await click('[role="menuitem"]', 'Hapus');
  await waitFor(() => document.querySelector('.expenses-page dialog[open]'));
  await click('.expenses-page dialog button', 'Ya, hapus');
  await waitFor(() => !document.querySelector('.expense-list tbody')?.textContent.includes('T014 visual QA'));
  results.deleteConfirmed = execFileSync('/usr/bin/sqlite3', [process.env.T014_DB, "SELECT count(*) FROM expenses WHERE description='T014 visual QA'"]).toString().trim() === '0';
  assert.equal(results.deleteConfirmed, true);

  await resize(1280);
  await click('form[action="/logout"] button[type="submit"]', 'Keluar');
  await waitFor(() => location.pathname === '/login');
  await typeText('#username', 'qa-tech');
  await typeText('#password', process.env.T014_LOGIN_PASSWORD);
  await click('[data-test="login-button"]');
  await waitFor(() => location.pathname === '/');
  results.technicianStatus = await page.eval(async () => (await fetch('/pengeluaran')).status);
  assert.equal(results.technicianStatus, 403);

  for (const [file, name] of [['tts.html', 'reference-tts'], ['tts-baru.html', 'reference-tts-form']]) {
    await page.goto(`file:///Users/muzib/.codex/worktrees/v1-stock/repair-app/mockup/${file}`);
    for (const width of [360, 1280]) {
      results.measurements[`${name}-${width}`] = await resize(width);
      await screenshot(`${name}-${width}`);
      results.screenshots.push(`${name}-${width}.png`);
    }
  }
  results.consoleErrors = page.consoleErrors;
  assert.equal(results.consoleErrors.length, 0, `Console errors: ${results.consoleErrors.join('; ')}`);
  writeFileSync(new URL('./browser-results.json', import.meta.url), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results));
} finally {
  await browser.close();
}
