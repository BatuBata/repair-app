import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync,copyFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
const repo=process.cwd(), out=repo+'/docs/evidence/T008', priv=JSON.parse(readFileSync(out+'/session.json')).private_root;
const secret=JSON.parse(readFileSync(priv+'/private.json')); const base='http://127.0.0.1:8088';
const browser=await Browser.launch();const p=await browser.newPage({width:360,height:800,mobile:true});
const report={tool:'Installed Chrome via design-taste-frontend zero-dependency CDP client',version:await browser.send('Browser.getVersion'),states:[],interactions:[],manifest:[],sessionCases:[],errors:[],started_at:new Date().toISOString()};
const prior=JSON.parse(readFileSync(out+'/browser-second-run.json')); report.states=prior.states; report.interactions=prior.interactions; report.errors=prior.errors;
const save=()=>writeFileSync(out+'/browser-results.json',JSON.stringify(report,null,2));
let w=360;
const ev=async()=>p.eval(()=>({width:innerWidth,height:innerHeight,doc:document.documentElement.scrollWidth,coarse:matchMedia('(pointer:coarse)').matches,touch:navigator.maxTouchPoints,url:location.pathname}));
async function go(path){await p.goto(path.startsWith('file:')?path:base+path,{settleMs:200});await p.eval(()=>document.fonts.ready.then(()=>true));}
async function wait(fn){for(let i=0;i<60;i++){if(await p.eval(fn))return;await sleep(100);}throw Error('UI condition timeout: '+fn.toString().slice(0,90));}
async function click(selector,kind=w<1024?'touch':'mouse'){
 const r=await p.eval(s=>{const e=[...document.querySelectorAll(s)].find(e=>{const r=e.getBoundingClientRect();return r.width&&r.height&&getComputedStyle(e).visibility!=='hidden'});if(!e)throw Error('No visible '+s);e.scrollIntoView({block:'nearest'});const r=e.getBoundingClientRect();window.__qaEvents=[];e.addEventListener('click',x=>window.__qaEvents.push({type:x.type,isTrusted:x.isTrusted,pointerType:x.pointerType}),{once:true});return {x:r.x+r.width/2,y:r.y+r.height/2,label:e.getAttribute('aria-label')||e.textContent.trim().slice(0,60)};},selector);
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
 const file=`screenshots/${id}-${w}${lower?'-lower':''}.png`;await p.screenshot({path:out+'/'+file,format:'png',fullPage:!lower&&!id.includes('menu')});
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
try {
 for(const width of [360,768,1280]){
  w=width;await p.setViewport({width:w,height:800,mobile:w<1024});
  if(w!==360) {
  for(const page of ['login','tts','tts-baru']) {await go('file://'+repo+'/mockup/'+page+'.html');await snap('mockup-'+page);}
  await p.send('Network.clearBrowserCookies');await go('/login');await snap('login');await fill('[name=username]','qa_admin');await click('button[type=submit]');await wait(()=>document.querySelector('[aria-invalid=true]'));await snap('login-error');
  await login('admin');await go('/');await snap('admin-home');
  await go('/pengaturan/pengguna');await snap('users-list');await menuChecks('users');
  await click('button[wire\\:click="create"]');await wait(()=>document.querySelector('input[name=password_confirmation]'));await snap('users-create');await snap('users-create',{lower:true});await submitError();await snap('users-create-error');
  await go('/pengaturan/pengguna');await click('.act-btn');await click('.menu:not([hidden]) [role=menuitem]');await wait(()=>document.querySelector('input[name=username]'));await snap('users-edit');
  }
  if(w!==360){
  await login('admin');await go('/pengaturan/pengguna');if(w===360)await menuChecks('users-recheck');await go('/pengaturan/pengguna');await click('.act-btn');await key('ArrowDown','ArrowDown',40);await click('.menu:not([hidden]) button:nth-of-type(2)');await wait(()=>document.querySelector('h1').textContent.includes('Reset'));await snap('users-reset');await submitError();await snap('users-reset-error');
  await go('/profil');await snap('profile-admin');await snap('profile-admin',{lower:true});await submitError();await snap('profile-admin-error');
  await go('/pengaturan/toko');await snap('store');await snap('store',{lower:true});await fill('[name=default_warranty_days]','-1');await submitError();await snap('store-error');
  }
  await login('admin');await go('/pengaturan/toko');const {root}=await p.send('DOM.getDocument');const {nodeId}=await p.send('DOM.querySelector',{nodeId:root.nodeId,selector:'input[type=file]'});copyFileSync(repo+'/public/icons/ticket-192.png',priv+'/qa-logo.png');await p.send('DOM.setFileInputFiles',{nodeId,files:[priv+'/qa-logo.png']});await wait(()=>document.querySelector('.logo-preview')?.complete);await snap('store-logo');await snap('store-logo',{lower:true});
  await go('/pengaturan/kategori');await snap('categories-list');await menuChecks('categories');await click('button[wire\\:click="create"]');await wait(()=>document.querySelector('input[name=name]'));await snap('categories-create');await submitError();await snap('categories-error');
  await go('/pengaturan/kategori');await click('.act-btn');await click('.menu:not([hidden]) [role=menuitem]');await wait(()=>document.querySelector('input[name=name]'));await snap('categories-edit');
  await login('teknisi');await go('/profil');await snap('profile-technician');await snap('profile-technician',{lower:true});await submitError();await snap('profile-technician-error');
 }
 // Manifest: real launch navigation from all three session states, not just post-login redirect.
 w=360;await p.setViewport({width:360,height:800,mobile:true});
 for(const role of ['guest','admin','teknisi']){
  if(role==='guest'){await p.send('Network.clearBrowserCookies');await go('/login');} else {await login(role);await go('/profil');}
  const manifest=await p.send('Page.getAppManifest');const install=await p.send('Page.getInstallabilityErrors').catch(e=>({toolError:e.message}));
  const data=JSON.parse(manifest.data);const before=await ev();
  const iconCheck=await p.eval(async m=>Promise.all(m.icons.map(async i=>{const r=await fetch(i.src);const b=await r.blob();const img=await createImageBitmap(b);return {src:i.src,status:r.status,width:img.width,height:img.height,type:b.type};})),data);
  await go(new URL(data.start_url,base).pathname);const actual=await ev();const title=await p.eval(()=>document.title);await snap('manifest-launch-'+role);
  report.manifest.push({role,before,manifest:{url:manifest.url,errors:manifest.errors,data},installability:install,icons:iconCheck,actual,title,expected:role==='guest'?'/login':role==='admin'?'/':'/tts/baru',verdict:actual.url===(role==='guest'?'/login':role==='admin'?'/':'/tts/baru')?'pass':'fail'});save();
 }
} catch(e){report.errors.push({phase:'visual_or_manifest',message:e.message,viewport:w,url:await p.eval(()=>location.pathname).catch(()=>null)});save();}
report.errors[0].classification='Evidence/tool sequencing: full-page screenshot resized/scrolled the page and closed its dropdown before subsequent keyboard input; rerun only the unproved subset with viewport menu capture and explicit live state.';
report.finished_at=new Date().toISOString();save();await p.close();await browser.close();console.log(JSON.stringify({states:report.states.length,manifest:report.manifest.map(x=>({role:x.role,verdict:x.verdict,path:x.actual.url})),errors:report.errors}));
