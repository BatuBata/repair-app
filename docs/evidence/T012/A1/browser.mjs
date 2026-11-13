import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import assert from 'node:assert/strict';
const out=new URL('./',import.meta.url).pathname;
const root=readFileSync('/private/tmp/t012-a1-location','utf8').trim();
const secret=JSON.parse(readFileSync(root+'/private.json','utf8'));process.env.TASTE_SKILL_TMP=root+'/chrome';
const base='http://127.0.0.1:8190', observations=[];let browser;
const record=(id,actual)=>observations.push({id,actual,verdict:'pass'});
try{
 browser=await Browser.launch();
 const p=await browser.newPage({width:360,height:900,mobile:true});
 const wait=async(fn,label)=>{for(let i=0;i<80;i++){if(await p.eval(fn))return;await sleep(100);}throw Error('Timeout '+label);};
 const goto=async(path)=>{await p.goto(base+path,{settleMs:300,timeoutMs:10000});await p.eval(()=>document.fonts.ready.then(()=>true));};
 const click=async(sel)=>{const pt=await p.eval(s=>{const e=document.querySelector(s);if(!e)throw Error('Missing '+s);e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2};},sel);await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...pt,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...pt,button:'left',clickCount:1});};
 const fill=async(sel,text)=>{await click(sel);await p.eval(s=>document.querySelector(s).select(),sel);await p.send('Input.insertText',{text});};
 const shot=async(name)=>{await p.eval(()=>scrollTo(0,0));await sleep(100);await p.screenshot({path:out+name+'.png',format:'png'});};
 const login=async(username)=>{await goto('/login');await fill('#username',username);await fill('#password',secret.QA_PASSWORD);await click('[data-test="login-button"]');await wait(()=>location.pathname!='/login','login');};
 await login('t012_admin');await goto('/stok');assert.ok(await p.eval(()=>document.body.innerText.includes('Belum ada barang jualan.')));await shot('empty-360');record('EMPTY',{text:'Belum ada barang jualan.'});
 execFileSync('python3',['docs/evidence/T012/A1/runtime.py','fixture','products'],{stdio:'pipe'});
 await goto('/stok/baru');await click('.actionbar button[type=submit]');await wait(()=>!!document.querySelector('[aria-invalid=true]'),'validation');await sleep(200);assert.equal(await p.eval(()=>document.activeElement.name),'name');await shot('validation-360');record('VALIDATION-FOCUS',{focused:'name',errors:await p.eval(()=>[...document.querySelectorAll('.error-text')].map(x=>x.textContent))});
 await fill('input[name=name]','Pengisi daya USB T012');await fill('[data-money=money-cost_price]','150000');await fill('[data-money=money-sell_price]','200000');await fill('input[name=stock]','4');
 assert.equal(await p.eval(()=>document.querySelector('[data-money=money-cost_price]').value),'150.000');await shot('create-filled-360');
 await click('.actionbar button[type=submit]');await wait(()=>location.pathname==='/stok','save create');record('MONEY-CREATE',{display:'150.000',expectedInteger:150000});
 await goto('/stok/4/restok');await fill('input[name=qty]','2');await fill('[data-money=money-cost_price]','175000');await fill('input[name=note]','Pembelian sintetis T012');await shot('restock-filled-360');await click('.actionbar button[type=submit]');await wait(()=>document.body.innerText.includes('Restok berhasil disimpan'),'restock');assert.ok(await p.eval(()=>document.body.innerText.includes('6 unit')&&document.body.innerText.includes('Rp175.000')&&document.body.innerText.includes('+2 unit')));record('RESTOCK',{stock:6,cost_price:175000,historyQty:2});await shot('restock-success-360');await p.eval(()=>document.querySelector('.history').scrollIntoView({block:'start'}));await p.screenshot({path:out+'history-360.png',format:'png'});
 for(const width of [360,768,1280]){
  await p.setViewport({width,height:900,mobile:width<1024});await goto('/stok');
  const metrics=await p.eval(()=>({width:innerWidth,documentWidth:document.documentElement.scrollWidth,body:getComputedStyle(document.body).fontSize,font:getComputedStyle(document.body).fontFamily,menuTarget:(()=>{let r=document.querySelector('.act-btn').getBoundingClientRect();return{width:r.width,height:r.height,x:r.x}})(),firstNameX:document.querySelector('.stock-name').getBoundingClientRect().x,primary:(()=>{let e=[...document.querySelectorAll('.btn.primary')].filter(e=>e.getBoundingClientRect().width>0);return e.length})()}));
  assert.equal(metrics.width,width);assert.equal(metrics.documentWidth,width);assert.equal(metrics.body,width<1024?'16px':'14px');assert.ok(metrics.font.includes('IBM Plex Sans'));assert.equal(metrics.primary,1);assert.ok(metrics.menuTarget.x<metrics.firstNameX);if(width<1024)assert.ok(metrics.menuTarget.width>=44&&metrics.menuTarget.height>=44);record('LIST-'+width,metrics);await shot('list-'+width);
  await click('.act-btn');await wait(()=>!!document.querySelector('.menu:not([hidden])'),'menu');assert.equal(await p.eval(()=>document.activeElement.getAttribute('role')),'menuitem');await p.send('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowDown',code:'ArrowDown'});assert.ok(await p.eval(()=>document.activeElement.textContent.includes('Restok')));await shot('menu-'+width);await p.send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape'});assert.ok(await p.eval(()=>document.activeElement.classList.contains('act-btn')));record('MENU-'+width,{leftmost:true,keyboard:true,escapeFocus:true});
  await goto('/stok/4/restok');const restockMetrics=await p.eval(()=>({width:innerWidth,documentWidth:document.documentElement.scrollWidth,numericInput:document.querySelector('[data-money]').inputMode,qtyInput:document.querySelector('input[name=qty]').inputMode,actionbar:(()=>{const r=document.querySelector('.actionbar').getBoundingClientRect();const n=document.querySelector('.bottomnav').getBoundingClientRect();return{visible:r.width>0,bottom:r.bottom,navTop:n.top}})()}));assert.equal(restockMetrics.documentWidth,width);assert.equal(restockMetrics.numericInput,'numeric');assert.equal(restockMetrics.qtyInput,'numeric');if(width<1024){assert.ok(restockMetrics.actionbar.visible);assert.ok(restockMetrics.actionbar.bottom<=restockMetrics.actionbar.navTop+1);}record('RESTOCK-LAYOUT-'+width,restockMetrics);await shot('restock-'+width);
  await goto('/stok/baru');await shot('form-'+width);
  if(width===360||width===1280){for(const name of ['tts','tts-baru']){await p.goto('file://'+process.cwd()+'/mockup/'+name+'.html',{settleMs:300});await p.eval(()=>document.fonts.ready.then(()=>true));await shot('reference-'+name+'-'+width);}}
 }
 await p.send('Network.enable');await p.send('Network.clearBrowserCookies');await login('t012_teknisi');
 for(const path of ['/stok','/stok/baru','/stok/4/edit','/stok/4/restok']){await goto(path);assert.ok(await p.eval(()=>document.body.innerText.includes('403')));record('TECH-GUARD-'+path,{status:403});}await shot('technician-403-1280');
 assert.equal(p.consoleErrors.length,0,'runtime JavaScript errors');record('NO-JS-EXCEPTIONS',{count:0});
 execFileSync('python3',['docs/evidence/T012/A1/runtime.py','fixture','proof'],{stdio:'pipe'});const proof=JSON.parse(readFileSync(out+'fixture-proof.log','utf8'));assert.deepEqual(proof.product,{name:'Pengisi daya USB T012',cost_price:175000,sell_price:200000,stock:6});assert.equal(proof.entries.length,1);assert.equal(proof.expenses,0);assert.ok(proof.private_session&&proof.private_view);record('DATABASE-PROOF',proof);
 writeFileSync(out+'browser-results.json',JSON.stringify({verdict:'pass',version:await browser.send('Browser.getVersion'),observations},null,2)+'\n');console.log(JSON.stringify({verdict:'pass',observations:observations.length}));
}catch(e){writeFileSync(out+'browser-failure.json',JSON.stringify({error:e.message,observations},null,2)+'\n');console.error(e.stack);process.exitCode=1;}
finally{if(browser)await browser.close();}
