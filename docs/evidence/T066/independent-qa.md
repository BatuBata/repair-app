# T067 — QA independen catatan pemeriksaan tunggal

Verdict: **PASS** untuk perubahan T066 pada main lokal, base `7320a61eec8c150daceee3a98bca461b8932b124`, checked revision `sha256:d7c83aff89c3f05c8a075c308a1efdc754ead4bf38703d2b68471a06f1b33656`. Input T066: `source-manifest.json`; ketujuh SHA-256 source cocok sebelum dan sesudah pemeriksaan. Tidak ada temuan penghambat.

Tester sesi terpisah memakai Ponytail **full**, sumber `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`, serta project-delivery dan `references/qa-and-release.md`. Runtime diwariskan; tidak melakukan atau mengklaim pergantian model. PRD D-39 dan kontrak T066/T067 diperiksa.

## Pemeriksaan yang dijalankan

PHP 8.3; PHPUnit memakai konfigurasi `phpunit.xml`, database SQLite `:memory:` dan fixture sintetis. Tidak memakai database operator/MySQL.

```sh
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit -c phpunit.xml --do-not-cache-result /private/tmp/T067CombinedNotesTest.php
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit -c phpunit.xml --do-not-cache-result tests/Feature/Invoice/InspectionTest.php --filter 'test_inspection_validation_cancellation_and_stale_actions|test_admin_and_tenant_guards_cover_all_new_actions_and_whatsapp'
```

Hasil aktual: **4 tes / 81 assertion PASS**, lalu **2 tes / 23 assertion PASS**; total **6 tes / 104 assertion**. Test tambahan sementara berada di `/private/tmp/T067CombinedNotesTest.php`; tidak mengubah source produk atau tes candidate.

| Kasus | Expected / actual |
|---|---|
| Dua catatan lama berisi Unicode masing-masing 4000 karakter; result saja; plan saja | Form dan WA memuat gabungan utuh; membaca tidak mengubah seluruh raw row. PASS pada ketiga variasi. |
| Save dua kali dan reload ketiga variasi tersebut | Isi sama persis, `action_plan=null`, tanpa duplikasi; setelah mulai pengerjaan detail menampilkan catatan dan tanpa textarea. PASS. |
| Ganti isi lama lalu Simpan & kirim WA | Isi terbaru disimpan dan muncul tepat sekali pada redirect WA; teks lama tidak muncul. Tautan tanda terima biasa tetap tidak memuat catatan inspeksi. PASS. |
| Kosong, spasi, 8101 karakter, null dan array | Ditolak, tanpa mengubah seluruh raw row legacy; tidak redirect pada submit tidak valid. PASS. |
| Tepat 8100 karakter | Disimpan utuh; kolom legacy menjadi null. PASS. |
| Simpan langsung dengan ID milik tenant lain, WA lintas tenant | 404 dan seluruh data asli tetap sama. PASS. |
| XSS, status stale setelah pembatalan, admin/teknisi dan tenant guard existing | HTML berbahaya di-escape; perubahan pada status batal dan akses tidak berizin ditolak. PASS. |

Review seluruh caller `saveInspection`, `inspectionNotes`, `inspectionMessage`, field legacy, detail, controller WA dan binding Livewire: satu input sampai penyimpanan/detail/WA; pembacaan legacy tidak melakukan backfill; izin/status dan jalur tracking publik tetap. Tidak ada perubahan migrasi/dependency pada candidate.

Browser bukan observasi tester ini. Koordinator melaporkan desktop1280/HP390: satu textarea, legacy utuh, lebar390 tanpa overflow, save/reload satu kalimat sama; bukti browser dicatat terpisah oleh koordinator.

Tidak mengulang suite penuh, build, Pint atau PHPStan yang sudah dijalankan pengembang pada pin sama. Tidak menguji pengiriman WA nyata, perangkat/printer fisik, atau deployment. Batas8100 diuji pada SQLite; tidak ada perubahan skema dan tidak menjalankan migrasi/backfill/database operator, push atau deploy.
