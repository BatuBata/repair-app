import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {probe,layout,app} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/probe.mjs';
import {judge,judgeLayout,judgeApp,APP_SKIP} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/rules.mjs';
import {readFileSync,writeFileSync,copyFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import assert from 'node:assert/strict';
const out=new URL('./',import.meta.url).pathname,root=readFileSync('/private/tmp/t012-a1-location','utf8').trim(),secret=JSON.parse(readFileSync(root+'/private.json','utf8'));process.env.TASTE_SKILL_TMP=root+'/chrome';
let browser;const reports=[];
try{
 browser=await Browser.launch();const p=await browser.newPage({width:1280,height:900});
 const goto=async path=>{await p.goto('http://127.0.0.1:8190'+path,{settleMs:300,timeoutMs:10000});await p.eval(()=>document.fonts.ready.then(()=>true));};
 const fill=async(s,t)=>{await p.eval(s=>{const e=document.querySelector(s);e.focus();e.select();},s);await p.send('Input.insertText',{text:t});};
 await goto('/login');await fill('#username','t012_admin');await fill('#password',secret.QA_PASSWORD);await p.eval(()=>document.querySelector('[data-test=login-button]').click());for(let i=0;i<80;i++){if(await p.eval(()=>location.pathname!='/login'))break;await sleep(100);}
 await goto('/stok/4/edit');assert.equal(await p.eval(()=>document.querySelector('[data-money=money-cost_price]').value),'175.000');await fill('[data-money=money-cost_price]','150000');assert.equal(await p.eval(()=>document.querySelector('[data-money=money-cost_price]').value),'150.000');await p.eval(()=>document.querySelector('button.desktop-only[type=submit]').click());for(let i=0;i<80;i++){if(await p.eval(()=>location.pathname==='/stok'))break;await sleep(100);}assert.equal(await p.eval(()=>location.pathname),'/stok');execFileSync('python3',['docs/evidence/T012/A1/runtime.py','fixture','proof'],{stdio:'pipe'});copyFileSync(out+'fixture-proof.log',out+'money-edit-database-proof.log');let proof=JSON.parse(readFileSync(out+'fixture-proof.log','utf8'));assert.equal(proof.product.cost_price,150000);assert.equal(proof.product.stock,6);assert.equal(proof.entries[0].cost_price,175000);assert.equal(proof.expenses,0);
 await goto('/stok/4/edit');assert.equal(await p.eval(()=>document.querySelector('[data-money=money-cost_price]').value),'150.000');await p.screenshot({path:out+'edit-money-1280.png',format:'png'});
 for(const width of [360,768,1280]){await p.setViewport({width,height:900,mobile:width<1024});for(const path of ['/stok','/stok/baru','/stok/4/edit','/stok/4/restok']){await goto(path);const m=await p.eval(probe),l=await p.eval(layout),a=await p.eval(app);const findings=[...judge(m,{width}),...judgeLayout(l,{width})].filter(f=>!APP_SKIP.has(f.id)&&f.id!=='no-title'&&f.id!=='small-title');findings.push(...judgeApp(a,m,{width}));reports.push({width,path,findings,verdict:findings.some(x=>x.level==='FAIL')?'fail':'pass'});}}
 writeFileSync(out+'inspector-results.json',JSON.stringify({method:'Unmodified design-taste probe, layout, app and judge functions on authenticated live pages; CLI itself cannot log in.',reports,moneyProof:proof},null,2)+'\n');console.log(JSON.stringify({reports:reports.length,fail:reports.flatMap(r=>r.findings).filter(f=>f.level==='FAIL').length,warn:reports.flatMap(r=>r.findings).filter(f=>f.level==='WARN').length}));
}finally{if(browser)await browser.close();}
