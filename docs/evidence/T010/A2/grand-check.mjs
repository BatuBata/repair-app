import {Browser,sleep} from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
import {readFileSync,writeFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import assert from 'node:assert/strict';
const root=readFileSync('/private/tmp/t010-a2-location','utf8').trim(),env=JSON.parse(readFileSync(root+'/private.json')),ids=JSON.parse(readFileSync(root+'/ids.json')),out=process.cwd()+'/docs/evidence/T010/A2/';
process.env.TASTE_SKILL_TMP=root+'/chrome';
const cases=[],inputs=[],errors=[];let b,p;
const expected={normal:'Rp1.000.000',large:'Rp9.223.372.034.707.292.160',negative:'Rp0'};
try {
 b=await Browser.launch({width:360,height:900});p=await b.newPage({width:360,height:900,mobile:true});
 writeFileSync(out+'grand-preflight.json',JSON.stringify({runtime:root,profile:b.dir,pid:b.proc.pid,port:8189,isolated:true,headless:true,CDP:'owned port0',storage:'private SQLite/config/cache/session/views/storage'},null,2));
 const wait=async(fn,label,arg)=>{for(let i=0;i<100;i++){try{if(await p.eval(fn,arg))return;}catch{}await sleep(100);}throw Error('Timeout '+label);};
 const settle=async()=>{await wait(()=>document.fonts.status==='loaded'&&getComputedStyle(document.body).fontFamily.includes('IBM Plex Sans'),'CSS/fonts');await sleep(150);};
 const goto=async path=>{await p.goto('http://127.0.0.1:8189'+path,{settleMs:150});await settle();};
 const click=async s=>{let r=await p.eval(s=>{let e=[...document.querySelectorAll(s)].find(e=>e.getBoundingClientRect().width>0&&e.getBoundingClientRect().height>0);if(!e)throw Error('No visible '+s);e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2};},s);await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...r,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...r,button:'left',clickCount:1});};
 const fill=async(s,v)=>{await click(s);await p.eval(s=>document.querySelector(s).select(),s);await p.send('Input.insertText',{text:String(v)});await sleep(350);};
 const money=async(label,value)=>{let s=`[aria-label="${label} baris 1"]`;await fill(s,0);assert.equal(await p.eval(s=>document.querySelector(s).value.replace(/\D/g,''),s),'0');await fill(s,value);assert.equal(await p.eval(s=>document.querySelector(s).value.replace(/\D/g,''),s),String(value));inputs.push({label,baseline:'0',target:String(value),method:'CDP Input.insertText after visible click/select'});};
 const values=async kind=>{await fill('[aria-label="Qty baris 1"]',1);await money('Harga jual',0);await money('Modal',0);await fill('[aria-label="Deskripsi baris 1"]',kind==='normal'?'LCD 14 inci HD':'Deskripsi perangkat elektronik sintetis '.repeat(6));if(kind!=='normal')await fill('[aria-label="Qty baris 1"]','2147483648');await money('Harga jual',kind==='normal'?1000000:kind==='large'?4294967295:0);await money('Modal',kind==='normal'?700000:4294967295);await wait(value=>document.querySelector('.totals .grand dd')?.innerText===value,'total '+kind,expected[kind]);await p.eval(()=>document.activeElement?.blur());};
 const capture=async(name,width,form=false)=>{await p.setViewport({width,height:900,mobile:width<1024});await settle();await p.eval(()=>scrollTo(0,0));await p.screenshot({path:out+name+'-'+width+'.png',format:'png'});await p.eval(form=>document.querySelector(form?'.summary':'.invoice-published').scrollIntoView({block:'center'}),form);await sleep(150);
 const m=await p.eval(()=>{const rects=s=>[...document.querySelectorAll(s)].filter(e=>e.getBoundingClientRect().width>0).map(e=>{const r=e.getBoundingClientRect(),c=getComputedStyle(e);return{class:e.className,text:e.innerText,x:r.x,right:r.right,y:r.y,bottom:r.bottom,width:r.width,height:r.height,scrollWidth:e.scrollWidth,clientWidth:e.clientWidth,whiteSpace:c.whiteSpace,color:c.color,fontSize:c.fontSize};});return{innerWidth,visualViewportWidth:visualViewport.width,documentWidth:document.documentElement.scrollWidth,font:getComputedStyle(document.body).fontFamily,totals:rects('.totals dd'),buttons:rects('.actionbar button,.summary button'),actionbar:rects('.actionbar'),table:rects('.invoice-published .table'),cells:rects('.invoice-published td'),nav:rects('.bottomnav')};});cases.push({id:name+'-'+width,requestedWidth:width,...m});await p.screenshot({path:out+name+'-lower-'+width+'.png',format:'png'});
 const grand=m.totals[0];assert.equal(grand.height,34,name+' grand single line');if(name.includes('large')){assert.equal(grand.whiteSpace,'nowrap');assert.ok(grand.scrollWidth<=grand.clientWidth+1,name+' grand fits');}else assert.equal(grand.fontSize,'28px',name+' normal font preserved');
 assert.equal(m.innerWidth,width,name+' inner');assert.equal(Math.round(m.visualViewportWidth),width,name+' visual');assert.ok(m.documentWidth<=width,name+' document '+m.documentWidth);
 for(const r of [...m.totals,...m.buttons,...m.cells,...m.table])assert.ok(r.x>=-0.5&&r.right<=width+0.5,name+' out of bounds '+r.class+' '+r.right);
 if(form&&width<1024){const btn=m.buttons.find(r=>r.text.includes('Terbitkan')||r.text.includes('Simpan koreksi'));assert.ok(btn&&btn.height>=44&&btn.width>=44,name+' target');assert.ok(btn.y>=0&&btn.bottom<=m.nav[0].y+0.5,name+' nav overlap');}
 if(name.includes('negative')){const profit=m.totals.at(-1);assert.equal(profit.text,'Rp-9.223.372.034.707.292.160');assert.equal(profit.color,'rgb(196, 36, 42)');}
 if(width>=1024)for(const r of m.cells.filter(r=>r.class.includes('n')||r.class==='c-meta'))assert.equal(r.whiteSpace,'nowrap');
 console.log(name,width,'PASS');};
 const capture3=async(name,form=false)=>{for(const width of[360,768,1280])await capture(name,width,form);};
 await goto('/login');await fill('#username','t010_a2_admin');await fill('#password',env.QA_PASSWORD);await click('[data-test=login-button]');await wait(()=>location.pathname==='/','login');
 await goto('/tts/'+ids['grand-working']+'/rincian');

 await values('normal');await capture3('final-issue-normal',true);
 await values('large');await capture3('final-issue-large',true);
 await goto('/tts/'+ids.working+'/rincian');await capture3('final-correction-large',true);
 await goto('/tts/'+ids.working);await capture3('final-published-large');
 await goto('/tts/'+ids['published-normal']);await capture3('final-published-normal');
 writeFileSync(out+'grand-results.json',JSON.stringify({browser:await b.send('Browser.getVersion'),cases,inputs,exceptions:p.consoleErrors,scope:'Only adaptive long-grand and normal font subset after final CSS; earlier table/sticky/negative proof retained'},null,2));assert.equal(p.consoleErrors.length,0);
} catch(e){if(p){try{writeFileSync(out+'error-state.json',JSON.stringify(await p.eval(()=>({url:location.pathname,inputs:[...document.querySelectorAll('.invoice-form input')].map(e=>({label:e.getAttribute('aria-label'),value:e.value})),totals:document.querySelector('.totals')?.innerText,rows:Livewire.find(document.querySelector('.invoice-form').getAttribute('wire:id')).rows})),null,2));await p.screenshot({path:out+'runner-error.png',format:'png'});}catch{}}writeFileSync(out+'grand-error.json',JSON.stringify({error:e.message,cases,inputs,exceptions:p?.consoleErrors},null,2));console.error(e);process.exitCode=1;} finally{if(b)await b.close();}
