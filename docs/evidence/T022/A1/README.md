# T022 A1 — kandidat integrasi v1

**PASS untuk kontrak integrasi T022. Belum diterima sebagai fitur/rilis; T023 independen masih wajib.**
Sumber yang diperiksa: `44154b674393e7d4c8a715131b13cae3e863cca7` pada `codex/t022-release`. Commit bukti sesudahnya hanya menambah `docs/evidence/T022/**`; gunakan `checked-source.json` dan manifest untuk membuktikan sumber identik. Model diminta `gpt-6.1-sol high`; identitas backend aktual tidak diekspos runtime. Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery dibaca dari `/Users/muzib/.agents/skills/project-delivery/SKILL.md`.

## Input dan perubahan

| Input | Pin | Tindakan |
|---|---|---|
| T017 | `2d7e613d5592ff169cb7743ff6fd086ec06fe58c` | Sudah ada; 6 file sumber sama persis. |
| T018 | `c9d1f429056676576310e88e524a75c9bc897c83` | Cherry-pick utuh menjadi `ae1b7c8`; 6 file sumber/tes sama persis, view scaffold lama tidak ada. |
| T019 | `7b7a438fcde75185c2160b642a02fbb2c6ed3ff1` | Sudah ada; 4 file laporan sama persis. Hook dashboard ke route `reports.index` kini aktif. |
| T020 | `6095a5ca34a5b28bf65a481007d69b21a8bc7084` | Hanya 15 file deploy/runbook/bukti diimpor; 7 file deploy sama persis. |
| T021 | `9d011685cde0f75ce676091cceca598978cdcf6a` | Panduan sudah ada dan sama persis; kategori pengeluaran, WA manual admin, unduh PDF publik, dashboard, batas grafik 31 hari dicocokkan ke sumber gabungan. |

`input-source-equality.json` memuat hash per file dan pin input. Selain menggabungkan input yang dipin, T022 tidak memodifikasi kode bisnis, migrasi atau lockfile dan tidak menambah dependency. Dua tes integrasi lama diselaraskan dengan T017 yang sudah diterima: sukses TTS dan detail penjualan sekarang mengharapkan **Cetak struk** serta URL route sebenarnya; route edit penjualan tetap404. Batas teknisi noA5/noharga tetap dibuktikan oleh suite Documents dan smoke. Runbook diperbaiki untuk membedakan restore MySQL lokal PASS dari FAIL awal pembandingan teks schema; bukti keduanya dipertahankan. Langkah instalasi pertama kini konkret dari `DatabaseSeeder`/`config/repair.php`: key sekali, storage:link, migrasi/cache melalui deploy script, seed sekali dalam maintenance, hapus rahasia awal dan bangun ulang config cache. Perintah produksi tersebut tidak dijalankan.

## Pemeriksaan aktual

| Pemeriksaan | Hasil/bukti |
|---|---|
| PHP8.3 `vendor/bin/pint --test` | PASS; `pint-final.log`. |
| PHP8.3 `vendor/bin/phpstan analyse --memory-limit=512M --no-progress` | PASS; `phpstan.log`. Tidak diulang setelah dua assertion tes/runbook saja berubah; app/config/routes identik. |
| PHP8.3 `artisan test --no-ansi` | PASS 181, total 182, 1.913 assertion; 1 guarded MySQL concurrency SKIP (`RUN_MYSQL_DOMAIN=0`), bukan bukti MySQL. `full-suite-final.log`, `checks-final.json`. |
| Auth targeted | PASS 6/96 assertion setelah CACHE_STORE/SESSION_DRIVER=array; `auth-isolation-repro.log`. |
| `npm run build` | PASS,14 berkas; `build.log`, `build-manifest.json`. Bundle lama dipertahankan sebelum build. Peringatan optional fontaine/URL font absolut tidak memblokir; 23 build/font asset HTTP200 dan SHA256 cocok (`asset-http-check.json`). |
| `bash -n deploy/backup.sh`, restore.sh, deploy.sh | Ketiganya PASS; log masing-masing.31 stub safety cases T020 digunakan ulang dari `host-check-final.log` karena7 file deploy hash identik. Tidak ada MySQL/SSH/restore yang dijalankan T022. |
| Cache | `artisan config:cache`, `route:cache`, `view:cache` PASS pada direktori privat. Runtime final `APP_ENV=production`; DB/session/view/cache/storage semuanya privat (`private-runtime-proof.log`). |
| Browser headless |26 kasus PASS, Chrome157; `browser-results.json` dan tiga script. TTS wajib/normalisasi, assignment→nota→diambil, dashboard→laporanRp1.350.000 kemudianRp2.350.000,8 halaman admin200, tracking anonim, larangan teknisi403. WA tidak dibuka/dikirim. |
| PDF |5 dokumen PASS ukuran/text: A5 419.53×595.28pt, thermal164.409pt (58 mm). Modal 634567 tidak ada; PDF publik juga tanpa identitas/HP.3 PNG render ditinjau visual: teks/total/QR utuh, tanpa tumpang tindih (`pdf-check.json`). |
| Whitespace | `git diff --check` PASS. |

Spasi akhir baris dan baris kosong akhir log dinormalisasi untuk pemeriksaan whitespace; isi hasil tetap sama. Perintah dan durasi di `checks.json`, `checks-final.json`, `smoke-preparation.json`; runner yang dipakai disimpan. Semua perintah PHP memakai `/opt/homebrew/opt/php@8.3/bin/php`. Suite memakai DB SQLite `:memory:`, DB_URL kosong, MySQL guard 0, cache/session array; browser memakai SQLite file baru dan konfigurasi terpisah. Tidak ada nilai `.env` asli atau rahasia fixture di Git. Log Laravel privat memuat exception lock yang memang dipicu kasus konkurensi SQLite pada suite; tidak diperlakukan sebagai error smoke HTTP.

## Kegagalan yang dipertahankan

- `full-suite.log`: dua assertion lama menolak tombol cetak T017; koreksi terbatas ke tes integrasi. Kegagalan throttle429 disebabkan harness awal memasang CACHE_STORE=file yang menimpa konfigurasi PHPUnitarray; sesudah isolasi dikoreksi, targeted dan suite penuh PASS. Ini bukan perubahan implementasi auth.
- `browser-initial-failure.*`: harness mencoba mengembalikan DOMnode melalui CDPJSON pada tunggu dashboard; screenshot memperlihatkan dashboard benar. Tunggu memakai boolean pada kelanjutan.
- `browser-publish-wait-failure.*`: fungsi tunggu terserialisasi merujuk variabel Node `createdId` yang tidak ada di halaman; nota sebenarnya sudah terbit (screenshot dan proof SQLite). Dilanjutkan dari order7 yang sama tanpa reset/duplikasi nota.
- `browser-continuation-failure.*`: assertion peka huruf besar mengharapkan `Sudah diambil`, sementara kalimat nyata `Laptop Anda sudah diambil.`. Pemeriksaan akhir memakai pola tanpa beda huruf besar; status bisnis benar.

`browser-results.json` mengumpulkan hanya kasus yang benar-benar telah lulus di sesi-sesi tersebut pada sumber sama; tidak mengklaim satu run browser tanpa gangguan. Tidak ada patch produk untuk kegagalan harness ini.

## Kandidat dan handoff

Manifest sumber230 file, bundle14 file dan font9 file tersedia. `checked-source.json` mengikat digest pada sumber commit. `public/build` adalah keluaran build yang diabaikan Git; snapshot bundle dan fixture dipertahankan di lokasi privat pada `resource-release.json`, bukan diterbitkan sebagai source. Kandidat tidak digabung ke main dan tidak dipush oleh worker. Coordinator menangani push serta ledger; worker tidak mengubah TODO/AGENTS.

Deploy/rollback siap ditinjau di `docs/DEPLOYMENT.md` dan7 file `deploy/`. Q-03 VPS/domain/izin deploy dan Q-04 backup luar VPS tetap diperlukan. Restore MySQL nyata T020 dipakai dengan provenance pin/semantik schema; jangan mengulang restore atas fixture 20 tabel kini. Tidak ada perubahan/reset/drop terhadap DB existing.

Resource T022 sudah dilepas: server port8192 mati; proses/profile Chrome milik T022 tertutup; folder Chrome privat kosong. SQLite/bundle lama/kandidat/log privat dipertahankan. `resource-release.json` mencatat PID dan path yang relevan.

Belum diuji/dilewati: QA independen T023, matriks semua layar 360/768/1280, batas panjang PDF/QR yang menjadi kasus T023, MySQL concurrency baru, ShellCheck, VPS/Nginx/FPM/HTTPS/cron/transferluar VPS nyata, Android/printer fisik. Bukti FPM/longPDF T017 tetap tersedia dari pin aslinya; smoke kecil T022 tidak menggantikan gate itu. Risiko: kandidat belum boleh dirilis/merge main sebelum T023 PASS, deployment/restore produksi tetap belum diizinkan.
