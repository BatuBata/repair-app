import { Browser, sleep } from '/Users/muzib/.codex/skills/design-taste-frontend/scripts/lib/cdp.mjs';
const b=await Browser.launch();const p=await b.newPage({width:1280,height:900});
const press=async(key,code=key,modifiers=0)=>{await p.send('Input.dispatchKeyEvent',{type:'keyDown',key,code,modifiers});await p.send('Input.dispatchKeyEvent',{type:'keyUp',key,code,modifiers})};
const click=async(selector)=>{const q=await p.eval(s=>{const e=document.querySelector(s);if(!e)return null;const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}},selector);if(q){await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...q,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...q,button:'left',clickCount:1})}};
try{
 await p.goto('http://127.0.0.1:8190/login');
 for(const [s,v] of [['#username','qa-expenses'],['#password',process.env.T014_LOGIN_PASSWORD]]){await click(s);await press('a','KeyA',4);await p.send('Input.insertText',{text:v})}
 await click('[data-test="login-button"]');await sleep(500);await p.goto('http://127.0.0.1:8190/pengeluaran');await sleep(500);
 const id=await p.eval(()=>[...document.querySelectorAll('.expense-list tr')].find(e=>e.textContent.includes('T014 visual QA'))?.querySelector('.act-btn')?.getAttribute('aria-controls'));
 const before=await p.eval(id=>({id,trigger:document.querySelector(`[aria-controls="${id}"]`)?.outerHTML,menu:document.getElementById(id)?.outerHTML}),id);
 await click(`[aria-controls="${id}"]`);await sleep(200);
 const opened=await p.eval(id=>({expanded:document.querySelector(`[aria-controls="${id}"]`)?.getAttribute('aria-expanded'),menuHidden:document.getElementById(id)?.hidden,items:[...document.querySelectorAll(`#${id} [role="menuitem"]`)].map(e=>({text:e.textContent,rect:e.getBoundingClientRect().toJSON(),hidden:getComputedStyle(e).display}))}),id);
 const point=await p.eval(id=>{const e=[...document.querySelectorAll(`#${id} [role="menuitem"]`)].find(e=>e.textContent.includes('Ubah'));const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2,text:e.textContent}},id);
 await p.send('Input.dispatchMouseEvent',{type:'mousePressed',...point,button:'left',clickCount:1});await p.send('Input.dispatchMouseEvent',{type:'mouseReleased',...point,button:'left',clickCount:1});await sleep(1500);
 const after=await p.eval(()=>({form:!!document.querySelector('.expense-form'),title:document.querySelector('.expenses-page h1')?.textContent,body:document.querySelector('.expenses-page')?.innerText?.slice(0,300),menu:[...document.querySelectorAll('.menu')].map(e=>({hidden:e.hidden,text:e.innerText.slice(0,80)}))}));
 console.log(JSON.stringify({before,opened,point,after,errors:p.consoleErrors}));
}finally{await b.close()}
