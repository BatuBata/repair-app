# S006 A1 — candidate integrasi Lean SaaS

Candidate gabungan siap diperiksa oleh QA independen S007. Hasil di sini adalah pemeriksaan integrator, **bukan penerimaan QA atau izin melewati S007/S009/S010**. Worker tidak mengubah ledger, commit, push, main, deployment, atau database existing.

- Root `/Users/muzib/Projects/web/repair-app`, branch `codex/saas-multitenant`.
- Base `4329aa9` (`chore: start S006 SaaS integration`), source produksi sebelum SaaS `a03f6f1`.
- Candidate tunggal **`sha256:066bb9e82bd3ac5142a1ef516fc46c862c7e3b1b2cbc28ee4eb0a8ab402996f6`**; [candidate.json](candidate.json) memuat algoritma dan hash 246 file source/test/config. Ledger, evidence, dokumen, mockup dan build ignored dikecualikan. Pin mencakup perubahan lokal S006 di atas base, sehingga commit base saja tidak cukup untuk QA.
- Inputs: S003 `sha256:d5a8bb86214580207d73ca1e8039b84f20e34dc912cad3c924be5398320a3160`; S004 `sha256:c721514661e503ee70abe9fbdd508896f18835b3e0070cb1d6c20664c4a66674`; S005 `sha256:9ab1f13ae929cb26cdac0c12aa61137e48d518e2617b886cf354f44b9340802f`.
- Ponytail **full** dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; Project Delivery, coordination (worker/integrasi/Git), qa-and-release, adapter Codex, AGENTS aktual, S006, keputusan S001 dan rancangan SaaS juga dibaca.
- Model diminta `gpt-6.1-sol`, effort `high`; model efektif/receipt `null`, karena runtime worker tidak menyediakan receipt backend.

## Integrasi dan koreksi minimum

Tidak ada cherry-pick/merge baru: S002 → S003 → S004 → S005 sudah berurutan di branch. [ancestry.txt](ancestry.txt) menyimpan seluruh commit dari `a03f6f1`; manifest input dicocokkan dengan commit hasil masing-masing (S003 `8b03e0b`, S004 `1c022ff`, S005 `f720cb9`) dan semuanya ancestor HEAD. Bukti, FAIL history dan pin sebelumnya tidak diubah. [integration-review.json](integration-review.json) berisi pencocokan hash, dependency, migrasi, dan bukti MySQL yang digunakan kembali.

Suite awal membuktikan jangkauan integrasi yang belum diperbarui oleh kandidat parsial. Semua koreksi berada pada A1:

1. `DomainTest` kini menetapkan TenantContext fixture eksplisit; unique nomor diperiksa `(tenant_id, number)`, sementara tracking token tetap unik global. Assertion lama bahwa ID pengaturan harus 1 dihapus karena singleton global memang diganti menjadi satu pengaturan per tenant; constraint baru tetap diperiksa `FoundationTest`. Validasi nilai stok/uang/garansi tetap diuji.
2. `SeederCacheTest` memigrasikan SQLite sementara, lalu menetapkan tenant 1 eksplisit sebelum memanggil seeder di subprocess. Password sintetis tetap hanya berasal dari config cache dan dihapus dari environment proses. Tidak ada fallback tenant implicit atau command operasional baru.
3. Fixture `ChangePasswordTest` menetapkan context kembali sebelum Livewire yang dijalankan setelah HTTP request membersihkan context. Kode profil/security aplikasi tidak dilonggarkan.
4. Preview layout lokal/testing memang tidak memakai database. Shell tidak membaca StoreSetting saat context belum tersedia dan memakai nama generik. Route preview merender response sebelum mengembalikan user auth sebelumnya pada `finally`; user sintetis tidak bocor ke request preview berikutnya. Route tetap 404 pada production, dibuktikan tes existing.
5. Manifest PWA shared-domain memakai `Aplikasi Servis` / `Servis`, menggantikan identitas Anugerah yang terlihat oleh seluruh tenant sebelum login. Assertion manifest existing diperbarui. Tidak ada layout/CSS/fitur desain baru.

## Audit source dan caller

Review mencakup source utuh dan diff S002–S005, bukan hanya tujuh file koreksi S006. [callers.txt](callers.txt) menyimpan inventaris pencarian query pada layanan, Livewire, command dan route; Action serta seluruh model/relasi juga dibaca.

| Data/jalur | Hasil review |
|---|---|
| Tenant/context/middleware/auth | Tabel tenant hanya id/name/is_active/timestamps. Delapan root memakai tenant_id wajib dengan FK. Scope model tanpa context kosong; create mengambil tenant dari context. Fortify username unik global; bypass scope user hanya untuk menemukan/memulihkan identitas auth. Akun dan tenant nonaktif ditolak area internal. |
| Users/category/settings/onboarding | Caller daftar, edit, reset, toggle, admin terakhir, kategori TTS dan pengaturan memakai root scoped. Validasi username global dan kategori per tenant sesuai S001. Pengaturan bukan `find(1)` pada source aplikasi. Logo baru memakai `logos/{tenant_id}`; provisioning tenant/store/owner satu transaksi, input password tersembunyi. Seeder membutuhkan context eksplisit. |
| ServiceOrder dan ServiceOrderItem | CreateServiceOrder, TTS list/form/detail, ManageServiceOrder, Invoice form/InvoiceLines memuat root scoped sebelum membaca/mutasi child. Validasi kategori/creator/teknisi mengikat tenant. Tidak ada pembacaan global ServiceOrderItem di caller produksi. |
| Product/StockEntry | Daftar/form/restok/toggle memakai Product scoped; restok memakai parent atau validasi product/creator pada saving. Total laporan memakai `whereHas('product')`. Update child existing memeriksa parent asli. |
| Sale/SaleItem/Expense | CreateSale mengunci produk scoped; DeleteSale memuat penjualan dan produk scoped sebelum restore stok. Detail/list/receipt memakai root. SaleItem memeriksa parent asli dan kedua relasi tenant. Expense list/edit/delete/create scoped dan creator divalidasi. |
| NumberSequences | Caller DocumentNumber hanya dalam transaksi; kunci tenant/prefix/period. Counter dua tenant independen; tidak menambah model/layer untuk tabel ini. |
| Dashboard/report/warranty | Root query scoped; dua join SQL dashboard memfilter tenant pemilik eksplisit. Laporan restok memfilter parent produk. Pengingat memakai root dan nama tenant aktif. |
| Public tracking/PDF/WhatsApp | Lookup publik memerlukan token 64 alfanumerik global dan perbandingan exact `hash_equals`; relasi dan pengaturan berasal dari tenant order. Public tracking/PDF saja dikecualikan dari middleware aktif sesuai S001; route internal tetap terlindungi. Output publik mempertahankan subset aman existing. |
| Branding | Shell/TTS/users/dokumen/WhatsApp/dashboard/tracking memakai StoreSetting tenant lalu nama Tenant bila kosong. Login dan manifest shared-domain generik. Pencarian Anugerah pada app/resources/routes/public hanya menyisakan fallback migrasi tenant pertama dan komentar CSS historis; starter header yang tidak dipakai route tidak menjadi shell aktif. |

Composer/npm manifest dan kedua lockfile **byte-identik** dengan `a03f6f1`. Tepat **satu** migrasi SaaS: `2026_11_17_000000_add_tenant_foundation.php`; tidak ada schema tambahan. Review file/route/schema/dependency tidak menemukan panel platform, registrasi publik, billing/paket/trial, subdomain, membership banyak toko, API, queue, repository, feature flag, package tenancy atau fitur lain di luar rancangan. [secret-check.json](secret-check.json) merekam pemeriksaan pola private key/PAT/AWS dan file environment tracked; tidak ada temuan. Review manual baris password/token baru menemukan fixture sintetis dan pembacaan symbolic, bukan kredensial nyata. Ini bukan klaim pemindaian secret eksternal menyeluruh.

## Pemeriksaan yang benar-benar dijalankan

Seluruh PHP memakai `/opt/homebrew/opt/php@8.3/bin/php` (8.3), bukan PHP global. Perintah lengkap, exit code, expected/actual dan ringkasan gagal ada di [checks.json](checks.json).

| Perintah/kasus | Expected | Actual |
|---|---|---|
| `php artisan test --no-ansi` awal | Seluruh regresi software lulus | **FAIL**: 217 total, 204 PASS, 5 FAIL, 7 ERROR, 1 skipped; [tests-initial.txt](tests-initial.txt). Fixture/context/schema lama dan preview shell seperti koreksi di atas. |
| Tes terarah Domain/Layout/Profile/RoleLauncher | Kasus terdampak lulus | 26 total, 24 PASS, 1 FAIL, 1 skipped; [affected.txt](affected.txt). Request preview kedua 403 karena auth sintetis dipertahankan; diperbaiki di route. |
| `php artisan test tests/Feature/Layout --no-ansi` | Preview berulang dan gate production lulus | **PASS** 3/3, 22 assertion; [layout-final.txt](layout-final.txt). |
| `php artisan test --no-ansi` akhir | Suite gabungan lulus | **PASS** 216, **1 skipped**, 217 total, 2368 assertion, 9480 ms; [tests-final.txt](tests-final.txt). Seluruh tes isolasi Foundation/Identity/Service/Commerce termasuk dalam suite ini. |
| `php vendor/bin/pint --test` seluruh proyek | Format sesuai proyek | Awal FAIL satu ordered_imports fixture Profile; fix terarah lalu **PASS** seluruh proyek [pint-final.txt](pint-final.txt). FAIL disimpan [pint-initial.txt](pint-initial.txt). |
| `php vendor/bin/phpstan analyse --memory-limit=512M --debug` seluruh konfigurasi proyek | 0 error tipe | **PASS**, 0 error, sebelum dan setelah perubahan route preview. `--debug` menghindari socket worker sandbox. [phpstan-final.txt](phpstan-final.txt). |
| `npm run build` | Build aset produksi berhasil | **PASS**, exit 0; [build.txt](build.txt). Tidak ada script npm check/lint/test. Warning optional fontaine dan referensi lima font IBM yang diselesaikan saat runtime dipertahankan; tidak memasang dependency. |
| `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=<file /tmp baru> DB_URL= APP_CONFIG_CACHE=<cache absent> php artisan migrate --force --no-ansi` | Seluruh migrasi normal pada DB baru | **PASS**, 7 migrasi; [migrate-sqlite.txt](migrate-sqlite.txt). Query SQLite membuktikan 1 tenant, 8 tenant_id non-null dan `foreign_key_check` kosong; [sqlite-proof.json](sqlite-proof.json). File DB baru dipertahankan; tanpa reset/drop. |
| Manifest input/ancestry/lockfiles/migrasi | Input yang benar, tanpa dependency/schema tambahan | **PASS** [integration-review.json](integration-review.json). |
| `git diff --check` | Tidak ada whitespace error | **PASS** [diff-check.txt](diff-check.txt), output kosong. |

### Bukti MySQL nyata yang digunakan kembali

S006 **tidak menjalankan ulang MySQL** pada kondisi/source yang identik. [integration-review.json](integration-review.json) mem-pin hash bukti dan source relevan yang dibandingkan terhadap commit pelaksana:

- S004 `mysql-concurrency.json`: MySQL lokal terisolasi `saas_s004_20261116_164324_153`, 32 worker, dua tenant, TTS/JL counter baru dan existing masing-masing 001–008, **PASS**. Source DocumentNumber/context/migrasi identik.
- S005 `mysql-concurrency.json`: database baru `saas_s005_20261116_165831_3331`, dua admin per tenant memperebutkan satu stok, tiap tenant satu sukses/satu shortage, nomor JL masing-masing 001, tanpa orphan dan baseline row tetap identik, **PASS**. CreateSale/root/child/trait/context/DocumentNumber/migrasi/harness identik.
- Runner tersebut menjalankan migrasi normal pada database baru; database dan fixture bukti dipertahankan. Tidak ada akses ulang S006 ke `repair_app`, `repair_app_test`, atau produksi.
- Satu skip suite adalah `Domain/MySqlConcurrencyTest` historis T002, yang mensyaratkan `repair_app_test` kosong dan memanggil `migrate:fresh --seed`. S006 sengaja tidak mengaktifkan jalur itu karena database menyimpan baseline yang dilindungi; penggantinya untuk kasus nomor/stock tenant adalah bukti terisolasi di atas. Tidak mengklaim test historis skipped itu PASS.

Tidak dijalankan: QA independen S007, rehearsal MariaDB/backup/restore S008–S009, browser/visual, perangkat/printer fisik, atau deploy/produksi. Risiko tersisa: schema/rehearsal target dan migrasi data nyata tetap gate lanjutan; build menyimpan warning font existing; test MySQL historis yang membutuhkan DB kosong tetap skipped. Tidak ada penerimaan software/rilis oleh integrator ini.
