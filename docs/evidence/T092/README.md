# T092 — branding header tracking

D-55, main lokal tanpa push/deploy. Header tracking sebelumnya menanam SVG tiket lama dan teks tetap `Servis Elektronik`. Diganti dengan komponen `x-ui.mark` existing dan subtitle netral `Status servis`; nama toko tetap `$storeName` yang sudah sesuai tenant. CSS header mengatur `.brand-mark`26px; komponen img memiliki width/height24 eksplisit untuk ukuran sebelumCSS.

Pemeriksaan aktual:

- PHP8.3 `artisan test --compact tests/Feature/Tracking/TrackingTest.php tests/Feature/Tracking/CancelledTrackingTest.php`:5PASS129assert491ms, termasuk assertionlogo/neutralcopy dan regresi pembatalan/privasi.
- PHP8.3 `vendor/bin/pint --dirty --test` PASS; `git diff --check`PASS.
- CUA publictracking fixtureSQLite T091, viewport1280/375 serta refresh: image `/icons/repair-app-192.png` complete,naturalWidth192,display26x26; atribut24x24; nama/subtitlebenar,documentwidth sama viewport. Screenshot customer-desktop.jpg/customer-mobile.jpg, browser-checks.json. Serverfixture/tabagent ditutup danviewportdipulihkan.
- QA independen mereview2file dan bukti, pin source-manifest.json sha256:fec898f36d86fb559d97964c554e3afb74043cd3a5c3846780fb32110a3bc2d3.

Tidak mengubah backend/data/skema/dependency atau menambah pengaturan jenis toko. Build/suite penuh tidak diulang untuk dua perubahan template+assertion ini: tidak ada asset-source atau utility baru. Tidak menguji produksi/perangkat fisik; tidak push/deploy.
