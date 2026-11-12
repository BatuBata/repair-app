import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync,copyFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
const repo=process.cwd(), out=repo+'/docs/evidence/T008', priv=JSON.parse(readFileSync(out+'/session.json')).private_root;
const secret=JSON.parse(readFileSync(priv+'/private.json')); const base='http://127.0.0.1:8088';
const browser=await Browser.launch();const p=await browser.newPage({width:360,height:800,mobile:true});
const report={tool:'Installed Chrome via design-taste-frontend zero-dependency CDP client',version:await browser.send('Browser.getVersion'),states:[],interactions:[],manifest:[],sessionCases:[],errors:[],started_at:new Date().toISOString()};
const priorSupplement=JSON.parse(readFileSync(out+'/supplement-first-run.json'));report.states=priorSupplement.states;report.interactions=priorSupplement.interactions;report.errors=priorSupplement.errors;
const save=()=>writeFileSync(out+'/supplement-results.json',JSON.stringify(report,null,2));
let w=360;
const ev=async()=>p.eval(()=>({width:innerWidth,height:innerHeight,doc:document.documentElement.scrollWidth,coarse:matchMedia('(pointer:coarse)').matches,touch:navigator.maxTouchPoints,url:location.pathname}));
async function go(path){await p.goto(path.startsWith('file:')?path:base+path,{settleMs:200});await p.eval(()=>document.fonts.ready.then(()=>true));}
async function wait(fn){for(let i=0;i<60;i++){if(await p.eval(fn))return;await sleep(100);}throw Error('UI condition timeout: '+fn.toString().slice(0,90));}
async function click(selector,kind=w<1024?'touch':'mouse'){
 const r=await p.eval(s=>{const e=[...document.querySelectorAll(s)].find(e=>{const r=e.getBoundingClientRect();return r.width&&r.height&&getComputedStyle(e).visibility!=='hidden'});if(!e)throw Error('No visible '+s);e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();const hit=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);if(!e.contains(hit))throw Error('Harness target obscured: '+s);window.__qaEvents=[];e.addEventListener('click',x=>window.__qaEvents.push({type:x.type,isTrusted:x.isTrusted,pointerType:x.pointerType}),{once:true});return {x:r.x+r.width/2,y:r.y+r.height/2,label:e.getAttribute('aria-label')||e.textContent.trim().slice(0,60)};},selector);
 if(kind==='touch'){await p.send('Input.dispatchTouchEvent',{type:'touchStart',touchPoints:[{x:r.x,y:r.y}]});await p.send('Input.dispatchTouchEvent',{type:'touchEnd',touchPoints:[]});}
 else{await p.send('Input.dispatchMouseEvent',{type:'mousePressed',x:r.x,y:r.y,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',x:r.x,y:r.y,button:'left',clickCount:1});}
 await sleep(250);report.interactions.push({viewport:w,selector,kind,label:r.label,events:await p.eval(()=>window.__qaEvents||[])});save();
}
async function key(key,code=key,virtual=0){await p.send('Input.dispatchKeyEvent',{type:'keyDown',key,code,windowsVirtualKeyCode:virtual});await p.send('Input.dispatchKeyEvent',{type:'keyUp',key,code,windowsVirtualKeyCode:virtual});await sleep(100);}
async function fill(selector,value){await click(selector);await p.send('Input.dispatchKeyEvent',{type:'keyDown',key:'a',code:'KeyA',modifiers:4,windowsVirtualKeyCode:65});await p.send('Input.dispatchKeyEvent',{type:'keyUp',key:'a',code:'KeyA',modifiers:4,windowsVirtualKeyCode:65});await p.send('Input.insertText',{text:value});}
async function snap(id,{lower=false}={}){
 if(lower)await p.wheel(1800);
 const metrics=await p.eval(()=>{
  const visible=e=>{let r=e.getBoundingClientRect();return r.width>0&&r.height>0&&getComputedStyle(e).visibility!=='hidden'};
  const box=e=>{let r=e.getBoundingClientRect(),s=getComputedStyle(e);return {tag:e.tagName,text:(e.getAttribute('aria-label')||e.innerText||e.getAttribute('name')||e.type||'').trim().slice(0,75),x:r.x,y:r.y,width:r.width,height:r.height,font:s.fontSize,fontFamily:s.fontFamily};};
  const targets=[...document.querySelectorAll('a,button,input,select,textarea')].filter(visible).map(e=>{let target=e;if(e.type==='checkbox')target=e.closest('label')||e;return box(target)});
  return {viewport:{width:innerWidth,height:innerHeight,documentWidth:document.documentElement.scrollWidth,scrollY},h1:document.querySelector('h1')?.innerText,bodyFont:getComputedStyle(document.body).fontSize,fontLoaded:document.fonts.check('16px "IBM Plex Sans"'),fonts:[...document.fonts].filter(f=>f.status==='loaded').map(f=>({family:f.family,weight:f.weight})),tokens:Object.fromEntries(['--ground','--surface','--text','--field','--primary','--fs-body'].map(k=>[k,getComputedStyle(document.documentElement).getPropertyValue(k).trim()])),targets,overflow:[...document.querySelectorAll('main *')].filter(visible).filter(e=>{let r=e.getBoundingClientRect();return r.x<-.5||r.right>innerWidth+.5}).slice(0,12).map(box),invalid:[...document.querySelectorAll('[aria-invalid=true]')].map(e=>e.name||e.id),focus:document.activeElement?.getAttribute('name')||document.activeElement?.id,actionbar:[...document.querySelectorAll('.actionbar')].filter(visible).map(box),logo:[...document.images].filter(e=>e.classList.contains('logo-preview')).map(e=>({loaded:e.complete&&e.naturalWidth>0,width:e.naturalWidth,height:e.naturalHeight}))};
 });
 const file=`screenshots/${id}-${w}${lower?'-lower':''}.png`;await p.screenshot({path:out+'/'+file,format:'png',fullPage:false});
 report.states.push({id,width:w,screenshot:file,metrics});save();
}
async function login(role){await p.send('Network.clearBrowserCookies');await go('/login');await fill('[name=username]','qa_'+role);await fill('[name=password]',secret.QA_PASSWORD);await click('[name=remember]');await click('button[type=submit]');await wait(()=>location.pathname!=='/login');}
async function action(name){await click(`button[wire\\:click="${name}"]`);await wait(()=>!document.querySelector('button[wire\\:click="create"]'));}
async function submitError(){await click('form[wire\\:submit] button[type=submit]');await wait(()=>document.querySelector('[aria-invalid=true]'));}
async function menuChecks(type){
 await click('.act-btn'); await wait(()=>document.querySelector('.act-btn[aria-expanded=true]'));
 const open=await p.eval(()=>{const e=document.querySelector('.menu:not([hidden])'),r=e.getBoundingClientRect();return {parent:e.parentElement.tagName,left:r.left,right:r.right,top:r.top,bottom:r.bottom,focus:document.activeElement.textContent.trim()}});
 await snap(type+'-menu');if(!await p.eval(()=>document.querySelector('.act-btn[aria-expanded=true]'))) await click('.act-btn');await key('ArrowDown','ArrowDown',40);let down=await p.eval(()=>document.activeElement.textContent.trim());await key('ArrowUp','ArrowUp',38);let up=await p.eval(()=>document.activeElement.textContent.trim());await key('Escape','Escape',27);let escape=await p.eval(()=>!document.querySelector('.act-btn[aria-expanded=true]')&&document.activeElement.classList.contains('act-btn'));
 await click('.act-btn');await click('h1');let outside=await p.eval(()=>!document.querySelector('.act-btn[aria-expanded=true]'));
 await click('.act-btn');await p.wheel(300);let scroll=await p.eval(()=>!document.querySelector('.act-btn[aria-expanded=true]'));
 report.interactions.push({case:type+'-menu-keyboard-outside-scroll',viewport:w,open,down,up,escape,outside,scroll});save();await p.eval(()=>scrollTo(0,0));
}

async function replace(selector,value){await p.eval(s=>{const e=document.querySelector(s);e.value='';e.dispatchEvent(new Event('input',{bubbles:true}));},selector);await fill(selector,value);const actual=await p.eval(s=>document.querySelector(s).value,selector);if(actual!==value)throw Error('Harness input mismatch '+selector);}
async function contrast(id){const data=await p.eval(()=>{const s=getComputedStyle(document.querySelector('input.input')),b=getComputedStyle(document.body);return {input:{ink:s.color,background:s.backgroundColor,border:s.borderTopColor,size:s.fontSize},body:{ink:b.color,background:b.backgroundColor,size:b.fontSize}};});report.interactions.push({case:'contrast-'+id,viewport:w,data});}
try {
 for(const width of [360,768]){
  w=width;await p.setViewport({width:w,height:800,mobile:true});await login('admin');await go('/pengaturan/pengguna');await click('button[wire\\:click="create"]');await wait(()=>document.querySelector('input[name=password_confirmation]'));if(w!==360)await snap('users-create-viewport');
  await submitError();if(w!==360)await snap('users-error-viewport');await p.wheel(1600);if(w!==360)await snap('users-error-bottom-viewport');
  await replace('[name=name]','Pengguna sentuh T008 '+w);await replace('[name=username]','qa_browser_'+w);await replace('[name=password]',secret.QA_PASSWORD);await replace('[name=password_confirmation]',secret.QA_PASSWORD);await snap('users-filled-bottom-viewport');await click('form[wire\\:submit] button[type=submit]');await wait(()=>document.querySelector('[role=status]'));await snap('users-save-success');
  await go('/pengaturan/toko');await contrast('store');await replace('[name=default_warranty_days]','-1');await submitError();await snap('store-error-viewport');await p.wheel(1600);await snap('store-error-bottom-viewport');await replace('[name=default_warranty_days]','30');await click('form[wire\\:submit] button[type=submit]');await wait(()=>document.querySelector('[role=status]'));await snap('store-save-success');
  await go('/profil');await submitError();await snap('profile-admin-error-viewport');await p.wheel(1600);await snap('profile-admin-error-bottom-viewport');
  await go('/pengaturan/kategori');await click('button[wire\\:click="create"]');await wait(()=>document.querySelector('input[name=name]'));await replace('[name=name]','Kategori sentuh T008 '+w);await click('form[wire\\:submit] button[type=submit]');await wait(()=>document.querySelector('[role=status]'));await snap('category-save-success');
  await login('teknisi');await go('/profil');await submitError();await snap('profile-technician-error-viewport');await p.wheel(1600);await snap('profile-technician-error-bottom-viewport');
 }
 w=360;await p.setViewport({width:w,height:800,mobile:true});await login('teknisi');await go('/profil');await replace('[name=current_password]',secret.QA_PASSWORD);await replace('[name=password]',secret.QA_PASSWORD+'-ui');await replace('[name=password_confirmation]',secret.QA_PASSWORD+'-ui');await click('form[wire\\:submit] button[type=submit]');await wait(()=>document.querySelector('[role=status]'));await snap('profile-save-success');
 await p.send('Network.clearBrowserCookies');await go('/login');await fill('[name=username]','qa_admin');await click('button[type=submit]');await wait(()=>document.querySelector('[aria-invalid=true]'));await fill('[name=password]',secret.QA_PASSWORD);await click('button[type=submit]');await wait(()=>location.pathname==='/');report.interactions.push({case:'login-error-recovery',verdict:'pass',actual:await ev()});
}catch(e){report.errors.push({phase:'supplement',message:e.message,viewport:w});await snap('supplement-error').catch(()=>{});}
report.finished_at=new Date().toISOString();save();await p.close();await browser.close();console.log(JSON.stringify({states:report.states.length,errors:report.errors}));
