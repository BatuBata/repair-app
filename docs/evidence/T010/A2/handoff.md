# T010 A2 — F01 layout nota

**Developer self-check PASS; belum penerimaan QA independen.** Input T009 tetap `12124a71bd24b30b7f343941bacd41315a4281f7`. Base `49a9891ef607284fc31f0338330358aca084731f`; hash sumber yang benar-benar diuji ada di `source-after.json`, commit hasil disampaikan dalam result worker. A2 adalah upaya implementasi kedua, tanpa reset budget. T016 A1 FAIL `ffb4e0975e20a22e948749580aaf0901006fef33` merupakan konteks temuan, bukan dependency baru; bukti A1/QA lama tidak diubah.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, DESIGN dan mockup `tts-rincian.html`/`tts-detail.html` berlaku. Model diminta Sol high; backend efektif tidak diekspos oleh receipt native.

## Perubahan dan bukti

Hanya tiga view: form ringkasan/bilah aksi, CSS totals detail, serta include nota terbit. Breakpoint stacking form diperluas1199→1279 agar kolom deskripsi tetap layak sebelum referensi desktop1280. Grand total memakai lebar container dan panjang teks hasil `number_format` yang sama untuk menyesuaikan font16–28px; tidak memakai ambang jumlah digit. Nominal normal tetap28px. Bilah HP boleh membungkus agar total utuh dan tombol48px tetap di atas navigasi. Tabel terbit memakai sel angka nowrap; deskripsi membungkus dan grid HP tidak menyisakan kolom aksi kosong. Tidak ada angka dipotong/disembunyikan, pembatasan input valid, perubahan hitungan/formatter/backend, dependency, migrasi, atau global CSS/JS.

Sebelum: QA trusted input qty2147483648 × harga/modal4294967295 →9223372034707292160, form360 melebar425 dan form1280 melebar1342; published360 melebar408; published desktop memenggal angka sel. Sumber/gambar/actual FAIL tetap di `docs/evidence/T016/A1/README.md`, `trusted-visual-results.json`, `published-results.json`. Gambar FAIL360 dibuka sebelum penilaian hasil.

Sesudah final: requestedWidth, visual viewport, innerWidth, documentWidth sama360/768/1280; seluruh grand satu baris, tombol dan sel di dalam layar. Contoh form360 grand kanan327/font≈17px, form1280 kanan1218/font16px; published360 kanan344/font≈19px. Lihat actual presisi di `final-grand-results.json`. Tombol issue/koreksi HP48px, di atas nav; angka qty/harga/modal desktop utuh. Deskripsi transaksi yang benar-benar tersimpan240karakter (valid≤255). Laba negatif `Rp-9.223.372.034.707.292.160` tetap merah `rgb(196,36,42)` dan muat.

- `node docs/evidence/T010/A2/browser.mjs`: 27 state visual normal/besar/negatif × issue/koreksi/published ×3lebar selesai pengukuran; input uang CDP nyata dari0→target, bukan Livewire.set-only. Issue dan koreksi disimpan melalui tombol nyata. Terminal assertion panjang fixture salah234 vs aktual240; log/error dipertahankan (`geometry-*`).
- `node docs/evidence/T010/A2/proof-continuation.mjs`: hanya bukti DB dan satu koreksi tambahan, **PASS**. Decimal server disimpan sebagai string pada evidence agar JSON Number JS tidak membulatkan: total/modal tepat9223372034707292160. Tanggal/status/rows sesudah koreksi identik. Tidak mengulang27state.
- `node docs/evidence/T010/A2/final-grand.mjs`: **15state PASS**, normal +grand besar issue/koreksi/published3lebar setelah font final; **315probe layout** nilaiRp0/normal, setiap panjang integer1–18digit dan PHP_INT_MAX19digit, termasuk14/15digit. Probe CSS sementara diberi label layout-only, bukan transaksi; screenshot diambil sebelum probe dan DOM dipulihkan.
- `node docs/evidence/T010/A2/boundary-check.mjs`: grand/layout **4state/84probe PASS**, tetapi review gambar menemukan heading Deskripsi pecah dan input≈26px pada1200. `boundary-review.json` mencatat **FAIL manual** sebelum konstanta breakpoint diperbaiki.
- `node docs/evidence/T010/A2/breakpoint-final.mjs`: **6state/126probe PASS** pada1200/1279/1280 normal+besar sesudah1199→1279. Heading satu baris, input deskripsi≥72px, grand utuh; normal28px. Gambar top1200/1280 dan grand1279 dibuka. Tidak mengulang alur lain.

JS exception0 pada run geometri, kelanjutan, font final dan kedua boundary. Gambar hasil besar360/1280, koreksi768, published360/1280, negatif dan normal dibuka; contact sheets serta mockup persis dibuka. Tampilan normal mempertahankan shell/IBM Plex, field, angka28px dan bilah aksi mockup; jumlah baris/nomor/status fixture berbeda wajar. Iterasi antara yang grand-nya masih terbelah dipertahankan dalam screenshot/`grand-results.json`; bukti final memakai prefix **final2-** dan **boundary-**.

## Perintah tes aktual

Semua PHPUnit memakai `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' RUN_MYSQL_DOMAIN=0` dan PHP `/opt/homebrew/opt/php@8.3/bin/php`:

| Perintah | Actual |
|---|---|
| `vendor/bin/phpunit --colors=never tests/Feature/Invoice tests/Feature/Tts tests/Feature/Tracking` | **42PASS328assertions**, sebelum penyempurnaan font/atribut length final; `targeted-tests.log` |
| `vendor/bin/phpunit --colors=never --filter 'zero_total_unrepairable_default_warranty_and_dynamic_rows\|direct_and_livewire_actions_recheck_current_admin_and_technician_gets_no_money\|published_correction_changes_amounts_without_changing_original_date\|public_tracking_is_exact_and_discloses_only_public_information\|whatsapp_is_admin_only_and_encodes_receipt_and_invoice' tests/Feature/Invoice tests/Feature/Tracking` | **5PASS85assertions pada markup/font final, sebelum satu konstanta breakpoint CSS terakhir**; render Form/Detail, role/privacy/snapshot, koreksi tanggal, tracking/WA guard; `final-render-tests.log` |
| `vendor/bin/pint --test docs/evidence/T010/A2/fixture.php` | **PASS** final; `pint-final.log` |
| `node --check` runner terkait; `git diff --check` | **PASS** |

Angka42 dan5 saling tumpang tindih, bukan47kasus unik. Suite A1/T016157PASS1guardedSKIP1585assertions adalah sejarah sebelumnya, **bukan rerun A2**. Tidak ada unit assert yang meniru CSS.

## Preservasi dan batas

`source-before.json`/`source-after.json`: hanya tiga view berubah. Detail TTS identik di luar `<style>`, termasuk hook WA admin. Controller tracking/WA, helper WhatsApp, routes tracking dan public tracking view hash-identik; Tracking privacy/adminhook tes final lulus. `.env`/AGENTS tidak berubah; TODO perubahan reservation milik coordinator tidak distage. Empat belas build file identik artefak T015 A1 `6e43a91e8f08f49aae6ab334fa46dde48fcc52c1900d1a0e11f60344e2556f36`, tanpa rebuild. T015 berikutnya harus membangun kandidat baru sekali.

Private SQLite/config/env0600 di runtime0700; cache/session/views/storage dan Chrome/process/profile/CDPport0 terpisah. `resource-release.json`: server exit130, listener8189false, Chrome milik task0, profile0; fixture privat dipertahankan. Pemeriksaan `ps` awal ditolak sandbox, read-only escalation berhasil; tidak memengaruhi aplikasi. Tidak menyentuh MySQL/data existing, main, push/deploy atau ledger.

Tidak dijalankan ulang: fullsuite, PHPStan global, MySQL/race, build dan layar tak terdampak. Tidak diuji Android/printer fisik; CSS container units diperiksa pada Chrome157 headless. Discovery boundary1200 diselesaikan dalam A2 dengan satu konstanta scoped; riwayat gambar sebelum perbaikan dipertahankan. Coordinator perlu revalidasi T011, reintegrasi/build T015, lalu **tester lain T016 A2** menilai perbaikan ini. Developer tidak menerima F002/F003/main sendiri.
