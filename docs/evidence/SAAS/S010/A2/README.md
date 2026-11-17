# S010 A2 — deployment manual PASS

Produksi `https://demo-repair-app.scannara.asia` dibuka kembali **17 November 2026 04:37:24 UTC**, memakai tepat `99f64bf59ff1489825333aa821015a281db611ea`. `/up`, `/login`, dan tiga aset entry HTTPS mengembalikan 200. S010 selesai; penerimaan independen produksi tetap milik S011, pilot kedua milik S012.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; Project Delivery dan referensi coordination/QA/release, AGENTS, kontrak S010, runbook dan skrip deployment existing dipakai. Model diminta `gpt-6.1-sol`, effort high; model efektif belum terverifikasi karena receipt runtime tidak tersedia. Izin adalah Goal user S001–S012, `saas-production-2026-11-17`; koreksi cache dan urutan marker dijelaskan kepada coordinator dan disetujui sebelum pelaksanaan.

## Sumber, target, dan gate CI

Target: akun `muzib@202.10.40.165`, direktori biasa `/var/www/demo-repair-app/current`. SSH memakai BatchMode dan StrictHostKeyChecking existing. Source SaaS S006/A2 `sha256:6b01398fbd10ba0196919655f94c9f26917e906fedbef562abb94eec2c6549cd` diterima S007/A2. Runbook/backup S008 diterima S009/A2.

Preflight nyata: PHP8.3.36, server dan dump client MariaDB10.11.14, 20 tabel seluruhnya InnoDB, enam migrasi lama, tabel/migrasi tenant belum ada, key tersedia, marker/down belum ada. Source DB memakai `SYSTEM/WIB`, bukan timezone default container UTC. Ruang VPS118G dan Mac22Gi cukup untuk backup14MB. Tidak ada background writer target yang terdeteksi. Tidak memakai sudo atau mengubah layanan/konfigurasi host.

`origin/main` semula `a03f6f1e2becd1f3168d475eae3a66ce2565cdb6`, ancestor kandidat dengan divergence0/27. CI branch [44015956031](https://github.com/BatuBata/repair-app/actions/runs/44015956031) exact99f64bf PASS; CI pin QA [44014653205](https://github.com/BatuBata/repair-app/actions/runs/44014653205) exact6d0254c juga PASS. Setelah backup/offsite/restore lulus, main di-fast-forward non-destruktif ke99f64bf dan dipush. Checkout lokal dikembalikan ke `codex/saas-multitenant` sebelum menulis evidence.

[Main run44017276215](https://github.com/BatuBata/repair-app/actions/runs/44017276215) job CI **PASS**: 220 tests/2435 assertions, deployment-safety, package, dan upload berhasil. **Workflow keseluruhan FAIL yang diharapkan** karena auto receiver ditolak lock: `Another deployment holds the server lock` pada04:29:49Z. Versi/maintenance/marker live diverifikasi tetap lama setelah penolakan; tidak ada double deployment. Receiver tidak dipakai untuk migrasi ini.

Artifact14299636930 exact99f64bf diunduh. ZIP SHA256 `71b5bb8e6c5fb4f612b1e0f4eaf209cfacc3e4acb3589ee5a0da750bd433f954` cocok digest GitHub. Isi `release.tar.gz` SHA256 `9a4d835b6c631f1205852a6bd5febae4cb973cb7d301574c1afc3734412a61a5`, ukuran14,128,986byte. Validator `ci-archive.py` lulus pada Mac dan VPS; platform requirements staging PASS.

## Backup, salinan luar VPS, dan restore nyata

Lock existing `cicd/deploy.lock` dipegang terus oleh sesi SSH39910. Marker window berisi `S010-A2-20261117T041815Z` dibuat sebelum `artisan down --retry=60`; `optimize:clear --except=cache` dipakai. Baseline seluruh field lama diambil privat sebelum dump.

Backup baru mode700 di VPS: `/home/muzib/deploy-demo-repair-app/saas-s010-20261117T041815Z/backup`. Skrip backup/restore terpin disalin ke `ops` di luar live tree. File opsi DB dibuat privat dari konfigurasi Laravel tanpa mencetak kredensial. `backup.sh` menghasilkan satu gzip valid dan exit1 **hanya** untuk pesan Q-04 yang tepat; bukan klaim backup harian sukses. Arsip mencakup DB, upload+manifest, baseline+schema, source rollback sebelumnya, `.env` privat dan metadata key/hash.

Seluruh12 berkas backup disalin melalui SSH strict ke Mac persistent mode700: `/Users/muzib/Projects/web/repair-app/storage/app/private/saas-s010-20261117T041815Z`. Kedua sisi memverifikasi manifest SHA256 `794d56a5e23bdb91fe14f5c5010c0920387b41bf82e24e044825009c623d0bcb`. Pemegang salinan: muzib/operator Mac; retensi minimum sampai **1 Desember 2026 04:19:41 UTC**. Tidak ada backup lama dihapus. Detail checksum tanpa isi data ada di `backup.json`.

Restore **dari salinan Mac** memakai container baru MariaDB10.11.14, image digest `sha256:dbe56e20372fc6d6b8e0e396866ba89c4c7f128c38c4f59aaa54d957db95790c`, port hanya loopback, data tmpfs, akun restore hanya berhak pada scratch baru. Semua schema lama, field19tabel nonmigration, upload dan key cocok dengan baseline. Candidate dimigrasikan pada scratch; seluruh field lama tetap identik. Laporan source lama dan candidate pada periode yang sama cocok (`db3934926ad86c3c0cf4f52fc2184c640d952216693be38c5da570c06f60ddaf`). Relasi, counter probe yang dibatalkan, dokumen HTML/PDF, saleHTML dan guest tracking lulus.

Riwayat diagnosis dipertahankan: scratch pertama import berhasil tetapi inventory readback memberi kegagalan generik; container defaultUTC tidak sama dengan sumberSYSTEM/WIB. Scratch berikutnya berhenti karena collision direktori harness sebelum import. Scratch baru ketiga yang menyamai WIB/collation sumber lulus semua perbandingan; tidak ada import ulang ke DB parsial. Verifier tambahan awal mengasumsikan nama literal “Anugerah”, sedangkan nama toko live tidak mengandung kata itu; asumsi dibuang dan **nama sumber dipertahankan persis**, tanpa rename. Scratch candidate awal belum punya build manifest karena aset ignoredGit; aset existing dari snapshot lama dipasang hanya untuk smoke scratch. Deployment nyata memakai aset exact artifact CI.

## Penerapan dan bukti data

Sebelum perubahan schema live, checkpoint dikirim ke coordinator dengan backup/offsite/restore, mainCI/artifact, staging, dan baseline live yang masih identik. Jalur manual memakai rsync exclusions runbook untuk `.env`, `.git`, `storage`, `public/storage`, `bootstrap/cache`, `.release-revision`; umask rsync0027 sama dengan receiver, tanpa chmod/chgrp massal. `artisan migrate --force --no-interaction` berjalan **sekali**. Tidak ada seed, fresh, rollback/down migration, restore live, atau retry DDL.

Config/route/view caches, migrate:status dan route:list berhasil. Tenant foundation tercatat batch2 Ran; enam migrasi lama tetap batch1. Semua9,771 file release terpasang byte-identik staging, digest manifest `855e56400ca890da1dc1e26d3dce546c7e98acbc15d53e96cf28a5b64e245908`. `.env`, APP_KEY dan upload tetap identik. Inventaris19tabel nonmigration cocok setelah migrasi dan setelah smoke internal, sebelum request HTTP.

Tepat satu tenant aktif id1, namanya sama persis dengan store_settings lama. Delapan root memiliki tenant_id wajib, FK ke tenants, semua row tenant1, dan unique key tenant benar. Tujuh model scoped mengembalikan jumlah row yang sama; seluruh19FK diperiksa tanpa orphan. Report sama baseline; nomor berikutnya diprobe dalam transaksi yang di-rollback, counter tetap. OrderHTML/PDF, saleHTML dan guest tracking PASS tanpa transaksi bisnis baru atau penggunaan password owner.

## Dua koreksi operasi, tanpa perubahan source/schema

1. Cache bootstrap yang dihasilkan shell privat umask077 menjadi700. Ini risiko readability FPM yang nyata; config/route/view diregenerasi dengan umask0027, kemudian `clear-compiled`/`package:discover` meregenerasi hanya packages/services. Keempat bootstrapPHP menjadi750/www-data, views750 dan fontcache660. Tidak ada recursive chmod atau migrasi ulang. Inventory/env tetap cocok sesudahnya.
2. Open HTTPS pertama memberi503 dan segera dikembalikan ke maintenance dengan marker tetap. Vhost aktual ternyata memiliki guard Nginx `if (-f .deploy-pending) { return 503; }` sebelum PHP; urutan runbook “HTTPS200 lalu hapus marker” tidak bisa lulus. BypassLaravel juga terblokir guard ini. Setelah persetujuan coordinator, lock tetap dipegang dan Laravel tetap down; **hanya marker milik window** dihapus. Publiclogin503 dari Laravel dibuktikan, bypass privat `/up`+/login200, lalu `artisan up` dan publicHTTPS200. Pengaman akan down+buat kembali marker pada kegagalan. Tidak ada perubahan Nginx/sudo/layanan.

Sesudah akses HTTP, penambahan sesi framework wajar. Verifier akhir seluruh18tabel nonmigration/nonsession, environment, dan backup manifest PASS. Verifier proyeksi awal mengecualikan sessions hanya pada hasil, sehingga salah membandingkan bentuk baseline; diperbaiki pada kedua sisi dan dibuktikan pada scratch sebelum final read-only live check. Ini kesalahan harness, bukan perubahan data produk.

## Retensi, cleanup, dan handoff

Aplikasi open, `.release-revision` exact99f64bf, marker/down tidak ada. Lock39910 dilepas normal; `flock -n ... true` membuktikan lock bebas. Tiga container scratch berlabel S010-A2 dihapus beserta tmpfs; MySQL host existing, container lain, Colima dan image bersama tidak disentuh. Config dump dan output bypass sementara server dihapus; backup VPS/Mac tetap utuh, konfigurasi/key hanya pada salinan privat. Receipt privat tambahan disimpan di `operation-private` dalam folder Mac tersebut. Runtime lokal sementara dihapus setelah scan evidence.

Belum dilakukan oleh S010: S011 independen, login browser memakai password owner, transaksi produksi baru, pilot kedua, Android/printer fisik, serta pembuktian jadwal backup harian dan monitoring. Salinan Mac sekali ini memenuhi gate migrasi/off-VPS/restore; tidak membuktikan operasi backup harian berkelanjutan. Rilis masih membutuhkan acceptance S011 dan S012 sesuai ledger.
