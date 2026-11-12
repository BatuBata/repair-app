import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import { writeFileSync } from 'node:fs';
import assert from 'node:assert/strict';
const dir = 'docs/evidence/T003/A2';
const base = 'http://127.0.0.1:8083/layout-preview';
const root = 'file:///Users/muzib/orca/workspaces/repair-app/repair-t003-layout/mockup/';
const browser = await Browser.launch();
const result = { checks: [], diagnostics: {}, screens: [], errors: [] };
const check = (name, value) => { result.checks.push({ name, pass: !!value }); assert.ok(value, name); };
const box = (sel) => `(function(){const r=document.querySelector(${JSON.stringify(sel)}).getBoundingClientRect();return {x:r.left+r.width/2,y:r.top+r.height/2,width:r.width,height:r.height}})()`;
const click = async (p, sel, touch = false) => {
  const {x,y} = await p.eval(box(sel));
  if (touch) {
    await p.send('Input.dispatchTouchEvent',{type:'touchStart',touchPoints:[{x,y}]});
    await p.send('Input.dispatchTouchEvent',{type:'touchEnd',touchPoints:[]});
  } else {
    await p.send('Input.dispatchMouseEvent',{type:'mousePressed',x,y,button:'left',clickCount:1});
    await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',x,y,button:'left',clickCount:1});
  }
  await sleep(150);
};
const key = async (p, k) => { const code = { Escape: 27, ArrowDown: 40, ArrowUp: 38 }[k]; await p.send('Input.dispatchKeyEvent',{type:'keyDown',key:k,code:k,windowsVirtualKeyCode:code}); await p.send('Input.dispatchKeyEvent',{type:'keyUp',key:k,code:k,windowsVirtualKeyCode:code}); await sleep(100); };
const snap = async (p, name, fullPage = false) => {const path = `${dir}/${name}.png`; await p.screenshot({path,format:'png',fullPage});result.screens.push(path);};
try {
 const version = await browser.send('Browser.getVersion'); result.diagnostics.chrome = version.product;
 const p = await browser.newPage({width:360,height:900,mobile:true});
 await p.goto(base+'?page=servis',{settleMs:300});
 await p.eval(`window.__touchEvidence=[];document.addEventListener('touchstart',e=>window.__touchEvidence.push({target:e.target.closest('button')?.getAttribute('aria-controls')||null,trusted:e.isTrusted}),true)`);
 await click(p,'[aria-controls="pilihan-14"]',true);
 const touch = await p.eval(`({events:window.__touchEvidence,open:!document.querySelector('#pilihan-14').hidden,focus:document.activeElement.textContent.trim()})`);
 result.diagnostics.touch=touch;
 check('CDP touch input targets button, trusted and opens menu', touch.events.some(e=>e.target==='pilihan-14'&&e.trusted)&&touch.open);
 await key(p,'ArrowDown');check('ArrowDown focuses second item',await p.eval(`document.activeElement.textContent.trim()==='Ubah TTS'`));
 await key(p,'ArrowUp');check('ArrowUp focuses first item',await p.eval(`document.activeElement.textContent.trim()==='Lihat TTS'`));
 await key(p,'Escape');check('Escape closes and restores focus',await p.eval(`document.querySelector('#pilihan-14').hidden && document.activeElement.getAttribute('aria-controls')==='pilihan-14'`));
 await click(p,'[aria-controls="pilihan-14"]',true);await click(p,'h1',true);check('outside touch closes',await p.eval(`document.querySelector('#pilihan-14').hidden`));
 await click(p,'[aria-controls="pilihan-14"]',true);await p.wheel(300);await sleep(250);check('scroll closes',await p.eval(`document.querySelector('#pilihan-14').hidden`));
 const money = await p.eval(`(() => { const e=document.querySelector('[data-money]'), raw=document.querySelector('#money-nominal'); return ['', '000', '-1234567', '99a999'].map(s=>{e.value=s;e.dispatchEvent(new Event('input',{bubbles:true}));return [s,e.value,raw.value]})})()`);
 result.diagnostics.money=money;check('empty, zero, non-digit and grouping money',JSON.stringify(money)===JSON.stringify([['','',''],['000','0','0'],['-1234567','1.234.567','1234567'],['99a999','99.999','99999']]));
 await p.eval(`document.querySelector('[data-open="contoh-dialog"]').scrollIntoView({block:'center'})`);
 await click(p,'[data-open="contoh-dialog"]');check('dialog opens',await p.eval(`document.querySelector('#contoh-dialog').open`));await key(p,'Escape');check('dialog Escape restores focus',await p.eval(`!document.querySelector('#contoh-dialog').open && document.activeElement.dataset.open==='contoh-dialog'`));
 await p.goto(base+'?page=servis',{settleMs:300});
 const manifest = await p.send('Page.getAppManifest'); const install = await p.send('Page.getInstallabilityErrors');
 result.diagnostics.manifest = manifest;result.diagnostics.installability=install;
 check('manifest loaded by Chrome without parse errors',manifest.url.endsWith('/manifest.webmanifest') && !(manifest.errors||[]).length && JSON.parse(manifest.data).icons.length===2);
 check('Chrome reports no installability errors',install.installabilityErrors.length===0);
 const icons=await p.eval(`Promise.all(['/icons/ticket-192.png','/icons/ticket-512.png'].map(src=>new Promise(resolve=>{let i=new Image;i.onload=()=>resolve([i.naturalWidth,i.naturalHeight]);i.onerror=()=>resolve(null);i.src=src})))`);
 result.diagnostics.decodedIcons=icons;check('Chrome decodes manifest icons at declared sizes',JSON.stringify(icons)==='[[192,192],[512,512]]');
 const checks = await p.eval(`({viewport:document.querySelector('meta[name=viewport]').content, sans:document.fonts.check('16px IBM Plex Sans'), mono:document.fonts.check('500 14px IBM Plex Mono'),input:getComputedStyle(document.querySelector('[name=hp]')).fontSize,border:getComputedStyle(document.querySelector('[name=hp]')).borderColor,targets:[...document.querySelectorAll('a,button,input:not([type=hidden])')].filter(e=>{let r=e.getBoundingClientRect(),s=getComputedStyle(e);return r.width && r.height && s.visibility!=='hidden' && (r.width<44||r.height<44)}).map(e=>({tag:e.tagName,text:e.textContent.trim().slice(0,20),width:Math.round(e.getBoundingClientRect().width),height:Math.round(e.getBoundingClientRect().height)}))})`);
 result.diagnostics.mobileAudit=checks;check('viewport allows zoom and fonts loaded',!checks.viewport.includes('user-scalable=no')&&checks.sans&&checks.mono);check('mobile input at least 16px',parseFloat(checks.input)>=16);check('visible mobile targets at least 44px',checks.targets.length===0);
 for (const w of [360,768,1280]) {
   await p.setViewport({width:w,height:900,mobile:w<1024});await p.goto(base+'?page=servis',{settleMs:350});
   const audit=await p.eval(`({width:innerWidth,scroll:document.documentElement.scrollWidth,sidebar:Math.round(document.querySelector('.sidebar').getBoundingClientRect().width),top:Math.round(document.querySelector('.topbar').getBoundingClientRect().height),body:getComputedStyle(document.body).fontFamily,nav:[...document.querySelectorAll('.bottomnav [aria-current]')].map(e=>e.textContent.trim())})`);
   result.diagnostics['width'+w]=audit;check(`no horizontal overflow ${w}`,audit.width===audit.scroll);check(`active navigation ${w}`,audit.nav.includes('Servis'));
   await snap(p,`preview-list-${w}`);
   await p.eval(`document.querySelector('.form-grid').scrollIntoView({block:'center'})`);await sleep(100);await snap(p,`preview-form-${w}`);
   await p.eval(`document.querySelector('.empty').scrollIntoView({block:'center'})`);await sleep(100);await snap(p,`preview-empty-${w}`);
   await p.eval(`document.querySelector('[data-open="contoh-dialog"]').scrollIntoView({block:'center'})`);await click(p,'[data-open="contoh-dialog"]');await snap(p,`preview-dialog-${w}`);await key(p,'Escape');
   await p.eval(`document.querySelector('[data-toast="Data contoh tersimpan"]').scrollIntoView({block:'center'})`);await click(p,'[data-toast="Data contoh tersimpan"]');await snap(p,`preview-toast-${w}`);
   const m=await browser.newPage({width:w,height:900,mobile:w<1024});
   await m.goto(root+'tts.html',{settleMs:250});await snap(m,`mockup-list-${w}`);
   await m.eval(`let q=document.querySelector('#tts-q');q.value='tidak ada data cocok';q.dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('#tts-empty').scrollIntoView({block:'center'})`);await sleep(100);await snap(m,`mockup-empty-${w}`);
   await m.goto(root+'tts-baru.html?peran=admin',{settleMs:250});await m.eval(`document.querySelector('#hp').scrollIntoView({block:'center'})`);await sleep(100);await snap(m,`mockup-form-${w}`);
   await m.goto(root+'tts-rincian.html',{settleMs:250});await m.eval(`document.querySelector('.money').scrollIntoView({block:'center'})`);await sleep(100);await snap(m,`mockup-money-${w}`);
   await m.goto(root+'tts-detail.html',{settleMs:250});await m.eval(`document.querySelector('#batal').showModal()`);await snap(m,`mockup-dialog-${w}`);await m.eval(`document.querySelector('#batal').close();document.querySelector('[data-toast]').click()`);await sleep(100);await snap(m,`mockup-toast-${w}`);await m.close();
 }
 await p.goto(base+'?role=teknisi&page=buat',{settleMs:300});
 const tech=await p.eval(`({name:document.querySelector('.me strong').textContent,role:document.querySelector('.me div span').textContent,active:[...document.querySelectorAll('.bottomnav [aria-current]')].map(e=>e.textContent.trim()),items:[...document.querySelectorAll('.bottomnav a')].map(e=>e.textContent.trim())})`);
 result.diagnostics.technician=tech;check('technician active and no admin destinations',tech.name==='Rizky'&&tech.role==='Teknisi'&&tech.active.includes('Buat TTS')&&!tech.items.includes('Laporan'));await snap(p,'technician-1280');
 await p.setViewport({width:360,height:900,mobile:true});await snap(p,'technician-360');
 result.errors.push(...p.consoleErrors);check('no runtime exceptions',p.consoleErrors.length===0);
} catch(e) { result.errors.push(String(e.stack||e)); process.exitCode=1; }
finally { writeFileSync(`${dir}/browser-results.json`,JSON.stringify(result,null,2)); await browser.close(); }
