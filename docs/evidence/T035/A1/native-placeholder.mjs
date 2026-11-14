import { execFileSync } from 'node:child_process';

const html = execFileSync('/opt/homebrew/opt/php@8.3/bin/php', [new URL('./render-probe.php', import.meta.url).pathname, '--html'], { encoding: 'utf8' });
const target = await fetch('http://127.0.0.1:9235/json/new?about:blank', { method: 'PUT' }).then(r => r.json());
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, { once: true });
    socket.addEventListener('error', reject, { once: true });
});
let nextId = 1;
const pending = new Map();
socket.addEventListener('message', ({ data }) => {
    const message = JSON.parse(data);
    const request = pending.get(message.id);
    if (!request) return;
    pending.delete(message.id);
    message.error ? request.reject(new Error(message.error.message)) : request.resolve(message.result);
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const id = nextId++;
    pending.set(id, { resolve, reject });
    socket.send(JSON.stringify({ id, method, params }));
});
try {
    const result = await send('Runtime.evaluate', {
        expression: `(() => {
            document.body.innerHTML = ${JSON.stringify(html)};
            const selectors = ['[name=text]', '[name=phone]', '[name=password]', '[name=number]', '[name=zero]', '[data-money]', 'textarea'];
            return selectors.map(selector => {
                const input = document.querySelector(selector);
                input.value = '';
                const empty = input.matches(':placeholder-shown');
                input.value = selector === '[name=number]' ? '7' : 'Budi';
                const filled = input.matches(':placeholder-shown');
                const retainedValue = input.value;
                input.value = '';
                const cleared = input.matches(':placeholder-shown');
                return { selector, placeholder: input.getAttribute('placeholder'), empty, filled, cleared, retainedValue, pass: empty && !filled && cleared && retainedValue !== '' };
            });
        })()`,
        returnByValue: true,
    });
    console.log(JSON.stringify(result.result.value, null, 2));
    if (!result.result.value.every(c => c.pass)) process.exitCode = 1;
} finally {
    await send('Page.close');
    socket.close();
}
