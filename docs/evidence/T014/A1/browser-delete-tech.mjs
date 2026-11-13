import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';
import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
const base='http://127.0.0.1:8190';const out=new URL('./screens/',import.meta.url).pathname;
const b=await Browser.launch({width:1280,height:900});const p=await b.newPage({width:1280,height:900});
const wait=async(fn,ms=10000)=>{const end=Date.now()+ms;while(Date.now()<end){if(await p.eval(fn))return;await sleep(100)}throw new Error(`Timeout: ${JSON.stringify(await p.eval(()=>({path:location.pathname,dialog:document.querySelector('.expenses-page dialog[open]')?.innerText,total:document.querySelector('.expense-total')?.textContent})))}`)};
const click=async(sel,text='')=>{const q=await p.eval(({sel,text})=>{const e=[...document.querySelectorAll(sel)].find(n=>(!text||n.textContent.includes(text))&&n.getClientRects().length);if(!e)return null;e.scrollIntoView({block:'center',inline:'center'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}},{sel,text});assert.ok(q,`Missing visible target ${sel} ${text}`);await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...q,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...q,button:'left',clickCount:1})};
const text=async(sel,value)=>{await click(sel);await p.eval(s=>document.querySelector(s).select(),sel);await p.send('Input.insertText',{text:value})};
const date=async(sel,value)=>{await click(sel);await p.eval(({sel,value})=>document.querySelector(sel).value=value,{sel,value});await p.send('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowUp',code:'ArrowUp'});await p.send('Input.dispatchKeyEvent',{type:'keyUp',key:'ArrowUp',code:'ArrowUp'});await p.send('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowDown',code:'ArrowDown'});await p.send('Input.dispatchKeyEvent',{type:'keyUp',key:'ArrowDown',code:'ArrowDown'});assert.equal(await p.eval(s=>document.querySelector(s).value,sel),value)};
const resize=async(width)=>{await p.setViewport({width,height:900,dsf:1,mobile:width<640});await sleep(250);const m=await p.eval(()=>({width:innerWidth,pageWidth:document.documentElement.scrollWidth}));assert.ok(m.pageWidth<=width,`Overflow ${width}: ${m.pageWidth}`);return m};
const shot=(name)=>p.screenshot({path:`${out}/${name}.png`,format:'png',fullPage:true});
const results={editAmount:175000,cancelFocus:null,confirmedDelete:false,technicianStatus:null,screenshots:[],pageWidths:{},consoleErrors:[]};
try{
 await p.goto(`${base}/login`);await text('#username','qa-expenses');await text('#password',process.env.T014_LOGIN_PASSWORD);await click('[data-test="login-button"]');await wait(()=>location.pathname==='/');await p.goto(`${base}/pengeluaran`);await wait(()=>document.querySelector('.expense-list'));
 for(const width of [360,768,1280]){results.pageWidths[`list-${width}`]=await resize(width);await shot(`list-final-${width}`);results.screenshots.push(`list-final-${width}.png`)}
 await resize(1280);await click('.expenses-page button','Tambah pengeluaran');await wait(()=>document.querySelector('.expense-form'));
 for(const width of [360,768,1280]){results.pageWidths[`form-${width}`]=await resize(width);await shot(`form-final-${width}`);results.screenshots.push(`form-final-${width}.png`)}
 await resize(1280);await click('.expense-form button','Batal');await wait(()=>!document.querySelector('.expense-form'));
 const rowId=await p.eval(()=>[...document.querySelectorAll('.expense-list tr')].find(tr=>tr.textContent.includes('T014 visual QA'))?.querySelector('.act-btn')?.getAttribute('aria-controls'));assert.ok(rowId);
 await click(`.expenses-page [aria-controls="${rowId}"]`);await click('[role="menuitem"]','Hapus');await wait(()=>document.querySelector('.expenses-page dialog[open]'));
 await click('.expenses-page dialog button','Batal');await wait(()=>!document.querySelector('.expenses-page dialog[open]'));
 results.cancelFocus=await p.eval(id=>document.activeElement?.getAttribute('aria-controls'),rowId);assert.equal(results.cancelFocus,rowId);
 await click(`.expenses-page [aria-controls="${rowId}"]`);await click('[role="menuitem"]','Hapus');await wait(()=>document.querySelector('.expenses-page dialog[open]'));
 await click('.expenses-page dialog button','Ya, hapus');await wait(()=>![...document.querySelectorAll('.expense-list tbody tr')].some(tr=>tr.textContent.includes('T014 visual QA')));
 results.confirmedDelete=execFileSync('/usr/bin/sqlite3',[process.env.T014_DB,"SELECT count(*) FROM expenses WHERE description='T014 visual QA'"]).toString().trim()==='0';assert.equal(results.confirmedDelete,true);
 await resize(1280);await click('form[action="/logout"] button[type="submit"]','Keluar');await wait(()=>location.pathname==='/login');await text('#username','qa-tech');await text('#password',process.env.T014_LOGIN_PASSWORD);await click('[data-test="login-button"]');await wait(()=>location.pathname==='/tts/baru');results.technicianStatus=await p.eval(async()=>(await fetch('/pengeluaran')).status);assert.equal(results.technicianStatus,403);
 for(const[file,name]of[['tts.html','reference-tts'],['tts-baru.html','reference-tts-form']]){await p.goto(`file:///Users/muzib/.codex/worktrees/v1-stock/repair-app/mockup/${file}`);for(const width of[360,1280]){results.pageWidths[`${name}-${width}`]=await resize(width);await shot(`${name}-${width}`);results.screenshots.push(`${name}-${width}.png`)}}
 results.consoleErrors=p.consoleErrors;assert.equal(results.consoleErrors.length,0,JSON.stringify(results.consoleErrors));writeFileSync(new URL('./browser-final-results.json',import.meta.url),JSON.stringify(results,null,2));console.log(JSON.stringify(results));
}finally{await b.close()}
