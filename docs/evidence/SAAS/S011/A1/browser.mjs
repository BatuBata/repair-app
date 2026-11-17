import { createRequire } from 'node:module';
import fs from 'node:fs';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
const require=createRequire(import.meta.url);
const {chromium}=require('/Users/muzib/.codex/tools/mobile-mcp/node_modules/playwright');
const evidence=new URL('.',import.meta.url).pathname;
const expected=JSON.parse(fs.readFileSync(evidence+'data-before.json','utf8'));
const origin='https://demo-repair-app.scannara.asia';
let stage='launch', browser;
const prior=process.env.S011_RESUME ? JSON.parse(fs.readFileSync(evidence+'browser.json','utf8')) : {checks:[]};
const has=id=>prior.checks.some(c=>c.id===id&&c.result==='pass');
const result={utc:new Date().toISOString(),browser:'Google Chrome / Playwright',mode:'headless real Chromium engine',checks:prior.checks,console_errors:0,page_errors:0,failed_requests:0,unexpected_http:[],raw_customer_data_saved:false};
function record(id,data={}){result.checks=result.checks.filter(c=>c.id!==id);result.checks.push({id,result:'pass',observed_utc:new Date().toISOString(),...data});fs.writeFileSync(evidence+'browser.json',JSON.stringify(result,null,2)+'\n');console.log(id+' PASS');}
const hash=bytes=>crypto.createHash('sha256').update(bytes).digest('hex');
const money=n=>(String(n).startsWith('-')?'−':'')+'Rp'+String(n).replace('-','').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
try {
 assert(process.env.S011_PASSWORD,'private credential unavailable');
 browser=await chromium.launch({executablePath:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});
 const context=await browser.newContext({viewport:{width:1440,height:1000},acceptDownloads:false});
 const page=await context.newPage();page.setDefaultTimeout(15000);page.setDefaultNavigationTimeout(30000);
 page.on('console',m=>{if(m.type()==='error')result.console_errors++;});page.on('pageerror',()=>result.page_errors++);page.on('requestfailed',()=>result.failed_requests++);
 page.on('response',r=>{if(r.status()>=400)result.unexpected_http.push({status:r.status(),resource:r.request().resourceType()});});
 async function go(path,{live=true}={}){const response=await page.goto(origin+path);assert.equal(response.status(),200);if(live)await page.waitForFunction(()=>window.Livewire?.all().length>0);return response;}
 const text=()=>page.locator('body').innerText();
 stage='https-health-login-assets';
 const health=await context.request.get(origin+'/up');assert.equal(health.status(),200);
 await go('/login',{live:false});assert((await text()).includes('Masuk'));assert(!(await text()).includes('Tenant'));
 const assets=await page.locator('link[rel=stylesheet],script[src]').evaluateAll(els=>els.map(e=>e.href||e.src).filter(u=>u.includes('/build/')));
 for(const url of [...new Set(assets)]){const r=await context.request.get(url);assert.equal(r.status(),200);assert((await r.body()).length>0);}
 record(stage,{health:200,login:200,entry_assets_200:[...new Set(assets)].length,generic_login:true});
 stage='admin-login-dashboard';
 await page.locator('[name=username]').fill('admin');await page.locator('[name=password]').fill(process.env.S011_PASSWORD);
 await Promise.all([page.waitForURL(origin+'/'),page.getByRole('button',{name:'Masuk',exact:true}).click()]);
 await page.waitForFunction(()=>window.Livewire?.all().length>0);
 assert.equal(await page.locator('h1').innerText(),'Beranda');
 const store=await page.locator('.sidebar .brand strong').innerText();assert(store.length>0);
 const summaries=await page.locator('.dashboard-summary .summary-value').allTextContents();
 assert(summaries.some(v=>v.replace(/\s/g,'')===money(expected.reports.today.totals.revenue)));
 assert.equal((await page.locator('#tab-revenue .kpi-value').innerText()).replace(/\s/g,''),money(expected.reports.month.totals.revenue));
 assert.equal((await page.locator('#tab-profit .kpi-value').innerText()).replace(/\s/g,''),money(expected.reports.month.totals.gross));
 record(stage,{login_success:true,store_identity_present:true,today_revenue_sql_match:true,monthly_revenue_gross_sql_match:true,timezone:'Asia/Makassar'});
 if(!has('users-settings-navigation')){
 stage='users-settings-navigation';
 await page.locator('.sidebar a[href="/pengaturan/toko"]').click();await page.waitForURL(origin+'/pengaturan/toko');
 assert.equal(await page.locator('[name=name]').inputValue(),store);assert.equal(await page.locator('.logo-preview').count(),0);
 const usersLink=page.locator('a[href$="/pengaturan/pengguna"]').first();assert(await usersLink.count());await usersLink.click();await page.waitForURL(origin+'/pengaturan/pengguna');
 assert((await text()).includes('Pengguna'));assert.equal(await page.locator('.sidebar .brand strong').innerText(),store);
 await go('/pengaturan/kategori');assert((await text()).includes('Kategori'));record(stage,{store_name_consistent:true,legacy_logo_absent_preserved:true,users:true,categories:true,no_save_submitted:true});
 }
 if(!has('guest-tracking-existing-order')){
 stage='tts-list-detail-documents';
 await go('/tts');assert((await page.locator('.tts-list .total').innerText()).includes(String(expected.roots.service_orders.rows)));
 const orderLink=page.locator('.tts-list .c-id a').first();const orderPath=new URL(await orderLink.getAttribute('href'),origin).pathname;const orderNumber=await orderLink.innerText();
 await orderLink.click();await page.waitForURL(origin+orderPath);assert.equal(await page.locator('h1').innerText(),orderNumber);assert.equal(await page.locator('.sidebar .brand strong').innerText(),store);
 const orderId=orderPath.split('/').pop();
 const documents=[];
 for(const suffix of ['/struk','/struk.pdf','/pdf']){
  const r=await context.request.get(origin+orderPath+suffix);assert.equal(r.status(),200);const bytes=await r.body();assert(bytes.length>300);
  if(suffix.endsWith('pdf'))assert(bytes.subarray(0,5).equals(Buffer.from('%PDF-')));
  else {const html=bytes.toString();assert(html.includes(store));assert(html.includes(orderNumber));}
  documents.push({type:suffix,status:r.status(),bytes:bytes.length,sha256:hash(bytes),pdf:suffix.endsWith('pdf')});
 }
 record(stage,{existing_rows:expected.roots.service_orders.rows,list_detail_identity_consistent:true,documents});
 stage='guest-tracking-existing-order';
 // Read the application's existing redirect in memory only; never follow the external messaging URL.
 const wa=await context.request.get(origin+orderPath+'/whatsapp',{maxRedirects:0});assert.equal(wa.status(),302);
 const location=wa.headers().location;const message=new URL(location).searchParams.get('text');const match=message?.match(/\/t\/([a-zA-Z0-9]{64})/);assert(match);
 const guest=await browser.newContext({viewport:{width:390,height:844},acceptDownloads:false});const gp=await guest.newPage();const tr=await gp.goto(origin+'/t/'+match[1]);assert.equal(tr.status(),200);
 const trackingText=await gp.locator('body').innerText();assert(trackingText.includes(store));assert(trackingText.includes(orderNumber));assert.equal(tr.headers()['x-robots-tag'],'noindex');assert(tr.headers()['cache-control'].includes('no-store'));
 const publicPdfLink=gp.locator('a[href$="/pdf"]').first();let publicPdf='not_available_for_uninvoiced_order';
 if(await publicPdfLink.count()) {const href=await publicPdfLink.getAttribute('href');const r=await guest.request.get(new URL(href,origin).href);const bytes=await r.body();assert.equal(r.status(),200);assert(bytes.subarray(0,5).equals(Buffer.from('%PDF-')));publicPdf={status:200,bytes:bytes.length,sha256:hash(bytes)};}
 await guest.close();record(stage,{status:200,guest_without_auth:true,owner_identity_and_order_match:true,noindex:true,no_store:true,external_message_not_sent:true,public_pdf:publicPdf});
 }
 stage='stock-sales-expenses';
 await go('/stok');assert.equal(await page.locator('.sidebar .brand strong').innerText(),store);assert(await page.locator('tbody tr').count());
 await go('/penjualan');const saleLink=page.locator('.sales-page tbody tr .act a').first();
 const salePath=new URL(await saleLink.getAttribute('href'),origin).pathname;const saleNumber=await page.locator('.sales-page tbody .sale-number').first().innerText();await saleLink.click();await page.waitForURL(origin+salePath);assert((await text()).includes(saleNumber));
 const saleDocs=[];for(const suffix of ['/struk','/struk.pdf','/pdf']){const r=await context.request.get(origin+salePath+suffix);const bytes=await r.body();assert.equal(r.status(),200);if(suffix.endsWith('pdf'))assert(bytes.subarray(0,5).equals(Buffer.from('%PDF-')));else{assert(bytes.toString().includes(store));assert(bytes.toString().includes(saleNumber));}saleDocs.push({type:suffix,status:200,bytes:bytes.length,sha256:hash(bytes)});}
 await go('/pengeluaran');assert.equal(await page.locator('.sidebar .brand strong').innerText(),store);assert((await text()).includes('Pengeluaran'));
 record(stage,{stock_existing:true,sales_existing_detail:true,sale_documents:saleDocs,expenses_empty_baseline:expected.roots.expenses.rows===0});
 stage='report-db-totals-browser';
 await go('/laporan');
 async function checkReport(label){const report=expected.reports[label];const keys={'Omzet servis':'service','Omzet penjualan':'sales','Omzet':'revenue','Modal':'cost','Laba kotor':'gross','Pengeluaran':'expenses','Laba bersih':'net'};
  for(const [title,key] of Object.entries(keys)){const row=page.locator('.statement > div').filter({has:page.locator('dt').filter({hasText:new RegExp('^'+title+'$')})}).first();const actual=(await row.locator('dd').innerText()).replace(/\s/g,'');const want=(['cost','expenses'].includes(key)&&report.totals[key]!=='0'?'−':'')+money(report.totals[key]);assert.equal(actual,want);}
 }
 await checkReport('month');
 await page.getByRole('button',{name:'Hari ini',exact:true}).click();await page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('livewire'));await page.waitForTimeout(500);await checkReport('today');
 record(stage,{month:{from:expected.reports.month.from,to:expected.reports.month.to,all_seven_totals_match_sql:true},today:{from:expected.reports.today.from,to:expected.reports.today.to,all_seven_totals_match_sql:true},filter_livewire_success:true});
 stage='logout-generic-login';
 await page.locator('.sidebar [data-test=logout-button]').click();await page.waitForURL(origin+'/login');assert((await text()).includes('Masuk'));assert(!(await text()).includes(store));const denied=await context.request.get(origin+'/tts',{maxRedirects:0});assert.equal(denied.status(),302);assert(new URL(denied.headers().location).pathname==='/login');
 record(stage,{logout_success:true,post_logout_auth_redirect:302,generic_login:true});
 result.verdict='pass';await context.close();
} catch(e){result.verdict='fail';result.failed_stage=stage;result.error_class=e.name;console.error('S011 browser FAIL at '+stage+' ('+e.name+'); no raw error data persisted');process.exitCode=1;}
finally{if(browser)await browser.close();result.finished_utc=new Date().toISOString();fs.writeFileSync(evidence+'browser.json',JSON.stringify(result,null,2)+'\n');}
