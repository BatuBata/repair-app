import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import assert from 'node:assert/strict';
const root='/private/tmp/repair-t019-a2-20261113', mode=process.argv[2], out=root+'/'+mode;
const password=JSON.parse(readFileSync(root+'/private.json')).password;
process.env.TASTE_SKILL_TMP=root+'/taste';mkdirSync(out,{recursive:true});
let b,p;const cases=[],taps=[];let assertionCount=0;
const check=(condition,label)=>{assert.ok(condition,label);assertionCount++;};
const wait=async(fn,label)=>{for(let i=0;i<100;i++){if(await p.eval(fn).catch(()=>false))return;await sleep(80);}throw Error('Timeout '+label);};
const goto=async(path)=>{await p.goto('http://127.0.0.1:8191'+path,{settleMs:150});await p.eval(()=>document.fonts.ready.then(()=>true));await sleep(100);};
const click=async(sel)=>{const pt=await p.eval(sel=>{const e=document.querySelector(sel);e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2};},sel);await mouse(pt);};
const mouse=async(pt)=>{await p.send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...pt});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...pt});};
const touch=async(pt)=>{await p.send('Input.dispatchTouchEvent',{type:'touchStart',touchPoints:[{...pt,radiusX:1,radiusY:1,force:1,id:1}]});await p.send('Input.dispatchTouchEvent',{type:'touchEnd',touchPoints:[]});};
const fill=async(sel,text)=>{await click(sel);await p.eval(sel=>document.querySelector(sel).select(),sel);await p.send('Input.insertText',{text});};
const dates=async(day)=>{await p.eval(async day=>{const c=Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));await c.set('to',day);await c.set('from',day);},day);await sleep(150);};
const shot=async(name)=>{await p.eval(()=>scrollTo(0,0));await sleep(100);await p.screenshot({path:out+'/'+name+'.png',format:'png',fullPage:true});};
const measure=async()=>await p.eval(()=>({width:innerWidth,documentWidth:document.documentElement.scrollWidth,font:getComputedStyle(document.querySelector('h1')).fontFamily,links:[...document.querySelectorAll('.report-number a')].map(a=>{const r=a.getBoundingClientRect(),range=document.createRange();range.selectNodeContents(a);const tr=range.getBoundingClientRect();const e=a.closest('tr').querySelector('.report-detail'),er=e.getBoundingClientRect();return{label:a.textContent,href:a.getAttribute('href'),width:r.width,height:r.height,display:getComputedStyle(a).display,textHeight:tr.height,eye:{x:er.x,width:er.width,height:er.height},x:r.x};}),money:document.querySelector('.statement').innerText.replaceAll('\n',' '),rows:[...document.querySelectorAll('.report-table tbody tr')].map(tr=>({number:tr.querySelector('.report-number')?.textContent,amount:tr.querySelector('.report-amount')?.innerText})),footer:document.querySelector('.report-table tfoot').innerText}));
const watch=async()=>{await p.send('Runtime.addBinding',{name:'recordTap'});await p.eval(()=>document.addEventListener('pointerdown',e=>window.recordTap(JSON.stringify({trusted:e.isTrusted,pointerType:e.pointerType,anchor:e.target.closest('a')?.getAttribute('href'),eye:!!e.target.closest('.report-detail')})),true));};
try{
 b=await Browser.launch({width:360,height:900});
 writeFileSync(out+'/browser-process.json',JSON.stringify({pid:b.proc.pid,profile:b.dir,cdp:b.ws.url,taste:process.env.TASTE_SKILL_TMP,node:process.version},null,2));
 p=await b.newPage({width:360,height:900});b.on(p.sessionId,'Runtime.bindingCalled',e=>{if(e.name==='recordTap')taps.push(JSON.parse(e.payload));});
 await goto('/login');await fill('#username','t019review');await fill('#password',password);await click('[data-test=login-button]');await wait(()=>location.pathname!='/login','login');
 for(const width of[360,768,1280]){
  await p.setViewport({width,height:width===768?1024:900});await goto('/laporan');
  const large=await measure();check(large.documentWidth===width,'long/negative no overflow '+width);check(large.money.includes('−Rp1.179.869.179.999.265.000'),'exact long negative '+width);check(large.money.includes('Rp16.000.000.000.002.600.000'),'exact long revenue '+width);check(large.font.includes('IBM Plex Sans'),'Plex Sans '+width);
  await shot('long-negative-'+width);
  await dates('2026-11-11');const sample=await measure();check(sample.documentWidth===width,'sample no overflow '+width);check(sample.money.includes('Rp735.000'),'sample net '+width);check(sample.links.length===2,'sample TTS and sale '+width);check(sample.links.every(a=>a.eye.x<a.x),'eye left of link '+width);await shot('sample-'+width);
  cases.push({width,large,sample});
  if(mode==='before')continue;
  if(width<1024)check(sample.links.every(a=>a.width>=44&&a.height>=44),'all actual anchors44 '+width);
  for(const kind of['tts','penjualan']){
   await goto('/laporan');await dates('2026-11-11');await watch();
   const selector=`.report-number a[href*="/${kind}/"]`;
   const pt=await p.eval(selector=>{const a=document.querySelector(selector);a.scrollIntoView({block:'center'});const r=a.getBoundingClientRect(),range=document.createRange();range.selectNodeContents(a);const t=range.getBoundingClientRect(),x=r.x+r.width/2,y=innerWidth<1024?Math.min(r.bottom-3,t.bottom+5):r.y+r.height/2,hit=document.elementFromPoint(x,y);return{x,y,href:new URL(a.href).pathname,belowText:y>t.bottom,hit:hit?.closest('a')===a,width:r.width,height:r.height};},selector);
   check(pt.hit,'added area hits actual anchor');if(width<1024)check(pt.belowText,'tap below text area');
   const start=taps.length;if(width<1024){await p.send('Emulation.setTouchEmulationEnabled',{enabled:true});await touch(pt);}else await mouse(pt);
   await wait(()=>location.pathname!=='/laporan','link navigation');check(await p.eval(()=>location.pathname)===pt.href,'correct route');check(taps.length>start&&taps.at(-1).trusted,'trusted pointer');if(width<1024)check(taps.at(-1).pointerType==='touch','real touch');cases.push({width,kind,pt,navigated:await p.eval(()=>location.pathname),event:taps.at(-1)});
  }
  await goto('/laporan');await dates('2026-11-11');await watch();
  const eye=await p.eval(()=>{const e=document.querySelector('.report-detail');e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2};});
  if(width<1024)await touch(eye);else await mouse(eye);
  await wait(()=>!!document.querySelector('dialog[open]'),'distinct eye dialog');check(await p.eval(()=>location.pathname)==='/laporan','eye keeps route');check(taps.at(-1).trusted&&taps.at(-1).eye,'trusted eye tap');await p.screenshot({path:out+'/eye-dialog-'+width+'.png',format:'png'});
  await click('dialog[open] .section-head button');await wait(()=>!document.querySelector('dialog[open]'),'close');check(await p.eval(()=>document.activeElement?.classList.contains('report-detail')),'eye focus restored');cases.push({width,eyeDialog:true,route:'/laporan',focusRestored:true});
 }
 if(mode==='after')for(const width of[360,1280]){await p.setViewport({width,height:900});await p.goto('file:///Users/muzib/Projects/web/repair-app/mockup/laporan.html',{settleMs:100});await p.eval(()=>document.fonts.ready.then(()=>true));await shot('reference-'+width);}
 check(p.consoleErrors.length===0,'no JS exceptions');writeFileSync(out+'/results.json',JSON.stringify({verdict:'pass',mode,assertionCount,cases,taps,consoleErrors:p.consoleErrors},null,2));console.log(JSON.stringify({mode,assertionCount,cases:cases.length}));
}catch(e){writeFileSync(out+'/failure.json',JSON.stringify({error:e.stack,assertionCount,cases,taps},null,2));if(p)await p.screenshot({path:out+'/failure.png',format:'png'});console.error(e);process.exitCode=1;}finally{if(b)await b.close();}
