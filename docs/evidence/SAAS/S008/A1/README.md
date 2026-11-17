# S008/A1 — rehearsal migrasi dan runbook Anugerah

**Verdict pengembang: PASS untuk S008 lokal; belum QA independen atau deployment.** Runbook siap direview S009. Tidak ada koneksi ke server/produksi, modifikasi schema/model/UI aplikasi, dependency, ledger, commit/push, atau perubahan skrip deploy existing.

- Base: `284f0acd310c2d2cb3500672d05b814faac7a425`, branch `codex/saas-multitenant`, root `/Users/muzib/Projects/web/repair-app`.
- Candidate immutable: **`sha256:066cd061d3b189d9873333a06e3ecf46a493469cc096715b1461b758157b8157`**, [260 file dan algoritme hash](candidate.json). Mencakup 246 file S006 yang tetap identik, 11 skrip/config deploy existing, serta runbook/test/harness S008. README/log/ledger dikecualikan dari candidate agar tidak melingkar; output bukti dipin terpisah pada `evidence-manifest.json`.
- Hard input S007: `sha256:6ba364a204d98ac1d9754182d2a4fd1c54de6f940d7341ad88bef207aea74ad4` (hash file `S007/A1/result.json` diperiksa). Software: `sha256:066bb9e82bd3ac5142a1ef516fc46c862c7e3b1b2cbc28ee4eb0a8ab402996f6`; seluruh 246 file dan hash gabungan diperiksa sendiri, 0 mismatch: [input-check.json](input-check.json).
- Model diminta `gpt-6.1-sol`, effort `high`; model efektif **belum terverifikasi**, receipt runtime tidak tersedia.
- Ponytail **full** dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project-delivery, QA/release, kontrak worker, AGENTS aktual, S008, rancangan §10/12, skrip existing dan bukti T020/S007 dibaca.

## Perubahan

[Runbook](../../../../SAAS-MIGRATION-RUNBOOK.md) mencakup gerbang MariaDB/QA/offsite, lock receiver dan maintenance, inventaris semua field lama, dump/upload/key, pull Mac tanpa secret, checksum, restore ke scratch kosong, urutan sinkronisasi/migrasi existing, pemeriksaan data/aplikasi, batas rollback, dan pembukaan kembali. Skrip backup/restore cukup; tidak dibuat sistem backup/deploy baru.

Receiver CI demo membuka maintenance otomatis dan tidak mengambil backup. Runbook SaaS menahan lock/marker yang sama dan memakai rsync/Artisan existing secara manual sampai verifikasi selesai. `deploy.sh` tetap utuh: guard Git bersihnya tidak cocok dengan `.git` lama pada instalasi receiver, sehingga runbook tidak mengklaim HEAD live sebagai versi aktif.

[MigrationTest](../../../../../tests/Feature/Tenancy/MigrationTest.php) memakai koneksi SQLite `:memory:` baru secara eksplisit, membangun schema pre-SaaS tanpa reset DB existing, memasukkan fixture lama, lalu membuktikan semua field bisnis, backfill, laporan, relasi, rollback single tenant dan guard tenant kedua. Fixture statis yang sama dipakai harness MySQL agar ekspektasi tidak menyimpang.

## Perintah aktual dan hasil

PHP selalu `/opt/homebrew/opt/php@8.3/bin/php`; command dijalankan dari root di atas. Tidak menjalankan `composer setup`, `migrate:fresh`, drop/reset DB, atau seed akun aplikasi.

| Perintah | Expected | Actual / bukti |
|---|---|---|
| `S008_RUNTIME=/private/tmp/saas-s008-a1-final S008_DB_SUFFIX=20261117_a1c php docs/evidence/SAAS/S008/A1/rehearsal.php` | Backup/copy/restore/migrasi/pemulihan scratch benar | Semua 7 tahap PASS; [rehearsal.log](rehearsal.log) |
| `php artisan test --compact tests/Feature/Tenancy/MigrationTest.php` | Test migrasi baru lulus | **3 tests, 23 assertions**, 314 ms; [migration-test.log](migration-test.log) |
| `php vendor/bin/pint --test tests/Feature/Tenancy/MigrationTest.php docs/evidence/SAAS/S008/A1/rehearsal.php` | Format valid | PASS; [pint.log](pint.log). Format harness dirapikan setelah rehearsal; tidak mengubah logika/aplikasi. |
| `php vendor/bin/phpstan analyse --memory-limit=512M` | Analisis kode existing 0 error | PASS 0 error; [phpstan.log](phpstan.log). Konfigurasi existing menganalisis app/config/database/routes, bukan test/evidence. |
| `python3 deploy/check-scripts.py` | Guard backup/restore/deploy lulus | **31 host safety cases PASS** memakai stub; [deploy-safety.log](deploy-safety.log) |
| `python3 deploy/ci-checks.py` | Guard arsip/release lulus sejauh host tersedia | **12 cases PASS**; GNU tar packager dan Linux flock/rsync receiver **NOT_RUN** pada Mac, bukan PASS; [ci-safety.log](ci-safety.log) |
| `bash -n deploy/backup.sh deploy/restore.sh deploy/deploy.sh deploy/ci-remote.sh` | Syntax shell valid | Exit 0 |
| Blok PHP inventaris runbook: capture source, verify recovery | Seluruh field pada 19 tabel lama non-migration identik | PASS; [runbook-inventory.log](runbook-inventory.log) |
| Blok inventaris: target salah, hash baseline palsu, baseline valid | Salah/palsu exit 1; valid exit 0 | PASS; [runbook-negative-checks.json](runbook-negative-checks.json) |
| `git diff --check` dan pemeriksaan whitespace file S008 baru | Tidak ada whitespace error | PASS; `diff-check.log` dan `scope-check.json` |

## Rehearsal MySQL nyata

MySQL client/server **8.0.31**, PHP 8.3.30. Credential dibaca privat dari `.env`, host wajib loopback; option file privat di runtime, bukan CLI/chat/Git. Semua nama DB dicek belum ada sebelum CREATE; harness tidak memiliki perintah DROP/reset. Resource `db:mysql-saas-migration` eksklusif. Database `repair_app`, `repair_app_test`, S004/S005/S007 tidak dipilih atau dimodifikasi.

Set final **`saas_s008_20261117_a1c_{source,target,failed,recovery,sqlfail}`**:

1. **Source pre-SaaS:** 20 tabel schema lama. Fixture 11 tabel bisnis: 2 akun (admin/teknisi), 1 kategori/produk/setting, 2 TTS (diambil/batal), 2 item servis (part/jasa), 1 sale/item/restock/expense, dan 2 counter. Semua field lama disnapshot sebagai jumlah+SHA-256, termasuk null, tanggal/status/garansi, token/password hash, nomor, harga/modal, stok, foreign ID, dan identitas toko. Tidak ada raw record di bukti.
2. **Backup/upload/copy:** `deploy/backup.sh` menjalankan mysqldump → gzip nyata. Opsi `- -` menghasilkan **exit 1 yang diharapkan** dengan pesan Q-04 dan arsip lokal utuh; ini bukan backup harian/offsite sukses. Arsip SQL, tar upload dan file logo disalin ke direktori operator lokal, dicek SHA-256, upload di-extract dan hash relatif cocok. Hash arsip/manifest ada di log. Dua direktori di Mac **bukan salinan ke mesin lain**.
3. **Restore:** `deploy/restore.sh` mengimpor salinan arsip operator ke tiga DB baru: target, failed, recovery. Semuanya identik untuk **seluruh 20 tabel/schema/baris lama**, hanya nama database lokal foreign key dinormalisasi setelah memastikan relasinya tetap berada di DB itu sendiri. Gzip terpotong ditolak sebelum import dan target utuh. Gzip valid berisi SQL salah meninggalkan satu tabel/baris pada DB `sqlfail`; warning partial-state benar dan DB itu ditandai discard-only (jangan dipakai/retry).
4. **Migrasi target:** seluruh 11 inventaris bisnis tetap identik; delapan tenant_id semuanya `1`, wajib dan memiliki FK ke tenants. Tenant/store Anugerah dan logo legacy benar. Hash password masih dapat diverifikasi; relasi teknisi dan dua item servis benar. Laporan aplikasi: servis **1.250**, sale **500**, omzet **1.750**, modal **600**, laba kotor **1.150**, biaya **50**, net **1.100**, modal restock **1.600**; stok **7**. Probe counter menghasilkan TTS `010`, JL `005`, lalu transaksi probe di-rollback sehingga baseline tetap identik.
5. **Rollback single tenant:** rollback path migrasi SaaS mengembalikan seluruh schema/baris lama identik; apply ulang identik. Setelah tenant kedua dan kategori bernama sama dibuat, rollback ditolak. Hash **seluruh database termasuk schema, semua data tenant, dan migration history** sebelum/sesudah guard identik.
6. **Kegagalan backfill:** trigger sintetis pada `categories` menolak UPDATE ketika backfill. MySQL telah membuat `tenants` dan tenant_id `users`; `products` belum berubah; migration history belum merekam keberhasilan. Semua field bisnis lama tetap identik, tetapi schema parsial tidak boleh diretry buta. DB failed dipertahankan/discard-only. DB recovery dari restore backup terpisah dimigrasikan sukses dengan semua field lama identik. Ini membuktikan jalur pemulihan via DB baru, bukan menganggap DDL atomik.

## Riwayat harness dan retensi

[rehearsal-initial.log](rehearsal-initial.log): sandbox menolak TCP lokal sebelum DB dibuat. Escalation hanya untuk loopback MySQL. [rehearsal-harness-initial.log](rehearsal-harness-initial.log): `Schema::getTables()` tanpa schema awalnya menginventaris metadata lintas DB; pembacaan row tetap memakai koneksi S008 dan berhenti ketika nama tabel tidak ada. Diperbaiki menjadi schema S008 eksplisit. [rehearsal-schema-normalization.log](rehearsal-schema-normalization.log): perbandingan restore terlalu ketat pada nama DB lokal FK; diagnosis menunjukkan seluruh beda hanya `foreign_schema` source/target, lalu dinormalisasi secara terjaga. Ini kegagalan harness, bukan perubahan/defect produk; seluruh log gagal tetap disimpan. Tidak ada data DB lain dibaca/diubah.

Uji negatif runbook menemukan bahwa exception CLI setelah bootstrap Laravel bisa tercetak dengan exit 0. Blok inventaris kini menangkap exception sendiri dan keluar 1; target salah, hash palsu dan jalur valid benar-benar diuji sesudah perbaikan. Tidak ada exception mentah/secret yang dikirim ke evidence.

Tiga set DB S008 dipertahankan untuk audit: `20261117_a1_*` (run harness awal), `20261117_a1b_*` (normalisasi restore), dan `20261117_a1c_*` (run final), masing-masing lima nama suffix source/target/failed/recovery/sqlfail. Semuanya baru milik S008 dan hanya sintetis; run lama tidak ditimpa. Runtime privat `/private/tmp/saas-s008-a1*` menyimpan dump/upload/diagnostic sintetis di luar Git. Semua `client.cnf` yang dibuat telah dihapus; cache Python generated dari check script dibersihkan. Tidak ada database yang dihapus. [cleanup.json](cleanup.json) mencatat batas ini; QA memakai nama DB baru, jangan mengulang setup terhadap nama retained.

Tidak diuji: MariaDB 10.11/flag client target (S009), transfer luar mesin dan restore salinan produksi, cron/retensi harian, deployment/maintenance/HTTPS nyata (S010–S011), HP/printer fisik. Suite regresi/build S007 pada source 246 file yang identik tidak diulang; yang berubah hanya test/runbook/evidence, lalu pemeriksaan terarah dijalankan. Risiko utama tetap partial DDL/import dan kehilangan transaksi baru bila memulihkan backup lama; runbook menahan maintenance dan memakai recovery kosong/rekonsiliasi owner.
