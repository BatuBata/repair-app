# Aturan proyek — Aplikasi Servis Anugerah (repair-app)

**Status proyek: T001–T024 SELESAI — QA perangkat lunak independen PASS dan demo HTTPS aktif pada 14 November 2026. Goal T001–T023 tetap selesai. Deployment demo mengikuti D-20/D-21; backup ditunda oleh user melalui D-22. Status verifikasi web T025 mengikuti TODO.md; penerimaan penuh R001 masih membutuhkan uji transaksi produksi, HP Android/printer nyata, dan serah terima owner. Riwayat PASS/FAIL dipertahankan di TODO.md.**

Bahasa: Indonesia sederhana. Jelaskan istilah teknis saat pertama dipakai, laporkan hasil pemeriksaan yang benar-benar dijalankan, dan minta persetujuan sebelum menghapus data atau deploy. Commit dan push branch pekerjaan ke origin sudah diizinkan user 12 November 2026; tidak perlu meminta ulang setiap task.

## Sumber keputusan

- Kebutuhan produk: `PRD.md` (v1.2, disetujui). Jangan memperluas scope tanpa keputusan user yang dicatat di §20.3.
- Rencana dan status kerja: `TODO.md`, ledger JSON tunggal. Validasi setelah mengubah ledger:
  `python3 ~/.agents/skills/project-delivery/scripts/validate_todo.py TODO.md --write-overview --check-prd`
- Tampilan: `DESIGN.md` (token, komponen, aturan) dan layar acuan di `mockup/` yang disetujui user. Setiap layar dibandingkan dengan layar mockup yang sama. Tombol aksi baris selalu di kolom paling kiri; lebih dari satu aksi memakai menu pilihan `···`.
- Fakta produk untuk desain: `PRODUCT.md`.
- `.impeccable/` dan `.taste-skill/` berisi catatan arah desain dan bukti review; bukan kode aplikasi.

## Cara bekerja

User 14 November 2026 melalui D-23 mengizinkan GitHub CI/CD: push `main` yang lolos tes memperbarui demo D-20. T026–T029 mencakup pipeline, QA independen, integrasi dan aktivasi nyata; ini tidak membuka kembali penerimaan HP/printer T025. Identitas source auto-deploy memakai `.release-revision`, bukan Git HEAD instalasi awal di VPS.

Gunakan skill `project-delivery` (sumber: `~/.agents/skills/project-delivery/SKILL.md`) untuk triase, dependensi, penugasan, QA, integrasi, dan rilis. Batas eksekusi terkini dibaca dari `project.execution` di `TODO.md`; Goal T001–T023 sudah selesai. T024 demo sudah live; pekerjaan T025 terbatas pada izin dan bukti perangkat yang tersedia. Ikuti D-20/D-21/D-22 untuk target, database, dan penundaan backup. Gunakan koordinasi/worker bawaan Codex; jangan menjalankan orchestration Orca. Jangan menyamakan cakupan task dengan izin push/deploy.

- Satu coordinator menulis ledger. Worker mengerjakan satu task dan mengembalikan perubahan serta bukti, tanpa mengubah status pusat atau membuat worker lain.
- Maksimal dua worker aktif (termasuk tester). Maksimal dua upaya implementasi per task.
- Coordinator dan semua worker memakai skill `ponytail` level full (lihat `~/.codex/AGENTS.md`).
- Model per task ada di `model_hint` (`gpt-6-luna`, `gpt-6.1-terra`, `gpt-6.1-sol`). Katalog Codex diverifikasi dari tool native; catat model yang diminta dan receipt aktual sebelum mengklaim model efektif.
- Untuk pekerjaan tampilan, skill `design-taste-frontend` tersedia di `~/.codex/skills/`; aturannya sudah dituangkan ke `DESIGN.md`, yang berlaku bila ada perbedaan.

## Lingkungan lokal (diperiksa 12 Nov 2026)

- `php` bawaan di PATH adalah **7.4** dan tidak cukup untuk Laravel 13. Gunakan PHP 8.3 khusus proyek ini tanpa mengubah PHP global:
  - PHP: `/opt/homebrew/opt/php@8.3/bin/php` (8.3.30; ekstensi pdo_mysql, mbstring, intl, gd, dom, zip, sqlite tersedia)
  - Composer: `/opt/homebrew/opt/php@8.3/bin/php $(command -v composer) <perintah>`
  - Artisan: `/opt/homebrew/opt/php@8.3/bin/php artisan <perintah>`
  - Mengubah PHP global (`brew link`) memerlukan persetujuan user.
- Composer 2.8.6, Node 22, npm 10, Git 2.50.
- MySQL 8.0.31 lokal dapat diakses memakai user `root` dari `.env` (izin user 12 Nov 2026). Password tidak boleh dicetak/dikirim chat atau masuk Git. ADMIN_INITIAL_PASSWORD aplikasi masih menunggu isian user; pengujian DB boleh memakai password admin sintetis dari fixture privat.
- Database yang dibutuhkan: `repair_app` (lokal) dan `repair_app_test` (uji konkurensi, resource `db:mysql-test` eksklusif). Feature test lain memakai SQLite in-memory.
- Repo Git lokal sudah dibuat T001. Fondasi M1 telah lulus QA independen T008; status integrasi/penerimaan terbaru mengikuti TODO. Kandidat dan bukti lama tetap dipertahankan di branch/worktree, sedangkan pekerjaan lanjutan memakai worker Codex native. Remote `origin`: `https://github.com/BatuBata/repair-app.git`; user 12 Nov 2026 mengizinkan commit dan push setiap pekerjaan selesai. Push checkpoint/candidate tidak menerima fitur atau melepas gate QA sebelum merge ke main.

## Blocker yang diketahui

- Eksekusi lokal serta commit/push branch diizinkan user 12 November 2026; deployment demo D-20 diizinkan user 14 November 2026; target lain belum diizinkan.
- Akses MySQL pulih. T020 backup/restore lokal nyata sudah PASS (evidence6095a5c); repair_app_test mempertahankan20 tabel dan fixture T002A4 (8TTS/8penjualan); uji T013 menambah2 akun/1barang/1penjualan/1item milik task serta JLcounter+1 tanpa mengubah baris lama lain (inventaris di bukti T013/TODO); snapshot21 tabel T020 sebelumnya dipertahankan privat. QA independen T016 menambah fixture miliknya (3 akun,1 kategori,1 barang,2 TTS dan2 counter periode3101; sale/item fixture sendiri dihapus lewat action untuk uji restore sekali); seluruh baris baseline20 tabel tetap byte-identik, bukti/provenance dipertahankan di T016/A1. T023/A1 menambah fixture sendiri periode3201 dan mempertahankan seluruh baris/counter baseline20 tabel identik; rincian di docs/evidence/T023/A1/mysql-proof.json dan mysql-extension.json. T002 developer checks sudah PASS pada4a0667c termasuk16 penyimpanan MySQL; riwayat3 code attempts dan FAIL tetap disimpan. Jangan reset/drop database atau mengulang migrate:fresh atas fixture existing tanpa scope/izin yang sesuai. Resource db:mysql-test tetap eksklusif per assignment. Password admin aplikasi masih menunggu user; seed tes sintetis tidak menetapkan password aplikasi.
- Demo di `/var/www/demo-repair-app/current` sudah memakai admin awal yang dibuat atas permintaan user dan disimpan hanya di berkas privat server. Nilai `ADMIN_INITIAL_PASSWORD` telah dihapus dari environment instalasi dan cache; jangan seed ulang atau membuat ulang APP_KEY. Status password lokal pada catatan historis di atas tidak berlaku untuk demo ini. Rincian ada di `docs/evidence/T024/A2/deployment-HANDOFF.md`.
- PRD §19: Q-02 printer (uji cetak T025), Q-03 VPS/domain dan izin deploy telah dijawab D-20 (T024–T025), Q-04 backup ditunda khusus demo oleh user 14 November 2026 (PRD D-22); tidak menghalangi demo, tetapi NFR-05 belum dipenuhi untuk produksi. Q-01, Q-05–Q-08 tidak menghambat; pakai nilai bawaan PRD.

## Batas

- Jangan menimpa perubahan user, menghapus data, mengubah database produksi, melakukan reset/clean Git yang merusak atau deploy tanpa persetujuan. Push normal ke origin sudah diizinkan; jangan force-push atau menghapus branch remote tanpa izin.
- Tambahkan dependency hanya bila task memerlukannya (lockfile Composer hanya diubah oleh T001 dan T017).
- Hanya T002 yang membuat migrasi database.
