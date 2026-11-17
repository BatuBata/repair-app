# S007/A2 — QA independen perbaikan harness CI

Verdict: **PASS**. Kandidat S006 `sha256:6b01398fbd10ba0196919655f94c9f26917e906fedbef562abb94eec2c6549cd` pada commit **`6d0254c2c0b54a42ed7c0a8917a4cf1400667f42`** lulus tes MariaDB independen dan CI remote commit yang tepat. Laporan ini melengkapi S007/A1; tidak mengulang browser/source yang tidak berubah.

Ponytail **full** dan kontrak tester project-delivery tetap dipakai; skill Ponytail sudah dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Model diminta `gpt-6.1-sol high`; model efektif belum terverifikasi karena tidak ada receipt. Tester hanya menulis `docs/evidence/SAAS/S007/A2/**`, tidak mengubah source, ledger, commit, push, atau produksi.

## Pin perubahan dan penggunaan ulang

`pin.json` memverifikasi byte/hash seluruh **246 file dalam manifest kandidat** dan digest gabungannya. Dibanding S007/A1, **244 identik**, hanya dua file berubah:

- `tests/Feature/Domain/MySqlConcurrencyTest.php`: migrasi selesai dahulu, context tenant 1 ditetapkan, lalu seed dijalankan.
- `tests/Feature/Domain/mysql-worker.php`: context tenant 1 ditetapkan sesudah guard driver/database, sebelum query model.

Seluruh **205 file non-test** dalam manifest identik, termasuk app/bootstrap/config/database/public/resources/routes/workflow dan manifest/lock dependency. Guard host lokal, database `repair_app_test`, database kosong, worker driver/database, transaksi, dan assertion hasil tidak dilemahkan.

Ketelitian scope: label S006 menyebut seluruh tracked files, tetapi manifest 246 mewarisi scope sebelum S008 menambah `tests/Feature/Tenancy/MigrationTest.php`. File tambahan tersebut dipin terpisah ke hash **`42b994a3a04d613664623820c2345826e804710ae118a485e220f8fab5da9a34`** melalui kandidat S008/A2, dan diuji oleh remote CI. Jadi laporan ini tidak mengklaim seluruh 247 tracked scope files berada dalam manifest 246.

`reuse-inputs.json` membuktikan:

| Bukti/input | Verifikasi pin | Kasus yang dipakai kembali / kondisi |
|---|---|---|
| S007/A1 `manifest.json` | 33 file bukti SHA256 identik | Suite SQLite 216 PASS + satu skip historis, Pint/PHPStan/build, 34 alur + 11 security browser, foreign ID/raw payload/signed snapshot replay, inactive tenant, public identity/documents, migration/backfill/rollback, MySQL tenant counter dan stok race. App/schema/assets/dependency/context autentikasi tidak berubah; hanya harness tes yang tidak dijalankan browser berubah. |
| S008/A2 candidate `sha256:ce45314d50e2a865026fab4ee75736fa3833e90b070990880adac047a0631c58` | 259/261 source files identik; dua mismatch hanya harness S007 ini | Script backup/runbook/check-scripts/migration-test tetap pada source yang telah diperiksa. Tidak ada perubahan operasional pada patch `6d0254c`. |
| S008/A2 evidence-manifest | 13 file bukti identik | Bukti developer operasional tetap valid sesuai scope-nya, tidak dipromosikan menjadi QA independen baru. |
| S009/A2 manifest | 12 file bukti identik | QA independen native MariaDB backup, salin/restore/migration/guard dan pemetaan A1 tetap tidak terdampak. Riwayat FAIL S009/A1 tidak dihapus. |

Perubahan backup yang sudah terjadi dalam S008/A2 berada di luar manifest software S006; file itu dibandingkan terhadap kandidat operasional S008/A2, bukan dinyatakan identik dengan backup sebelum S008.

## MariaDB 10.11.14 independen

Command aktual dari `/Users/muzib/Projects/web/repair-app`: `python3 docs/evidence/SAAS/S007/A2/verify.py`.

Script membuat **container baru milik S007-A2**, image `mariadb:10.11.14`, database kosong bernama `repair_app_test` dalam **tmpfs container**, port loopback acak. Nama database yang sama diperlukan guard harness, tetapi ini tidak memakai MySQL host atau database baseline existing. Versi server diperiksa persis **`10.11.14-MariaDB-ubu2204`**. Identitas image/container/port dan inventory kosong disimpan pada `mariadb-before.json`; inventory Docker awal tidak memiliki container lain.

Perintah tes sebenarnya: `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --filter MySqlConcurrencyTest --colors=never`, dengan `RUN_MYSQL_DOMAIN=1`, environment sintetis privat dan diagnostics waktu ke `mysql-timings.jsonl`.

| Kasus | Expected | Actual / bukti |
|---|---|---|
| Migrasi + seed tenant 1 | Context benar sebelum seeder | **PASS**, 1 tenant, 1 admin tenant 1, 9 kategori tenant 1 |
| Test harness pada MariaDB nyata | Semua assertion lulus | **1 tes PASS, 44 assertion**; `mariadb-test.log` |
| Child writes dan nomor | 8 TTS + 8 JL, unik, semua tenant 1 | **16 child writes**; nomor TTS/JL `2611-001` sampai `2611-008`; 8 token unik; `mariadb-proof.json` |
| Konkurensi | Row counter baru dan existing, 4 worker tiap ronde overlap | **4 ronde overlap** (TTS/JL × offset 0/4); masing-masing counter berakhir 8; `mysql-timings.jsonl` |
| Database sudah berisi data | Tolak sebelum `migrate:fresh` | Negative test **gagal sesuai harapan** pada assertion kosong, seluruh 21 tabel milik container tetap identik; `nonempty-guard.log` dan assertion inventory pada verifier |
| Worker driver/database salah | Tolak sebelum lookup tenant/query SQL | **PASS fail-closed**, pesan guard muncul dan tidak ada JSON worker valid; `worker-guards.json` |

Guard worker diperiksa lewat `python3 docs/evidence/SAAS/S007/A2/followup.py` untuk SQLite memory dan nama database MySQL terlarang dengan port 1 yang tidak membutuhkan server. Laravel exception renderer existing mengembalikan **exit 0** untuk exception script ini; tidak diklaim nonzero. Guard tetap berhenti sebelum SQL dan parent menolak output karena JSON child tidak valid. Kesimpulan keamanan didasarkan pada penolakan aktual, bukan exit code saja.

## Syntax, format, dan analisis statis

- `php -l` pada kedua file: **PASS**.
- `php vendor/bin/pint --test tests/Feature/Domain/MySqlConcurrencyTest.php tests/Feature/Domain/mysql-worker.php`: **PASS**.
- `python3 docs/evidence/SAAS/S007/A2/followup.py --static-only`: PHPStan level 7 membandingkan dua file A1 dengan A2 dalam tree sementara, memakai `--debug --error-format=json --no-progress --memory-limit=512M` agar tidak memerlukan socket paralel sandbox.
- PHPStan **ad hoc di luar configured paths**: baseline 2 diagnostic, kandidat 3; bukan clean PASS. Dua kelas pesan sama: `Dotenv::parse(file_get_contents)` bertipe `string|false`, dan union Laravel `PendingCommand|int` untuk `assertExitCode`. Pemisahan migrate/seed mengulang pesan union pada satu baris tambahan. Worker tidak mendapat diagnostic. Rincian persis ada di `phpstan-baseline.json`, `phpstan-candidate.json`, `static-comparison.json`.
- Gerbang PHPStan proyek yang configured (app/bootstrap/config/database/routes) lulus melalui remote `composer ci:check`; tes tidak termasuk paths gerbang tersebut. Tidak ada suppression atau perubahan konfigurasi untuk QA ini.

## CI remote exact commit

[Run 44014653205](https://github.com/BatuBata/repair-app/actions/runs/44014653205), attempt 1, event push branch `codex/saas-multitenant`, head SHA **`6d0254c2c0b54a42ed7c0a8917a4cf1400667f42`**: **completed / success**.

[Job ci 133809562344](https://github.com/BatuBata/repair-app/actions/runs/44014653205/job/133809562344): checkout/setup/build, **Run CI Checks**, dan **Test deployment safety** semuanya success. Log remote: **220 passed / 2.435 assertions**, durasi suite 20,93 detik, dan **20 deployment safety cases PASS**. Workflow yang diperiksa menjalankan Composer lint, PHPStan, suite penuh dengan `RUN_MYSQL_DOMAIN=1`, serta npm build pada setup. Jadi tes MySQL historis sekarang benar-benar berjalan di CI, bukan skip.

Job deployment, package dan upload production **skipped sesuai kondisi branch**. Ini bukan deployment atau bukti QA server. Receipt native GitHub beserta step, SHA, URL dan excerpt aman disimpan pada `remote-ci.json`.

## Riwayat harness dan pembersihan

Verifier awal menyelesaikan MariaDB PASS dan guard database nonempty sebelum berhenti pada ekspektasi keliru bahwa exception worker pasti nonzero. Followup hanya memeriksa kasus guard/statik yang belum terbukti; tes MariaDB yang sudah lulus tidak diulang. Percobaan PHPStan awal terblokir socket sandbox (`phpstan-environment-initial.json`), lalu dijalankan sequential memakai `--debug`. Ini kegagalan harness/environment, bukan perubahan atau defect aplikasi.

Container dihapus hanya sesudah ID dan label `task=S007-A2` cocok. Database/tmpfs container, password/key/envfile privat dan runtime sementara sudah dihapus; tidak ada container lain sesudahnya (`cleanup.json`). Static tree sementara followup juga dihapus. MySQL host/produksi tidak disentuh. Tidak ada password/token/key sintetis disimpan pada bukti terbit.

Tidak diulang: browser A1, migrasi/stok race lintas tenant A1, build dan suite lokal penuh pada source sama; pemetaan reuse dipin di atas dan suite remote exact commit tetap dijalankan. Tidak diuji: produksi, deployment, offsite backup nyata, perangkat fisik, dan PDF visual baru. Static ad hoc test memiliki diagnostic tipe sebagaimana dicatat; gerbang CI configured tetap lulus.
