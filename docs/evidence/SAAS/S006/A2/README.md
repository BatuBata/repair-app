# S006 A2 — konteks tenant pada harness CI MySQL

Perbaikan dua file harness sudah lulus pemeriksaan lokal pada **MariaDB 10.11.14** dengan database baru/kosong. Ini bukti worker integrasi, bukan QA independen atau hasil GitHub Actions terbaru.

- Root `/Users/muzib/Projects/web/repair-app`, branch `codex/saas-multitenant`, base `5b15eaab79d8a0f7f93750471340572cdaa6e59d`.
- Candidate **`sha256:6b01398fbd10ba0196919655f94c9f26917e906fedbef562abb94eec2c6549cd`**, dipin oleh [candidate.json](candidate.json): hash path/NUL/bytes dari 246 file, dengan cakupan source/test/config yang sama seperti S006/A1. Hanya dua file harness berbeda dari base; seluruh aplikasi, migrasi, konfigurasi, workflow, aset dan dependency byte-identik. [source-review.json](source-review.json) dan [harness.patch](harness.patch) memuat jangkauan perubahan.
- Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery beserta kontrak worker/adapter Codex, AGENTS, task S006 pada SAAS-TODO dan rancangan SaaS terkait juga dibaca. Worker tidak mengubah ledger atau membuat worker lain.
- Model diminta `gpt-6.1-sol`, effort `high`; model efektif dan receipt `null` karena receipt backend tidak tersedia kepada worker.

## Akar masalah dan perubahan minimum

`MySqlConcurrencyTest` adalah satu-satunya caller `mysql-worker.php`. Proses child bootstrap aplikasi baru, sehingga konteks tenant proses utama tidak terbawa. `DocumentNumber`, `CreateServiceOrder`, lookup User/Category dan event model mengharuskan konteks tenant eksplisit.

Reproduksi pertama pada MariaDB kosong menemukan kegagalan lebih awal: `migrate:fresh --seed` memanggil `DatabaseSeeder` sebelum konteks tenant tersedia. [mysql-initial.txt](mysql-initial.txt) mempertahankan FAIL aktual, 1 error/7 assertion pada seeder. Coordinator memperluas write scope yang semula hanya child agar mencakup `MySqlConcurrencyTest.php`; aplikasi/seeder tetap di luar scope.

1. Test utama menjalankan `migrate:fresh` tanpa seed, menetapkan `Tenant::findOrFail(1)` ke `TenantContext`, lalu memanggil `db:seed`. Pemulihan konfigurasi password dalam `finally` tetap ada. Guard host lokal, database/driver tepat, serta tabel/view kosong tetap ada sebelum migrasi.
2. Child menetapkan tenant yang sama **setelah guard MySQL/repair_app_test** dan sebelum operasi bisnis. Tenant ID 1 berasal dari migrasi fondasi tenant, yang memasukkannya eksplisit; seed admin/kategori dan fixture test memang memakai tenant ini. `findOrFail` tetap menolak tenant yang tidak ditemukan.

Tidak ada perubahan assertion/skip, fallback tenant aplikasi, seeder, production source, migrasi, workflow, atau dependency.

## Pemeriksaan aktual

Perintah/exit code ada di [checks.json](checks.json); seluruh PHP memakai `/opt/homebrew/opt/php@8.3/bin/php`.

| Pemeriksaan | Actual |
|---|---|
| `php artisan test tests/Feature/Domain/MySqlConcurrencyTest.php --no-ansi`, `RUN_MYSQL_DOMAIN=1` | **PASS**, 1 test, 44 assertion, 11361 ms; [mysql-final.txt](mysql-final.txt). Tidak skipped. |
| `php artisan test tests/Feature/Domain/DomainTest.php tests/Feature/Domain/SeederCacheTest.php --no-ansi`, SQLite | **PASS**, 15 test, 286 assertion; [sqlite.txt](sqlite.txt). |
| `php -l` kedua file dan `php vendor/bin/pint --test` kedua file | **PASS**. |
| `php vendor/bin/phpstan analyse --memory-limit=512M --debug` memakai paths konfigurasi resmi | **PASS**, 0 error; [phpstan-project.txt](phpstan-project.txt). |
| PHPStan explicit pada `mysql-worker.php` | **PASS**, 0 error; [phpstan-worker.txt](phpstan-worker.txt). |
| PHPStan eksploratif explicit pada kedua file test (test tidak termasuk paths konfigurasi resmi) | **FAIL**, 3 temuan: `file_get_contents` bertipe `string|false`, dan dua `artisan()->assertExitCode` bertipe `PendingCommand|int`; [phpstan.txt](phpstan.txt). Baseline base memiliki 2 temuan yang sama sebelum split seed; [phpstan-test-baseline.txt](phpstan-test-baseline.txt). Baris seed baru mengikuti pola assertion existing. Tidak menambah suppression atau memperluas scope untuk type workaround. |
| Guard child dengan SQLite dan nama database MySQL yang salah | Kedua jalur berhenti sebelum lookup tenant/operasi data; [fail-closed.json](fail-closed.json). Renderer exception console Laravel existing memberi exit 0, tetapi output bukan JSON sehingga parent tetap gagal saat decode. Perilaku exit existing tidak diubah. |
| `git diff --check` | **PASS**, output kosong. |

## Isolasi MariaDB dan pembersihan

Docker selalu memakai `--context colima`; konteks global tidak diubah. Image lokal exact `mariadb:10.11.14` memiliki ID `sha256:dbe56e20372fc6d6b8e0e396866ba89c4c7f128c38c4f59aaa54d957db95790c`. Server actual `10.11.14-MariaDB-ubu2204`. Container task memakai label `task=S006-A2`, data **tmpfs baru** `/var/lib/mysql`, tanpa bind/volume, serta port loopback acak `127.0.0.1:32769`; kredensial DB/admin sintetis hanya di berkas privat `/tmp` dan environment proses, tidak membaca `.env` lokal atau password nyata.

Container reproduksi FAIL dibersihkan lalu dibuat container baru, sehingga test final benar-benar dimulai dari database kosong. [mariadb-before.json](mariadb-before.json) membuktikan 0 tabel/0 view sebelum test. [mysql-timings.jsonl](mysql-timings.jsonl) mencatat empat ronde × empat worker yang overlap: counter TTS/JL baru dan existing; seluruh assertion overlap/nomor existing tetap berjalan. [mariadb-proof.json](mariadb-proof.json) membuktikan:

- tenant 1 aktif, satu admin dan sembilan kategori seeded;
- delapan TTS, delapan nomor unik dan delapan tracking token unik;
- delapan penjualan dengan delapan nomor unik;
- counter `(tenant_id=1, prefix=TTS/JL, period=2611)` masing-masing `last_seq=8`;
- tidak ada pembuat TTS/penjualan yatim atau lintas tenant.

Kedua container disposable dibersihkan hanya setelah verifikasi ID dan label. Colima kembali tanpa container seperti inventaris awal; data tmpfs serta kredensial sementara dibuang. [cleanup.json](cleanup.json) menyimpan hasilnya. Database existing `repair_app`, `repair_app_test` pada MySQL host, dan produksi tidak diakses/reset/drop.

Tidak dijalankan: suite/build penuh yang sudah tersedia di S006/A1, QA independen, remote GitHub Actions, commit/push, deploy, atau perangkat/printer. Risiko: hasil remote CI masih menunggu coordinator; PHPStan manual untuk file test mencatat limit tipe di atas, dan guard child mempertahankan perilaku renderer exit existing. [result.json](result.json) merangkum handoff beserta batas tersebut.
