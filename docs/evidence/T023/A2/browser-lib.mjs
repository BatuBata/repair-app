import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
export { assert, sleep, readFileSync, writeFileSync };
export const tmp = readFileSync('/private/tmp/t023-a2-location', 'utf8').trim();
export const env = JSON.parse(readFileSync(tmp + '/private.json'));
export const out = process.cwd() + '/docs/evidence/T023/A2/';
export async function harness(label, width = 1280) {
  process.env.TASTE_SKILL_TMP = tmp + '/' + label;
  mkdirSync(process.env.TASTE_SKILL_TMP, { recursive: true });
  const browser = await Browser.launch({ width, height: 900 });
  const page = await browser.newPage({ width, height: 900 });
  const responses = [];
  await page.send('Network.enable');
  browser.on(page.sessionId, 'Network.responseReceived', e => {
    responses.push({ status: e.response.status, type: e.type, url: e.response.url.replace(/\/t\/[A-Za-z0-9]{64}/g, '/t/[synthetic-token]') });
  });
  writeFileSync(out + label + '-preflight.json', JSON.stringify({ pid: browser.proc.pid, profile: browser.dir, TASTE_SKILL_TMP: process.env.TASTE_SKILL_TMP, applicationPort: 8193, explicitPrivateStorage: true, privateSQLite: true, browser: await browser.send('Browser.getVersion') }, null, 2));
  const wait = async (fn, arg, label) => {
    for (let i = 0; i < 100; i++) {
      if (await page.eval(fn, arg).catch(() => false)) return;
      await sleep(70);
    }
    throw Error('Timeout ' + label);
  };
  const goto = async path => {
    await page.goto('http://127.0.0.1:8193' + path, { settleMs: 100 });
    await page.eval(() => document.fonts.ready.then(() => true));
    await sleep(80);
  };
  const point = async selector => page.eval(async selector => {
    const element = [...document.querySelectorAll(selector)].find(e => e.getBoundingClientRect().width && e.getBoundingClientRect().height);
    if (!element) throw Error('No visible ' + selector);
    let rect = element.getBoundingClientRect();
    if (!element.closest('[role=menu]') && (rect.top < 50 || rect.bottom > innerHeight - 170)) {
      element.scrollIntoView({ block: 'center' });
      await new Promise(r => setTimeout(r, 120));
      rect = element.getBoundingClientRect();
    }
    const x = rect.x + rect.width / 2, y = rect.y + rect.height / 2;
    const hit = document.elementFromPoint(x, y);
    if (!(hit === element || element.contains(hit) || hit?.closest('label')?.contains(element))) throw Error('Occluded ' + selector);
    return { x, y };
  }, selector);
  const tap = async position => {
    await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', button: 'left', clickCount: 1, ...position });
    await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', button: 'left', clickCount: 1, ...position });
  };
  const click = async selector => tap(await point(selector));
  const fill = async (selector, text) => {
    await click(selector);
    await page.eval(selector => document.querySelector(selector).select(), selector);
    await page.send('Input.insertText', { text });
    await page.eval(selector => document.querySelector(selector).dispatchEvent(new Event('change', { bubbles: true })), selector);
    await sleep(120);
  };
  const key = async key => {
    await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key, code: key });
    await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key, code: key });
  };
  const date = async (selector, value) => {
    await click(selector);
    await page.eval(({ selector, value }) => document.querySelector(selector).value = value, { selector, value });
    await key('ArrowUp'); await key('ArrowDown'); await sleep(450);
  };
  const login = async username => {
    await goto('/login'); await fill('#username', username); await fill('#password', env.QA_PASSWORD);
    await click('[data-test=login-button]');
    await wait(() => location.pathname !== '/login', undefined, 'login');
  };
  const logout = async () => {
    await goto('/profil'); await click('form[action$="/logout"] button');
    await wait(() => location.pathname === '/login', undefined, 'logout');
  };
  const shot = async (name, fullPage = true) => {
    if (fullPage) await page.eval(() => scrollTo(0, 0));
    await sleep(80);
    await page.screenshot({ path: out + name + '.png', format: 'png', fullPage });
  };
  const assets = async () => {
    const rows = ['build', 'font'].flatMap(name => JSON.parse(readFileSync(process.cwd() + '/docs/evidence/T022/A2/' + name + '-manifest.json')));
    const result = [];
    for (const row of rows) {
      const path = '/' + row.path.replace(/^public\//, '');
      const response = await fetch('http://127.0.0.1:8193' + path);
      const bytes = Buffer.from(await response.arrayBuffer());
      const sha256 = createHash('sha256').update(bytes).digest('hex');
      assert.equal(response.status, 200); assert.equal(sha256, row.sha256);
      result.push({ path, status: response.status, bytes: bytes.length, sha256 });
    }
    return result;
  };
  return { browser, page, responses, wait, goto, click, tap, fill, date, key, login, logout, shot, assets };
}
