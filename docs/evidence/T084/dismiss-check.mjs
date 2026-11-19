import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const clicks = [];
vm.runInNewContext(readFileSync(new URL('../../../resources/js/layout.js', import.meta.url), 'utf8'), {
    document: { addEventListener(type, handler) { if (type === 'click') clicks.push(handler); } },
    addEventListener() {},
});
let removed = [];
const click = (button) => clicks.forEach((handler) => handler({ target: {
    closest: (selector) => selector === '[data-dismiss-notice]' ? button : null,
} }));
click(null);
assert.deepEqual(removed, []);
click({ closest: () => ({ remove() { removed.push('first'); } }) });
assert.deepEqual(removed, ['first']);
click({ closest: () => null });
assert.deepEqual(removed, ['first']);
click({ closest: () => ({ remove() { removed.push('new-message'); } }) });
assert.deepEqual(removed, ['first', 'new-message']);
console.log('PASS: unrelated click, own notice, detached target, and newly rendered notice.');
