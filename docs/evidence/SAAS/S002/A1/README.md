# S002/A1 — Fondasi tenant

## Pin dan batas assignment

- Branch: `codex/saas-multitenant`
- Base revision: `4b12d61f1843f46d0a52b3185433c9d0dbd33e0d`
- Input kontrak S001: `sha256:a2b342d0aabb9c1b9dbcf57668a5409b448b6df575dc1f571e568346b0b698c6`
- Peran: implementer S002; tidak mengubah ledger, modul bisnis S003+, dependency, remote, atau server.
- Ponytail full — loaded `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`.
- Kontrak worker project-delivery — loaded `/Users/muzib/.agents/skills/project-delivery/SKILL.md` dan `references/coordination.md`.

## Perubahan

- Satu migrasi SaaS membuat tabel minimum `tenants`, membuat tenant Anugerah, menambah dan mengunci `tenant_id` pada delapan tabel utama, serta mengganti unique key kategori/nomor dokumen menjadi per tenant.
- `number_sequences` dan `store_settings` dibangun ulang sambil menyalin semua baris agar kunci per tenant dapat diterapkan tanpa `migrate:fresh` atau reset data.
- Rollback hanya diizinkan selama belum ada tenant kedua; jika sudah ada, migrasi berhenti sebelum menghapus data.
- `TenantContext`, `BelongsToTenant`, dan `EnsureTenant` memberi scope fail-closed, mengisi `tenant_id` dari context (nilai kiriman diabaikan), menghasilkan 404 untuk record tenant lain, serta memblokir tenant nonaktif.
- `TenantContext` didaftarkan sebagai scoped binding dan middleware dipasang pada grup web existing.
- Tidak ada package, repository, API, queue, feature flag, atau layer baru.

## Pemeriksaan aktual

| Pemeriksaan | Expected | Actual |
|---|---|---|
| `php artisan test tests/Feature/Tenancy/FoundationTest.php` | Schema/FK/unique, scope/create, context middleware, 404 lintas tenant, dan tenant nonaktif lulus | **PASS** — 4 test, 40 assertion |
| SQLite bersih: migrate → rollback S002 → migrate | Migrasi dapat naik, turun, lalu naik lagi | **PASS** |
| SQLite fixture existing: migrate dasar → isi 1 fixture per tabel → migrate S002 → rollback | Jumlah baris dan total tetap identik, seluruh root row menjadi tenant 1 | **PASS** — snapshot sebelum/sesudah/rollback `1:1:1:200000:3:1:1:75000:10000:1`; root tenant rows `1:1:1:1:1:1:1` |
| SQLite rollback dengan tenant kedua | Rollback ditolak dan dua tenant tetap tersimpan | **PASS** — `tenants_preserved=2` |
| Pint pada seluruh file S002 | Tidak ada pelanggaran format | **PASS** |
| PHPStan pada `app`, migrasi S002, dan foundation test (`--debug` karena sandbox menolak TCP worker) | 0 error | **PASS** — 0 error |
| `git diff --check` | Tidak ada whitespace error | **PASS** |
| MySQL 8.0.31 sementara pada database khusus `repair_app_saas_test` | Migrate → rollback S002 → migrate nyata lulus | **PASS** — server terisolasi di `/tmp` pada port 3307; database existing dan produksi tidak disentuh |

## File hasil

- `database/migrations/2026_11_17_000000_add_tenant_foundation.php`
- `app/Models/Tenant.php`
- `app/Models/Concerns/BelongsToTenant.php`
- `app/Support/TenantContext.php`
- `app/Http/Middleware/EnsureTenant.php`
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`
- `tests/Feature/Tenancy/FoundationTest.php`

## Dilewati dan risiko tersisa

- Full regression belum dijalankan pada tahap fondasi: model/caller bisnis baru akan memakai trait dan mengirim context tenant pada S003–S006. Menjalankan S002 sendiri memang membuat insert lama tanpa `tenant_id` gagal cepat, bukan diam-diam masuk ke tenant Anugerah.
- Kompatibilitas MariaDB 10.11 target tetap menjadi gate migrasi S008–S009; S002 sudah dibuktikan pada MySQL 8.0.31 sesuai lingkungan lokal proyek.
- Tidak ada commit atau push dari worker ini.
