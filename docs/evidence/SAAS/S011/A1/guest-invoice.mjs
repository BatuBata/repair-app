import {createRequire} from 'node:module';
import fs from 'node:fs';
import assert from 'node:assert/strict';
import crypto from 'node:crypto';
const require=createRequire(import.meta.url),{chromium}=require('/Users/muzib/.codex/tools/mobile-mcp/node_modules/playwright');
const origin='https://demo-repair-app.scannara.asia',evidence=new URL('.',import.meta.url).pathname;
const result={observed_utc:new Date().toISOString(),browser:'Google Chrome',raw_customer_data_saved:false,raw_pdf_saved:false};let browser,stage='login';
try{
 browser=await chromium.launch({executablePath:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});const ctx=await browser.newContext(),page=await ctx.newPage();page.setDefaultTimeout(15000);
 await page.goto(origin+'/login');await page.locator('[name=username]').fill('admin');await page.locator('[name=password]').fill(process.env.S011_PASSWORD);await Promise.all([page.waitForURL(origin+'/'),page.getByRole('button',{name:'Masuk',exact:true}).click()]);
 const store=await page.locator('.sidebar .brand strong').innerText();await page.goto(origin+'/laporan');
 stage='existing-invoiced-order';const link=page.locator('.report-number a').filter({hasText:/TTS-/}).first();const number=await link.innerText(),path=new URL(await link.getAttribute('href'),origin).pathname;
 const wa=await ctx.request.get(origin+path+'/whatsapp',{maxRedirects:0});assert.equal(wa.status(),302);const message=new URL(wa.headers().location).searchParams.get('text');const token=message.match(/\/t\/([a-zA-Z0-9]{64})/)[1];
 const guest=await browser.newContext(),gp=await guest.newPage();const response=await gp.goto(origin+'/t/'+token);assert.equal(response.status(),200);const body=await gp.locator('body').innerText();assert(body.includes(store));assert(body.includes(number));
 stage='guest-invoice-pdf';const pdfLink=gp.locator('a[href$="/pdf"]').first();assert(await pdfLink.count());const pdf=await guest.request.get(new URL(await pdfLink.getAttribute('href'),origin).href);const bytes=await pdf.body();assert.equal(pdf.status(),200);assert(bytes.subarray(0,5).equals(Buffer.from('%PDF-')));assert(bytes.length>5000);
 result.guest_tracking={status:200,owner_store_match:true,existing_invoiced_order_match:true,no_auth_context:true};result.guest_pdf={status:200,bytes:bytes.length,sha256:crypto.createHash('sha256').update(bytes).digest('hex'),content_type:pdf.headers()['content-type'],pdf_signature:true};
 await guest.close();stage='logout';await page.locator('.sidebar [data-test=logout-button]').click();await page.waitForURL(origin+'/login');assert(!(await page.locator('body').innerText()).includes(store));await ctx.close();result.verdict='pass';console.log('guest-invoice-existing-record PASS');
}catch(e){result.verdict='fail';result.failed_stage=stage;result.error_class=e.name;process.exitCode=1;console.error('guest-invoice FAIL '+stage+' '+e.name);}
finally{if(browser)await browser.close();fs.writeFileSync(evidence+'guest-invoice.json',JSON.stringify(result,null,2)+'\n');}
