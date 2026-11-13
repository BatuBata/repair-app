# T016 A2 — retest independen F01 dan kandidat T015

**Verdict: PASS untuk cakupan T016 yang tersedia. F01 ditutup berdasarkan observasi independen baru.** Penerimaan fitur/merge tetap keputusan coordinator. T016 A1 FAIL dan seluruh bukti awal tetap dipertahankan; hasil A2 tidak menghapus riwayat itu.

`checked_revision` dan input T015: **22e26d244dee2c361b7c21ea3d740e500fc83c9a**. Workspace `/Users/muzib/Projects/web/repair-app`, branch `codex/qa-m2-m4-a2`; HEAD awal `b41d9416a64f1df9ef0c65883ee62cd515547afd` hanya dokumen ledger di atas kandidat. App source immutable sepanjang retest. Tiga view final cocok T010 A2 `291fa2451c012c2d07097b8166a8c4317305aa97`.

Ponytail **full** tetap aktif dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md` yang telah dibaca awal sesi. Tester native `/root/t016_qa` tetap terpisah dari implementer/integrator T010/T015. Sol high diminta; backend efektif tidak diekspos receipt. Kontrak T016 terbaru, handoff/manifests T010 A2 dan T015 A2, Project Delivery QA, DESIGN/mockup dipakai. Tidak patch app/test proyek/ledger, tidak nestedworker/push/main/deploy, tidak rebuild/install/dependency update.

## Pemeriksaan baru pada A2

| Pemeriksaan aktual | Hasil |
|---|---|
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' RUN_MYSQL_DOMAIN=0 /opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --colors=never --log-junit docs/evidence/T016/A2/suite.xml` | **157PASS,1guardedMySQL SKIP,1585assertions** dari158tes, satu run baru independen pada kandidat ini. `suite.log/xml`. |
| `node docs/evidence/T016/A2/browser-retest.mjs` | **75state PASS,0JS exception**: nominal normal/besar/negatif pada issue/published/correction, ditambah nominal14/15/19digit pada issue/published, setiap state di360/768/1200/1279/1280. |
| Sembilan penyimpanan melalui tombol nyata pada360 | Tiga issue +tiga correction +tiga issue kasus panjang nominal. `database-proof.json` mem-pin hasil decimal sebagai **string** PHP, tanpa pembulatan JSON Number JavaScript. |
| Audit sumber/artefak/reuse sebelum dan akhir | **211sumber cocok target**,208identik A1 dan tepat3view berubah; build14/input73/font5 cocok manifest A2. Tidak rebuild. `preflight-pins.json`, `final-pins.json`, `reuse-proof.json`. |
| Pint scoped helper PHP A2 | **PASS**, `fixture-pint.log`, `pint-own-final.log`. Tidak mengulang suite hanya karena formatter. |
| Review visual baru | **75gambar utama** dibuka lewat10contact sheet; empat gambar penting dibuka ukuran penuh. Tambahan18gambar top desktop tersedia untuk inspeksi. `review-proof.json`. |

Pemeriksaan developer T010/T015 dibaca sebagai petunjuk, bukan sebagai PASS tester ini. Tidak mengulang315probe developer dan tidak mengklaimnya sebagai observasi sendiri.

## F01 — expected dan actual baru

Pengisian memakai klik CDP dan `Input.insertText` pada field sebenarnya, mulai dari default0. Tidak memakai Livewire.set atau penggantian teks DOM untuk membuktikan kasus berikut. Field deskripsi awal **234karakter** (valid≤255); koreksi234karakter. Seluruh pembacaan angka memakai teks DOM atau string hasil PHP.

- Kasus asli: qty**2147483648** ×harga/modal**4294967295** → total/modal **9223372034707292160**, laba0.
- Negatif: qty sama, harga0, modal4294967295 → total0 dan laba **-9223372034707292160**.
- Normal:1000000/modal700000; koreksi harga menjadi1100000, laba400000.
- Kasus14digit:99999×1000000000 = **99999000000000**;15digit:999999×1000000000 = **999999000000000**. Ini transaksi aplikasi nyata, bukan probe CSS saja.
- Batas19digit **PHP_INT_MAX9223372036854775807** disusun dari tiga baris valid:2147483647×4294967295 +1×4294967295 +1×2147483647. Nilai tepat tersimpan/dipublikasikan sebagai string decimal, bukan float.

| Subkasus F01 | Actual A2 | Bukti |
|---|---|---|
| Form360 besar: dulu layout425/CTA keluar | requested=inner=visual=document**360**; grand kanan327/font17,4222px; nilai utuh satu baris; total bilah utuh dan tombol48px kanan344/bawah824, di atas nav836 | `large-issue-360.png`, `large-correction-360.png`, `browser-results.json` |
| Form1280 besar: dulu document1342 | seluruh viewport/document**1280**; grand kanan1218/font16px, teks utuh; panel/CTA di dalam layar | `large-issue-1280.png`, `large-correction-1280.png` |
| Published360 besar: dulu document408 | semua ukuran**360**, grand kanan344/font19,437px; phone grid menggunakan lebar penuh dan qty/harga/modal utuh | `large-published-360.png` |
| Published1280 deskripsi panjang: dulu angka pecah | Qty2147483648×, hargaRp4.294.967.295 dan modal berada pada satu baris nowrap tanpa memenggal digit; deskripsi membungkus sendiri | `large-published-1280.png` |
| Breakpoint1200/1279/1280 | Heading Deskripsi satu baris; lebar input334/413/80px (seluruhnya≥72). Form bertumpuk sampai1279 dan dua kolom1280; grand tetap muat | `*-issue-top-1200/1279/1280.png`, metrics JSON |
| Ukuran nominal dan font | Semua75grand satu text line, font16–28px; seluruh kasus normal tetap28px, termasuk1200/1279/1280. Semua225nilai grand/modal/laba utuh satu baris dan dalam viewport | `review-proof.json`, `browser-results.json` |
| Koreksi dan angka negatif | Ketiga correction menyimpan tanpa mengubah invoice_date/status/picked_date. Laba negatif string tepat dan merahrgb(196,36,42) pada lima lebar | `database-proof.json`, `negative-*.png` |

Assertions membandingkan ukuran dengan **requestedWidth yang dikonfigurasi**, bukan screen.width atau innerWidth yang mungkin sudah melebar. DOM Range memeriksa jumlah baris/lebar teks nyata; tidak ada ancestor grand dengan overflow hidden/clip/ellipsis sebagai jalan pintas. Sumber tiga view juga ditinjau: hitungan/validasi backend dan number_format tidak berubah; hanya layout, font adaptif berdasarkan panjang format, grid HP dan nowrap sel. Nilai/input sah tidak dibatasi untuk menyembunyikan masalah.

Ukuran tombol issue/correction HP48px (≥44), seluruh tombol berada di dalam viewport dan di atas bottomnav. Bilah total besar membungkus **tombol ke baris berikutnya**, sementara angka utuh; ini teramati dan berhasil dipakai menyimpan. Input panjang tetap native single-line yang dapat digeser saat diedit; seluruh teks nilai pada published/ringkasan ditampilkan utuh.

Review membandingkan layar yang sama dengan mockup `tts-rincian.html` dan `tts-detail.html` yang sudah dirender pada A1. **25berkas DESIGN/mockup** masih hash-identik dengan target A1; acuan A1 tetap berlaku. Shell/IBM Plex/aksi hitam dan font28 untuk nominal normal dipertahankan. F01 dinyatakan selesai hanya berdasarkan data/gambar A2 ini.

## Bukti independen A1 yang dipakai kembali

Asal: tester yang sama, checked_revision **545b52a275687703e6046270afb45e01a0334922**, evidence commit **ffb4e0975e20a22e948749580aaf0901006fef33**. Mapping case IDs, alasan dan SHA bukti ada di `reuse-proof.json`; dua belas bukti kunci dibandingkan byte-identik dengan commit asal. Tidak menamai reuse sebagai run baru.

Syarat yang diperiksa sendiri:

- Tepat3perbedaan pada211source manifest: invoice form, invoice published, CSS TTS detail. **208sumber identik**, termasuk actions/services/models/controllers/routes/schema/tests/config/dependency/sharedCSSJS dan tracking publik.
- Isi TTS detail **di luar style identik**. Perubahan selector totals tidak aktif pada state teknisi/received/cancelled/success yang tidak merender angka keuangan. Hook WA admin/proyeksi data tidak berubah. Fullsuite A2 baru juga menjalankan pengujian akses/privacy/render.
- Artefak hasil build A2 **14/14 byte-identik** dengan artifact A1 **6e43a91e8f08f49aae6ab334fa46dde48fcc52c1900d1a0e11f60344e2556f36**, entry `app-CxA26Lmt.css`;5font runtime sama.73input build sesuai manifest A2.
- PHP8.3.30, Node22.23.2 dan Chrome157.0.8103.79 sama. Empat hash vendor kritis yang sudah dipin di T008 A2 cocok, termasuk installed.json/Livewire/framework. Instalasi vendor yang sama tidak memiliki berkas lebih baru daripada akhir A1; fingerprint seluruh12377berkas vendor sekarang dicatat. Tidak mengarang digest vendor lengkap A1 yang memang belum disimpan.
- Root.env hash cocok preflight T010 A2. Browser A2 memakai pola config/session/cache/views/storage privat yang sama dengan A1; key/password/fileSQLite baru memang perbedaan fixture yang disengaja. MySQL tidak diakses/diubah A2.

| Cakupan acceptance T016 | Bukti dipertahankan + bukti baru |
|---|---|
|AC02–07: validasi/normalisasi TTS, penomoranWITA, hak teknisi/edit/assign/terbitnota|A1 independent service +suite/browser TTS/assign; fullsuite A2 baru menguji render/guards/aturan gabungan; issue/correction A2 baru.|
|AC08/12/13: totalPart/Jasa, privacy tracking/HTML/snapshot, exacttoken/WAmanual|A1 independent service/Tracking suite/browser/HTTP403 dan MySQL casefold controller; controller/helper/routes/publicview/projection identik; fresh fullsuite A2.|
|AC09/10/11/21: tanggalgaransi, pickup/cancel/transisi/stale correction|A1 independent service+suite/browser/proof; backend identik. A2 tiga correction baru membuktikan tanggal/status/pickup tetap dan total benar.|
|AC15/16: stok/snapshot/nomor/shortage/restore/restoktanpaExpense|A1 independent stock13assert, suite+browser, MySQL dua admin berebutstock1 dan TTS numbering; services/schema/actions/config identik, bukti tetap valid.|
|AC18: CRUDpengeluaran, rentanginklusif/sameday/reversed/clear/recover, total/datebounds/dialogfocus|A1 independent expenses+suite/browser; source/asset layar tidak berubah.|
|AC22: BiayacekRp50000/modalJasa0, unrepairable/Rp0|A1 independent fee9assert+suite/publicbrowser; backend/publicview identik. A2 harga0 dan negatif juga baru tersimpan utuh.|
|AC24/25 tersedia|A1 layar TTS/stok/penjualan/pengeluaran/tracking dan interaksi yang tidak terdampak dipertahankan. Presentasi issue/correction/published A1 **tidak direuse sebagai PASS**;75state A2 di atas menggantikannya. F01 sudah ditutup.|

Empat kasus tambahan independen A1 **114assertions** tetap dipakai (service58+expenses34+stock13+fee9). Ini terpisah dari **1585assertions baru** A2; tidak dijumlahkan seolah semua dijalankan ulang. Race MySQL asli, preservasi baseline20tabel, restore sekali dan exacttoken pada collationCI tetap merujuk laporan A1. Tidak rerun/reset/memutasi fixture/counter3101 yang dipertahankan privat.

## Isolasi, batas, dan penutupan

Runtime A2 baru0700 di `/private/tmp/t016-a2-*`; credential/config/SQLite0600, cache/session/views/storage privat terverifikasi `fixture-config.log`. Port8189 dicek sebelum server; grant phase dilaporkan coordinator sebelum browser. Profil Chrome/process/TASTE subtree sendiri, CDPport0, headless; tidak GUI/CUA/Orca. Tidak ada MySQL resource atau akses A2.

Chrome dan server ditutup; `resource-release.json`: listener8189false, ownedChrome0/profile0. Fixture/provenance privat dipertahankan, tanpa cleanup data existing. Final source211/build14 cocok (`final-pins.json`). Hanya evidence `docs/evidence/T016/A2/**` yang dikomit, TODO dan app tidak disentuh.

Tidak dijalankan ulang:114assertions independen A1, seluruh78normal/24edge screenshot A1, MySQL/race/casefold, PHPStan/globalPint yang input relevannya tidak berubah; mapping reuse di atas. Pint helper baru tetap dijalankan. Suite/build tidak diulang setelahPASS; build baru milik T015 A2 dibuktikan manifest, tester tidak membangun ulang.

**Belum diuji dan tidak diklaim PASS:** dokumen/PDF T017 (AC08/12/22 bagian dokumen), dashboard/pengingat T018 (AC04/09), laporan T019 (AC04/11/18/21/22), QA akhirT023, Android/printerfisik T025 dan deploymentT024–T025. Seluruh AC akhirv1 tetap wajib setelah task pemilik tersedia. Tidak ada risiko F01 yang tersisa teramati pada lima lebar/Chrome yang diperiksa; compatibility/keyboard/perangkat fisik tetap gate berikutnya.
