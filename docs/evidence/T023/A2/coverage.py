from pathlib import Path
import json,hashlib,subprocess
p=Path('docs/evidence/T023/A2');a=json.loads(Path('docs/evidence/T023/A1/coverage.json').read_text())
for r in a['acceptance']:
 r['reused_A1_evidence']=r.pop('evidence');r['software']='pass';r['evidence']='A1 independent source/condition-equal proof in reuse-proof.json; historical A1 FAIL remains preserved.'
 if r['id'] in ['AC-24','AC-25']:r['evidence']='A1 unaffected screens/forms reused; F01 independently closed by A2 chrome-results.json: all anchors>=44x44 at360/768; 4trusted taps belowtext navigate service/sale; eye/focus/Tab3width;9sample/large/negative layouts/no horizontaloverflow; same-approved-mockup visualreview. Desktop inline unchanged. Hardware parts deferredT025.'
 if r['id']=='AC-17':r['evidence']='A1 exactmoney/domain/report proof reused; fresh Chrome and actualEdge report11Nov sample2.6m/1.8m/800k/65k/735k passes. Report layout normal/big/negative retested.'
 if r['id']=='AC-04':r['evidence']='A1 serverauthorization/privacy suite+HTTP proof reused; newactualEdge six403routes and technician HTML nofinancialvalues, publicprivacy/PDFsmoke.'
 if r['id']=='AC-01':r['evidence']='A1 remember-only-proof.json explicitrecaller-only cookie whitelist + serverviaRemembertrue reused, unchanged auth. NewEdge admin/technician launcher+logout PASS; physicalAndroid shortcut deferredT025.'
 if r['id']=='AC-14':r['evidence']='A1 tenPDF/fiveQR/long2/5/63pages renderer/fonts/source/inlineCSS byteequal reused; freshEdge2HTTPPDFsmoke/privacy PASS. Physicalprinter/camerascan deferredT025.'
 if r['id']=='AC-26':r['evidence']='A1 manifestnames/icons/standalone + currenttrackedpublicbytes unchanged (supporting-source-proof.json); newEdge rolelauncherPASS. ActualAndroid installation deferredT025.'
for r in a['business_rules']:
 r['reused_A1_evidence']=r.pop('evidence');r['evidence']='A1 independent BR case remainsPASS; all PHP/domain/schema/test/route dependencies unchanged and evidencehashes verified by reuse-proof.json.'
a.update({'checked_revision':'d047f25ea31d703660212e4205b937053c92adcb','software_contract_verdict':'pass','historical_A1_verdict':'fail preserved; F01 now independently closed, oldartifact not rewritten','NFR04desktopEdge':{'status':'pass','product':'Edg/157.0.4341.57','evidence':['edge-runtime.json','edge-results.json','edge-assets.json','edge-pdf-smoke.json']},'not_run_current':['physical Android browser/device/keyboard/install/4G/60secpilot/printer/camera T025','productionVPSHTTPSFPMcron/offsitebackup T024-T025 not authorized'],'primary_evidence':['input-proof.json','bundle-delta.json','reuse-proof.json','supporting-source-proof.json','chrome-results.json','chrome-assets.json','edge-runtime.json','edge-results.json','edge-assets.json','edge-pdf-smoke.json','visual-review.json','final-freeze.json','resource-release.json']})
a.pop('not_run_A1',None);(p/'coverage.json').write_text(json.dumps(a,ensure_ascii=False,indent=2)+'\n')
files=['public/index.php','public/manifest.webmanifest','public/icons/ticket-192.png','public/icons/ticket-512.png','public/apple-touch-icon.png','public/robots.txt','composer.lock','package-lock.json'];rows=[]
for f in files:
 old=subprocess.check_output(['git','show','a95a3058c2160617d2e59ce7ac44685774d7528d:'+f]);now=Path(f).read_bytes();assert old==now;rows.append({'path':f,'sha256':hashlib.sha256(now).hexdigest(),'matches_A1':True})
(p/'supporting-source-proof.json').write_text(json.dumps(rows,indent=2)+'\n');print('all26softwareAC +16BR mapped, public/dependency source unchanged')
