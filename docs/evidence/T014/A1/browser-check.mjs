import assert from 'node:assert/strict';
import { mkdirSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';

const base = 'http://127.0.0.1:8190';
const out = new URL('./screens/', import.meta.url).pathname;
mkdirSync(out, { recursive: true });
const browser = await Browser.launch({ width: 1280, height: 900 });
const page = await browser.newPage({ width: 1280, height: 900 });
const waitFor = async (fn, timeout = 10000) => {
  const end = Date.now() + timeout;
  while (Date.now() < end) {
    const value = await page.eval(fn);
    if (value) return value;
    await sleep(100);
  }
  const state = await page.eval(() => ({ url: location.pathname, filters: [...document.querySelectorAll('.expense-filters input')].map((el) => el.value), errors: [...document.querySelectorAll('.expense-filters .error-text')].map((el) => el.textContent), total: document.querySelector('.expense-total')?.textContent, rows: document.querySelector('.expense-list tbody')?.textContent }));
  throw new Error(`Timed out waiting for browser state: ${JSON.stringify(state)}`);
};
const clickAt = async (selector, text) => {
  const point = await page.eval(({ selector, text }) => {
    const el = [...document.querySelectorAll(selector)].find((node) => node.textContent.trim().includes(text) && node.getClientRects().length && getComputedStyle(node).visibility === 'visible');
    if (!el) return null;
    el.scrollIntoView({ block: 'center', inline: 'center' });
    const rect = el.getBoundingClientRect();
    return { x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 };
  }, { selector, text });
  assert.ok(point, `Could not find visible ${text}`);
  await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', ...point, button: 'left', clickCount: 1 });
  await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', ...point, button: 'left', clickCount: 1 });
};
const clickText = (selector, text) => clickAt(selector, text);
const clickSelector = async (selector) => {
  const point = await page.eval((selector) => {
    const el = document.querySelector(selector);
    if (!el || !el.getClientRects().length) return null;
    el.scrollIntoView({ block: 'center', inline: 'center' });
    const rect = el.getBoundingClientRect();
    return { x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 };
  }, selector);
  assert.ok(point, `Could not find visible ${selector}`);
  await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', ...point, button: 'left', clickCount: 1 });
  await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', ...point, button: 'left', clickCount: 1 });
};
const press = async (key, code = key, modifiers = 0) => {
  await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, modifiers });
  await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, modifiers });
};
const typeText = async (selector, value) => {
  await clickSelector(selector);
  await press('a', 'KeyA', 4); // Select all with the platform's Command+A shortcut.
  await page.send('Input.insertText', { text: value });
};
const typeDate = async (selector, value) => {
  await clickSelector(selector);
  await page.eval(({ selector, value }) => { document.querySelector(selector).value = value; }, { selector, value });
  await press('ArrowUp');
  await press('ArrowDown');
  assert.equal(await page.eval((selector) => document.querySelector(selector).value, selector), value, `Date input did not settle on ${value}`);
};
const clearDate = async (selector) => {
  await clickSelector(selector);
  await press('Backspace');
  assert.equal(await page.eval((selector) => document.querySelector(selector).value, selector), '', 'Date input did not clear with Backspace.');
};
const selectOption = async (selector, value) => {
  await clickSelector(selector);
  const selected = await page.eval(({ selector, value }) => {
    const el = document.querySelector(selector);
    if (![...el.options].some((option) => option.value === value)) return false;
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    return el.value === value;
  }, { selector, value });
  assert.equal(selected, true, `Select did not choose ${value}`);
};
const screenshot = async (name) => page.screenshot({ path: `${out}/${name}.png`, format: 'png', fullPage: true });
const dimensions = async () => page.eval(() => ({
  width: innerWidth,
  pageWidth: document.documentElement.scrollWidth,
  bodyFont: getComputedStyle(document.querySelector('.expenses-page') || document.querySelector('main') || document.body).fontSize,
  inputFonts: [...document.querySelectorAll('.expenses-page input, main input')].map((el) => getComputedStyle(el).fontSize),
  smallTargets: [...document.querySelectorAll('.expenses-page button, .expenses-page a, .expenses-page input, .expenses-page select')]
    .filter((el) => el.getClientRects().length).map((el) => ({ text: el.textContent.trim(), h: Math.round(el.getBoundingClientRect().height) }))
    .filter((el) => el.h < 44),
}));
const resize = async (width) => {
  await page.setViewport({ width, height: 900, dsf: 1, mobile: width < 640 });
  await sleep(300);
  const m = await dimensions();
  assert.ok(m.pageWidth <= width, `Horizontal page overflow at ${width}px: ${m.pageWidth}px`);
  return m;
};
try {
  await page.goto(`${base}/login`);
  await typeText('#username', 'qa-expenses');
  await typeText('#password', process.env.T014_LOGIN_PASSWORD);
  await clickSelector('[data-test="login-button"]');
  await waitFor(() => location.pathname === '/');
  await page.goto(`${base}/pengeluaran`);
  await waitFor(() => document.querySelector('.expenses-page h1')?.textContent === 'Pengeluaran');

  // Capture the current empty/list state at all required viewport classes.
  for (const width of [360, 768, 1280]) {
    await resize(width);
    await screenshot(`list-${width}`);
  }

  // Add a WITA date row with the shared money component's formatted visible value.
  await clickText('.expenses-page button', 'Tambah pengeluaran');
  await waitFor(() => document.querySelector('.expenses-page .expense-form'));
  for (const width of [360, 768, 1280]) {
    await resize(width);
    await screenshot(`form-${width}`);
  }
  await typeDate('.expense-form input[type="date"]', '2026-11-13');
  await selectOption('.expense-form select', 'bensin');
  await typeText('.expense-form input[type="text"]:not([data-money])', 'T014 visual QA');
  await typeText('.expense-form input[data-money]', '150000');
  await waitFor(() => document.querySelector('.expense-form input[data-money]')?.value === '150.000');
  const money = await page.eval(() => ({ visible: document.querySelector('.expense-form input[data-money]')?.value, raw: document.querySelector('.expense-form input[type="hidden"]')?.value }));
  assert.deepEqual(money, { visible: '150.000', raw: '150000' });
  await clickText('.expense-form button[type="submit"]', 'Simpan');
  await waitFor(() => document.querySelector('.expenses-page .expense-list')?.textContent.includes('T014 visual QA'));
  const storedAmount = execFileSync('/usr/bin/sqlite3', [process.env.T014_DB, "SELECT amount FROM expenses WHERE description='T014 visual QA' ORDER BY id LIMIT 1"]).toString().trim();
  assert.equal(storedAmount, '150000', 'The formatted field must persist the raw integer amount.');
  const afterCreate = await page.eval(() => ({ total: document.querySelector('.expense-total')?.textContent, row: document.querySelector('.expense-list tbody')?.textContent }));
  assert.match(afterCreate.total, /Rp150\.000/);

  // Two more real rows exercise both inclusive endpoints and exclude the next day.
  const addRow = async (date, category, description, amount) => {
    await clickText('.expenses-page button', 'Tambah pengeluaran');
    await waitFor(() => document.querySelector('.expense-form'));
    await typeDate('.expense-form input[type="date"]', date);
    await selectOption('.expense-form select', category);
    await typeText('.expense-form input[type="text"]:not([data-money])', description);
    await typeText('.expense-form input[data-money]', String(amount));
    await clickText('.expense-form button[type="submit"]', 'Simpan');
    await waitFor(() => !document.querySelector('.expense-form'));
  };
  await addRow('2026-11-12', 'konsumsi', 'T014 batas awal', 10000);
  await addRow('2026-11-14', 'lainnya', 'T014 setelah rentang', 40000);
  await typeDate('.expense-filters label:first-child input[type="date"]', '2026-11-12');
  await typeDate('.expense-filters label:last-child input[type="date"]', '2026-11-13');
  await waitFor(() => document.querySelector('.expense-total')?.textContent.includes('Rp160.000'));
  const filterRange = await page.eval(() => ({ total: document.querySelector('.expense-total')?.textContent, rows: document.querySelector('.expense-list tbody')?.textContent }));
  assert.match(filterRange.total, /Rp160\.000/);
  assert.ok(!filterRange.rows.includes('T014 setelah rentang'));
  for (const width of [360, 768, 1280]) {
    await resize(width);
    await screenshot(`list-populated-${width}`);
  }
  await resize(1280);
  await typeDate('.expense-filters label:first-child input[type="date"]', '2026-11-14');
  await waitFor(() => document.querySelector('.expense-filters')?.textContent.includes('Sampai tanggal harus sama'));
  const reversed = await page.eval(() => ({ total: document.querySelector('.expense-total')?.textContent, rows: document.querySelector('.expense-list tbody')?.textContent, error: document.querySelector('#to-error')?.textContent }));
  assert.match(reversed.error, /Sampai tanggal harus sama/);
  assert.match(reversed.total, /Rp0/);
  assert.ok(!reversed.rows.includes('T014 visual QA'));
  await clearDate('.expense-filters label:first-child input[type="date"]');
  await waitFor(() => document.querySelector('.expense-filters')?.textContent.includes('Dari tanggal wajib'));
  assert.match(await page.eval(() => document.querySelector('#from-error')?.textContent), /Dari tanggal wajib/);
  await typeDate('.expense-filters label:first-child input[type="date"]', '2026-11-12');
  await waitFor(() => document.querySelector('.expense-total')?.textContent.includes('Rp160.000'));

  // Edit persisted amount, then prove cancellation returns focus to the visible row trigger.
  const rowAction = await page.eval(() => [...document.querySelectorAll('.expense-list tr')].find((tr) => tr.textContent.includes('T014 visual QA'))?.querySelector('.act-btn')?.getAttribute('aria-controls'));
  assert.ok(rowAction);
  await clickSelector(`.expenses-page [aria-controls="${rowAction}"]`);
  await clickText('[role="menuitem"]', 'Ubah');
  await waitFor(() => document.querySelector('.expense-form'));
  await typeText('.expense-form input[data-money]', '175000');
  await waitFor(() => document.querySelector('.expense-form input[data-money]')?.value === '175.000');
  await clickText('.expense-form button[type="submit"]', 'Simpan');
  await waitFor(() => document.querySelector('.expense-list tbody')?.textContent.includes('Rp175.000'));
  await clickSelector(`.expenses-page [aria-controls="${rowAction}"]`);
  await clickText('[role="menuitem"]', 'Hapus');
  await waitFor(() => document.querySelector('.expenses-page dialog[open]'));
  assert.ok(await page.eval(() => !!document.querySelector('.expenses-page dialog[open]')));
  await clickText('.expenses-page dialog button', 'Batal');
  await waitFor(() => !document.querySelector('.expenses-page dialog[open]'));
  const focusAfterCancel = await page.eval((id) => document.activeElement?.getAttribute('aria-controls'), rowAction);
  assert.equal(focusAfterCancel, rowAction, 'Delete cancel should return focus to row action trigger');

  // Confirm deletion from this row.
  await clickSelector(`.expenses-page [aria-controls="${rowAction}"]`);
  await clickText('[role="menuitem"]', 'Hapus');
  await waitFor(() => document.querySelector('.expenses-page dialog[open]'));
  await clickText('.expenses-page dialog button', 'Ya, hapus');
  await waitFor(() => !document.querySelector('.expense-list tbody')?.textContent.includes('T014 visual QA'));

  // Technician is denied by both the UI route and admin middleware.
  await resize(1280);
  await clickText('form[action="/logout"] button[type="submit"]', 'Keluar');
  await waitFor(() => location.pathname === '/login');
  await typeText('#username', 'qa-tech');
  await typeText('#password', process.env.T014_LOGIN_PASSWORD);
  await clickSelector('[data-test="login-button"]');
  await waitFor(() => location.pathname === '/');
  const techStatus = await page.eval(async () => (await fetch('/pengeluaran')).status);
  assert.equal(techStatus, 403);

  // Compare list and new-form screens against their approved nearest mockups.
  for (const [file, name] of [['tts.html', 'reference-tts'], ['tts-baru.html', 'reference-tts-form']]) {
    await page.goto(`file:///Users/muzib/.codex/worktrees/v1-stock/repair-app/mockup/${file}`);
    for (const width of [360, 1280]) {
      await resize(width);
      await screenshot(`${name}-${width}`);
    }
  }

  const errors = page.consoleErrors;
  assert.equal(errors.length, 0, `Browser console errors: ${errors.join('; ')}`);
  console.log(JSON.stringify({ screenshots: ['list-360', 'list-768', 'list-1280', 'list-populated-360', 'list-populated-768', 'list-populated-1280', 'form-360', 'form-768', 'form-1280', 'reference-tts-360', 'reference-tts-1280', 'reference-tts-form-360', 'reference-tts-form-1280'], money, storedAmount, afterCreate, filterRange, reversed, focusAfterCancel, techStatus, consoleErrors: errors }));
} finally {
  await browser.close();
}
