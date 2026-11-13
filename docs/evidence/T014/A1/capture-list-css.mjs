import assert from 'node:assert/strict';
import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';

const browser = await Browser.launch({ width: 1280, height: 900 });
const page = await browser.newPage({ width: 1280, height: 900 });
const wait = async (predicate) => {
    const until = Date.now() + 10000;
    while (Date.now() < until) {
        if (await page.eval(predicate)) return;
        await sleep(100);
    }
    throw new Error(`Timed out at ${await page.eval(() => location.pathname)}`);
};
const click = async (selector) => {
    const point = await page.eval((s) => {
        const el = [...document.querySelectorAll(s)].find((item) => item.getClientRects().length);
        if (!el) return null;
        const rect = el.getBoundingClientRect();
        return { x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 };
    }, selector);
    assert.ok(point, `No visible ${selector}`);
    await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', ...point, button: 'left', clickCount: 1 });
    await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', ...point, button: 'left', clickCount: 1 });
};

try {
    await page.goto('http://127.0.0.1:8190/login');
    for (const [selector, text] of [['#username', 'qa-expenses'], ['#password', process.env.T014_LOGIN_PASSWORD]]) {
        await page.eval((s) => document.querySelector(s).focus(), selector);
        await page.send('Input.insertText', { text });
    }
    await click('[data-test="login-button"]');
    await wait(() => location.pathname === '/');
    await page.goto('http://127.0.0.1:8190/pengeluaran');
    await wait(() => document.querySelector('.expenses-page .expense-list tbody tr'));

    const results = [];
    for (const width of [360, 1280]) {
        await page.setViewport({ width, height: 900, dsf: 1, mobile: width < 640 });
        await sleep(250);
        const layout = await page.eval(() => ({
            viewport: innerWidth,
            scrollWidth: document.documentElement.scrollWidth,
            firstRow: [...document.querySelector('.expense-list tbody tr').querySelectorAll('td:nth-child(2) > *')]
                .map((node) => ({ className: node.className, display: getComputedStyle(node).display })),
        }));
        assert.ok(layout.scrollWidth <= layout.viewport, `Horizontal overflow at ${width}px`);
        assert.deepEqual(layout.firstRow.map((item) => item.display), ['block', 'block']);
        const file = new URL(`./screens/list-final-${width}.png`, import.meta.url).pathname;
        await page.screenshot({ path: file, format: 'png', fullPage: true });
        results.push({ width, file, ...layout });
    }
    assert.deepEqual(page.consoleErrors, []);
    console.log(JSON.stringify({ results, consoleErrors: page.consoleErrors }));
} finally {
    await browser.close();
}
