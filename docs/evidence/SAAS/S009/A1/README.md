# S009/A1 — QA independen migrasi Anugerah

**Verdict: FAIL, cacat kompatibilitas client backup yang teramati.** Pada MariaDB **10.11.14**, `deploy/backup.sh` keluar **1** tanpa arsip selesai. Perintah dump persisnya keluar **7**:

```text
/usr/bin/mariadb-dump: unknown variable 'set-gtid-purged=OFF'
/usr/bin/mariadb-dump: unknown variable 'column-statistics=0'
```

Ini bukan exit 1 Q-04 yang diharapkan runbook setelah arsip lokal berhasil. Gate S009/S010 belum dilepas. Source/runbook/script/test tidak diperbaiki oleh tester.

- Checked candidate S008: `sha256:066cd061d3b189d9873333a06e3ecf46a493469cc096715b1461b758157b8157`, commit konten `66795fb`; HEAD saat QA `2641f9396c46ff16304ccc7bb6a461ca15ecf339` hanya menambah status ledger. Seluruh **260 file dan digest gabungan cocok**, sebelum/sesudah QA; [input-check.json](input-check.json), [scope-check.json](scope-check.json).
- Input S007 accepted: `sha256:6ba364a204d98ac1d9754182d2a4fd1c54de6f940d7341ad88bef207aea74ad4`; software S006 `sha256:066bb9e82bd3ac5142a1ef516fc46c862c7e3b1b2cbc28ee4eb0a8ab402996f6`.
- Model diminta `gpt-6.1-sol`, effort `high`; model efektif/receipt runtime **belum terverifikasi**.
- Ponytail **full**, dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project-delivery, QA/release, kontrak worker, AGENTS root aktual, kontrak S009, rancangan §10–12, runbook, S008 dan skrip deploy dibaca. Peran tester independen; hanya direktori bukti ini dan runtime privat yang ditulis, tanpa ledger/commit/push/deploy/server.

## Lingkungan nyata

Colima 0.9.1/Lima 2.0.3 awalnya berhenti; driver VZ tidak terdaftar, QEMU belum ada. Coordinator mengizinkan dan memasang QEMU 11.1.3 sebagai **runtime QA lokal**, tanpa dependency aplikasi. Startup QEMU berhasil dengan `--activate=false`, sehingga konteks Docker global tetap `default`. Pull pertama terputus `unexpected EOF`; satu retry dengan layer cache berhasil. Riwayat ada di [environment.json](environment.json), [colima-start.log](colima-start.log), [image-pull-initial.log](image-pull-initial.log), [image-pull.log](image-pull.log).

Image **`mariadb:10.11.14`**, digest **`sha256:dbe56e20372fc6d6b8e0e396866ba89c4c7f128c38c4f59aaa54d957db95790c`**, `linux/arm64`; server `10.11.14-MariaDB-ubu2204`; client/dump Distrib **10.11.14**. Ini container Linux arm64 lokal, bukan host Debian produksi. [runtime.json](runtime.json) memuat identitas yang diperiksa, tanpa environment secret.

Container `saas-s009-a1-mariadb`, volume `saas-s009-a1-data`, port **127.0.0.1:13319**. Lima database baru, keberadaannya ditolak bila sudah ada: `saas_s009_a1_{source,target,failed,recovery,sqlfail}`. Root sintetis hanya membuat database/akun di container ini; akun restore `s009` diberi hak **hanya lima DB QA tersebut**, tanpa grant global. Tidak memakai `.env` untuk credential koneksi QA; tidak mengakses MySQL existing/VPS/produksi. Option file mode 600, password acak hanya di runtime privat dan container sendiri. Dua nilai credential privat dipindai terhadap seluruh bukti, **0 kecocokan**: [secret-scan.json](secret-scan.json).

Client dalam container dicapai lewat [mysql-transport.sh](mysql-transport.sh) dan [mysqldump-transport.sh](mysqldump-transport.sh): config privat disalin ke path sama di container, semua argumen diteruskan **utuh**, tanpa menyaring flag. `mysql` dipetakan ke `mariadb`, `mysqldump` ke `mariadb-dump` asli 10.11.14. Bash/gzip/tar dan script candidate berjalan di Mac.

## Perintah aktual dan hasil

Dari `/Users/muzib/Projects/web/repair-app`, PHP selalu `/opt/homebrew/opt/php@8.3/bin/php`. `R=/private/tmp/saas-s009-a1-20261117` di bawah hanya singkatan path runtime yang benar-benar dipakai; runtime kini sudah dibersihkan. Tidak menjalankan reset/drop/migrate:fresh atau seed aplikasi existing.

| Perintah aktual | Expected | Actual |
|---|---|---|
| Recompute sorted path + NUL + file bytes dan hash file | Candidate/input cocok | 260 file, 0 mismatch; digest cocok |
| `php artisan test --compact tests/Feature/Tenancy/MigrationTest.php` | Semua tes migrasi lulus | **3 tests / 23 assertions**, [log](migration-test.log) |
| `python3 deploy/check-scripts.py` | Guard host lulus | **31 PASS**, memakai stub; bukan bukti backup nyata, [log](deploy-safety.log) |
| `python3 deploy/ci-checks.py` | Guard yang tersedia lulus | **12 PASS**; GNU tar packager/Linux receiver **NOT_RUN** di Mac, [log](ci-safety.log) |
| `bash -n deploy/backup.sh deploy/restore.sh deploy/deploy.sh deploy/ci-remote.sh` | Syntax valid | Exit 0 |
| `PATH="$R/bin:$PATH" php docs/evidence/SAAS/S009/A1/qa.php` | Seluruh workflow candidate kompatibel | Harness exit 0 karena berhasil membuktikan cacat; **candidate FAIL**, diagnostic checks PASS; [harness](qa.php), [log](mariadb-qa.log) |
| `bash deploy/backup.sh "$R/client.cnf" saas_s009_a1_source "$R/backup" - -` | Arsip utuh lalu exit 1 dengan pesan Q-04 | **Exit 1, `Dump/compression failed`, 0 arsip selesai** |
| `mysqldump --defaults-file="$R/client.cnf" --single-transaction --quick --no-tablespaces --set-gtid-purged=OFF --column-statistics=0 --hex-blob --triggers saas_s009_a1_source` | Dump valid | **Exit 7**, kedua unknown variable di atas |
| Perintah dump sama, hanya dua flag MySQL dihapus, lalu gzip/copy/checksum | Diagnosis komponen lanjut tanpa memperbaiki candidate | Dump diagnostik valid; SHA copy DB/upload cocok; tiga restore identik |
| `bash deploy/restore.sh "$R/client.cnf" DB "$R/operator-copy/native.sql.gz" "RESTORE:DB"` untuk target/failed/recovery | Client MariaDB import benar | Ketiga restore seluruh 20 tabel/schema/baris identik |
| `python3 docs/evidence/SAAS/S009/A1/inventory-check.py` | Blok PHP runbook utuh valid; target/baseline salah ditolak | Source capture dan migrated recovery verify exit0; wrong DB/false baseline exit1, [hasil](runbook-inventory.json) |
| `php -l docs/evidence/SAAS/S009/A1/qa.php`, `php -l "$R/inventory.php"`, `git diff --check` | Syntax/whitespace valid | Exit 0 |

## Bukti data, aplikasi dan kegagalan

Harness independen membuat schema pre-SaaS dari migrasi lama dan fixture statis candidate `MigrationTest::seedLegacy`, lalu menambah satu akun teknisi **nonaktif** dengan remember token sintetis. Baseline mencakup **semua kolom lama di 19 tabel non-migration**, termasuk hash password/token, null, tanggal/status/garansi, nomor, biaya/harga/modal, stok, creator/teknisi dan identitas toko. Source mempunyai 20 tabel total, 3 akun (2 aktif/1 nonaktif), 2 TTS (diambil/batal), 2 item servis, 1 kategori/produk/setting/sale/item/restock/expense, 2 counter. Semua foreign key dipastikan tetap pada database sendiri sebelum nama schema lokal dinormalisasi untuk pembandingan restore. Full hash tambahan mencakup schema/index/FK, seluruh baris dan migration history.

- **Copy/restore diagnostik:** checksum SQL gzip dan tar upload cocok pada dua direktori lokal; file logo extract SHA-256 `6b1048f8a6d40bac0b2954c18fefa40c4ea7a96120fc2e54b7317c0e43c2bbec`. Tiga DB fresh hasil `restore.sh` identik untuk seluruh 20 tabel/schema/baris. Ini **bukan transfer luar mesin/offsite**, dan tidak mengubah kegagalan `backup.sh` menjadi PASS.
- **Migrasi/app:** seluruh field lama identik. Delapan tenant_id wajib, ber-FK ke tenants dan semua baris bernilai `1`; tepat satu tenant aktif **Anugerah**, identitas/`logos/legacy-s008.png` lama terjaga. Unique key tenant+kategori/nomor/setting/counter benar. Hash password valid, akun nonaktif/token tetap, relasi TTS/kategori/teknisi/item dan sale/item/product serta stok/creator tetap benar. Stok **7**. Laporan `2026-10-31`: servis **1250**, sale **500**, omzet **1750**, modal **600**, laba kotor **1150**, biaya **50**, net **1100**, restock **1600**. Probe nomor **TTS-2610-010/JL-2610-005** dibatalkan dalam transaksi; counter/baseline tetap. Baca service/controller nyata: HTML nota dengan logo embedded, public PDF (**23974 byte**, `%PDF-`), nota sale, tracking, dan laporan lulus. Ini smoke service/controller, bukan browser/login HTTP penuh atau review visual PDF.
- **Rollback:** single-tenant `migrate:rollback --path=database/migrations/2026_11_17_000000_add_tenant_foundation.php --force` mengembalikan seluruh schema dan baris lama identik; reapply identik. Tenant kedua dan kategori bernama sama dibuat; rollback ditolak dan **hash seluruh schema, data kedua tenant, dan migration history tidak berubah**.
- **Gzip rusak:** arsip terpotong ditolak sebelum client DB dipanggil; hash target tetap sama.
- **SQL parsial:** gzip valid membuat satu tabel/baris lalu SQL error. `restore.sh` memperingatkan target mungkin parsial; baris parsial teramati. DB diberi status **unhealthy/discard-only**, tidak di-retry/diklaim sehat.
- **DDL/backfill parsial:** trigger sintetis menolak update kategori. `tenants` dan kolom tenant users sudah menetap, products belum berubah, migration history belum mencatat sukses; semua field bisnis lama tetap sama. DB gagal **unhealthy/discard-only**, tanpa retry/down. Recovery pada DB baru yang terpisah dari backup utuh berhasil migrasi dengan semua field lama identik.

Runbook audit [runbook-review.json](runbook-review.json) menerima lock/maintenance, backup-offsite gate, target eksplisit/scoped privilege, menjaga key/upload, menahan partial state, dan larangan blind retry/restore menimpa live. Masalah client backup tetap **FAIL** walaupun runbook sudah menandainya sebagai gate S009.

## Koreksi minimal yang disarankan

Perbaiki kompatibilitas **satu jalur backup existing**: pilih argumen dump menurut keluarga client yang dipin. MariaDB 10.11 memakai argumen yang diuji di atas **tanpa** `--set-gtid-purged=OFF`/`--column-statistics=0`; MySQL 8 tetap memerlukan perlakuan GTID yang aman, jadi jangan menghapus dua flag secara global tanpa mempertimbangkan client MySQL. Pertahankan private option file, target eksplisit tanpa `--databases`, pipefail, validasi gzip, lock, offsite/retensi dan failure cleanup. Tambah kasus safety client MariaDB ke pemeriksaan existing, perbarui runbook/client pin, lalu QA ulang subset backup nyata pada candidate baru. Tidak perlu mengulang software S007 yang tetap identik.

## Cleanup dan batas

[cleanup.json](cleanup.json): container, volume dan semua lima DB QA telah dihapus; runtime privat, dump/upload/config/credential sintetis dihapus. Colima dan cache image nonsecret dipertahankan sebagai runtime bersama; tidak menghentikan/menghapus resource bersama. `deploy/__pycache__/` sudah ada sebelum task dan dibiarkan. Seluruh bukti hanya di S009/A1; source/runbook/script/test/ledger tidak berubah. Manifest mem-pin bukti, bukan menerima candidate.

Belum diuji: backup candidate yang berhasil pada client MariaDB (gagal nyata, perlu koreksi), transfer/restore luar mesin dan data produksi, host Debian target nyata, cron/retensi harian, Linux CI receiver/GNU packager, deployment/maintenance/HTTPS produksi, login/browser penuh, inspeksi visual PDF, HP/printer fisik. Regresi/build S007 atas 246 file software identik tidak diulang. Risiko utama: backup candidate belum menghasilkan arsip pada client target; import/DDL dapat parsial dan restore backup lama dapat kehilangan transaksi baru. Gate maintenance/offsite/QA tetap wajib sebelum S010.
