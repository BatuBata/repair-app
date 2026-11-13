# Aturan proyek — Aplikasi Servis Anugerah (repair-app)

**Status proyek: RUNNING — Goal user 13 November 2026 meresume T001–T023 sampai siap rilis memakai worker bawaan Codex. T024–T025/deployment belum diizinkan. Riwayat stop dan checkpoint dipertahankan di TODO.md.**

Bahasa: Indonesia sederhana. Jelaskan istilah teknis saat pertama dipakai, laporkan hasil pemeriksaan yang benar-benar dijalankan, dan minta persetujuan sebelum menghapus data atau deploy. Commit dan push branch pekerjaan ke origin sudah diizinkan user 12 November 2026; tidak perlu meminta ulang setiap task.

## Sumber keputusan

- Kebutuhan produk: `PRD.md` (v1.2, disetujui). Jangan memperluas scope tanpa keputusan user yang dicatat di §20.3.
- Rencana dan status kerja: `TODO.md`, ledger JSON tunggal. Validasi setelah mengubah ledger:
  `python3 ~/.agents/skills/project-delivery/scripts/validate_todo.py TODO.md --write-overview --check-prd`
- Tampilan: `DESIGN.md` (token, komponen, aturan) dan layar acuan di `mockup/` yang disetujui user. Setiap layar dibandingkan dengan layar mockup yang sama. Tombol aksi baris selalu di kolom paling kiri; lebih dari satu aksi memakai menu pilihan `···`.
- Fakta produk untuk desain: `PRODUCT.md`.
- `.impeccable/` dan `.taste-skill/` berisi catatan arah desain dan bukti review; bukan kode aplikasi.

## Cara bekerja

Gunakan skill `project-delivery` (sumber: `~/.agents/skills/project-delivery/SKILL.md`) untuk triase, dependensi, penugasan, QA, integrasi, dan rilis. Batas eksekusi terkini dibaca dari `project.execution` di `TODO.md`; Goal user 13 November 2026 mengizinkan T001–T023, mengikuti dependensi dan gate izin. Gunakan koordinasi/worker bawaan Codex; jangan menjalankan orchestration Orca. Jangan menyamakan cakupan task dengan izin push/deploy.

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
- Repo Git lokal sudah dibuat T001; main masih baseline dokumen. Kandidat aplikasi ada di branch/task dan worktree Orca, menunggu integrasi + QA sesuai TODO. Remote `origin`: `https://github.com/BatuBata/repair-app.git`; user 12 Nov 2026 mengizinkan commit dan push setiap pekerjaan selesai. Push checkpoint/candidate tidak menerima fitur atau melepas gate QA sebelum merge ke main.

## Blocker yang diketahui

- Eksekusi lokal serta commit/push branch diizinkan user 12 November 2026; deployment produksi belum diizinkan.
- Akses MySQL pulih. T020 backup/restore lokal nyata sudah PASS (evidence6095a5c); repair_app_test kini berisi20 tabel fixture sintetis (8TTS/8penjualan) setelah uji T002A4; snapshot21 tabel T020 sebelumnya dipertahankan privat. T002 developer checks sudah PASS pada4a0667c termasuk16 penyimpanan MySQL; riwayat3 code attempts dan FAIL tetap disimpan. Jangan reset/drop database atau mengulang migrate:fresh atas fixture existing tanpa scope/izin yang sesuai. Resource db:mysql-test tetap eksklusif per assignment. Password admin aplikasi masih menunggu user; seed tes sintetis tidak menetapkan password aplikasi.
- PRD §19: Q-02 printer (uji cetak T025), Q-03 VPS/domain dan izin deploy (T024–T025), Q-04 lokasi backup (T024). Q-01, Q-05–Q-08 tidak menghambat; pakai nilai bawaan PRD.

## Batas

- Jangan menimpa perubahan user, menghapus data, mengubah database produksi, melakukan reset/clean Git yang merusak atau deploy tanpa persetujuan. Push normal ke origin sudah diizinkan; jangan force-push atau menghapus branch remote tanpa izin.
- Tambahkan dependency hanya bila task memerlukannya (lockfile Composer hanya diubah oleh T001 dan T017).
- Hanya T002 yang membuat migrasi database.
