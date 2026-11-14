import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const out = '/private/tmp/repair-app-t025-web';
const origin = 'https://demo-repair-app.scannara.asia';
const source = '/Users/muzib/Projects/web/repair-app';
const result = { checked_revision: 'd047f25ea31d703660212e4205b937053c92adcb', target: origin, started_at: new Date().toISOString(), scope: 'Independent READ-ONLY live web smoke; login/logout permitted; no business data writes', cases: [], screens: [], assets: [], documents: [], screenshots: [], javascript_exception_count: 0, console_error_count: 0, failed_asset_count: 0, logout: false, limits: ['Production transaction UAT NOT_RUN','Actual Android device NOT_RUN','Physical printer NOT_RUN','Owner/technician handover NOT_RUN','Full T025/R001 acceptance NOT_GRANTED','Backup deferred by user D-22'] };
let stage = 'preflight', browser, page, credential, loggedIn = false;
const save = () => writeFileSync(out+'/result.json', JSON.stringify(result, null, 2)+'\n');
const requireCheck = (v, id) => { if (!v) { const e = new Error(id); e.safeId=id; throw e; } };
const pass = (id, details = {}) => { result.cases.push({id, status:'PASS', ...details}); save(); };
const safePath = url => { try { const u=new URL(url); return u.origin === origin ? u.pathname : '[other-origin]'; } catch { return '[invalid-url]'; } };
const wait = async (fn, label) => { for(let i=0;i<100;i++){if(await page.eval(fn).catch(()=>false))return;await sleep(80);}requireCheck(false,label); };
const goto = async path => {
    requireCheck(path.startsWith('/')&&!path.startsWith('//'),'allowed-path');
    await page.goto(origin+path,{settleMs:180});
    requireCheck(await page.eval(o=>location.origin===o,origin),'exact-origin-navigation');
    await page.eval(()=>document.fonts.ready.then(()=>true));
    await sleep(100);
};
const click = async selector => {
    const point=await page.eval(selector=>{
        const el=[...document.querySelectorAll(selector)].find(e=>e.getBoundingClientRect().width&&e.getBoundingClientRect().height);
        if(!el)return null;
        el.scrollIntoView({block:'center'});const r=el.getBoundingClientRect();
        return {x:r.x+r.width/2,y:r.y+r.height/2};
    },selector);
    requireCheck(point,'visible-click-target');
    await page.send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...point});
    await page.send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...point});
};
const shot = async name => {
    requireCheck(await page.eval(()=>![...document.querySelectorAll('input[type=password]')].some(e=>e.value)), 'screenshot-secret-guard');
    await page.eval(()=>scrollTo(0,0));
    await page.screenshot({path:out+'/'+name+'.png',format:'png',fullPage:false});
    result.screenshots.push(name+'.png');save();
};
const screen = async (path,heading,form=false) => {
    await goto(path);
    const row=await page.eval(()=>({path:location.pathname,heading:document.querySelector('h1')?.innerText.trim()??'',width:innerWidth,scrollWidth:document.documentElement.scrollWidth,forms:document.querySelectorAll('form').length,inputs:document.querySelectorAll('input,select,textarea').length,serverError:/Internal Server Error|Whoops|SQLSTATE\[/.test(document.body.innerText),emptyBusiness:/Belum ada TTS|Belum ada barang jualan|Belum ada penjualan|Belum ada pengeluaran/.test(document.body.innerText)}));
    requireCheck(row.path===path && row.heading===heading && !row.serverError,'screen-'+path);
    if(form)requireCheck(row.inputs>0,'form-render-'+path);
    result.screens.push(row);pass('screen-'+path,{heading,form_render_only:form});
};
const logout = async () => {
    await page.setViewport({width:1280,height:900});
    await goto('/profil');
    requireCheck(await page.eval(o=>{const f=[...document.forms].find(f=>new URL(f.action).pathname==='/logout');return !!f&&new URL(f.action).origin===o&&f.method.toLowerCase()==='post';},origin),'logout-form-origin');
    await click('form[action$="/logout"] button');
    await wait(()=>location.pathname==='/login','logout-complete');
    loggedIn=false;result.logout=true;pass('own-session-logout');
};

try {
    requireCheck(process.env.NODE_TLS_REJECT_UNAUTHORIZED!=='0','tls-verification-enabled');
    stage='http-https';
    const redirect=await fetch('http://demo-repair-app.scannara.asia/login',{redirect:'manual'});
    requireCheck(redirect.status===301&&redirect.headers.get('location')===origin+'/login','http-redirect');
    await redirect.body?.cancel();pass('http-https-redirect',{status:301});
    for(const path of ['/up','/login']){const r=await fetch(origin+path);requireCheck(r.status===200,'public-'+path);await r.body?.cancel();pass('public-'+path,{status:r.status});}
    stage='unauthorized-and-private-files';
    for(const path of ['/','/tts','/stok','/penjualan','/pengeluaran','/laporan','/pengaturan/pengguna','/pengaturan/kategori','/pengaturan/toko','/profil']){
        const r=await fetch(origin+path,{redirect:'manual'});requireCheck(r.status===302&&r.headers.get('location')===origin+'/login','guest-'+path);await r.body?.cancel();pass('guest-redirect-'+path,{status:302});
    }
    for(const path of ['/.env','/.git/config','/storage/logs/laravel.log','/vendor/autoload.php','/config/app.php','/storage/logos/a.php','/layout-preview']){
        const r=await fetch(origin+path,{redirect:'manual'});const status=r.status;await r.body?.cancel();requireCheck([403,404].includes(status),'private-denial-'+path);pass('private-denial-'+path,{status});
    }
    stage='frozen-assets';
    const manifest=['build','font'].flatMap(name=>JSON.parse(readFileSync(source+'/docs/evidence/T022/A2/'+name+'-manifest.json','utf8')));
    requireCheck(manifest.length===23,'asset-count');
    result.assets=await Promise.all(manifest.map(async row=>{
        const path='/'+row.path.replace(/^public\//,'');const r=await fetch(origin+path);const bytes=Buffer.from(await r.arrayBuffer());const sha256=createHash('sha256').update(bytes).digest('hex');
        requireCheck(r.status===200&&sha256===row.sha256,'asset-sha-'+path);return {path,status:r.status,bytes:bytes.length,sha256};
    }));pass('23-frozen-assets-sha256',{count:23});
    stage='fresh-browser';
    process.env.TASTE_SKILL_TMP=out+'/profiles';mkdirSync(process.env.TASTE_SKILL_TMP,{recursive:true});
    browser=await Browser.launch({width:1280,height:900});page=await browser.newPage({width:1280,height:900});
    result.browser=await browser.send('Browser.getVersion');result.owned_profile=browser.dir;
    await page.send('Network.enable');
    browser.on(page.sessionId,'Runtime.consoleAPICalled',e=>{if(e.type==='error')result.console_error_count++;});
    browser.on(page.sessionId,'Network.responseReceived',e=>{
        const path=safePath(e.response.url);
        if(e.type==='Document')result.documents.push({path,status:e.response.status,securityState:e.response.securityState,protocol:e.response.securityDetails?.protocol,issuer:e.response.securityDetails?.issuer,subjectName:e.response.securityDetails?.subjectName,validFrom:e.response.securityDetails?.validFrom,validTo:e.response.securityDetails?.validTo});
        if(['Script','Stylesheet','Font','Image'].includes(e.type)&&e.response.status>=400)result.failed_asset_count++;
    });
    await goto('/login');
    requireCheck(await page.eval(o=>{const p=document.querySelector('#password'),f=p?.form;return location.origin===o&&p?.type==='password'&&p.name==='password'&&f?.method.toLowerCase()==='post'&&f.action===o+'/login';},origin),'verified-password-input-origin');
    stage='private-credential-memory-login';
    try {
        const buffer=execFileSync('/usr/bin/ssh',['-o','BatchMode=yes','-o','ConnectTimeout=10','muzib@202.10.40.165','cat /home/muzib/deploy-demo-repair-app/initial-admin-password.txt'],{encoding:null,stdio:['ignore','pipe','pipe'],timeout:15000});
        credential=buffer.toString('utf8').trim();buffer.fill(0);
    } catch { requireCheck(false,'private-credential-read'); }
    requireCheck(credential.length>=12,'private-credential-present');
    await click('#username');await page.send('Input.insertText',{text:'admin'});
    await click('#password');await page.send('Input.insertText',{text:credential});credential=undefined;
    requireCheck(await page.eval(()=>document.querySelector('#password')?.type==='password'),'password-remains-masked');
    await click('[data-test="login-button"]');
    await wait(()=>location.pathname==='/'&&document.querySelector('h1')?.innerText.trim()==='Beranda','admin-login');
    loggedIn=true;pass('valid-admin-login-session');
    stage='desktop-read-only-screens';
    await shot('dashboard-1280');
    await click('a[href$="/tts"]');await wait(()=>location.pathname==='/tts','navigation-link-tts');pass('actual-navigation-link-tts');
    for(const [path,title,form] of [['/tts','Servis'],['/stok','Stok'],['/penjualan','Penjualan'],['/pengeluaran','Pengeluaran'],['/laporan','Laporan'],['/pengaturan/pengguna','Pengguna'],['/pengaturan/kategori','Kategori'],['/pengaturan/toko','Pengaturan toko',true],['/profil','Profil',true]])await screen(path,title,form);
    await goto('/pengaturan/toko');await shot('store-settings-1280');
    for(const [path,title] of [['/tts/baru','Buat TTS'],['/stok/baru','Tambah barang'],['/penjualan/baru','Buat penjualan']])await screen(path,title,true);
    stage='narrow-basic-layout';
    await page.setViewport({width:360,height:800});
    for(const [path,title,name] of [['/','Beranda','dashboard-360'],['/tts/baru','Buat TTS','tts-form-360'],['/laporan','Laporan','reports-360']]){
        await screen(path,title,path==='/tts/baru');const row=result.screens.at(-1);requireCheck(row.scrollWidth<=361,'narrow-overflow-'+path);await shot(name);pass('narrow-layout-'+path,{width:360,scrollWidth:row.scrollWidth});
    }
    stage='logout';await logout();
    await goto('/laporan');requireCheck(await page.eval(()=>location.pathname==='/login'),'post-logout-private-denied');pass('post-logout-private-route-denied');
    result.javascript_exception_count=page.consoleErrors.length;
    requireCheck(result.javascript_exception_count===0&&result.console_error_count===0&&result.failed_asset_count===0,'browser-errors-or-missing-assets');
    requireCheck(result.documents.every(d=>d.status===200&&d.securityState==='secure'),'browser-document-status-tls');
    pass('browser-tls-js-assets',{documents:result.documents.length,js_exceptions:0,console_errors:0,missing_assets:0});
    result.verdict='PARTIAL_WEB_PASS';result.overall_T025='NOT_ACCEPTED';
} catch (e) {
    result.verdict='FAIL';result.failure={stage,check:e.safeId??'automation-error',error_class:e.constructor?.name??'Error'};
    process.exitCode=1;
} finally {
    credential=undefined;
    if(loggedIn&&page){try{await logout();}catch{result.cleanup_logout='FAILED';}}
    if(browser){try{await browser.close();result.owned_browser_closed=true;}catch{result.owned_browser_closed=false;}}
    result.finished_at=new Date().toISOString();save();
    console.log(JSON.stringify({verdict:result.verdict,cases:result.cases.length,assets:result.assets.length,screens:result.screens.length,logout:result.logout,failure:result.failure,overall_T025:result.overall_T025}));
}
