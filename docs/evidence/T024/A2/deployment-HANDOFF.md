# T024 A2 — actual demo deployment PASS (D-20/D-21/D-22)

Live: https://demo-repair-app.scannara.asia ; observed UTC2026-11-14T02:43:12Z. PHP8.3 Artisanup exit0, only task-owned regular503marker removed; HTTPS /up200 and/login200 withcertificateverification. Headers containingcookies remain private600 server-side, not in this evidence. Git exactclean d047f25ea31d703660212e4205b937053c92adcb;230source/14build/9font,253uniquehashes0mismatch.

PHP8.3.36/Laravel13.42.0/MariaDB10.11.14. Immediately beforefirstmigration: exacttarget/accountidentity +0tables, productionpasswordrulePASS. migrate/configcache/routecache/viewcache/dbseed all exit0; finalconfigcacheaftersecretclear exit0.20InnoDBtables/6migrationsbatch1;1activeadmin/9categories/store1;11FK+unsignedmetadataPASS. Afteropening readonly service_orders/sales/products/expenses all0; normalwebsessionsmayexist.

Intentional ownerrequested handover `/home/muzib/deploy-demo-repair-app/initial-admin-password.txt` mode600, parent700; no value/hash/lengthlogged. Generatedonce, initialsecretcleared incurrent.env+staging/environment+cachedconfig; APP_KEY/DBpasswordpreserved. Nevergeneratekeyagainonsync. Earlierinvalidpasswordenvironmentblock receipt preserved; noDDL/DMLoccurred untilsuccessfulcredentialrecovery.

Coordinator suppliedindependentMariaDBQA/FPM-ACL/rootpermission/TLShook-renewalPASS beforeopening. Parent owns canonicalledger/T025 dispatch. DeploymentPASS appliesonly demoD20/D21/D22; independentlivewebacceptanceT025 andphysicalphone/printerremainseparate.

Allbackups/cron/restore NOT_RUN/deferredD22; nofakebackupPASS/NFR05claim. Source deploy/deploy.sh unchanged; exactunderlying Artisancalls usedunderthe explicitexception. FirstinstallhasnopreviousdeployedSHA; failureplanisrestoreowned503marker/diagnoseforwardfix, noautomaticDBrollback/reset/drop.
