from pathlib import Path
import json,re
p=Path('docs/evidence/T023/A1')
ac={
'01':('pass','Auth AuthenticationTest::remember_me_issues_framework_recaller_cookie_and_rotates_session + RoleLauncherTest; flows remember persistent recaller in fresh Chrome process without session cookie. Actual Android shortcut/browser install deferred T025.'),
'02':('pass','TtsTest required/phone/injection cases; flows tts-four-required, invalid-phone-retains-fields, normalized6281234567890 with success screenshot.'),
'03':('pass','DomainTest WITA month/min3/rollbacks + SalesTest WITA clock pin; actual parallel16 MySQL saves T002A4 reused only after identical numbering/model/migration hash proof. Guarded destructive MySQL test skipped here.'),
'04':('pass','Auth/Invoice/Sales/Stock/Expenses/Reports/Settings/Dashboard tests revoke and role guards; actual11 HTTP403 technician routes and HTML no amounts; tech-list/detail screenshots3widths.'),
'05':('pass','TtsTest own received/other owner/all states/stale actor lock; browser own received edit saved, tech-edit captures3widths.'),
'06':('pass','InvoiceTest active technician/time/duplicate assignment + browser assign->Dikerjakan/name.'),
'07':('pass','InvoiceTest all issue states/missing technician/rows/Jasa cost forced0; browser invalid invoice then Part+Jasa2lines; privateSQLite proof Jasa cost0.'),
'08':('pass','InvoiceTest exact arithmetic; browser LCD1m+Jasa100k =1.1m;10HTTP PDFs text checks no700.000; public/tracking HTML privacy; QR and renders reviewed.'),
'09':('pass','DashboardTest inclusive H-3/lastday30days/WITA/zero/contacted/expired + new MySQL service AND sale rowlocks at midnight and duplicate2admin mark; actual dashboard portalled mark removes row.'),
'10':('pass','InvoiceTest legal collect states/date; browser collect id7 ->Diambil; tracking-collected screenshots and exact status in TrackingTest.'),
'11':('pass','InvoiceTest cancel allowed/closed rejects; ReportsTest excludes cancelled; received/working/cancelled screenshots.'),
'12':('pass','TrackingTest exacttoken/noindex/no financial private fields, DocumentsTest invoice gate; fresh browser public404/noindex and no leaked HP/address/cost; MySQL wrong-casePDF404 despite rawCIlookup matching.'),
'13':('pass','TrackingTest WA exactencodednumber/template/role + DashboardTest warranty WA links. Browser inspected opaque redirect without opening/sending WA. Actual app switching deferred T025.'),
'14':('pass','10new HTTP PDFs A5/58mm sizes/fonts/text/bounds;5QR decoded with CoreImage to exact64token appURLs; long2/5/63page artifacts independently rechecked with source/hash provenance. Physical print/phone scan deferred T025.'),
'15':('pass','SalesTest shortage/rollback/restore-once/warranty/number + old actual MySQL parallelstock/delete proof reused with exactsource hashes. Browser warrantyphone rejection,2unit sale/PDF and ownsale deletion; privateSQLite stock restored5.'),
'16':('pass','StockTest restock/snapshot/notexpense/overflow/rollback; browser restock4->6/currentcost1.2m. Original sale snapshot1.1m retained in SQLite proof.'),
'17':('pass','ReportsTest PRDsample/currentrevision; actual11Nov browser report exact2.6m/1.8m/800k/65k/735k at3widths; newMySQL ownperiod report exact247912/222/247690.'),
'18':('pass','ExpensesTest inclusive date filters/invaliddates/category/revocation + ReportsTest expense date effect; browser expense created15k and edit form3widths; empty/reversed report captures.'),
'19':('pass','SettingsTest category deactivate/historicalname + TtsTest inactive category oldnames; browser category create/edit form callback/captures3widths.'),
'20':('pass','UsersTest all actions, lastadmin protection; ConcurrentAdminTest4cases; Auth inactive/stale session/passwordreset; browser users create/edit/reset forms3widths actual bodyportalled callbacks.'),
'21':('pass','InvoiceTest stale correction/date/status/pickup + ReportsTest updatedtotals originaldate; browser id7 correction1.1m->1.125m, retained invoiced_on proof.'),
'22':('pass','InvoiceTest zero/unrepairable/servicecharge; DocumentsTest actualHTML contains Tidak dapat diperbaiki; report includes Selesai/Diambil invoice independent of flag.'),
'23':('pass','SettingsTest fieldlimits/rasterupload/spoof/failedsave retainsold; actualUIlogo uploaded120x40,10PDF text/raster embedding, headers/store/address/phone/terms.255unbrokenname/65535address+terms full checks.'),
'24':('fail','111primary captures37states3widths plus extra forms/states compared same approved mockup when one exists; no horizontaloverflow/JSerror. F01 report number link20px violates DESIGN/NFR07 touch contract. Fullpage fixednav at original viewport boundary is capture behavior; normalfold/scroll reviewed.'),
'25':('fail','StickyTTS/invoice Save/Publish and input modes numeric/tel/16px/groupedmoney proven; label22px checkbox expands through44px label. F01 report anchors108.42x20px fail44x44. Actual Android keyboard deferred T025; qty formatting follows detailed§10.2 nominal-only separator rule.'),
'26':('pass','ManifestHTTP TokoAnugerah/Anugerah standalone /mulai and192/512icons200 actual dimensions; software launcherroles/auth proved. Android installation/addressbar-free installedlaunch deferred T025.')}
br={
'01':'DomainTest numbers/WITA/transaction/rollback + reusedT00216parallelMySQL sourceequal.',
'02':'DomainTest phone table; TtsTest/TrackingTest; browserinvalid/normalized.',
'03':'TtsTest fourrequired+activecategory; browserfourfieldvalidation retainsdata.',
'04':'DomainTest exactstatusgraph + InvoiceTest allowed/rejected transitions/stale locks; browser assign/publish/collect.',
'05':'InvoiceTest allstates/technician/rows/zero; browser validation then publish.',
'06':'DomainTest/InvoiceTest exactinteger/bounds/Jasa0; browser1.1m andlarge9.223e18 exactstrings; noNumber conversion ofmoney.',
'07':'DashboardTest WITA inclusivewindow; newMySQL service/sale duplicate andmidnightlockedread; actualUICallback mark.',
'08':'InvoiceTest correctionretainsdate + ReportsTest; browserid7invoiced_on retains13Nov.',
'09':'TtsTest/InvoiceTest/ReportsTest revokedroles;11actualtechHTTP403; nofinancialHTML.',
'10':'DomainTest tokenunique64 + TrackingTest exactcase/noindex/noleaks; existingT016CItracking sourceequal + newMySQLPDFwrongcase404.',
'11':'SalesTest shortage+wholetransactionrollback; T016actualparallelstock1twoadmins sourceequal retained; currentMySQLnewown fixtures no baselinechange.',
'12':'SalesTest snapshots + StockTest pricechanges; browserrestock changedproduct1cost1.2m whileorig sale1cost1.1m.',
'13':'StockTest notexpense and SalesTest deleteonce/restoreoverflow rollback; browserownsale5delete restoredstock5; priorindependentMySQLonce-delete proofsourceequal.',
'14':'ReportsTest PRDsample/inclusive dates/correction/delete/negative/large/31daywindows; actualsampleUI + newMySQL exact period.',
'15':'TtsTest oldinactivecategory/creator & Users/Auth/Settings tests; renderedexistinghistory retainsnames.',
'16':'ExpensesTest categories Bensin/Konsumsi/Gaji/Lainnya amountpositive/datebounds; browsercreate15k/edit callback.'}
result={'checked_revision':'a95a3058c2160617d2e59ce7ac44685774d7528d','software_contract_verdict':'fail','acceptance':[{'id':'AC-'+k,'software':v[0],'evidence':v[1]}for k,v in ac.items()],'business_rules':[{'id':'BR-'+k,'status':'pass','evidence':v}for k,v in br.items()],'deferred_T025':['physical Android installation/shortcut/browserclose/keyboard/4G/60sec pilot','physical58mmprinter/readability/photo/physicalQRscan'],'not_run_A1':['MicrosoftEdge actual smoke planned on fixedA2','productionVPSHTTPSFPMcron/offsitebackup T024-T025 authorization absent'],'primary_evidence':['suite.xml','test-inventory.json','checks.json','screen-matrix.json','matrix-summary.json','flows.json','extra-states.json','final-ui.json','large-positive-browser.json','large-negative-browser.json','mysql-proof.json','mysql-extension.json','reuse-proof.json','pdf-independent.json','long-pdf-independent.json','qr-check.log','manifest-http.json','report-touch-defect.json']}
(p/'coverage.json').write_text(json.dumps(result,ensure_ascii=False,indent=2))
print('coverage',len(ac),'AC',len(br),'BR')
