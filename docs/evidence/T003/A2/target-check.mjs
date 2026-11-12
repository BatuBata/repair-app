import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import { writeFileSync } from 'node:fs';
import assert from 'node:assert/strict';
const b=await Browser.launch(), result={widths:{},dialog:[],toast:null};
try {
 const p=await b.newPage();
 for(const width of [360,768,1280]) {
  await p.setViewport({width,height:900,mobile:width<1024});await p.goto('http://127.0.0.1:8083/layout-preview?page=servis',{settleMs:300});
  const v=await p.eval(`(()=>{const bad=[...document.querySelectorAll('a,button,input:not([type=hidden])')].filter(e=>{const r=e.getBoundingClientRect(),s=getComputedStyle(e);return r.width&&r.height&&s.visibility!=='hidden'&&(r.width<44||r.height<44)}).map(e=>({name:e.textContent.trim(),w:e.getBoundingClientRect().width,h:e.getBoundingClientRect().height}));return {input:parseFloat(getComputedStyle(document.querySelector('[name=hp]')).fontSize),body:parseFloat(getComputedStyle(document.body).fontSize),bad}})()`);
  result.widths[width]=v;assert.ok(v.input >= (width<1024?16:14));assert.ok(v.body >= (width<1024?16:14));if(width<1024) assert.deepEqual(v.bad,[]);
 }
 const pos=await p.eval(`(()=>{const r=document.querySelector('[aria-controls="pilihan-14"]').getBoundingClientRect();return {x:r.left+r.width/2,y:r.top+r.height/2}})()`);
 await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...pos,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...pos,button:'left',clickCount:1});
 result.desktopMenu=await p.eval(`!document.querySelector('#pilihan-14').hidden`);assert.equal(result.desktopMenu,true);
 await p.send('Input.dispatchMouseEvent',{type:'mousePressed',x:450,y:80,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',x:450,y:80,button:'left',clickCount:1});
 result.desktopOutside=await p.eval(`document.querySelector('#pilihan-14').hidden`);assert.equal(result.desktopOutside,true);
 await p.setViewport({width:360,height:900,mobile:true});await p.goto('http://127.0.0.1:8083/layout-preview',{settleMs:300});
 await p.eval(`document.querySelector('#contoh-dialog').showModal()`);
 result.dialog=await p.eval(`[...document.querySelectorAll('#contoh-dialog button,#contoh-dialog input')].map(e=>{let r=e.getBoundingClientRect();return {name:e.textContent.trim()||e.name,width:r.width,height:r.height}})`);
 assert.ok(result.dialog.every(e=>e.width>=44&&e.height>=44));
 await p.eval(`document.querySelector('#contoh-dialog').close();document.querySelector('[data-toast="Data contoh tersimpan"]').click()`);
 assert.equal(await p.eval(`document.querySelector('.toast').classList.contains('show')`),true);await sleep(2800);result.toast=await p.eval(`document.querySelector('.toast').classList.contains('show')`);assert.equal(result.toast,false);
} catch(e) {result.error=String(e.stack||e);process.exitCode=1;} finally {writeFileSync('docs/evidence/T003/A2/target-results.json',JSON.stringify(result,null,2));await b.close()}
