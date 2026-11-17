import { createRequire } from 'node:module';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const require = createRequire(import.meta.url);
const { chromium } = require('/Users/muzib/.codex/tools/mobile-mcp/node_modules/playwright');
const root='/private/tmp/saas-s007-runtime-20261117';
const fixtures=JSON.parse(fs.readFileSync(root+'/fixtures.json','utf8'));
const evidence=process.cwd()+'/docs/evidence/SAAS/S007/A1';
const browser=await chromium.launch({executablePath:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});
const contexts={};const pages={};const results=fs.existsSync(evidence+'/browser.json')?JSON.parse(fs.readFileSync(evidence+'/browser.json','utf8')):[];
const has=id=>results.some(r=>r.id===id&&r.result==='pass');
const record=(id,data={})=>{const existing=results.findIndex(r=>r.id===id);if(existing>=0)results.splice(existing,1);results.push({id,result:'pass',...data});fs.writeFileSync(evidence+'/browser.json',JSON.stringify(results,null,2));console.log(id,'pass');};
const body=page=>page.locator('body').innerText();
const wait=page=>page.waitForFunction(()=>window.Livewire?.all().length>0);
async function live(page,method,...args){return page.evaluate(async({method,args})=>{let component=window.Livewire.all()[0];return await component.$call(method,...args);},{method,args});}
async function raw(page,updates={},method=null,params=[]){
 return page.evaluate(async({updates,method,params})=>{
  const el=Array.from(document.querySelectorAll('*')).find(el=>el.hasAttribute('wire:snapshot'));
  const uri=document.querySelector('[data-update-uri]').getAttribute('data-update-uri');
  const csrf=document.querySelector('[data-csrf]').getAttribute('data-csrf');
  const response=await fetch(uri,{method:'POST',headers:{'Content-Type':'application/json','X-Livewire':'','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({_token:csrf,components:[{snapshot:el.getAttribute('wire:snapshot'),updates,calls:method?[{path:'',method,params}]:[]}]})});
  return {status:response.status,text:await response.text()};
 },{updates,method,params});
}
async function goto(page,path){const response=await page.goto('http://127.0.0.1:8877'+path);assert.equal(response.status(),200,path);await wait(page);}
function noForeign(text,label){assert(!text.includes('S007 '+(label==='A'?'B':'A')),'Foreign tenant marker visible');}
try{
 for(const label of ['A','B']){
  const ctx=await browser.newContext({viewport:{width:1440,height:1000}});contexts[label]=ctx;const page=await ctx.newPage();page.setDefaultTimeout(10000);page.setDefaultNavigationTimeout(20000);pages[label]=page;
  await page.goto('http://127.0.0.1:8877/login');await page.locator('[name=username]').fill(fixtures.shops[label].username);await page.locator('[name=password]').fill(fixtures.password);
  await Promise.all([page.waitForURL('http://127.0.0.1:8877/'),page.getByRole('button',{name:'Masuk',exact:true}).click()]);
  await wait(page);assert((await body(page)).includes('Identitas S007 '+label));
  await page.screenshot({path:evidence+'/dashboard-'+label+'.png',fullPage:true});record('login-dashboard-'+label);
 }
 if(process.argv[2]==='inspect'){
  for(const url of ['/tts/baru','/stok/baru','/penjualan/baru','/laporan']){
   await pages.A.goto('http://127.0.0.1:8877'+url);await wait(pages.A);console.log(url,JSON.stringify(await pages.A.locator('input,textarea,button').evaluateAll(els=>els.map(e=>({tag:e.tagName,id:e.id,name:e.getAttribute('name'),type:e.getAttribute('type'),text:e.textContent?.trim().slice(0,70),'wire:model':e.getAttribute('wire:model')})))));
  }
 }else{
  for(const label of ['A','B']){
   const page=pages[label],own=fixtures.shops[label],foreign=fixtures.shops[label==='A'?'B':'A'];
   let attack;
   if(!has('tts-create-detail-foreign-technician-'+label)){
   const baseline=150000*own.factor+1000;
   assert((await body(page)).includes('Rp'+baseline.toLocaleString('id-ID')),'Dashboard revenue');
   noForeign(await body(page),label);record('dashboard-total-'+label,{expected_revenue:baseline});
   await goto(page,'/laporan');assert((await body(page)).includes('Rp'+baseline.toLocaleString('id-ID')));noForeign(await body(page),label);
   await page.getByRole('button',{name:'Hari ini',exact:true}).first().click();await page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('livewire'));
   await page.getByRole('button',{name:'Servis',exact:true}).click();await page.waitForFunction(()=>document.querySelector('[aria-label="Jenis transaksi"] button[aria-pressed=true]')?.textContent==='Servis');
   assert((await body(page)).includes('Private customer S007 '+label));noForeign(await body(page),label);
   const reportAttack=await raw(page,{detail:'jual-'+foreign.sale});assert.equal(reportAttack.status,200);assert(!reportAttack.text.includes('Sale S007 '+(label==='A'?'B':'A')));
   await page.screenshot({path:evidence+'/report-'+label+'.png',fullPage:true});record('reports-filters-totals-foreign-detail-'+label,{expected_revenue:baseline});
   for(const [path,marker] of [['/tts','Private customer S007 '+label],['/stok','Produk S007 '+label],['/penjualan','Sale S007 '+label],['/pengeluaran','Biaya S007 '+label],['/pengaturan/pengguna','Owner S007 '+label],['/pengaturan/kategori','Kategori S007 '+label]]){
    await goto(page,path);assert((await body(page)).includes(marker),path);noForeign(await body(page),label);
   }
   record('all-module-lists-'+label);
   await goto(page,'/pengaturan/toko');assert.equal(await page.locator('[name=name]').inputValue(),'Identitas S007 '+label);record('store-identity-'+label);
   // Foreign primary keys across routes, including every document form.
   const paths=[`/tts/${foreign.order}`,`/tts/${foreign.order}/edit`,`/tts/${foreign.order}/sukses`,`/tts/${foreign.order}/rincian`,`/tts/${foreign.order}/struk`,`/tts/${foreign.order}/struk.pdf`,`/tts/${foreign.order}/pdf`,`/tts/${foreign.order}/whatsapp`,`/stok/${foreign.product}/edit`,`/stok/${foreign.product}/restok`,`/penjualan/${foreign.sale}`,`/penjualan/${foreign.sale}/struk`,`/penjualan/${foreign.sale}/struk.pdf`,`/penjualan/${foreign.sale}/pdf`];
   for(const path of paths){const response=await contexts[label].request.get('http://127.0.0.1:8877'+path,{maxRedirects:0});assert.equal(response.status(),404,path);}
   record('foreign-direct-routes-'+label,{routes:paths.length,status:404});
   for(const [path,method,id] of [['/stok','toggleActive',foreign.product],['/pengaturan/pengguna','edit',foreign.user],['/pengaturan/pengguna','toggleActive',foreign.tech],['/pengaturan/pengguna','resetPassword',foreign.user],['/pengaturan/kategori','edit',foreign.category],['/pengaturan/kategori','toggleActive',foreign.category]]){
    await goto(page,path);const attack=await raw(page,{},method,[id]);assert.equal(attack.status,404,path+' '+method);
   }
   record('foreign-livewire-action-ids-'+label,{status:404});
   await goto(page,`/tts/${own.order}`);assert((await body(page)).includes('Private customer S007 '+label));noForeign(await body(page),label);
   let locked=await raw(page,{orderId:foreign.order});assert.equal(locked.status,419);record('locked-orderId-'+label,{status:locked.status});
   await goto(page,`/stok/${own.product}/edit`);locked=await raw(page,{productId:foreign.product,name:'Forged'} ,'save');assert.equal(locked.status,419);record('locked-productId-'+label,{status:locked.status});
   await goto(page,'/tts/baru');noForeign(await page.content(),label);
   attack=await raw(page,{customer_name:'Forged customer',customer_phone:'081234567890',category_id:String(foreign.category),complaint:'Foreign payload'},'save');
   assert.equal(attack.status,200);assert(attack.text.includes('Pilih kategori barang yang aktif'));record('foreign-category-payload-'+label);
   await goto(page,'/tts/baru');await page.locator('#customer_name').fill('Browser customer S007 '+label);await page.locator('#customer_phone').fill('081234567890');await page.locator('#complaint').fill('Browser complaint '+label);
   await page.getByRole('combobox',{name:'Barang',exact:true}).click();await page.getByRole('option',{name:'Kategori S007 '+label,exact:true}).click();
   await Promise.all([page.waitForURL(/\/tts\/\d+\/sukses$/),page.getByRole('button',{name:'Simpan TTS',exact:true}).first().click()]);await wait(page);
   const createdId=Number(page.url().match(/\/tts\/(\d+)\/sukses$/)[1]);assert((await body(page)).includes('Browser customer S007 '+label));noForeign(await body(page),label);
   attack=await raw(page,{technician_id:String(foreign.tech)},'assign');assert.equal(attack.status,200);assert(attack.text.includes('Pilih teknisi yang aktif'));record('tts-create-detail-foreign-technician-'+label,{created_id:createdId});
   await page.screenshot({path:evidence+'/tts-'+label+'.png',fullPage:true});
   }
   if(!has('product-create-'+label)){
   await goto(page,'/stok/baru');await page.getByLabel('Nama barang',{exact:true}).fill('Browser product S007 '+label);await page.locator('[data-money=money-cost_price]').fill('20000');await page.locator('[data-money=money-sell_price]').fill('100000');await page.getByLabel('Stok awal (unit)',{exact:true}).fill('3');
   await Promise.all([page.waitForURL('http://127.0.0.1:8877/stok'),page.getByRole('button',{name:'Simpan barang',exact:true}).first().click()]);await wait(page);assert((await body(page)).includes('Browser product S007 '+label));record('product-create-'+label);
   }
   if(!has('stock-restock-'+label)){
   await goto(page,`/stok/${own.product}/restok`);noForeign(await body(page),label);await page.getByLabel('Jumlah masuk (unit)',{exact:true}).fill('2');await page.locator('[data-money=money-cost_price]').fill('20000');
   await page.getByRole('button',{name:'Simpan restok',exact:true}).first().click();await page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('livewire'));record('stock-restock-'+label);
   }
   if(!has('sale-create-detail-'+label)){
   await goto(page,'/penjualan/baru');noForeign(await page.content(),label);
   attack=await raw(page,{},'selectProduct',[0,foreign.product]);assert.equal(attack.status,404);
   attack=await raw(page,{items:[{product_id:String(foreign.product),qty:'1',price:'100000'}]},'save');assert.equal(attack.status,200);assert(attack.text.includes('Pilih barang aktif yang tersedia'));record('foreign-product-livewire-payload-'+label);
   await goto(page,'/penjualan/baru');await page.locator('[name=customer_name]').fill('Browser sale S007 '+label);await page.getByRole('combobox',{name:'Barang',exact:true}).click();await page.getByRole('option',{name:new RegExp('^Produk S007 '+label+' ·')}).click();
   await page.locator('.stock-hint').waitFor({state:'visible'});
   await Promise.all([page.waitForURL(/\/penjualan\/\d+$/),page.getByRole('button',{name:'Simpan penjualan',exact:true}).first().click()]);await wait(page);assert((await body(page)).includes('Browser sale S007 '+label));record('sale-create-detail-'+label);
   }
   for(const path of [`/tts/${own.order}/struk`,`/penjualan/${own.sale}/struk`]){const response=await contexts[label].request.get('http://127.0.0.1:8877'+path);assert.equal(response.status(),200);const text=await response.text();assert(text.includes('Identitas S007 '+label));noForeign(text,label);}
   for(const path of [`/tts/${own.order}/pdf`,`/penjualan/${own.sale}/pdf`]){const response=await contexts[label].request.get('http://127.0.0.1:8877'+path);assert.equal(response.status(),200);assert(response.headers()['content-type'].includes('application/pdf'));assert((await response.body()).subarray(0,4).toString()==='%PDF');}
   record('own-documents-'+label);
   // The public token belongs to B/A even when an authenticated foreign session opens it.
   const response=await page.goto('http://127.0.0.1:8877/t/'+foreign.token);assert.equal(response.status(),200);const text=await page.content();assert(text.includes('Identitas S007 '+(label==='A'?'B':'A')));assert(!text.includes('Private customer'));assert(!text.includes('PRIVATE-ADDRESS'));assert(!text.includes('cost_price'));assert(!text.includes('"cost"'));assert.equal(response.headers()['x-robots-tag'],'noindex');
   await page.screenshot({path:evidence+'/tracking-owner-'+(label==='A'?'B':'A')+'.png',fullPage:true});record('public-tracking-owner-safe-fields-'+label);
  }
 }
}catch(error){console.error(error);fs.appendFileSync(evidence+'/harness-errors.txt',String(error)+'\n');for(const [label,page]of Object.entries(pages)){await page.screenshot({path:root+'/debug-'+label+'.png',fullPage:true}).catch(()=>{});fs.writeFileSync(root+'/debug-'+label+'.txt',await body(page).catch(()=>''));}process.exitCode=1;}finally{await browser.close();}
