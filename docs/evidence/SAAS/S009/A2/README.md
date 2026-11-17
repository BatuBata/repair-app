# S009/A2 — QA independen koreksi backup

**Verdict: PASS** pada commit `e790e7b019e2dd3b4569cf118f7136119658ad9f`, candidate **`sha256:ce45314d50e2a865026fab4ee75736fa3833e90b070990880adac047a0631c58`**. Cacat kompatibilitas MariaDB A1 tertutup oleh pengujian native baru. Riwayat S009/A1 tetap **FAIL**, tidak ditimpa.

Ponytail **full** memakai skill yang telah dibaca lengkap `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery tester/QA contract tetap berlaku. Kontrak S009/A2, S008/A2 dan diff A1 dibaca. Model diminta `gpt-6.1-sol high`; model efektif/receipt **belum terverifikasi**. Hanya bukti A2 dan runtime privat ditulis; tanpa perubahan source/ledger/commit/push/deploy/produksi.

[Pin independen](input-check.json): **261 file**, digest gabungan dan semua hash cocok; hanya `deploy/backup.sh`, `deploy/check-scripts.py`, dan pengantar/client gate runbook berubah pada 260 path lama. Satu harness pengembang A2 ditambahkan. **257 path lama dan seluruh 246 file aplikasi tetap identik**. Manifest S009/A1 sebanyak **22 file** diperiksa ulang, 0 mismatch. PHP lokal tetap **8.3.30**.

## Pengujian native baru

[Harness independen](native-check.py), [hasil terstruktur](native-result.json), [log](native-check.log), [transport argumen utuh](client-transport.py). Ini bukan pengulangan harness implementer.

Image cached **`mariadb:10.11.14`**, digest **`sha256:dbe56e20372fc6d6b8e0e396866ba89c4c7f128c38c4f59aaa54d957db95790c`**, `linux/arm64`; server `10.11.14-MariaDB-ubu2204`; native client/dump Distrib **10.11.14**. Container baru `saas-s009-a2-qa`, **`--network none`**, DB pada tmpfs khusus, tanpa port/mount/shared DB. Root sintetis hanya bootstrap/validasi container sendiri; akun `qa_ops` hanya SELECT/SHOW VIEW/TRIGGER pada source dan ALL pada scratch, tanpa grant global. Credential acak melalui option/env file mode 600, tidak pada CLI/Git/log.

Fixture dua tabel dengan PK/FK, angka positif/negatif, null, UTF8, tanggal, binary `0001FEFF`, dan trigger tanpa perubahan data. Source/scratch baru; baseline dibandingkan sebagai lima SHA-256 atas seluruh row, CREATE TABLE dua tabel, dan metadata trigger. Transport hanya menyalin option file privat, mencatat argumen, lalu meneruskan seluruh argumen tanpa filter kepada binary asli. Probe tidak memuat credential.

| Perintah aktual dari root proyek | Expected | Actual |
|---|---|---|
| `python3 docs/evidence/SAAS/S009/A2/native-check.py` | Backup/restore native dan fail-closed lulus | **PASS**, exit0; container/runtime dibersihkan dalam finally |
| `bash deploy/backup.sh CLIENT_CNF saas_s009_a2_source BACKUP_DIR - -` (path privat persis di native-result) | Arsip valid lalu gerbang Q-04, bukan backup offsite sukses | **Exit1** dengan pesan Q-04 tepat; gzip **1272 byte**, SQL **3988 byte**, arsip SHA-256 `2701a904a43edbd7e357d23808f4212df416ff38a9ed3121091837852b2a3dd9` |
| Native probe `mysqldump --no-defaults --version` | Mengidentifikasi MariaDB10.11.14 | Tepat satu probe sebelum dump |
| Native dump `mysqldump --defaults-file=CLIENT_CNF --single-transaction --quick --no-tablespaces --hex-blob --triggers saas_s009_a2_source` | Opsi aman, target eksplisit | Tepat argumen tersebut; tanpa dua opsi MySQL, `--databases`, CREATE DATABASE, atau USE |
| Copy arsip privat, bandingkan SHA-256, `bash deploy/restore.sh CLIENT_CNF saas_s009_a2_scratch COPIED_ARCHIVE RESTORE:saas_s009_a2_scratch` | Copy utuh; import client MariaDB valid | Checksum sama; **restore exit0**, seluruh field/schema/FK/trigger snapshot identik |
| Enam fake probe independen: unknown, kosong, MariaDB10.10, MySQL9, ambigu MySQL+MariaDB, probe exit17 | Menolak sebelum dump/artifact/retensi | Semua **exit1**; hanya probe dipanggil, daftar path folder backup tetap; lock/work bersih |
| `python3 deploy/check-scripts.py` | Guard kedua keluarga dan existing safety lulus | **41 PASS**, [log](deploy-safety.log); MySQL8 menerima kedua flag khusus, MariaDB tidak; explicit target, trigger/hex, config pertama, offsite/failure/retensi/lock diperiksa |
| `bash -n deploy/backup.sh deploy/restore.sh`; Python AST check guard/harness; `git diff --check` | Syntax/whitespace valid | Exit0 |

Arsip lama berumur 20 hari tetap ada saat Q-04 pending, sehingga retensi tidak dijalankan sebelum offsite sukses. Tidak ada `.work-*` atau lock tertinggal. Native run hanya membuktikan salinan antar direktori lokal, **bukan offsite**.

Percobaan native pertama gagal saat bootstrap fixture: trigger `SET NEW.amount=NEW.amount` memakai definer yang sengaja tidak memiliki UPDATE sumber. Itu kegagalan fixture QA sebelum candidate dijalankan; [log awal](native-check-initial.log) dan [hasil awal](native-result-initial.json) dipertahankan. Fixture diubah menjadi `DO 0`, tanpa menambah privilege/source change. Container/runtime pertama dibersihkan; final run memakai container/data/credential baru dan lulus.

## Bukti A1 yang tetap berlaku

[reuse-map.json](reuse-map.json) mem-pin case ID, producer, input hash, kondisi dan alasan. Reuse berasal dari **tester independen S009/A1**, bukan hanya self-check implementer. Migrasi, fixture, restore script, model/action, report/document dan seluruh 246 file aplikasi identik; engine/image/PHP relevan sama. Bagian runbook sejak gate owner maintenance sampai akhir **byte-identik**, sehingga maintenance/offsite/recovery gate tidak melemah.

- `diagnostic-backup-copy-restore`: tiga restore seluruh 20 tabel/schema/baris identik dan upload hash sama. Bagian **backup candidate yang gagal A1 tidak digunakan ulang**; A2 membuktikan backup yang diperbaiki secara native.
- `migrated-inventory-app-smoke`: seluruh field lama pada 19 tabel non-migration identik; delapan tenant_id wajib/FK semuanya1, tenant aktif Anugerah, akun aktif/nonaktif/password/token, relasi, stok7, nomor/counter, laporan dan HTML/logo/public PDF/sale/tracking lulus.
- `rollback-reapply-second-tenant-guard`: rollback single-tenant mengembalikan seluruh schema/baris; reapply identik; tenant kedua ditolak sebelum perubahan schema/data/history.
- `restore-failures`, `partial-ddl-recovery`: gzip rusak tidak menyentuh target; SQL parsial/DDL gagal dinyatakan unhealthy tanpa retry/down; DB recovery baru berhasil. Inventory runbook positive/negative dan MigrationTest **3/23** juga tetap berlaku.

Perilaku MySQL8 candidate baru diperiksa lewat **41 guard yang dijalankan tester**. Native MySQL8 historis S008/A1 tetap bukti pendukung untuk opsi yang sama; **bukan** klaim native MySQL8 baru/QA independen A2. Tidak mengulang suite/build atau migrasi penuh pada kode dan kondisi relevan yang identik.

## Cleanup dan batas

Kedua native run menghapus container sendiri, tmpfs/database dan seluruh runtime/config/dump/credential privat; container dipastikan tidak ada. Dua nilai credential asli diperiksa terhadap bukti/result dan tidak ditemukan. Colima/image cache bersama dipertahankan; tidak menyentuh database existing/produksi. Scope/source hash sesudah QA ada di [scope-check.json](scope-check.json); bukti dipin pada [manifest.json](manifest.json).

Belum diuji: native MySQL8 baru pada A2, salinan/restore luar mesin atau data produksi, host Debian target, cron/retensi harian nyata, Linux receiver/GNU packager, deployment/maintenance/HTTPS produksi, browser/login penuh, visual PDF, HP/printer fisik. Risiko partial import/DDL dan kehilangan transaksi setelah backup tetap ditangani runbook. PASS ini melepas QA S009 lokal; **tidak menggantikan gerbang offsite/maintenance/otorisasi S010**.
