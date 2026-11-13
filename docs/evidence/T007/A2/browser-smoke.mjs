import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import assert from 'node:assert/strict';
const runtime = readFileSync('/private/tmp/t007-a2-runtime-location', 'utf8');
const credentials = JSON.parse(readFileSync(`${runtime}/credentials.json`, 'utf8'));
const out = new URL('./', import.meta.url).pathname;
const chromeTmp = `${runtime}/chrome-tmp`;
mkdirSync(chromeTmp, {mode:0o700,recursive:true});
process.env.TASTE_SKILL_TMP = chromeTmp;
const base = 'http://127.0.0.1:8187';
const observations = [];
let browser;
try {
  browser = await Browser.launch({width:360,height:800});
  const page = await browser.newPage({width:360,height:800,mobile:true});
  await page.send('Network.enable');
  const documents = [];
  browser.on(page.sessionId, 'Network.responseReceived', ({type,response}) => {
    if(type==='Document') documents.push({path:new URL(response.url).pathname,status:response.status});
  });
  const check = async (id, expectedPath, expectedStatus, text, screenshot) => {
    const state = await page.eval(() => ({path:location.pathname,text:document.body.innerText,width:innerWidth,documentWidth:document.documentElement.scrollWidth,font:getComputedStyle(document.body).fontFamily,fontsLoaded:document.fonts.status}));
    assert.equal(state.path,expectedPath,id);
    assert.equal(documents.at(-1)?.status,expectedStatus,id);
    if(text) assert.ok(state.text.includes(text),id);
    delete state.text;
    observations.push({id,...state,status:documents.at(-1).status,expectedPath,expectedStatus,result:'PASS'});
    if(screenshot) await page.screenshot({path:out+screenshot,format:'png'});
  };
  const goto = path => page.goto(base+path,{settleMs:120,timeoutMs:10000});
  const click = async selector => {
    const rect=await page.eval(selector=>{const r=document.querySelector(selector).getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2}},selector);
    await page.send('Input.dispatchMouseEvent',{type:'mousePressed',...rect,button:'left',clickCount:1});
    await page.send('Input.dispatchMouseEvent',{type:'mouseReleased',...rect,button:'left',clickCount:1});
  };
  const fill = async (selector,text) => {await click(selector);await page.send('Input.insertText',{text});};
  const login = async username => {
    await fill('#username',username);await fill('#password',credentials.password);await click('[data-test="login-button"]');
    for(let i=0;i<50;i++){if(await page.eval(()=>location.pathname!=='/login'))break;await sleep(100);}
  };
  await goto('/mulai');
  await check('guest-launcher-login','/login',200,'Masuk','guest-login-360.png');
  const manifest = await page.eval(async()=>{
    const link=document.querySelector('link[rel=manifest]');
    const response=await fetch(link.href);const data=await response.json();
    return {href:new URL(link.href).pathname,status:response.status,name:data.name,start_url:data.start_url,display:data.display};
  });
  assert.equal(manifest.start_url,'/mulai');assert.equal(manifest.status,200);
  const diagnostic=await page.send('Page.getAppManifest');
  assert.ok(diagnostic.url.endsWith('/manifest.webmanifest'));assert.equal(diagnostic.errors.length,0);assert.ok(diagnostic.data.length>0);
  observations.push({id:'guest-login-manifest',...manifest,chromeDiscovered:true,chromeParseErrors:diagnostic.errors.length,result:'PASS'});
  await login(credentials.admin);
  await check('admin-login-home','/',200,'Beranda');
  await goto('/mulai');await check('admin-launcher','/',200,'Beranda','admin-launcher-360.png');
  await goto('/pengaturan/toko');await check('admin-store','/pengaturan/toko',200,'Pengaturan toko','admin-settings-360.png');
  await goto('/pengaturan/kategori');await check('admin-categories','/pengaturan/kategori',200,'Kategori');
  await goto('/profil');await check('admin-profile','/profil',200,'Password');
  await click('[data-open="navigation-menu"]');
  await click('#navigation-menu form button');
  for(let i=0;i<50;i++){if(await page.eval(()=>location.pathname==='/login'))break;await sleep(100);}
  await check('admin-logout','/login',200,'Masuk');
  await login(credentials.technician);
  await check('technician-login-role-target','/tts/baru',404,'404');
  await goto('/mulai');await check('technician-launcher','/tts/baru',404,'404');
  await goto('/');await check('technician-root-forbidden','/',403,'Akses ditolak','technician-root-403-360.png');
  await goto('/dashboard');await check('technician-dashboard-forbidden','/dashboard',403,'Akses ditolak');
  await goto('/profil');await check('technician-profile','/profil',200,'Password','technician-profile-360.png');
  const errors=page.consoleErrors;
  assert.equal(errors.length,0,'No runtime JS exception');
  writeFileSync(out+'browser-results.json',JSON.stringify({candidate:'db5de005a345de644a7aa9056ea93f5a81117a3d',tool:'installed Chrome via existing design-taste-frontend CDP helper',browserVersion:await browser.send('Browser.getVersion'),observations,documents,runtimeExceptions:errors,pendingT009:'404 /tts/baru is expected at M1; role target verified, TTS form not claimed.'},null,2)+'\n');
  console.log(JSON.stringify({result:'PASS',observations:observations.length,screenshots:5}));
} catch(error) {
  writeFileSync(out+'browser-failure.json',JSON.stringify({message:error.message,observations},null,2)+'\n');
  console.error(error.message);process.exitCode=1;
} finally {
  if(browser)await browser.close();
}
