import fs from 'node:fs';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
const {chromium}=createRequire(import.meta.url)('/Users/muzib/.codex/tools/mobile-mcp/node_modules/playwright');
const root='/private/tmp/saas-s007-runtime-20261117',base='http://127.0.0.1:8877';
const fixture=JSON.parse(fs.readFileSync(root+'/fixtures.json','utf8'));
const evidence=process.cwd()+'/docs/evidence/SAAS/S007/A1';
const browser=await chromium.launch({executablePath:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});
const results=[];const pages={};
function record(id,data={}){results.push({id,result:'pass',...data});fs.writeFileSync(evidence+'/security-browser.json',JSON.stringify(results,null,2));console.log(id,'pass');}
async function login(label){const context=await browser.newContext({viewport:{width:1440,height:1000}});const page=await context.newPage();page.setDefaultTimeout(10000);page.setDefaultNavigationTimeout(20000);await page.goto(base+'/login');await page.locator('[name=username]').fill(fixture.shops[label].username);await page.locator('[name=password]').fill(fixture.password);await page.locator('[name=remember]').check();await Promise.all([page.waitForURL(base+'/'),page.getByRole('button',{name:'Masuk',exact:true}).click()]);return page;}
async function goto(page,path){const r=await page.goto(base+path);assert.equal(r.status(),200,path);await page.waitForFunction(()=>window.Livewire?.all().length>0);}
async function snapshot(page){return page.evaluate(()=>Array.from(document.querySelectorAll('*')).find(el=>el.hasAttribute('wire:snapshot')).getAttribute('wire:snapshot'));}
async function raw(page,updates={},calls=[],source=null){return page.evaluate(async({updates,calls,source})=>{const el=Array.from(document.querySelectorAll('*')).find(el=>el.hasAttribute('wire:snapshot'));const script=document.querySelector('[data-update-uri]');const csrf=script.getAttribute('data-csrf');const r=await fetch(script.getAttribute('data-update-uri'),{method:'POST',headers:{'Content-Type':'application/json','X-Livewire':'','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({_token:csrf,components:[{snapshot:source??el.getAttribute('wire:snapshot'),updates,calls:calls.map(c=>({path:'',method:c[0],params:c.slice(1)}))}]})});return{status:r.status,text:await r.text()};},{updates,calls,source});}
try{
 for(const label of ['A','B'])pages[label]=await login(label);
 for(const label of ['A','B']){
  const page=pages[label],own=fixture.shops[label],foreign=fixture.shops[label==='A'?'B':'A'];
  await goto(page,'/pengeluaran');for(const method of ['edit','confirmDelete'])assert.equal((await raw(page,{},[[method,foreign.expense]])).status,404);
  for(const key of ['expenseId','deleteId'])assert.equal((await raw(page,{[key]:foreign.expense})).status,419);
  record('foreign-expense-ids-and-locked-payload-'+label,{actions_404:2,locked_419:2});
  for(const[path,updates]of [['/pengaturan/pengguna',{userId:foreign.user,mode:'edit'}],['/pengaturan/kategori',{categoryId:foreign.category,mode:'edit'}],[`/tts/${own.order}/rincian`,{orderId:foreign.order}],[`/penjualan/${own.sale}`,{saleId:foreign.sale,confirmingDelete:true}],['/pengaturan/toko',{logo_path:'logos/'+foreign.tenant+'/forged.png'}]]){await goto(page,path);assert.equal((await raw(page,updates)).status,419);}
  record('other-locked-identity-fields-'+label,{status:419,components:5});
 }
 // Replaying B's authentic server-signed snapshots under A's session must recheck ownership.
 for(const [path,calls,updates]of [[`/stok/${fixture.shops.B.product}/edit`,[['save']],{name:'Forged'}],[`/tts/${fixture.shops.B.order}/edit`,[['save']],{customer_name:'Forged customer'}],[`/tts/${fixture.shops.B.order}/rincian`,[['save']],{}],[`/penjualan/${fixture.shops.B.sale}`,[['confirmDelete'],['delete']],{}]]){
  await goto(pages.B,path);const source=await snapshot(pages.B);await goto(pages.A,'/stok');assert.equal((await raw(pages.A,updates,calls,source)).status,404,path);
 }
 record('signed-foreign-snapshot-replay',{status:404,components:4});
 const guest=await browser.newContext();
 for(const label of ['A','B']){
  const response=await guest.request.get(base+'/t/'+fixture.shops[label].token);assert.equal(response.status(),200);let text=await response.text();assert(text.includes('Identitas S007 '+label));assert(!text.includes('Private customer'));assert(!text.includes('PRIVATE-ADDRESS'));assert.equal(response.headers()['cache-control'],'no-store, private');
  const pdf=await guest.request.get(base+'/t/'+fixture.shops[label].token+'/pdf');assert.equal(pdf.status(),200);assert(pdf.headers()['content-type'].includes('application/pdf'));assert.equal((await pdf.body()).subarray(0,4).toString(),'%PDF');
 }
 const bad=await guest.request.get(base+'/t/'+'0'.repeat(64));assert.equal(bad.status(),404);record('guest-tracking-and-public-pdf',{tenants:2,invalid_token_status:404});
 const bHttp=await login('B');await goto(pages.B,'/stok');
 execFileSync('/opt/homebrew/opt/php@8.3/bin/php',['docs/evidence/SAAS/S007/A1/mysql.php','deactivate'],{cwd:process.cwd(),env:{...process.env,QA_RUNTIME:root},stdio:'pipe'});
 const mutation=await raw(pages.B,{},[['toggleActive',fixture.shops.B.product]]);assert.equal(mutation.status,403);record('inactive-tenant-livewire-mutation',{status:403});
 const disabledGet=await bHttp.goto(base+'/');assert.equal(disabledGet.status(),403);await bHttp.screenshot({path:evidence+'/inactive-tenant.png',fullPage:true});record('inactive-tenant-existing-remember-session',{status:403});
 const loginContext=await browser.newContext();const loginPage=await loginContext.newPage();await loginPage.goto(base+'/login');await loginPage.locator('[name=username]').fill(fixture.shops.B.username);await loginPage.locator('[name=password]').fill(fixture.password);await Promise.all([loginPage.waitForURL(base+'/login'),loginPage.getByRole('button',{name:'Masuk',exact:true}).click()]);await loginPage.getByText('Username atau password salah, atau akun tidak aktif.').waitFor();record('inactive-tenant-login-rejected');
 const oldTracking=await guest.request.get(base+'/t/'+fixture.shops.B.token);assert.equal(oldTracking.status(),200);assert((await oldTracking.text()).includes('Identitas S007 B'));const oldPdf=await guest.request.get(base+'/t/'+fixture.shops.B.token+'/pdf');assert.equal(oldPdf.status(),200);record('inactive-tenant-public-history-retained');
 await goto(pages.A,'/');assert((await pages.A.locator('body').innerText()).includes('Identitas S007 A'));record('other-tenant-remains-active');
}catch(error){console.error(error);fs.appendFileSync(evidence+'/harness-errors.txt',String(error)+'\n');process.exitCode=1;}finally{await browser.close();}
