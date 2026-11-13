import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const out=new URL('./',import.meta.url).pathname;
const root=readFileSync('/private/tmp/t008-a2-location','utf8').trim();
const secret=JSON.parse(readFileSync(root+'/private.json','utf8'));
process.env.TASTE_SKILL_TMP=root+'/chrome';
const base='http://127.0.0.1:8188';const observations=[];const diagnostics=[];let browser;
try{
 browser=await Browser.launch();
 for(const width of [360,768,1280]){
  const page=await browser.newPage({width,height:900,mobile:width<1024});
  await page.send('Network.enable');await page.send('Network.clearBrowserCookies');
  await page.send('Network.setUserAgentOverride',{userAgent:'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Mobile Safari/537.36',platform:'Android'});
  const docs=[];browser.on(page.sessionId,'Network.responseReceived',({type,response})=>{if(type==='Document')docs.push({path:new URL(response.url).pathname,status:response.status});});
  const settle=async()=>{await page.eval(()=>document.fonts.ready.then(()=>true));for(let i=0;i<40;i++){if(await page.eval(()=>getComputedStyle(document.body).fontFamily.includes('IBM Plex Sans')&&document.fonts.status==='loaded'))return;await sleep(100);}assert.fail('IBM Plex font/style not settled');};
  const goto=async path=>{await page.goto(base+path,{settleMs:200,timeoutMs:10000});if(await page.eval(()=>document.body.hasAttribute('data-repair-shell')))await settle();};
  const check=async(id,path,status,text,screenshot)=>{
   const data=await page.eval(()=>({path:location.pathname,width:innerWidth,documentWidth:document.documentElement.scrollWidth,font:getComputedStyle(document.body).fontFamily,fontSize:getComputedStyle(document.body).fontSize,fontsLoaded:document.fonts.status,text:document.body.innerText}));
   assert.equal(data.path,path,id);assert.equal(docs.at(-1)?.status,status,id);assert.equal(data.width,width,id);assert.equal(data.documentWidth,width,id);if(text)assert.ok(data.text.includes(text),id);delete data.text;
   observations.push({id,...data,status,expectedPath:path,expectedStatus:status,verdict:'pass'});if(screenshot)await page.screenshot({path:out+screenshot,format:'png'});
  };
  const click=async selector=>{const point=await page.eval(sel=>{const el=document.querySelector(sel);el.scrollIntoView({block:'center'});const r=el.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}},selector);await page.send('Input.dispatchMouseEvent',{type:'mousePressed',...point,button:'left',clickCount:1});await page.send('Input.dispatchMouseEvent',{type:'mouseReleased',...point,button:'left',clickCount:1});};
  const login=async username=>{for(const [s,text]of [['#username',username],['#password',secret.QA_PASSWORD]]){await click(s);await page.send('Input.insertText',{text});}await click('[data-test="login-button"]');for(let i=0;i<60;i++){if(await page.eval(()=>location.pathname!=='/login'))break;await sleep(100);}if(await page.eval(()=>location.pathname==='/'))await settle();};
  const manifest=async role=>{
   const data=await page.eval(async()=>{const link=document.querySelector('link[rel=manifest]');if(!link)return null;const r=await fetch(link.href);const m=await r.json();const icons=await Promise.all(m.icons.map(async i=>{const image=new Image();image.src=i.src;await image.decode();return{path:i.src,width:image.naturalWidth,height:image.naturalHeight};}));return{href:new URL(link.href).pathname,status:r.status,name:m.name,start:m.start_url,display:m.display,theme:m.theme_color,icons};});
   assert.ok(data,role);assert.equal(data.start,'/mulai');assert.equal(data.status,200);assert.equal(data.display,'standalone');assert.deepEqual(data.icons.map(i=>i.width),[192,512]);const d=await page.send('Page.getAppManifest');assert.ok(d.url.endsWith('/manifest.webmanifest'));assert.equal(d.errors.length,0);const install=await page.send('Page.getInstallabilityErrors');assert.ok(!install.installabilityErrors.some(e=>e.errorId==='no-manifest'));diagnostics.push({role,width,...data,chromeManifestDiscovered:true,parseErrors:d.errors,installabilityErrors:install.installabilityErrors,verdict:'pass'});
  };
  await goto('/mulai');await check('GUEST-LAUNCHER-'+width,'/login',200,'Masuk','guest-login-'+width+'.png');await manifest('guest');
  await login('t008_admin');await check('ADMIN-LOGIN-'+width,'/',200,'Beranda');await goto('/mulai');await check('ADMIN-LAUNCHER-'+width,'/',200,'Beranda','admin-home-'+width+'.png');await manifest('admin');
  await goto('/pengaturan/toko');await check('ADMIN-STORE-'+width,'/pengaturan/toko',200,'Pengaturan toko');
  await page.send('Network.clearBrowserCookies');await goto('/login');await login('t008_teknisi');await check('TECH-LOGIN-'+width,'/tts/baru',404,'404');
  await goto('/profil');await check('TECH-PROFILE-'+width,'/profil',200,'Password','technician-profile-'+width+'.png');await manifest('technician');
  await goto('/mulai');await check('TECH-LAUNCHER-'+width,'/tts/baru',404,'404',width===360?'technician-launcher-360.png':null);
  await goto('/');await check('TECH-ROOT-GUARD-'+width,'/',403,'Akses ditolak',width===360?'technician-root-403-360.png':null);
  await goto('/dashboard');await check('TECH-DASHBOARD-GUARD-'+width,'/dashboard',403,'Akses ditolak');
  assert.equal(page.consoleErrors.length,0,'no runtime JS exceptions');
 }
 writeFileSync(out+'browser-results.json',JSON.stringify({checked_revision:'db5de005a345de644a7aa9056ea93f5a81117a3d',version:await browser.send('Browser.getVersion'),observations,diagnostics,ua:'Android13 Pixel7 Chrome157 emulation; real installed desktop Chrome',screenshots:11,verdict:'pass',pendingT009:'/tts/baru404 expected; correct launcher destination proven, form not claimed'},null,2)+'\n');
 console.log(JSON.stringify({verdict:'pass',observations:observations.length,manifestDiagnostics:diagnostics.length}));
}catch(e){writeFileSync(out+'browser-environment-failure.json',JSON.stringify({error:e.message,observations},null,2)+'\n');console.error(e.message);process.exitCode=1;}
finally{if(browser)await browser.close();}
