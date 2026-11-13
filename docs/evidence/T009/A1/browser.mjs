import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const runtime=readFileSync('/private/tmp/t009-a1-location','utf8').trim();
const secret=JSON.parse(readFileSync(runtime+'/private.json','utf8'));
process.env.TASTE_SKILL_TMP=runtime+'/chrome';
const out=new URL('./',import.meta.url).pathname;
const repo=process.cwd();const base='http://127.0.0.1:8189';
const prior=JSON.parse(readFileSync(out+'browser-failure-third.json','utf8'));
const observations=prior.observations;const jsonPrivacy=prior.jsonPrivacy;let browser;
try{
 browser=await Browser.launch();
 for(const width of [768,1280]){
  const page=await browser.newPage({width,height:900,mobile:width<1024});
  await page.send('Network.enable');await page.send('Network.clearBrowserCookies');
  const responses=new Map();
  browser.on(page.sessionId,'Network.responseReceived',({requestId,response})=>{if(response.mimeType.includes('json')&&new URL(response.url).pathname.endsWith('/update'))responses.set(requestId,true);});
  browser.on(page.sessionId,'Network.loadingFinished',({requestId})=>{if(responses.has(requestId))page.send('Network.getResponseBody',{requestId}).then(r=>jsonPrivacy.push({width,privateValuesAbsent:!['818181','919191','PART PRIVAT UJI'].some(v=>r.body.includes(v))})).catch(()=>{});});
  const wait=async(fn,reason)=>{for(let i=0;i<80;i++){if(await page.eval(fn))return;await sleep(100);}throw Error(reason);};
  const settle=async()=>{await wait(()=>document.body&&getComputedStyle(document.body).fontFamily.includes('IBM Plex Sans')&&document.fonts.status==='loaded','Styles/fonts not settled');};
  const goto=async path=>{await page.goto(base+path,{settleMs:200,timeoutMs:10000});if(await page.eval(()=>document.body.hasAttribute('data-repair-shell')))await settle();};
  const click=async selector=>{const p=await page.eval(s=>{const e=document.querySelector(s);if(!e)throw Error('Missing selector '+s);e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}},selector);if(width<1024){await page.send('Input.dispatchTouchEvent',{type:'touchStart',touchPoints:[p]});await page.send('Input.dispatchTouchEvent',{type:'touchEnd',touchPoints:[]});}else{await page.send('Input.dispatchMouseEvent',{type:'mousePressed',...p,button:'left',clickCount:1});await page.send('Input.dispatchMouseEvent',{type:'mouseReleased',...p,button:'left',clickCount:1});}};
  const fill=async(selector,text)=>{await click(selector);await page.send('Input.dispatchKeyEvent',{type:'keyDown',key:'a',code:'KeyA',commands:['selectAll']});await page.send('Input.dispatchKeyEvent',{type:'keyUp',key:'a',code:'KeyA'});await page.send('Input.insertText',{text});};
  const capture=async(id,file)=>{await settle();await page.eval(()=>scrollTo(0,0));await sleep(100);const m=await page.eval(()=>({path:location.pathname,width:innerWidth,documentWidth:document.documentElement.scrollWidth,font:getComputedStyle(document.body).fontFamily,bodyFontSize:getComputedStyle(document.body).fontSize,phoneType:document.querySelector('#customer_phone')?.type,phoneInputMode:document.querySelector('#customer_phone')?.inputMode,actionbar:[...document.querySelectorAll('.actionbar')].filter(e=>getComputedStyle(e).display!=='none').map(e=>{const r=e.getBoundingClientRect();return{top:r.top,bottom:r.bottom,height:r.height}}),privateValuesAbsent:!['818181','919191','PART PRIVAT UJI'].some(v=>document.documentElement.outerHTML.includes(v))}));assert.equal(m.width,width,id);assert.equal(m.documentWidth,width,id);assert.ok(m.privateValuesAbsent,id);observations.push({id,...m});await page.screenshot({path:out+file,format:'png'});};
  const saveSelector=width<1024?'.actionbar button[type=submit]':'.form-end button[type=submit]';
  await goto('/login');await fill('#username','t009_teknisi');await fill('#password',secret.ADMIN_INITIAL_PASSWORD);await click('[data-test="login-button"]');await wait(()=>location.pathname==='/tts/baru','Technician login target');await settle();
  if(width===1280){
  await capture('form-'+width,'form-'+width+'.png');
  if(width<1024){const bars=observations.at(-1).actionbar;assert.equal(bars.length,1);assert.ok(bars[0].bottom<=900-64&&bars[0].top>0);}
  await fill('#customer_name','Bu Ratna Sari');await click(saveSelector);await wait(()=>document.querySelectorAll('.error-text').length>=3,'Required validation visible');assert.equal(await page.eval(()=>document.querySelector('#customer_name').value),'Bu Ratna Sari');await capture('form-errors-'+width,'form-errors-'+width+'.png');
  await fill('#customer_phone','0812-5531-2097');await click('#category-1');await fill('#complaint','Pengering tidak berputar');await click('details.more summary');await fill('#item_description','Sharp 2 tabung, 8 kg');await fill('#customer_address','Jl. A. Yani Km 4, Gg. Melati 2');
  await page.send('Network.emulateNetworkConditions',{offline:false,latency:300,downloadThroughput:-1,uploadThroughput:-1});
  await click(saveSelector);await sleep(40);const disabled=await page.eval(s=>document.querySelector(s)?.disabled,saveSelector);assert.equal(disabled,true,'Save disabled during request');observations.push({id:'saving-disabled-'+width,disabled});
  await wait(()=>location.pathname.endsWith('/sukses'),'Save redirects success');await page.send('Network.emulateNetworkConditions',{offline:false,latency:0,downloadThroughput:-1,uploadThroughput:-1});await settle();const created=await page.eval(()=>Number(location.pathname.split('/')[2]));
  assert.ok(await page.eval(()=>document.body.innerText.includes('Tanda terima tersimpan')&&document.body.innerText.includes('6281255312097')));await capture('success-'+width,'success-'+width+'.png');
  }
  if(width===768)await goto(prior.observations.find(o=>o.id==='success-768').path);
  await click('.success-actions a[href$="/tts/baru"]');await wait(()=>location.pathname==='/tts/baru','Create another');await settle();assert.equal(await page.eval(()=>document.querySelector('#customer_name').value),'');
  observations.push({id:'create-another-'+width,result:'PASS'});
  await goto('/tts');await capture('list-'+width,'list-'+width+'.png');
  const left=await page.eval(()=>{const row=document.querySelector('.table tbody tr');const a=row.querySelector('.act').getBoundingClientRect(),d=row.querySelector('.c-id').getBoundingClientRect();return{actionX:a.x,dataX:d.x};});assert.ok(left.actionX<left.dataX);observations.push({id:'left-actions-'+width,...left});
  await click('.act-btn');assert.ok(await page.eval(()=>document.querySelector('[role=menu]:not([hidden])')));await page.send('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowDown',code:'ArrowDown'});await page.send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape'});assert.equal(await page.eval(()=>document.querySelector('.act-btn').getAttribute('aria-expanded')),'false');
  await fill('.toolbar input','Hendra');await wait(()=>document.querySelector('.total')?.textContent.trim()==='1 TTS','Search updates list');assert.ok(await page.eval(()=>document.querySelector('.table').innerText.includes('Pak Hendra')));await fill('.toolbar input','');await sleep(450);await click('.tabs button[wire\\:click*="dikerjakan"]');await wait(()=>document.querySelector('.total')?.textContent.trim()==='1 TTS','Status filter');
  await goto('/tts/1');await capture('detail-'+width,'detail-'+width+'.png');assert.ok(await page.eval(()=>document.body.innerText.includes('Ubah TTS')));
  await goto('/tts/1/edit');await fill('#complaint','Pengering berhenti saat dipakai');await click(saveSelector);await wait(()=>location.pathname==='/tts/1','Edit redirects detail');await settle();assert.ok(await page.eval(()=>document.body.innerText.includes('Pengering berhenti saat dipakai')));
  await goto('/tts/2');assert.ok(!await page.eval(()=>document.body.innerText.includes('Ubah TTS')));await goto('/tts/2/edit');assert.ok(await page.eval(()=>document.body.innerText.includes('403')));observations.push({id:'peer-edit-guard-'+width,result:'PASS'});
  await goto('/profil');await click('.account-screen form[action$="/logout"] button');await wait(()=>location.pathname==='/login','Logout technician');await wait(()=>!!document.querySelector('#username'),'Login form ready');await fill('#username','admin');await fill('#password',secret.ADMIN_INITIAL_PASSWORD);await click('[data-test="login-button"]');await wait(()=>location.pathname==='/','Admin login');await goto('/tts/2/edit');await capture('admin-edit-'+width,'admin-edit-'+width+'.png');
  assert.equal(page.consoleErrors.length,0,'No runtime JavaScript errors');
  for(const screen of ['tts-baru','tts','tts-detail','tts-sukses']){await page.goto('file://'+repo+'/mockup/'+screen+'.html?peran=teknisi',{settleMs:200,timeoutMs:10000});await page.eval(()=>document.fonts.ready.then(()=>true));await page.screenshot({path:out+'reference-'+screen+'-'+width+'.png',format:'png'});}
  await page.close();
 }
 assert.ok(jsonPrivacy.length>0);assert.ok(jsonPrivacy.every(x=>x.privateValuesAbsent));
 writeFileSync(out+'browser-results.json',JSON.stringify({browser:await browser.send('Browser.getVersion'),observations,jsonPrivacy,source:'T009 A1 uncommitted task source; source-manifest pins final hash',result:'PASS'},null,2)+'\n');console.log(JSON.stringify({result:'PASS',observations:observations.length,jsonResponses:jsonPrivacy.length}));
}catch(e){writeFileSync(out+'browser-failure.json',JSON.stringify({message:e.message,observations,jsonPrivacy},null,2)+'\n');console.error(e.message);process.exitCode=1;}
finally{if(browser)await browser.close();}
