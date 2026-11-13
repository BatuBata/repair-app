# T016 A1 — QA independen kandidat T015

**Verdict: FAIL.** Ada satu kelompok cacat responsif T010: nominal nota yang sah melebar pada form dan detail nota terbit; pada desktop angka baris rincian terpecah ketika deskripsi panjang. Tidak ada patch aplikasi oleh tester. T015 belum boleh diterima/merge main berdasarkan laporan ini.

- `checked_revision`: **545b52a275687703e6046270afb45e01a0334922**, input hasil T015.
- Workspace kanonik `/Users/muzib/Projects/web/repair-app`; branch `codex/qa-m2-m4`, HEAD saat pemeriksaan `9e85f9093734fb846c99dc5e7f3f0a7ee91965c1` hanya menambahkan dokumen status di atas target. Sumber target tetap identik.
- Artefak produksi **6e43a91e8f08f49aae6ab334fa46dde48fcc52c1900d1a0e11f60344e2556f36**, entry `assets/app-CxA26Lmt.css`. Tidak rebuild/npm install/composer update.
- `final-pins.json`: 211 sumber, 14 berkas build, 73 input build, 5 font runtime cocok SHA256 manifest T015 sebelum/akhir QA. Tidak mengubah app, test proyek, dependency, migrasi, TODO, main, atau deployment.
- Sesi tester native `/root/t016_qa` berbeda dari implementer/integrator. Model diminta Sol high; receipt tidak mengekspos identitas backend efektif, sehingga tidak diklaim.
- Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, QA/release, coordination/runtime adapters, AGENTS, PRD §8/15, kontrak T016, DESIGN, dan design-taste-frontend/app-screens digunakan. DESIGN serta mockup proyek menjadi acuan yang mengikat.

## F01 — nominal nota sah keluar layar (pemilik T010, P2)

Input bukan overflow: qty **2147483648**, harga jual **4294967295**, modal **4294967295** → total/modal **9223372034707292160**, laba0. Qty/harga/modal masih dalam uint32; subtotal masih di bawah PHP_INT_MAX. Deskripsi panjang 230–234 karakter masih dalam batas255. Fixture `edge-fixture.php` menyimpan nominal tersebut lewat aksi aplikasi nyata; `edge-fixture.log` mencatat total integer yang sama. Browser form diulang lewat klik dan ketikan CDP biasa, bukan mengandalkan state injeksi.

Langkah reproduksi form:

1. Login admin sintetis; buka TTS Dikerjakan yang sudah ditetapkan teknisi, lalu `/tts/{id}/rincian`.
2. Isi deskripsi, qty2147483648, harga4294967295, modal4294967295 melalui field. Total tampil Rp9.223.372.034.707.292.160.
3. Pada viewport360, lihat ringkasan di bawah dan bilah Terbitkan nota. Ulangi1280.

| Subkasus | Expected | Actual dan bukti |
|---|---|---|
| Form360 | Semua digit dan aksi tetap di dalam360; bilah aksi dapat dijangkau utuh | visual viewport360, tetapi layout/document425; grand dd kanan424,5; tombol kanan409. Total bilah memecah digit dan tombol terpotong. `trusted-invoice-large-360.png`, `trusted-invoice-large-summary-360.png`, `trusted-visual-results.json`. |
| Form1280 | Panel ringkasan dan seluruh nilai muat | document1342; grand dd kanan1341,5; angka/panel melewati kanan1280. `trusted-invoice-large-summary-1280.png`. |
| Nota terbit360 | Nilai nota terbit dan aksi tetap muat | Aksi nyata sudah menyimpan nota; layout/document408, grand dd kanan407,5. `published-large-lower-360.png`, `published-results.json`. |
| Nota terbit1280, deskripsi panjang | Qty, harga dan modal tetap terbaca sebagai angka utuh | Qty2147483648, harga Rp4.294.967.295 dan modal terpecah di tengah digit ke beberapa baris. `published-large-lower-1280.png`. Grand total1280 sendiri muat. |

Form768 dan total nota terbit768 muat. Nominal normal pada form/koreksi/detail lulus tiga lebar. Nota harga0/modalbesar menghasilkan **Rp-9.223.372.034.707.292.160** yang tepat, merah, dan muat tiga lebar (`published-negative-lower-*.png`). Temuan tidak membuktikan cacat hitungan atau penyimpanan.

Caller terkait yang diperiksa: `resources/views/livewire/invoice/form.blade.php` (summary/bilah aksi), `resources/views/livewire/tts/detail.blade.php` (aturan totals), dan include `resources/views/livewire/invoice/published.blade.php` (tabel/ringkasan). Perbaikan perlu menjangkau ketiganya dalam satu batch T010, lalu verifikasi subset terdampak pada candidate baru. Tester tidak mengubah ketiganya.

## Pemeriksaan aktual

| Perintah/observasi independen | Actual |
|---|---|
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' RUN_MYSQL_DOMAIN=0 /opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --colors=never --log-junit docs/evidence/T016/A1/suite.xml` | **157PASS, 1guardedMySQL SKIP, 1585assertions** dari158tes; `suite.log/xml`. Sekali. |
| `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint --test` | **PASS global**; `pint-global.log`. |
| `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpstan analyse --memory-limit=512M --no-progress --debug` | **PASS0error**; `phpstan.log`. |
| Tes baru tester `IndependentTest.php` | Empat skenario akhir **PASS114assertions**: stock/snapshot/restore13 dari run awal; service+expenses92 dari rerun dua kasus; biaya cek Rp50.000 sembilan assertion dari run terarah. Log awal/final/fee dan XML dipertahankan. |
| `python3 docs/evidence/T016/A1/mysql-run.py` | TTS nomor bersamaan dan stok bersamaan pada MySQL nyata **PASS**. Hanya fixture baru sendiri, tanpa reset. |
| `mysql-tracking.php` lewat env privat | Controller tracking nyata **PASS** exact200/wrongcase404 pada MySQL case-insensitive; read-only. |
| `node docs/evidence/T016/A1/browser-matrix.mjs` | Alur UI normal selesai, **78screenshot / 80observasi**, JS exception0; expected/actual DB di `*-proof.json`. |
| `node docs/evidence/T016/A1/edge-browser.mjs` | **24screenshot**, JS exception0: tracking semua status/zero/besar, penjualan besar, pengeluaran maksimum. Tidak melebar pada tiga lebar. |
| `trusted-visual-check.mjs`, `published-check.mjs` | F01 di atas, plus normal/768/negative-profit yang lulus. JS exception0. |
| `interactions.mjs` + `guard-continuation.mjs` | Tanggal sama/terbalik/kosong/pulih, cancel/Escape/fokus/delete pengeluaran, aksesibilitas bawah nota, inputHP tel,11rute403 tanpa sentinel keuangan **PASS**. Log runner error dijelaskan di bawah. |
| `pint --test docs/evidence/T016/A1` setelah helper PHP ditambah | **PASS**; `pint-own-final.log` dan log scoped masing-masing. |

Pengujian suite dilakukan tester sendiri pada kandidat terpin; laporan developer tidak dipakai sebagai pengganti hasil. Tes tambahan menjalankan Livewire/aksi/HTTP aplikasi nyata pada SQLite in-memory. Tidak menambah tes ke direktori test aplikasi.

## Pemetaan acceptance criteria

| AC | Cakupan tersedia yang dibuktikan sekarang | Hasil/bukti | Bagian downstream |
|---|---|---|---|
|02|Empat field wajib, isian tetap, normalisasi0812→62812, kategori aktif, batas opsional|PASS suite Tts/Domain; independent service; browser create/edit+DB proof|—|
|03|Batas bulan WITA Nov30 UTC15:59:59→16:00:00, nomor2611/2612, digit1000, rollback, konkurensi|PASS suite Domain/Sales; MySQL TTS3101-001/002 dan JL3101-001|—|
|04|Teknisi dilarang nota/stok/penjualan/pengeluaran/pengaturan, fresh HTML/snapshot tanpa angka biaya; demosi/nonaktif/stale action dicek ulang|PASS suite auth/Tts/Invoice/Stock/Sales/Expenses; independent service;11HTTP403 browser `guard-results.json`|Dashboard/laporan sesudah T018/T019 → T023; tidak diklaim PASS di sini|
|05|Teknisi edit milik sendiri hanya Diterima; peer/Dikerjakan ditolak; role/status/category berubah setelah mount tidak boleh menembus|PASS suite Tts, independent service, browser own edit|—|
|06|Admin assign/ganti hanya teknisi aktif, Dikerjakan/assigned_at, duplicate assignment tidak mengganti waktu|PASS suite Invoice; independent service; browser assign+detail|—|
|07|Syarat status/teknisi/baris, Jasa modal5000→0, validasi qty/nominal/batas total, rollback penggantian nota|PASS suite Invoice; independent service/fee; browser issue dua baris+DB proof|—|
|08|Part1000000/modal700000 +Jasa100000 =1100000; tracking tanpa modal/HP/alamat|PASS suite Tracking/Invoice, browser `normal-invoice-proof.json` dan tracking; fresh HTML dan projection dipastikan|Struk/PDF T017 → T023|
|09|Tanggal11Nov+30→11Des untuk servis dan penjualan, nolgaransi, koreksi tetap tanggalnota|PASS suite Invoice/Sales + independent service/sale|Daftar H-3, hubungi/hilang/dashboard T018 → T023|
|10|Pickup membuat Diambil/tanggal; tracking menyebut sudah diambil; pengulangan/invalid status ditolak|PASS suite Invoice/Tracking, independent service, browser collect+proof|—|
|11|Cancel Diterima/Dikerjakan; larangan Selesai dan transisi invalid|PASS suite Invoice/Domain; browser membatalkan order Dikerjakan; trackingBatal|Pengecualian dari laporan T019 → T023|
|12|Token exact/invalid; seluruhstatus; rincian jual/total/garansi setelah issue; noHP/alamat/modal/snapshot; noindex/no-store|PASS suite Tracking, independent service, browser seluruhstatus/large/zero dan MySQL-controller casefold check|Tombol/unduhPDF T017 → T023|
|13|URL `https://wa.me/6281234567890?text=…`, nomor/linktracking/Unicode terenkode, receipt/invoice/tanpagaransi, adminonly|PASS suite Tracking+independent service, review linkaksi UI. URL redirect diamati lokal; tidak membuka WA/mengirim pesan|Tombol dashboard pengingat T018 → T023; helper template sudah diuji|
|15|Jual2dari3→1, shortage, rollback, snapshot, warrantyHP, WITA number, hapus/restoresekali/nonaktif/overflow|PASS suite Sales; independent stock; browser create/delete+proof; MySQLrace stock1|Dokumen penjualan T017 → T023|
|16|Restok+2 mengganti cost, stokbertambah, penjualanlama costtetap; tidakExpense|PASS suite Stock/Sales; independent stock; browser restock+proof|—|
|18|CRUDpengeluaran, amount>0/uint32, filterinklusif/sameday/reversed/clear/recover, totals, MySQL-datebounds|PASS suite Expenses; independent expenses; browser+proof/dialogs, maksimum4294967295|Pengaruh laba bersih di laporan T019 → T023|
|21|Koreksi harga mengubah total, tanggalnota/pickup/status tetap; stale/double save ditolak|PASS suite Invoice; independent service; browser correction+collect/proof|Perubahan laporan T019 → T023|
|22|Unrepairable+Jasa Biayacek50000 menghasilkan total50000/modalJasa0; juga Rp0 valid dan labelpublik|PASS tes independen fee9assert dan suite/edge browser|Omzet laporan/dokumen T017/T019 → T023|
|24|Semua layar M2/M4 tersedia diperiksa360/768/1280, sesuai shell/font/mockup/aksi kiri/menu|**FAIL F01** pada nota besar. Layar normal dan tracking/M4 tidak melebar; tabel↔baris dan aksi kiri teramati|Fitur downstream diperiksa pemilik/T023, perangkatfisik T025|
|25|TTSsave/issue/correction bilah sticky, numeric/tel inputmode, ukuran target di HP360|NormalPASS emulasi; tombol48px dan numeric/tel terbukti. **FAIL F01** pada bilah issue nominal besar|Keyboard/perangkatHP nyata dan printer T025, tidak diklaim lewat Chrome|

Suite juga mencakup invalid/inactive/forged IDs, stale mount/hydration, monetary overflow dan transaction failure rollback. Risiko tersebut dijalankan ulang dalam suite independen ini, bukan hanya dibaca dari laporan developer. Tidak semua memiliki screenshot terpisah.

## Browser, desain, dan bukti visual

Chrome157.0.8103.79, CDP headless melalui helper desain proyek, profil/proses/CDPport0 milik task. Server PHP8.3 loopback8189 memakai SQLite baru0600 di direktori0700. Private config cache mem-pin storage/session/views/cache; key/password hanya `private.json`0600 di runtime privat. `fixture-config.log` memverifikasi session/view privat. Tidak GUI/CUA/Orca.

Alur normal browser: teknisi login→TTSbaru→sukses→edit→detail/daftar; admin assign→dua baris Part/Jasa→issue1100000→koreksi1050000→pickup→list; cancelorderlain; tambah/editbarang→restok3→5 tanpaExpense; jual2→3 dengan cost snapshot100000→hapus via dialog→stok5; pengeluaran150000→edit175000→hapus fixture setelah cancel/Escape. Tracking customer menampilkan total1050000 tanpa modal700000 atau HP/alamat. Semua DB proof berasal SQLite task sendiri.

Setiap nama berikut memiliki `-360.png`, `-768.png`, `-1280.png`: `tts-create`, `tts-success-tech`, `tts-edit-tech`, `tts-detail-tech`, `tts-list-tech`, `tts-detail-admin`, `tts-assigned`, `invoice-form-normal`, `invoice-issued`, `invoice-correction`, `invoice-collected`, `tts-list-admin`, `tts-cancelled`, `stock-create`, `stock-list`, `stock-edit`, `stock-restock`, `stock-history`, `sale-create`, `sale-detail`, `sale-list`, `sale-delete-dialog`, `expense-create`, `expense-list`, `expense-edit`, `tracking-collected`. Tambahan edge/status dan published menggunakan prefix yang sama dengan case ID di JSON.

**78gambar normal dibuka lewat9contact sheet**,24edge lewat3contact sheet,18mockup lewat3contact sheet; gambar cacat/negative/lower dibuka sendiri. Acuan persis: mockup `tts.html`, `tts-baru.html`, `tts-detail.html`, `tts-sukses.html`, `tts-rincian.html`, `tracking.html`. Stok/penjualan/pengeluaran tidak memiliki mockup persis; memakai TTS list/form/rincian sebagai **padanan terdekat**, bukan mengklaim mockup khusus tersedia. Perbedaan konten/kategori/nomor fixture wajar. Print/PDF/QR pada mockup masih task downstream dan tidak dinilai seolah sudah tersedia.

Nominal normal mengikuti IBM Plex, bingkai L/sidebardesktop, navigasibawahHP, form/target48/44px, tabeldesktop/daftarHP, aksi palingkiri dan menu portalled. Probe normal360 tidak menemukan target link/button main di bawah44×44; bukti input mode dan bilahaksi ada di JSON. Formbawah nota360 (`invoice-lower-360.png`) membuktikan garansi/checkbox/ringkasan dapat dicapai, tombol koreksi tetap terlihat.

Catatan keterbacaan nonblocking: pada `sale-large-list-360.png`, totalintegerbesar membuat ID/tanggal membungkus beberapa baris. Semua digit tampak tanpa pemotongan/overlap; ini dicatat untuk penilaian owner, tidak disamakan dengan F01 yang benar-benar keluar viewport.

## MySQL nyata dan preservasi

Grant eksklusif coordinator diperoleh sebelum akses. Inventory20tabel disimpan privat dengan seluruh baris baseline; engine workflow InnoDB. Periode **3101** dicek kosong terlebih dahulu; clock2031-01-01 WITA aman untuk TIMESTAMP MySQL sebelum2038. Tidak migrate/reset/drop/truncate/restore/delete baris lama.

Fixture append-only unik menambah3user (2admin berbeda+1teknisi),1kategori,1produkstock1. Dua proses `CreateServiceOrder` mulai hampir bersamaan menghasilkanTTS3101-001/002. Dua admin `CreateSale` mulai hampir bersamaan menghasilkan1success/1shortage, stok0,1sale/1item, orphan0, JLsequence1/TTSsequence2. Test-only event memberi jeda250ms pada update produk sendiri untuk memaksa kontensi, tanpa mengganti aksi/SQL bisnis. `mysql-tts-*.json`, `mysql-sale-*.json`, `mysql-proof.json`.

Kemudian `DeleteSale` menghapus **hanya sale milik fixture** dan mengembalikan stok1; panggilan kedua404/stocktetap1. `mysql-final.json` memverifikasi semua baris baseline byte-identik sesudah penghapusan. Delta dipertahankan: users+3/category+1/product+1/TTS+2/number_sequences+2; sales/item delta0; tabel lain0. Counter periode lama tidak diubah. Tidak ada row existing yang dihapus.

Grant read-only terpisah setelah browser selesai: kolom tracking `utf8mb4_unicode_ci`; rawquery token swapcase memang menemukan IDfixture yang sama. `TrackingController::show` nyata menolak swapcase404 dan exact200, noindex/no-store/private projection. Before/after20tabel identik; baseline lama juga masih utuh (`mysql-tracking.json`). Tidak memakai HTTP kernel/session yang bisa menulis database. Token/body/URL penuh tidak dimasukkan ke bukti.

## Kegagalan harness yang dipertahankan

- `independent-initial.log/xml`: peeredit dihardcode `/ubah` sehingga404, padahal route aktual `/edit`; diperbaiki menjadi route yang benar dengan **expect403 tetap**. Expense dicari dengan `where(spent_on,date)` padahal SQLite menyimpan cast tanggal sebagai timestamp; diganti `whereDate`. Hanya dua kasus belum selesai diulang, lalu2PASS92assertion; stock1PASS13assertion tidak diulang.
- `fee-initial.log/xml`: tes AC22 baru kehilangan import `CreateServiceOrder` (sebelumnya unused import dihapus formatter). Import ditambah; hanya kasus fee diulang, PASS9assertion. Tidak mengubah produk.
- `interaction-error.json`: setelah11observasi selesai, helper logout tidak menemukan tombol tersembunyi pada layout360. Chrome/profil baru di `guard-continuation.mjs` menyelesaikan hanya phonekeyboard+11routeguards; tidak mengulang filter/dialog/delete.
- `visual-check.mjs`/`visual-results.json` adalah eksplorasi awal state Livewire.set; inputmoney DOM tertahan nilai lama sesudah morph. Ini bukan bukti final trusted-input. Repro `trusted-visual-check.mjs` mengetik qty/harga/modal aktual dan membuktikan F01 kembali.
- Metadata awal `screen.width` keliru bernama requestedWidth. Di trusted JSON/script nilainya dipertahankan sebagai `screenWidth`, sedangkan requestedWidth ditetapkan dari konfigurasi nama kasus. Geometri/screenshot tidak diubah. `visual.log`/`trusted-visual.log` awal membandingkan documentWidth dengan innerWidth yang sudah melebar di mobile, sehingga stdout melewatkan overflow360; penilaian final memakai **requested360/visualViewport360**, bukan innerWidth425. `final-pins.json` mencatat koreksi ini.

## Batas dan handoff

Resource browser/server telah ditutup: listener8189false, ownedChrome0; `resource-release.json`. MySQL connections berakhir bersama proses helper dan grant eksklusif dilepas ke coordinator. SQLite/provenance/baseline privat dipertahankan; tidak membersihkan data existing. Tidak ada push/main/deploy atau perubahan status ledger oleh tester. Hanya evidence scope T016 yang dikomit.

Tidak dijalankan: cetak/PDF T017, dashboardpengingat T018, laporan/omzet/lababersih T019, QAakhirT023, Android/printerfisik T025, deploymentT024–T025. Itu prasyarat downstream yang belum tersedia/berizin, bukan PASS maupun FAIL fitur hilir. `RUN_MYSQL_DOMAIN` lama tetap guardedskip; realMySQL di atas menggantikan kebutuhan race T016 dengan fixture preservatif, bukan mengklaim skip itu lulus.

Risiko tersisa yang nyata: F01 wajib diperbaiki T010 pada candidate baru; bukti lulus yang tidak terdampak dapat dipertahankan dengan pin/kondisi sama. Bukti lengkap ini **tidak menerima fitur otomatis**.
