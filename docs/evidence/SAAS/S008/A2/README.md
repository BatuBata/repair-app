# S008/A2 — kompatibilitas backup MariaDB dan MySQL

**Verdict implementer: PASS self-check; menunggu QA independen S009.** Candidate backup kini mendeteksi keluarga client secara fail-closed: probe gagal atau versi di luar MySQL 8/MariaDB 10.11 menghentikan proses sebelum dump. MariaDB tidak menerima opsi MySQL yang invalid; MySQL tetap menerima `--set-gtid-purged=OFF --column-statistics=0`.

- Root `/Users/muzib/Projects/web/repair-app`, branch `codex/saas-multitenant`, base `4584b8f947418b099603de20bbbe94835e0f2df4`.
- Candidate **`sha256:ce45314d50e2a865026fab4ee75736fa3833e90b070990880adac047a0631c58`**: [candidate.json](candidate.json), 261 file dan algoritme hash. 260 path candidate S008/A1 ditambah harness native A2; bukti output/README dan ledger dikecualikan agar tidak melingkar. [input-check.json](input-check.json) membuktikan hanya tiga path lama berubah, 257 path lainnya identik termasuk seluruh 246 file software S006.
- Model diminta `gpt-6.1-sol`, effort `high`; model efektif **belum terverifikasi**, receipt runtime tidak tersedia kepada worker.
- Ponytail **full** dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project-delivery, kontrak worker coordination, adapter runtime, AGENTS, S008/A2, rancangan §10/12, skrip/caller dan bukti S008/A1 serta S009/A1 dibaca.
- Tidak mengubah ledger, source aplikasi, migrasi, dependency atau bukti S009. Tidak commit/push/deploy atau mengakses server/DB existing/produksi.

## Perubahan terbatas

`deploy/backup.sh`: sesudah pemeriksaan private config/target dan lock, probe `mysqldump --no-defaults --version` mengidentifikasi client. Opsi umum dipertahankan dalam satu array; hanya MySQL 8 menambah dua opsi MySQL. Versi tidak dikenali/di luar rentang atau probe gagal ditolak, lock dibersihkan. Array selalu berisi opsi umum agar tetap bekerja dengan Bash 3.2 + `set -u` di Mac.

`deploy/check-scripts.py`: matriks kegagalan/sukses existing berjalan untuk kedua keluarga. Probe error, client unknown, MySQL 5.7 dan MariaDB 11.4 ditolak tanpa dump/offsite/artefak baru; perilaku lock tetap diperiksa. Private config sebagai argumen pertama, DB eksplisit, tanpa `--databases`, opsi transaksi/hex/trigger, validasi gzip, cleanup, offsite failure dan retensi tetap diperiksa. Tidak ada koneksi MySQL/SSH pada host safety.

`docs/SAAS-MIGRATION-RUNBOOK.md`: menjelaskan deteksi serta pin client MariaDB yang benar; S009 tetap harus memeriksa candidate ini. Gerbang offsite/restore/maintenance produksi tetap berlaku.

## Perintah aktual dan hasil

Semua command dari root di atas. [mariadb-result.json](mariadb-result.json) mencatat command Docker dan argumen backup aktual tanpa nilai credential. [mariadb-check.py](mariadb-check.py) memuat seluruh setup/assert/cleanup fixture sintetis.

| Perintah | Expected | Actual |
|---|---|---|
| `python3 deploy/check-scripts.py` | Kedua keluarga dan guard existing lulus | **41 host safety cases PASS**, [deploy-safety.log](deploy-safety.log) |
| `python3 docs/evidence/SAAS/S008/A2/mariadb-check.py` | Exact native MariaDB menghasilkan arsip lalu berhenti di Q-04 | **PASS**, [mariadb-check.log](mariadb-check.log), [mariadb-result.json](mariadb-result.json) |
| `bash -n deploy/backup.sh` | Shell valid | Exit 0, [shell-syntax.log](shell-syntax.log) |
| Python `ast.parse` atas check-scripts dan harness A2 | Syntax valid tanpa membuat cache | PASS |
| `git diff --check` | Tidak ada whitespace error | Exit 0, [diff-check.log](diff-check.log) |
| Perbandingan SHA-256 seluruh input S008/A1 | Hanya 3 file yang diizinkan berubah | PASS 257 identik; seluruh software S006 tetap identik |

## Native MariaDB tepat 10.11.14

Docker memakai **`--context colima` pada setiap command**, tanpa mengubah context global. Image cached `mariadb:10.11.14`, digest `sha256:dbe56e20372fc6d6b8e0e396866ba89c4c7f128c38c4f59aaa54d957db95790c`, Linux arm64. Container khusus task, `--pull=never`, **`--network none`**, data tmpfs, tanpa port atau mount data existing. Server `10.11.14-MariaDB-ubu2204`; dump `/usr/bin/mariadb-dump`, `Ver 10.19 Distrib 10.11.14-MariaDB`.

Transport `mysqldump` hanya menyalin option file privat ke path yang sama di container dan meneruskan **semua argumen utuh** ke client native; tidak menyaring opsi. Dua panggilan yang tercatat adalah probe `--no-defaults --version` dan dump `--defaults-file=<private> --single-transaction --quick --no-tablespaces --hex-blob --triggers saas_s008_a2_source`. Tidak ada dua flag invalid atau `--databases`.

Akun dump sintetis hanya mendapat SELECT/SHOW VIEW/TRIGGER pada DB sumber task. Root sintetis hanya dipakai bootstrap dan validasi DB disposable. Credential acak berada pada option/env file mode 600, tidak pada CLI atau bukti. `backup.sh` dan gzip berjalan di Mac, client/server di container; ini bukan pengujian host Debian produksi.

Hasil final:

- Exit backup **1**, stderr tepat `Local archive saved; Q-04 off-VPS destination missing; retention skipped (NOT a successful daily backup)`. Bukan kegagalan dump dan bukan backup offsite sukses.
- Tepat satu arsip gzip valid, **1.013 byte**, SQL 2.972 byte, SHA-256 `f941b8a1e3bc015bfaef78520b2ef2f168fe26abfe5ca1a8dc2265e280981b17`.
- Dump berisi tabel, baris biner `0001FEFF` dan trigger; tanpa CREATE DATABASE/USE. Import arsip yang sama ke DB scratch kosong berhasil, nilai row `7 / 0001FEFF` cocok.
- Tidak ada `.work-*`/lock tertinggal. Container dan tmpfs dihapus, container dipastikan tidak ada; runtime privat beserta credential/dump dihapus. Cache image/Colima bersama dipertahankan. Cleanup kedua run tercatat pada masing-masing result; tidak ada DB existing dihapus.

## Riwayat pemeriksaan yang dipertahankan

[deploy-safety-initial.log](deploy-safety-initial.log): pemeriksaan lock menemukan probe diletakkan terlalu awal. Probe dipindah sesudah lock sebelum verifikasi final; seluruh 41 kasus kemudian lulus.

[mariadb-check-initial.log](mariadb-check-initial.log) / [mariadb-result-initial.json](mariadb-result-initial.json): backup native sudah menghasilkan arsip valid dan exit Q-04 yang benar; pemeriksaan restore tambahan fixture keluar 1. Fixture awal memakai nama trigger/table dengan qualifier DB sumber. Harness lalu memakai nama lokal tanpa qualifier dan menangkap stderr jika command gagal; run final restore lulus. Stderr run awal tidak direkam, sehingga penyebab pasti exit tersebut tidak diklaim. Candidate backup tidak berubah antara dua run native. Bukti gagal tidak ditimpa.

Bukti final dipin pada [evidence-manifest.json](evidence-manifest.json); hasil ringkas di [result.json](result.json).

Belum diuji: native MySQL 8 pada candidate A2 (perilaku flag/stub lulus, hasil native historis S008/A1 dipertahankan), full suite/build aplikasi yang source-nya identik, transfer/restore luar mesin nyata, cron harian, server/produksi dan perangkat fisik. Risiko: impor/DDL dapat parsial, dan pemulihan backup lama dapat kehilangan transaksi baru; QA independen, offsite teruji dan maintenance tetap wajib sebelum S010.
