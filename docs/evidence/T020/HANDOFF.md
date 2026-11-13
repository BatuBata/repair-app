# T020 implementation A1 — candidate, BLOCKED environment

Verdict: **BLOCKED** untuk kontrak penuh T020; kode/runbook siap ditinjau, pemeriksaan host PASS. Uji MySQL backup/restore nyata belum dijalankan; tidak ada klaim backup VPS, deployment atau recovery produksi.

Artifact: worktree `/Users/muzib/orca/workspaces/repair-app/repair-t020-operations`, branch `muzib/repair-t020-operations`; revisi kandidat adalah commit `T020: prepare monolith deployment and safe backup recovery` yang memuat laporan ini, SHA aktual dikirim pada worker_done. `checked_revision` pada pesan hasil mem-pin commit tersebut; pemeriksaan sebelum commit memakai byte sumber yang sama (lihat [hash sumber](source-sha256.txt)).

## Input dan batas

- `inputs = {"T001":"4107c49d9098ab47c465773857fd5ee2811eb1b4"}`; ancestor base `c6799aceee695958ba176b06350ebaa0ed53711e` dikonfirmasi `git merge-base --is-ancestor` exit 0.
- Kontrak: T020 kanonik `/Users/muzib/Projects/web/repair-app/TODO.md`, running/A1, read 12 November 2026; PRD §14, §12 NFR-05, §19 Q-03/Q-04, §20.3 D-03/D-11/D-17 dan AGENTS kanonik dibaca. Ledger tidak diubah. Tidak ada scope produk/migrasi/lockfile/.env yang ditulis.
- Ponytail **full**, dibaca sendiri dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery dan `references/qa-and-release.md` dibaca dari `/Users/muzib/.agents/skills/project-delivery/`. Panduan Orca terpasang dibaca melalui `orca skills get orchestration` dan `orca skills get orca-cli`.
- Dispatch `ctx_d74c71f70016`, task `task_af3247e750f4`, meminta `gpt-6.1-sol high`; identitas backend efektif tidak dapat diverifikasi dari sesi ini, tidak ada fallback/pergantian model diminta. Tidak ada helper worker.

## Pemetaan sumber dan diff

| Sumber teramati | Pemakaian di paket |
|---|---|
| composer.json / package.json dari base T001 | Laravel 13/PHP ^8.3, Composer lock install no-dev, `npm ci` + `npm run build` (`vp build`); starter `composer setup` tidak dijadikan jalur produksi |
| bootstrap/app.php | Smoke `/up`, dengan batas bahwa endpoint itu bukan bukti DB/alur bisnis |
| .env.example / config/app.php / config/database.php | Nama secret saja, WITA, MySQL, session/cache database, APP_KEY tetap |
| config/logging.php | Channel daily dengan LOG_DAILY_DAYS default 14 tersedia; konfigurasi produksi oleh operator |
| PRD §14 / NFR-05 | Maintenance, backup sebelum migrasi, caches, rollback kode, restore data terpisah, smoke tujuh alur, 03.00 WITA dan retensi 14 hari |

Perubahan hanya:

- `deploy/{backup,restore,deploy}.sh`: backup gagal jelas, salin SSH sebelum retensi, file privat/lock/validasi argumen; restore explicit target/confirmation dan dekompresi sebelum DB; migrasi/cache gated revision/backup/maintenance, tidak auto-up.
- `deploy/{nginx.conf,php-fpm.conf,backup.cron}.example`: konfigurasi contoh dan jadwal operator, placeholder Q-03/Q-04.
- `deploy/check-scripts.py`: self-check stdlib dengan stub tool dan fixture sementara; tidak mengakses DB/SSH/service nyata.
- `docs/DEPLOYMENT.md`: preflight, izin, deployment checkout tunggal, migrasi/cache, rollback, actual restore workflow, smoke, bukti yang belum tersedia dan prosedur verifikasi sumber sama.
- `docs/evidence/T020/**`: bukti host/handoff ini.

## Pemeriksaan aktual

| Kasus / perintah | Expected | Actual |
|---|---|---|
| `bash -n deploy/backup.sh`, `restore.sh`, `deploy.sh` | Sintaks valid pada Bash lokal | PASS exit 0; final 0.007/0.005/0.005 detik |
| `python3 deploy/check-scripts.py` pertama | Kasus keselamatan stub lulus | PASS 23 kasus, 2.626 detik; [log awal](host-check-A1.log) |
| Self-check final setelah tambahan larangan system DB case-insensitive dan cakupan deploy | Kasus lama + tambahan lulus | PASS 31 kasus, 4.468 detik; [log final](host-check-final.log) |
| Jalur gagal dump/compression/copy/remote publish | Nonzero, tidak retensi; local dump tetap bila sudah valid | PASS stub; tidak ada layanan nyata |
| Retensi usia 15 hari vs 13 hari + file lain | Hanya dump sesuai DB lama terhapus sesudah copy sukses | PASS fixture privat; tidak ada data user dihapus |
| Target/path/permission/lock invalid | Berhenti sebelum tool destruktif | PASS stub |
| Restore gzip rusak/truncated/kosong, konfirmasi salah, system DB termasuk MySQL mixed case | MySQL tidak dipanggil | PASS stub |
| Restore sukses/gagal | Byte SQL diteruskan; kegagalan client nonzero | PASS stub; bukan PASS restore MySQL nyata |
| Deploy sukses dan gagal tiap tahap/cache, dirty source, maintenance hilang | Urutan migrate/config/route/view; berhenti saat gagal; tidak auto-up | PASS stub |
| Pemeriksaan secret | Password sintetis tidak masuk argv/stdout/stderr | PASS assert; tidak membaca nilai secret user ke output |
| ShellCheck | Jalankan bila terpasang | NOT_RUN: `command -v shellcheck` tidak menemukan binary; tidak dipasang |
| Host versi | Tidak menghubungi/mengubah server | Darwin, Bash 3.2.57, MySQL/mysqldump client 8.0.31, PHP proyek 8.3.30; `--help` mengonfirmasi flag client tanpa koneksi |
| Prasyarat .env kanonik | Hanya boolean kesiapan, tanpa nilai | `.env` ada, `DB_USERNAME configured: False`; tidak mencoba auth, tidak membuat/mengubah DB |
| Restore MySQL sintetis nyata | Kontrak meminta round-trip lokal | **BLOCKED environment**: kredensial/dedicated DB belum dikonfirmasi, resource db:mysql-test tidak diambil dan tidak ada SQL nyata dijalankan |
| Nginx/FPM syntax, cron, cert renewal, off-VPS copy, smoke target | Memerlukan Ubuntu/host/domain/akun tujuan terkonfirmasi | NOT_RUN: binary/target/izin Q-03/Q-04 belum tersedia |

Tidak ada kegagalan produk teramati pada host checks; ini bukan bukti fitur produksi. Log awal dipertahankan; pemeriksaan final diulang karena sumber dan kasus keselamatan bertambah. Durasi hanya perintah yang diukur, bukan estimasi waktu model/biaya. Build/suite aplikasi dan setup hooks sengaja dilewati karena perubahan terbatas shell/runbook tanpa dependency terpasang.

## Referensi primer yang dibaca 12 November 2026

- [Deployment Laravel 13](https://laravel.com/docs/13.x/deployment): kebutuhan runtime/root public/caches/health.
- [MySQL 8 mysqldump](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html) dan [option files](https://dev.mysql.com/doc/refman/8.0/en/option-files.html): transaksi, privileges, defaults-file dan GTID.
- [MySQL client](https://dev.mysql.com/doc/refman/8.0/en/mysql-command-options.html): batch/binary-mode dan batas SQL tepercaya.
- [Ubuntu Nginx](https://documentation.ubuntu.com/server/how-to/web-services/install-nginx/), [Nginx locations](https://nginx.org/en/docs/http/ngx_http_core_module.html#location), [PHP-FPM](https://www.php.net/manual/en/install.fpm.configuration.php): server/pool/socket; bukan klaim OS produksi.
- [Certbot instructions](https://certbot.eff.org/instructions?ws=nginx&os=snap), [webroot](https://eff-certbot.readthedocs.io/en/stable/using.html#webroot): bootstrap sertifikat/renewal.
- [Ubuntu crontab manual](https://manpages.ubuntu.com/manpages/noble/en/man5/crontab.5.html): jadwal dan variasi implementasi timezone. Runbook sengaja memakai timezone sistem yang diverifikasi, tidak mengasumsikan CRON_TZ tersedia.

## Sisa dan risiko

Coordinator perlu membuka prasyarat/mereservasi `db:mysql-test` **sebelum SQL nyata apa pun**; langkah unchanged-source persis ada di bagian akhir [runbook](../../DEPLOYMENT.md). Username masih kosong; jangan membuat user/DB atau menebak credential. Hasil ronde MySQL nyata harus ditambahkan terpisah tanpa mengubah PASS stub menjadi PASS pemulihan. QA independen/integrasi tetap diperlukan.

Q-03/Q-04 tetap terbuka; template belum dipasang/diuji di Ubuntu nyata. Tujuan salin contoh hanya SSH; object storage/Drive perlu adaptasi jika dipilih. Retensi remote menunggu kebijakan Q-04. Restore tidak atomik, SQL hanya tepercaya dan hak akun hanya target; backup DB tidak mencakup .env/APP_KEY/file. Tidak ada push, merge main, deploy, service change, data produksi atau DB user yang disentuh.
