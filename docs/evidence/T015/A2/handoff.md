# T015 A2 — verifikasi ulang integrasi M2/M4

**PASS integrator; belum penerimaan QA independen.** Branch `codex/m2-m4-candidate-a2`, base pemeriksaan `cf172e9c35999a393362859461b61ae5c898ea45`. Commit evidence hasil mengikat sumber aplikasi yang hash-nya sama; SHA final dikembalikan dalam result worker untuk T016 A2.

Input hasil tepat: **T011 `291fa2451c012c2d07097b8166a8c4317305aa97`**, **T013 `3c35dd1118121d02cca7abb0ae99b78baeb30634`**, **T014 `aef8ffc75b11344b10ddaabe9ccae4a7e1776e45`**. T011 adalah revalidasi coordinator sesudah T010 A2, tanpa implementasi baru (`docs/evidence/T011/revalidation-after-T010-A2.json`). Ketiganya sudah ancestor dan sumber owned cocok byte/hash; tidak perlu merge tambahan. `input-source-proof.json`/`preflight.json`.

Ponytail **full** tetap berlaku, dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; Project Delivery/QA/coordination dan DESIGN existing digunakan. Sol high diminta, backend efektif tidak diekspos receipt. Ini integrator yang pernah mengimplementasi T010, **bukan tester T016**. Tidak ada worker tambahan atau patch aplikasi/attempt tersembunyi.

## Pemeriksaan baru pada kandidat sekarang

| Perintah aktual | Hasil |
|---|---|
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' RUN_MYSQL_DOMAIN=0 /opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --colors=never --log-junit docs/evidence/T015/A2/suite.xml` | **157PASS,1guardedMySQL SKIP,1585assertions** dari158tes; sekali, `suite.log/xml`. Ini run baru pada markup/font/breakpoint1279 final. |
| `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint --test` | **PASS global**, termasuk helper PHP evidence T010 A2/T016 existing; `pint-global.log`. |
| `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpstan analyse --memory-limit=512M --no-progress --debug` | **PASS0error**, `phpstan.log`. |
| `npm run build` — Node22.23.2/npm10.9.8 | **PASS**, satu build produksi baru pada input Blade sekarang; `build.log`. Tidak install/update. |
| Hash/source/ancestry/control assertions dan `git diff --check` | **PASS**; manifest dan proof terkait. |

Tes MySQL `test_mysql_fresh_seed_and_parallel_document_creation` memang guardedskip, bukan PASS konkurensi: env run ini0. Tidak mengakses MySQL atau menjalankan reset/migrate:fresh. Bukti nyata dan preservasi fixture T016 A1 tetap sejarah terpisah; bukan run baru A2. Tidak mengulang suite/build setelah PASS.

## Sumber dan artefak

`source-manifest.json` mem-pin **211sumber**. Diff aplikasi terhadap T015 A1 `545b52a275687703e6046270afb45e01a0334922` tepat tiga Blade T010 A2: invoice form, invoice published, TTS detail. **208sumber lain identik**, termasuk backend/actions/controllers/routes/schema/tests/dependency serta shared CSS/JS. Hash tiga view cocok T010 A2 final; font adaptif berdasarkan panjang teks dan stacking sampai1279 tetap dipertahankan. Tidak ada migrasi/model/dependency/business patch baru.

`assets-manifest.json` mem-pin **14hasil build,73input build,5font runtime**. Build baru menghasilkan **14/14 byte-identik**, tanpa file berbeda/hilang, dengan T015 A1/T016 A1 dan arsip privat yang diverifikasi sebelum build. Artifact SHA256 tetap **`6e43a91e8f08f49aae6ab334fa46dde48fcc52c1900d1a0e11f60344e2556f36`**, CSS `assets/app-CxA26Lmt.css`. Perbedaan input build tepat tiga Blade tadi; CSS scoped dirender server sehingga output bundler tidak berubah. Ini hasil build aktual baru, bukan mengklaim bundle lama sebagai build kandidat baru.

Warning optional fontaine dan lima URL font IBM runtime tetap sama; lima berkas tracked/runtime hash-identik dan pemuatan IBM Plex telah dibuktikan pada smoke yang direuse. Tidak mengubah dependency untuk warning tersebut. Arsip `/private/tmp/repair-t016-a1-build-qjg314_u/build`, worktree, bukti FAIL T016 A1 dan iterasi T010 A2 tetap dipertahankan.

## Smoke yang direuse, bukan dijalankan ulang

**Tidak membuka browser/server baru.** Brief mengizinkan reuse bila build identik; syaratnya dibuktikan, mapping rinci di `smoke-reuse.json`:

- T015 A1: TTS→assign→nota→tracking, stok→jual→snapshot→hapus→stok pulih, pengeluaran tambah/edit, serta teknisi tanpa nominal/403. Backend/shared assets sumber alur itu identik. Evidence browser/DB A1 tetap dipakai untuk alur, bukan dianggap observasi browser baru.
- T010 A2: tiga view berubah cocok SHA final. Bukti input CDP nyata0→target, issue/koreksi dengan tombol nyata, total integer tersimpan9223372034707292160 dan tanggal koreksi tetap; normal/besar/negatif360/768/1280, font final15state/315probe, breakpoint final6state/126probe. Gambar final `final2-`/`final-breakpoint-` dipakai untuk presentasi berubah; screenshot nota lama T015 A1 tidak dipakai seolah membuktikan layout baru. Riwayat runner/manual FAIL tetap ada.

Fullsuite baru di atas sekarang menguji seluruh sumber final, menggantikan kebutuhan mengulang subset developer42 dan5tes. Tidak menjumlahkan hasil tumpang tindih atau mengklaim reuse sebagai QA independen. **Observasi browser baru T015 A2:0**.

## Kontrol dan batas

`.env`/AGENTS hash sama sejak preflight; AGENTS terbaru coordinator tentang fixture T016 dipertahankan. TODO tidak ditulis/distage integrator. Main tetap **`b5ae99a72e076fe6a5de5c5f3459ae01de666454`**. `control-proof.json`/`resources.json`: listener8189 tidak aktif; browser/server tidak dimulai, tidak ada resource baru untuk dilepas. Tidak mengubah DB existing, membuang arsip/worktree, merge main, push atau deploy.

Tidak dijalankan ulang: browser matrix, MySQL/race dan perangkat Android/printer fisik. Dashboard/laporan/PDF hilir tidak diklaim lulus. T016 A2 harus dilakukan **tester independen** atas kandidat dan artefak terpin ini sebelum penerimaan F002/F003 atau merge main; hasil integrator ini tidak melepas gate tersebut.
