# T020 — verifikasi MySQL nyata, sumber tetap

Verdict: **PASS untuk verifikasi paket lokal T020**, bukan penerimaan rilis atau bukti operasi VPS. Hasil BLOCKED pada [handoff A1](HANDOFF.md) tetap disimpan sebagai riwayat sebelum akses database tersedia; status lingkungan terbaru dibuktikan oleh laporan ini. Tidak ada perubahan skrip/runbook.

`checked_revision`: `a5a95970f5ff0caefdf8d85e89bc60604cd0638f`.
`artifact_ref`: `docs/evidence/T020/mysql-verification-assessment.json`.
`revision`: commit evidence T020 yang memuat laporan ini; SHA aktual dan hash remote diberikan pada worker_done sesudah commit/push.
`inputs`: `T001=4107c49d9098ab47c465773857fd5ee2811eb1b4`, `T002=e5706f31321d7540cdcaec5f2792248b9c324841`.
T002 adalah **input baseline sintetis**, bukan dependency yang diterima/PASS; kegagalan tes konkurensi T002 tidak dinilai ulang oleh T020.

Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; aturan Project Delivery/QA worker tetap dipakai. Brief `/tmp/repair-app-t020-mysql-verification.md` dan TODO/AGENTS kanonik dibaca. Resource `db:mysql-test` diberikan eksklusif oleh coordinator. Model yang diminta tetap Sol high; identitas backend efektif tidak dapat diverifikasi, tanpa fallback atau helper worker.

## Apa yang benar-benar dijalankan

1. Hash inventaris T002 pada worktree cocok byte demi byte dengan berkas dalam commit `e5706f3`: SHA-256 `16e059297412c17bbf5950f652cf1ab3e4f59ab0813eda51a04527c26fd7e7e1`. Koneksi PDO dan MySQL client dikunci ke loopback TCP / `repair_app_test`, server 8.0.31. Target awal cocok dengan 20 tabel InnoDB/0 view, jumlah baris dan ringkasan identitas TTS/counter/settings acuan; tidak ada probe, routine, event atau trigger. Data T002 berasal dari migrate/seed dan empat worker sintetis pada DB yang sebelumnya kosong.
2. Helper privat memakai installed Dotenv untuk membaca `.env` kanonik, tanpa boot aplikasi, tanpa mencetak rahasia. File pilihan client mode 600 dibuat di direktori privat 700, option koneksi dikutip aman, tidak ada `.mylogin.cnf`. MySQL client dengan `--defaults-file=<private-path>` membuktikan `DATABASE()=repair_app_test` dan versi server sebelum penulisan.
3. Setelah seluruh guard baseline lulus, hanya satu tabel baru `t020_backup_probe` dibuat, satu baris id 1 / marker `T020-synthetic-7` diisi dan dibaca kembali. Fingerprint privat semua isi baris dan definisi tabel disimpan untuk pembandingan; tidak ada password hash atau tracking token yang diekspor ke evidence.
4. Skrip **tidak berubah** dijalankan: `bash deploy/backup.sh <private-client-file> repair_app_test <empty-private-backup-dir> - -`. Actual exit **1**, sesuai expected cabang Q-04 belum tersedia, dengan pesan `Local archive saved; Q-04 off-VPS destination missing; retention skipped (NOT a successful daily backup)`. Tepat satu arsip baru terbentuk, `gzip -t` exit 0; tidak ada retensi maupun koneksi luar VPS. Ini membuktikan dump MySQL nyata dan jalur pending-offsite, **bukan** keberhasilan backup harian lengkap.
5. Hanya marker probe diubah menjadi `T020-mutated`, tepat satu baris, dan dibaca kembali. Sebelum mutasi, seluruh snapshot masih cocok. Kemudian `bash deploy/restore.sh <private-client-file> repair_app_test <exact-new-archive> RESTORE:repair_app_test` memakai dump privat tepercaya tersebut; actual import MySQL **exit 0**. Tidak ada reset/drop DB, create user/DB, perubahan `.env`, koneksi aplikasi aktif, atau restore ke `repair_app`.
6. Seluruh jumlah baris dan fingerprint isi baris kembali persis sama dengan snapshot sebelum mutasi; marker kembali `T020-synthetic-7`. Kondisi akhir 21 tabel/0 view: 20 tabel acuan beserta seluruh identitasnya tetap utuh, ditambah satu tabel/satu baris probe yang sengaja ditinggalkan. `repair_app` tidak diquery maupun diubah oleh task ini.

Arsip dipertahankan di lokasi privat yang dirujuk pada assessment JSON, di luar Git, mode 600. Direktori induk dan backup mode 700. **Hanya file koneksi rahasia yang dibuat task ini telah dihapus**, sesudah import/validasi awal, termasuk saat assertion gagal; berkas recovery lain tetap privat. Tidak ada salinan dump/secret yang dipush.

## Kegagalan assertion yang dipertahankan

[Log hasil awal](mysql-verification-result.json) mencatat **FAIL** pada assertion tambahan yang menuntut seluruh teks `SHOW CREATE TABLE` identik byte demi byte; dump dan import skrip sendiri selesai sesuai expected. Pemeriksaan baca-saja menemukan semua baris/identitas sudah identik, sementara teks definisi 21 tabel menambahkan deklarasi kolom `CHARACTER SET utf8mb4` yang sama dengan charset bawaan tabel.

Diagnosis membuktikan untuk **setiap tabel**: definisi di dump cocok dengan hash snapshot asli; setelah import, menghapus **hanya** deklarasi charset redundan yang sama itu membuat seluruh teks definisi kembali identik. Charset/collation, indeks, constraint, tipe kolom dan seluruh bagian lain tidak berubah. Karena itu restore data/skema setara dinilai PASS pada [assessment final](mysql-verification-assessment.json); kegagalan string literal awal tetap terlihat sebagai keterbatasan assertion evidence. Tidak ada modifikasi sumber, mutasi data tambahan, atau import kedua untuk mendapat PASS.

## Bukti dan durasi

- [mysql-verification-result.json](mysql-verification-result.json): perintah aktual (hanya path file rahasia), exit expected/actual, output aman, timestamp UTC, arsip SHA-256/ukuran, dan kegagalan assertion awal.
- [mysql-verification-assessment.json](mysql-verification-assessment.json): inventaris sebelum/sesudah, identitas baris cocok, normalisasi schema tepat per tabel, batas penerimaan, dan izin file.
- [source-sha256.txt](source-sha256.txt): seluruh delapan berkas sumber operasional/runbook masih cocok; `git diff a5a9597 -- deploy docs/DEPLOYMENT.md` kosong. Hanya evidence baru yang di-commit pada follow-up ini.
- Durasi urutan perintah nyata terukur **1.9511 detik**, termasuk assertion awal; dump nyata/cabang Q-04 **0.2494 detik**, import nyata **0.4835 detik**. Diagnosis baca-saja tambahan tidak dimasukkan ke angka tersebut; tidak ada estimasi biaya/waktu model.
- Bukti [31 kasus stub + tiga syntax check](host-check-final.log) digunakan kembali dari sumber identik `a5a9597`, tidak diulang. Tidak ada build/suite aplikasi atau dependency baru.

Sisa: QA independen/integrasi; Nginx/FPM, cron, HTTPS/renewal, transfer luar VPS nyata, dan smoke produksi tetap NOT_RUN menunggu Q-03/Q-04/izin. ShellCheck tetap tidak tersedia menurut pemeriksaan sebelumnya. Root MySQL hanya diizinkan untuk uji lokal ini; konfigurasi produksi wajib memakai akun terbatas sesuai runbook, dan pemulihan produksi tetap berisiko kehilangan transaksi/bersifat tidak atomik.

Commit/push branch pekerjaan diizinkan instruksi user 12 November 2026 pada AGENTS/TODO kanonik; push bukan merge main, penerimaan fitur, atau deployment. Ledger kanonik tidak diubah worker.
