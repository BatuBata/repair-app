import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const root=readFileSync('/private/tmp/t009-a1-location','utf8').trim();const secret=JSON.parse(readFileSync(root+'/private.json','utf8'));process.env.TASTE_SKILL_TMP=root+'/chrome';const out=new URL('./',import.meta.url).pathname;const results=[];let browser;
try{
 browser=await Browser.launch();
 for(const width of [360,768,1280]){
  const p=await browser.newPage({width,height:900,mobile:width<1024});await p.send('Network.enable');await p.send('Network.clearBrowserCookies');
  const wait=async f=>{for(let i=0;i<80;i++){if(await p.eval(f))return;await sleep(100);}throw Error('State did not settle');};
  const click=async s=>{const pos=await p.eval(s=>{const e=document.querySelector(s);e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}},s);await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...pos,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...pos,button:'left',clickCount:1});};
  await p.goto('http://127.0.0.1:8189/login',{settleMs:200,timeoutMs:10000});
  for(const [s,text]of [['#username','t009_teknisi'],['#password',secret.ADMIN_INITIAL_PASSWORD]]){await click(s);await p.send('Input.insertText',{text});}
  await click('[data-test="login-button"]');await wait(()=>location.pathname==='/tts/baru');
  await p.goto('http://127.0.0.1:8189/tts/7/sukses',{settleMs:200,timeoutMs:10000});await wait(()=>document.body&&getComputedStyle(document.body).fontFamily.includes('IBM Plex Sans')&&document.fonts.status==='loaded');
  const data=await p.eval(()=>({width:innerWidth,documentWidth:document.documentElement.scrollWidth,activeLinks:[...document.querySelectorAll('a[aria-current=page]')].map(e=>e.getAttribute('href')),dataOrder:[...document.querySelectorAll('dl.kv dt')].map(e=>e.innerText),privateValuesAbsent:!['818181','919191','PART PRIVAT UJI'].some(v=>document.documentElement.outerHTML.includes(v))}));assert.equal(data.documentWidth,width);assert.ok(data.activeLinks.every(h=>h==='/tts/baru'));assert.equal(data.dataOrder[0],'Customer');assert.ok(data.privateValuesAbsent);results.push(data);await p.screenshot({path:out+'success-'+width+'.png',format:'png'});
  if(width===360){await click('.success-actions a[href$="/tts/baru"]');await wait(()=>!!document.querySelector('#customer_name'));assert.equal(await p.eval(()=>document.querySelector('#customer_name').value),'');results.push({case:'create-another-360',result:'PASS'});}
  assert.equal(p.consoleErrors.length,0);await p.close();
 }
 writeFileSync(out+'success-final.json',JSON.stringify({result:'PASS',results},null,2)+'\n');console.log(JSON.stringify({result:'PASS',cases:results.length}));
}catch(e){writeFileSync(out+'success-final-failure.json',JSON.stringify({message:e.message,results},null,2)+'\n');console.error(e.message);process.exitCode=1;}
finally{if(browser)await browser.close();}
