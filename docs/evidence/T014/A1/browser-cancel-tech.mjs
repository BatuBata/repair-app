import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';
import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
const base='http://127.0.0.1:8190';const out=new URL('./screens/',import.meta.url).pathname;
const b=await Browser.launch({width:1280,height:900});const p=await b.newPage({width:1280,height:900});
const wait=async(fn,ms=10000)=>{const end=Date.now()+ms;while(Date.now()<end){if(await p.eval(fn))return;await sleep(100)}throw new Error(`Timeout: ${JSON.stringify(await p.eval(()=>({path:location.pathname,dialog:document.querySelector('.expenses-page dialog[open]')?.innerText,expense:document.querySelector('.expense-list tbody')?.innerText})))}`)};
const waitActive=async(id,ms=10000)=>{const end=Date.now()+ms;while(Date.now()<end){if(await p.eval(id=>document.activeElement?.getAttribute('aria-controls')===id,id))return;await sleep(100)}throw new Error('Timeout waiting for focus to return to the row action trigger.')};
const click=async(sel,text='')=>{const q=await p.eval(({sel,text})=>{const e=[...document.querySelectorAll(sel)].find(n=>(!text||n.textContent.includes(text))&&n.getClientRects().length);if(!e)return null;e.scrollIntoView({block:'center',inline:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}},{sel,text});assert.ok(q,`No visible ${sel} ${text}`);await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...q,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...q,button:'left',clickCount:1})};
const type=async(sel,value)=>{await click(sel);await p.eval(s=>document.querySelector(s).select(),sel);await p.send('Input.insertText',{text:value})};
const results={cancelFocus:null,confirmedDelete:false,technicianStatus:null,references:[],consoleErrors:[]};
try{
 await p.goto(`${base}/login`);await type('#username','qa-expenses');await type('#password',process.env.T014_LOGIN_PASSWORD);await click('[data-test="login-button"]');await wait(()=>location.pathname==='/');await p.goto(`${base}/pengeluaran`);await wait(()=>document.querySelector('.expense-list'));
 const id=await p.eval(()=>[...document.querySelectorAll('.expense-list tr')].find(tr=>tr.textContent.includes('T014 visual QA'))?.querySelector('.act-btn')?.getAttribute('aria-controls'));assert.ok(id);
 await click(`.expenses-page [aria-controls="${id}"]`);await click('[role="menuitem"]','Hapus');await wait(()=>document.querySelector('.expenses-page dialog[open]'));
 await click('.expenses-page dialog button','Batal');await wait(()=>!document.querySelector('.expenses-page dialog[open]'));await waitActive(id);
 results.cancelFocus=await p.eval(id=>document.activeElement?.getAttribute('aria-controls'),id);assert.equal(results.cancelFocus,id,'Cancel should return focus to the row action trigger after Livewire updates.');
 // Escape uses the same return-focus path.
 await click(`.expenses-page [aria-controls="${id}"]`);await click('[role="menuitem"]','Hapus');await wait(()=>document.querySelector('.expenses-page dialog[open]'));
 await p.send('Input.dispatchKeyEvent',{type:'rawKeyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27,nativeVirtualKeyCode:27});await p.send('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27,nativeVirtualKeyCode:27});
 await wait(()=>!document.querySelector('.expenses-page dialog[open]'));await waitActive(id);
 assert.equal(await p.eval(id=>document.activeElement?.getAttribute('aria-controls'),id),id,'Escape should return focus to the row action trigger.');
 // Reopen after both cancellation paths and confirm deletion.
 await click(`.expenses-page [aria-controls="${id}"]`);await click('[role="menuitem"]','Hapus');await wait(()=>document.querySelector('.expenses-page dialog[open]'));
 await click('.expenses-page dialog button','Ya, hapus');await wait(()=>![...document.querySelectorAll('.expense-list tbody tr')].some(tr=>tr.textContent.includes('T014 visual QA')));
 results.confirmedDelete=execFileSync('/usr/bin/sqlite3',[process.env.T014_DB,"SELECT count(*) FROM expenses WHERE description='T014 visual QA'"]).toString().trim()==='0';assert.equal(results.confirmedDelete,true);
 await click('[data-test="logout-button"]','Keluar');await wait(()=>location.pathname==='/login');await type('#username','qa-tech');await type('#password',process.env.T014_LOGIN_PASSWORD);await click('[data-test="login-button"]');await wait(()=>location.pathname==='/tts/baru');results.technicianStatus=await p.eval(async()=>(await fetch('/pengeluaran')).status);assert.equal(results.technicianStatus,403);
 for(const[file,name]of[['tts.html','reference-tts'],['tts-baru.html','reference-tts-form']]){await p.goto(`file:///Users/muzib/.codex/worktrees/v1-stock/repair-app/mockup/${file}`);for(const width of[360,1280]){await p.setViewport({width,height:900,dsf:1,mobile:width<640});await sleep(250);assert.ok(await p.eval(()=>document.documentElement.scrollWidth<=innerWidth));const fileName=`${name}-${width}.png`;await p.screenshot({path:`${out}/${fileName}`,format:'png',fullPage:true});results.references.push(fileName)}}
 results.consoleErrors=p.consoleErrors;assert.equal(results.consoleErrors.length,0,JSON.stringify(results.consoleErrors));writeFileSync(new URL('./browser-delete-results.json',import.meta.url),JSON.stringify(results,null,2));console.log(JSON.stringify(results));
}finally{await b.close()}
