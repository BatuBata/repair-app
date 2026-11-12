# Aturan proyek — Aplikasi Servis Anugerah (repair-app)

Bahasa: Indonesia sederhana. Jelaskan istilah teknis saat pertama dipakai, laporkan hasil pemeriksaan yang benar-benar dijalankan, dan minta persetujuan sebelum menghapus data, push, atau deploy.

## Sumber keputusan

- Kebutuhan produk: `PRD.md` (v1.2, disetujui). Jangan memperluas scope tanpa keputusan user yang dicatat di §20.3.
- Rencana dan status kerja: `TODO.md`, ledger JSON tunggal. Validasi setelah mengubah ledger:
  `python3 ~/.agents/skills/project-delivery/scripts/validate_todo.py TODO.md --write-overview --check-prd`
- Tampilan: `DESIGN.md` (token, komponen, aturan) dan layar acuan di `mockup/` yang disetujui user. Setiap layar dibandingkan dengan layar mockup yang sama. Tombol aksi baris selalu di kolom paling kiri; lebih dari satu aksi memakai menu pilihan `···`.
- Fakta produk untuk desain: `PRODUCT.md`.
- `.impeccable/` dan `.taste-skill/` berisi catatan arah desain dan bukti review; bukan kode aplikasi.

## Cara bekerja

Gunakan skill `project-delivery` (sumber: `~/.agents/skills/project-delivery/SKILL.md`) untuk triase, dependensi, penugasan, QA, integrasi, dan rilis. Batas eksekusi terkini dibaca dari `project.execution` di `TODO.md`; user mengizinkan kelanjutan T001–T025 pada 12 November 2026, mengikuti dependensi dan gate izin. Jangan menyamakan cakupan task dengan izin push/deploy.

- Satu coordinator menulis ledger. Worker mengerjakan satu task dan mengembalikan perubahan serta bukti, tanpa mengubah status pusat atau membuat worker lain.
- Maksimal dua worker aktif (termasuk tester). Maksimal dua upaya implementasi per task.
- Coordinator dan semua worker memakai skill `ponytail` level full (lihat `~/.codex/AGENTS.md`).
- Model per task ada di `model_hint` (`gpt-6-luna`, `gpt-6.1-terra`, `gpt-6.1-sol`). Katalog di ledger diverifikasi dari OpenCode; verifikasi ulang ketersediaannya di Codex sebelum dispatch dan perbarui `model_catalog.evidence_ref`.
- Untuk pekerjaan tampilan, skill `design-taste-frontend` tersedia di `~/.codex/skills/`; aturannya sudah dituangkan ke `DESIGN.md`, yang berlaku bila ada perbedaan.

## Lingkungan lokal (diperiksa 12 Nov 2026)

- `php` bawaan di PATH adalah **7.4** dan tidak cukup untuk Laravel 13. Gunakan PHP 8.3 khusus proyek ini tanpa mengubah PHP global:
  - PHP: `/opt/homebrew/opt/php@8.3/bin/php` (8.3.30; ekstensi pdo_mysql, mbstring, intl, gd, dom, zip, sqlite tersedia)
  - Composer: `/opt/homebrew/opt/php@8.3/bin/php $(command -v composer) <perintah>`
  - Artisan: `/opt/homebrew/opt/php@8.3/bin/php artisan <perintah>`
  - Mengubah PHP global (`brew link`) memerlukan persetujuan user.
- Composer 2.8.6, Node 22, npm 10, Git 2.50.
- MySQL 8.0.31 berjalan, tetapi akses user `muzib` tanpa password ditolak. User mengisi kredensial MySQL dan password admin awal sendiri di `.env`; jangan meminta nilai rahasia lewat chat dan jangan menulisnya ke file yang di-commit.
- Database yang dibutuhkan: `repair_app` (lokal) dan `repair_app_test` (uji konkurensi, resource `db:mysql-test` eksklusif). Feature test lain memakai SQLite in-memory.
- Repo Git lokal sudah dibuat T001; main masih baseline dokumen. Kandidat aplikasi ada di branch/task dan worktree Orca, menunggu integrasi + QA sesuai TODO. Belum ada remote; push tidak diizinkan.

## Blocker yang diketahui

- Eksekusi lokal diizinkan user 12 November 2026; push dan deployment produksi belum diizinkan.
- Kredensial MySQL dan password admin awal belum siap di `.env` (DB_USERNAME kosong pada pemeriksaan T020 12 Nov). Ini memblokir penerimaan T002, uji round-trip T020 dan task downstream. Siapkan `repair_app` dan `repair_app_test` khusus proyek; jangan menebak kredensial atau menghapus data existing. Verifikasi MySQL dan backup/restore dijalankan bergiliran memakai resource `db:mysql-test`.
- PRD §19: Q-02 printer (uji cetak T025), Q-03 VPS/domain dan izin deploy (T024–T025), Q-04 lokasi backup (T024). Q-01, Q-05–Q-08 tidak menghambat; pakai nilai bawaan PRD.

## Batas

- Jangan menimpa perubahan user, menghapus data, mengubah database produksi, melakukan reset/clean Git yang merusak, push, atau deploy tanpa persetujuan.
- Tambahkan dependency hanya bila task memerlukannya (lockfile Composer hanya diubah oleh T001 dan T017).
- Hanya T002 yang membuat migrasi database.
