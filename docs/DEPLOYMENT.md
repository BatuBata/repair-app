# Operasional Aplikasi Servis Anugerah

## Demo aktif — 14 November 2026

[Demo](https://demo-repair-app.scannara.asia) aktif sejak 10.43 WITA di `muzib@202.10.40.165:/var/www/demo-repair-app/current`, memakai revisi aplikasi `d047f25ea31d703660212e4205b937053c92adcb` yang sudah masuk `main`. [Receipt deployment](evidence/T024/A2/deployment-receipt.json) dan [serah terima operator](evidence/T024/A2/deployment-HANDOFF.md) merekam langkah serta hasil aktual. PHP 8.3 terpasang berdampingan dengan versi lama; MariaDB 10.11 existing sudah lulus [uji kompatibilitas independen](evidence/T024/A2/mariadb-qa/assessment.json). Let’s Encrypt, uji renewal, dan hook reload Nginx lulus.

Keputusan PRD D-20/D-21/D-22 berlaku khusus demo ini: Debian 12 dan MariaDB existing disetujui; **seluruh backup ditunda oleh user**. Tidak ada backup/cron/offsite/restore demo yang diklaim berhasil, sehingga NFR-05 serta penerimaan produksi penuh belum terpenuhi. Bagian berikut tetap merupakan runbook umum T020; ketentuan Ubuntu/MySQL/backup di sana bukan gambaran keadaan demo dan tidak membatalkan pengecualian user ini.

Admin demo: username `admin`; password awal hanya tersimpan di server pada `/home/muzib/deploy-demo-repair-app/initial-admin-password.txt` (pemilik `muzib`, mode `0600`, direktori induk `0700`). Owner dapat membacanya secara privat lewat SSH. Jangan memasukkan isinya ke Git, chat, laporan, atau tangkapan layar. Nilai instalasi `ADMIN_INITIAL_PASSWORD` sudah dikosongkan di kedua environment dan cache konfigurasi. Pertahankan APP_KEY pada `current/.env`; jangan menjalankan ulang bootstrap, key generation, atau seed instalasi pertama pada aplikasi live.

Status pengujian web, transaksi, HP dan printer yang benar-benar dijalankan mengikuti T025 di `TODO.md`. Pengujian web tanpa perubahan data tidak membuktikan cetak fisik atau penerimaan penuh R001.

## Runbook umum T020

Paket T020 menyiapkan **satu checkout Laravel di satu VPS**, dengan jeda maintenance saat rilis. Tidak ada staging, queue worker, scheduler aplikasi, container, atau layanan tambahan (PRD §7, §14, D-17). Semua perintah di bawah adalah **instruksi untuk operator setelah mendapat izin**, bukan catatan deployment yang sudah dijalankan. Hasil lokal yang benar-benar diperiksa ada di [bukti T020](evidence/T020/HANDOFF.md); uji backup/restore **MySQL lokal nyata sudah PASS** pada [assessment T020](evidence/T020/mysql-verification-assessment.json) dan [handoff MySQL](evidence/T020/mysql-verification-handoff.md). [FAIL awal](evidence/T020/mysql-verification-result.json) dipertahankan: pembandingan teks `SHOW CREATE` berubah karena charset redundan; isi, identitas, dan schema secara semantik sama. Bukti lokal ini belum membuktikan cron, salinan luar VPS, atau pemulihan produksi (Q-03/Q-04).

## Target dan gerbang

| Hal | Harus diisi/dibuktikan sebelum tindakan nyata |
|---|---|
| Q-03 | Penyedia VPS, Ubuntu LTS dan versinya, arsitektur, domain, DNS A/AAAA, pemilik pemeliharaan, akses SSH, persetujuan deploy/rollback |
| Q-04 | Tujuan luar VPS, pemilik akun, akses/pemulihan kredensial, kapasitas dan retensi; contoh skrip memakai host SSH terpisah, bukan direktori lain di VPS yang sama |
| Sumber | `RELEASE_REV` = SHA commit lengkap hasil integrasi yang diterima; QA independen dan QA gabungan cocok dengan SHA tersebut; jangan memakai branch bergerak sebagai bukti |
| Data | Review semua migrasi T002 yang termasuk rilis; kompatibilitas kode lama dengan schema baru dan jalur restore tertulis sebelum eksekusi |
| Izin | Rujukan izin user untuk target dan tindakan; T020 dan izin kerja lokal bukan izin deploy atau restore produksi |

Belum ada versi rilis produksi yang dinyatakan. T020 disusun dari base `c6799aceee695958ba176b06350ebaa0ed53711e`, memakai T001 `4107c49d9098ab47c465773857fd5ee2811eb1b4`; ini pemetaan input, **bukan pilihan commit go-live**. Layar/alur lengkap baru boleh diuji setelah task fiturnya terintegrasi.

## Preflight operator (stop bila satu gerbang gagal)

- Catat OS (`cat /etc/os-release`), arsitektur, `nginx -v`, versi PHP CLI **dan FPM**, MySQL client/server, Composer dan Node/npm. Contoh ini cocok untuk Ubuntu LTS dengan PHP 8.3; jangan menganggap versi paket bawaan LTS lain memenuhi syarat. `apt-cache policy php8.3-fpm php8.3-cli mysql-server nginx` membantu pemeriksaan pada OS yang sudah dipilih. Instal paket hanya setelah Q-03 diputuskan, tanpa menambah repositori paket secara diam-diam.
- `composer.json` mensyaratkan PHP `^8.3`, Laravel `^13.17`, Livewire `^4.1`, dompdf `^3.1`. Pasang dari **lockfile**; periksa PHP lewat `composer check-platform-reqs --no-dev`. CLI dan FPM memerlukan ekstensi Laravel serta `pdo_mysql`, `mbstring`, `intl`, `gd`, `dom`, `zip`; uji PDF sesuai hasil T006/T017. Lokal memakai `/opt/homebrew/opt/php@8.3/bin/php`, bukan PHP global 7.4. Di VPS tentukan path PHP/FPM nyata, contoh `/usr/bin/php8.3`.
- Gunakan MySQL **8**, bukan substitusi MariaDB; cek versi server melalui koneksi operator yang aman. Tabel aplikasi harus InnoDB. `--single-transaction` tidak menjamin konsistensi MyISAM atau DDL bersamaan: jangan menjalankan migrasi/ALTER selama dump. Client MySQL 8 harus mendukung flag skrip; cocokkan server minor/patch dan uji pada target. Schema v1 tidak mengandalkan routine/event; bila kelak ada, perluas cakupan dump dan uji restore sebelum rilis.
- Sediakan user OS deploy (tanpa akses root untuk aplikasi), user backup terpisah, serta DB/user yang dibuat pemilik sesuai izin. Aplikasi bukan user DB root. `SESSION_DRIVER=database` dan `CACHE_STORE=database` membutuhkan tabel migrasi; lakukan `migrate:status`, jangan menyimpulkan `/up` membuktikan koneksi DB.
- Pastikan ruang bebas cukup untuk dump, arsip, SQL hasil dekompresi, dependency/aset lama dan baru. Simpan versi sebelumnya dan backup terverifikasi; jangan hapus sumber pemulihan sebelum smoke test diterima.
- Pastikan HTTPS valid, sertifikat dapat diperbarui, DNS benar, firewall hanya membuka layanan yang diperlukan (MySQL tidak dipublikasikan). Uji `.env`, `.git/config`, `/vendor/autoload.php`, `/storage/logs/laravel.log`, dan PHP selain `/index.php` menghasilkan 403/404 tanpa isi rahasia.
- Backup harian terakhir harus ada, salinan luar VPS terverifikasi, dan satu **restore MySQL nyata** dengan data sintetis harus PASS sebelum go-live (NFR-05). Backup baru sebelum migrasi tetap wajib. Pantau status/usia backup setiap hari; jangan mengandalkan cron tanpa pemeriksaan.

## Konfigurasi, akun dan izin berkas

Contoh path: `/srv/repair-app/current` adalah direktori checkout biasa (bukan root web); root web hanya `current/public`. `/srv/repair-app/ops` menyimpan skrip operasional dari commit yang dicatat, supaya cron tidak berubah di tengah backup saat checkout aplikasi diganti. Jangan menyimpan backup, private key, `.cnf`, atau `.env` di `public/`.

Operator menyiapkan `.env` di server melalui kanal aman. Nama yang digunakan sumber saat ini:

- `APP_KEY` (rahasia; buat **sekali** saat instalasi pertama, pertahankan saat deploy/rollback dan simpan salinan aman), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<DOMAIN_Q03>`, `APP_TIMEZONE=Asia/Makassar`, `APP_LOCALE=id`.
- `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (rahasia), dan `DB_SOCKET`/`MYSQL_ATTR_SSL_CA` bila digunakan. Jangan menaruh password di argumen proses atau terminal history.
- `ADMIN_INITIAL_PASSWORD` adalah rahasia instalasi awal yang dibaca `config/repair.php` dan `DatabaseSeeder`. Pemilik mengisinya melalui kanal aman sebelum cache konfigurasi dibuat; jangan menaruh nilainya di contoh, argumen proses, atau log. Ikuti langkah instalasi pertama di bawah, bukan seed ulang setiap deploy.
- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `APP_MAINTENANCE_DRIVER=file`, `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`. Konfirmasi `daily` tersedia pada `config/logging.php` commit rilis dan retensinya; sumber T001 menyediakan channel tersebut. Nilai starter `QUEUE_CONNECTION=database` tidak berarti perlu worker: PRD tidak memakai job latar belakang.

`.env` dimiliki user deploy, grup FPM, mode `640`; direktori induk membatasi akses. Kode dan dependency hanya dapat ditulis deploy, dibaca FPM. Hanya `storage/` dan `bootstrap/cache/` dapat ditulis deploy + grup FPM (direktori `2770`, file `660`, tidak `777`). Sertakan direktori runtime `storage/framework/{cache/data,sessions,views}` dan `storage/logs`. Sesuaikan hak baca file statis agar Nginx dapat melayani `public/build`. Root membuat direktori log PHP untuk FPM, file log hanya terbaca pengelola; atur logrotate untuk log FPM, Nginx dan backup. Log Laravel daily dirotasi aplikasi; jangan log data customer/password. Cadangkan `.env`/APP_KEY secara terpisah dan aman: dump DB tidak mencakup file atau key.

## Instalasi pertama saja: kunci, logo, dan akun admin

Langkah ini **belum dijalankan di produksi**. Eksekusi menunggu target Q-03, lokasi backup Q-04, QA T023 dan izin T024. Jalankan hanya pada instalasi kosong yang sudah disetujui, sebagai operator deploy di checkout kandidat, dengan Nginx tetap 503 dan aplikasi maintenance. Gunakan path `PHP`/`COMPOSER` yang diverifikasi pada bagian urutan deploy; jangan memakai `composer setup`.

1. Pemilik menyiapkan `.env` privat, termasuk koneksi DB dan `ADMIN_INITIAL_PASSWORD` yang kuat sesuai validasi password aplikasi. Jangan menampilkan isinya. Pastikan `APP_KEY` belum ada hanya pada instalasi pertama; setelah vendor dari lockfile tersedia, jalankan perintah berikut sekali. Kunci ini wajib dipertahankan pada setiap deploy/rollback dan disalin ke penyimpanan rahasia yang aman.

```sh
"$PHP" artisan key:generate --force
"$PHP" artisan storage:link
```

`storage:link` membuat `public/storage` menunjuk ke `storage/app/public` untuk logo yang diunggah; verifikasi target/hak baca Nginx dan hak tulis FPM sesuai bagian izin berkas. Jangan mengganti tautan yang sudah ada tanpa memeriksa tujuannya. Jangan membuat ulang APP_KEY pada pembaruan aplikasi.

2. Jalankan urutan deploy di bawah sampai `deploy.sh` selesai: backup target dan gerbangnya tetap berlaku, lalu migrasi T002 dan cache config/route/view dibuat. **Jangan menjalankan `artisan up` dulu.** `ADMIN_INITIAL_PASSWORD` harus sudah terbaca oleh cache konfigurasi sebelum langkah berikutnya.
3. Sesudah migrasi berhasil, masih dalam maintenance, jalankan seed awal satu kali:

```sh
"$PHP" artisan db:seed --force
```

Seeder aktual membuat pengguna aktif bernama **Admin**, username **admin**, peran **Admin** jika admin belum ada; password berasal dari konfigurasi privat. Seeder juga menyiapkan sembilan kategori awal dan pengaturan toko dengan garansi bawaan 30 hari. Ia tidak mereset tabel atau mengganti password admin yang sudah ada. Periksa keberhasilan seed tanpa mencetak password; jika gagal, tahan maintenance dan diagnosis sebelum mengulang.
4. Setelah seed berhasil, hapus nilai `ADMIN_INITIAL_PASSWORD` dari `.env` melalui editor aman dan jalankan `"$PHP" artisan config:cache` lagi agar salinan rahasia awal di cache juga hilang. Pastikan hak berkas cache tetap terbatas. Setelah verifikasi operator dan reload FPM, lanjutkan `artisan up` serta smoke sesuai urutan deploy; serahkan akses admin melalui kanal pribadi, lalu owner mengganti password dan melengkapi pengaturan toko. Jangan men-seed ulang pada rilis berikutnya.

## Nginx, PHP-FPM dan HTTPS

Contoh [Nginx](../deploy/nginx.conf.example) dan [pool FPM](../deploy/php-fpm.conf.example) menggunakan socket khusus `/run/php/repair-app.sock`. Batas awal lima proses FPM adalah contoh, ukur RAM sebelum mengubahnya. Pasang pool ke direktori **versi yang benar-benar terpasang**, siapkan `/var/log/php/repair-app-error.log` dengan izin FPM, lalu jalankan `php-fpm8.3 -t` (nama binary sesuai OS). Pastikan Nginx berjalan sebagai user yang dapat mengakses socket. Dokumentasi [FPM resmi](https://www.php.net/manual/en/install.fpm.configuration.php) menjelaskan hak socket/pool; contoh routing mengikuti [deployment Laravel 13](https://laravel.com/docs/13.x/deployment).

Untuk bootstrap sertifikat, **jangan aktifkan blok 443 sebelum file sertifikat ada**. Pada Q-03 yang sudah disetujui, aktifkan dahulu server port 80 yang hanya melayani `/.well-known/acme-challenge/` dari `/var/www/letsencrypt`, dan `location / { return 503; }`; isi `server_name` dengan domain sesungguhnya. Buat direktori challenge yang dapat dibaca Nginx. Setelah `sudo nginx -t` dan reload berhasil serta port 80 dapat dicapai:

```sh
# Setelah instalasi Certbot sesuai OS resmi yang sudah dipilih:
sudo certbot certonly --webroot -w /var/www/letsencrypt -d <DOMAIN_Q03>
# Ganti semua DOMAIN.invalid, aktifkan contoh final HTTPS, lalu:
sudo nginx -t
sudo systemctl reload nginx
sudo certbot renew --dry-run
```

`<DOMAIN_Q03>` adalah placeholder, bukan perintah yang bisa ditempel langsung. Ikuti [Certbot resmi](https://certbot.eff.org/instructions?ws=nginx&os=snap) untuk instalasi/renewal dan [opsi webroot](https://eff-certbot.readthedocs.io/en/stable/using.html#webroot). Pastikan timer renewal aktif dan reload Nginx sesudah renewal berhasil melalui deploy-hook yang dikelola root; uji hook itu juga. Jangan mematikan verifikasi TLS pada smoke test. Panduan [Ubuntu Nginx](https://documentation.ubuntu.com/server/how-to/web-services/install-nginx/) dipakai untuk tata letak layanan; OS produksi belum diketahui.

## Backup 03.00 WITA, retensi dan luar VPS

[backup.sh](../deploy/backup.sh) memakai Bash, MySQL 8 client, gzip, stat, find, mktemp, OpenSSH scp/ssh. Tidak ada dependency proyek baru. Direktori lokal khusus mode `700`, `.cnf` mode `600`/`400`, dimiliki user yang menjalankan skrip; semua path absolut dan induknya harus milik pengelola tepercaya tanpa akses tulis pihak lain. Direktori tidak boleh berisi backup aplikasi lain. Skrip menolak symlink langsung, nama DB/tujuan berbahaya dan eksekusi backup bersamaan untuk DB yang sama. Gunakan satu direktori backup tetap per DB; lock tersebut tidak mencakup direktori berbeda. Bila lock tertinggal setelah kill/padam, periksa proses sebelum menghapus **hanya lock tersebut**.

Operator membuat file `/etc/repair-app/backup.cnf` secara aman, **tanpa mengisi contoh ini di repo**:

```ini
[client]
host=HOST_Q03
port=3306
user=BACKUP_DB_ACCOUNT
password=SECRET_FROM_OPERATOR
```

Ini nama placeholder, bukan kredensial yang sudah tersedia. Gunakan file terpisah untuk restore dan akun dengan hak **hanya database target**. Jangan `source .env`, jangan `-pPASSWORD`, `MYSQL_PWD`, `set -x`, `my_print_defaults`, atau mencetak file. Jalankan sebagai akun OS khusus tanpa `.mylogin.cnf`: MySQL tetap dapat membaca login file walau `--defaults-file` dipakai. Periksa file pilihan `[client]`/`[mysqldump]`/`[mysql]` tidak menambah database, `force`, log atau option lain; contoh hanya koneksi. [Dokumentasi option file MySQL](https://dev.mysql.com/doc/refman/8.0/en/option-files.html) menjadi rujukan.

Hak minimal dump mengikuti [mysqldump MySQL 8](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html): SELECT, SHOW VIEW, TRIGGER pada database sumber sesuai objeknya; dump single-transaction tidak mengunci tabel dan `--no-tablespaces` menghindari kebutuhan PROCESS. `--set-gtid-purged=OFF` cocok untuk pemulihan aplikasi tunggal ini, bukan replikasi/PITR (pemulihan ke waktu tertentu). Routines/events tidak dicakup. Dump tidak memakai `--databases` sehingga tidak menyertakan CREATE DATABASE/USE otomatis.

Q-04 contoh SSH: operator menyiapkan alias SSH, private key mode `600`, `known_hosts` yang diverifikasi lewat kanal lain, host terpisah, direktori remote yang sudah ada, dan hak hanya area backup. `StrictHostKeyChecking=yes` dan `BatchMode=yes` menolak prompt/host tak dikenal. Remote memerlukan `mv`; tidak boleh berupa tujuan di VPS sumber. Contoh ini memakai transfer scp lalu rename `.partial` di remote; cek checksum/retrieval sebelum go-live dan secara berkala. Bila Q-04 memilih object storage/Drive, sesuaikan langkah salin dan ulangi QA; jangan menyatakan adapter tersebut sudah tersedia. Remote retention belum diotomasi: tetapkan kebijakan minimal 14 hari di tujuan Q-04, hindari penghapusan salinan terakhir yang sehat, pantau kapasitas.

```sh
# Operator, sebagai akun backup, setelah konfigurasi aman tersedia:
bash /srv/repair-app/ops/backup.sh /etc/repair-app/backup.cnf repair_app /var/backups/repair-app offsite-repair /REMOTE_Q04
# Persiapan lokal saja bila Q-04 belum ada: menyimpan dump, EXIT NONZERO,
# tidak menjalankan retensi; ini sengaja tidak dihitung backup harian sukses:
bash /srv/repair-app/ops/backup.sh /etc/repair-app/backup.cnf repair_app /var/backups/repair-app - -
```

Dump/compression/copy/publish gagal → exit nonzero, tidak menghapus arsip lama; dump lokal yang sudah valid dipertahankan jika salin gagal. Operator memeriksa pesan tahap gagal, ruang bebas, akses dan koneksi secara privat. SQL/error mentah tidak dicetak ke log. File sementara dibersihkan pada exit normal/signal yang tertangani; setelah crash periksa `.work-*` privat sebelum membersihkannya. Retensi lokal hanya file dump DB yang sesuai pola di direktori paling atas, usia **sekurangnya 14 × 24 jam** (`find -mtime +13`), baru setelah salin luar VPS sukses. Retensi menghapus data lama, sehingga pemasangannya memerlukan izin kebijakan retensi; T020 tidak menjalankannya pada data user.

Pasang [contoh cron](../deploy/backup.cron.example) hanya setelah nama akun/path diganti dan restore nyata PASS. Contoh ini menggunakan zona waktu daemon/sistem. Implementasi cron berbeda: sebagian (misalnya Cronie) mendukung `CRON_TZ`, sedangkan paket lain berbeda; baca manual paket yang benar-benar terpasang. Menulis `APP_TIMEZONE` atau `TZ` untuk proses anak tidak cukup mengubah jadwal daemon. Periksa `timedatectl show -p Timezone --value` = `Asia/Makassar`; jika berbeda, perubahan timezone adalah operasi host oleh pemilik, lalu pastikan daemon cron membaca zona baru. Bila host harus tetap UTC, gunakan **19.00 UTC hari sebelumnya** untuk 03.00 WITA dan catat jadwal tersebut. WITA tidak memakai DST. Verifikasi jadwal lokal dengan manual cron pada versi OS nyata; [manpage cron Ubuntu 24.04](https://manpages.ubuntu.com/manpages/noble/en/man5/crontab.5.html) hanya acuan, bukan klaim OS produksi. `TZ` aplikasi tidak mengubah jadwal cron. Timestamp nama arsip memakai UTC `Z` agar jelas.

Cron harus punya PATH client MySQL nyata, akses alias SSH, file log yang dapat ditulis akun backup dan notifikasi error yang benar-benar diterima pemilik. `MAILTO=root` hanya contoh; Ubuntu tanpa MTA tidak otomatis mengirim email. Pengelola memeriksa log/exit dan umur backup/salinan luar VPS setiap hari. Tidak ada cron yang dipasang oleh T020.

## Urutan deploy operator

Untuk skala satu toko, gunakan satu checkout tetap `/srv/repair-app/current`, downtime terencana, dan storage/.env tetap di tempatnya. Tidak ada pergantian symlink otomatis. Operator memegang akses eksklusif deploy dan memberitahu owner agar transaksi dihentikan. Hindari `composer setup`: script starter itu membuat key, migrasi dan npm install; bukan jalur produksi. Jangan jalankan `composer update`, seed ulang, `migrate:fresh`, `git reset --hard`, atau cleanup data untuk deploy.

1. Isi variabel shell `RELEASE_REV` (40 karakter SHA yang lolos QA), `PREVIOUS_REV` (hasil `git rev-parse HEAD` dari versi aktif), path `PHP` dan `COMPOSER` yang terverifikasi. Catat SHA sebelumnya **di luar checkout**, hasil QA, izin, waktu, backup dan kompatibilitas migrasi dalam receipt rilis milik operator. Pastikan `git status --porcelain` kosong dan objek commit target tersedia. Instalasi pertama tidak punya versi aktif sebelumnya: catat hal itu, jangan mengarang SHA rollback. Siapkan PHP/dependency/aset target di lokal/build yang disetujui sebelum downtime agar risiko pemasangan sudah diketahui.
2. Dari checkout aktif, `"$PHP" artisan down`, lalu `"$PHP" artisan optimize:clear`. Hentikan semua penulis DB, termasuk akses admin langsung/skrip lain. Ambil backup **sebelum** checkout/migrasi dengan `backup.sh`; simpan path arsip persis sebagai `BACKUP_FILE` dan bukti salinan luar VPS sukses. Bila belum ada aplikasi pada instalasi pertama, operator menahan Nginx dalam 503. Periksa APP_KEY/config tersimpan aman dan backup benar-benar berasal dari DB/host yang akan diubah.
3. Setelah gerbang kompatibilitas dan backup lulus, operator menjalankan dari root checkout:

```sh
set -euo pipefail
: "${RELEASE_REV:?SHA target wajib}" "${PHP:?PHP absolut wajib}" "${COMPOSER:?Composer absolut wajib}" "${BACKUP_FILE:?Backup wajib}"
git checkout --detach "$RELEASE_REV"
"$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
"$PHP" "$COMPOSER" check-platform-reqs --no-dev
npm ci
npm run build
# Pastikan hak storage/bootstrap/cache benar sesudah proses pemasangan/build.
# Deploy awal: artisan down sesudah vendor tersedia (Nginx masih 503).
bash deploy/deploy.sh "$PHP" "$RELEASE_REV" "$BACKUP_FILE" "APPLY:$RELEASE_REV"
```

4. `deploy.sh` memastikan sumber bersih tepat pada SHA, vendor/aset/env tersedia, maintenance aktif dan gzip backup valid; kemudian menjalankan `migrate --force`, `config:cache`, `route:cache`, `view:cache`. Flag konfirmasi ini bukan pengganti izin user maupun bukti backup/schema kompatibel. Kegagalan menghentikan skrip dan **maintenance tetap aktif**. Tidak ada rollback schema otomatis.
5. Operator meninjau `"$PHP" artisan migrate:status`, `"$PHP" artisan route:list`, log, dan hasil perintah. Sesuaikan nama service, lalu `sudo systemctl reload php8.3-fpm` untuk melepas opcode lama; `sudo nginx -t` sebelum reload bila config berubah. `"$PHP" artisan up` hanya setelah langkah di atas lulus dan owner siap smoke test. Bila langkah apa pun gagal, tahan akses dan jalankan keputusan pemulihan di bawah.

`config:cache` membuat `.env` tidak dibaca langsung oleh request; konfigurasi harus melalui file config aplikasi. `public/build/manifest.json` berasal dari script repo `npm run build` (`vp build`), bukan asumsi perintah Vite lain. Node diperlukan saat build saja; aplikasi produksi tidak memakai `npm run dev` atau `php artisan serve`. Ketentuan deployment/caches merujuk [Laravel 13](https://laravel.com/docs/13.x/deployment).

## Smoke test dan penerimaan

Catat target/domain, SHA `git rev-parse HEAD` di server, waktu dan hasil per kasus; SHA itu harus sama dengan receipt/QA. `curl --fail --silent --show-error https://<DOMAIN_Q03>/up` harus 200, login harus terbuka lewat HTTPS, dan aset tidak 404. `/up` saat ini hanya health route framework, bukan pengujian bisnis/database. Jangan menampilkan password di curl atau bukti.

Dengan izin owner untuk transaksi uji, gunakan data sintetis yang mudah dikenali: login admin dan teknisi; buat TTS; proses sampai nota; cek harga hanya admin; unduh PDF A5; cek struk 58 mm dan printer Q-02; buka tracking token tanpa login; cek laporan terhadap transaksi tadi. Periksa angka/tanggal WITA, hak akses dan log tidak mengandung error baru. Tandai data uji agar owner menentukan penanganannya, jangan menghapus data produksi otomatis. T006/T017 dan fitur lain harus diterima sebelum kasus tersebut dinyatakan tersedia. Gagal salah satu alur PRD §14.3 → tahan penerimaan, maintenance lagi dan pilih rollback/forward-fix. QA independen/pascadeploy tetap gerbang terpisah; perintah exit 0 bukan penerimaan rilis.

## Rollback kode dan restore data

Pemicu: migrasi/cache gagal, HTTP 5xx, login/transaksi/dokumen/tracking/laporan gagal, atau data berubah salah. Operator mencatat waktu/error secara privat dan menghentikan penulisan dengan maintenance. Jangan terus mencoba migrasi/import tanpa diagnosis. **Rollback kode tidak mengembalikan schema/data.**

- Jika schema baru masih kompatibel dengan kode lama: pada checkout aktif jalankan `"$PHP" artisan down`, `"$PHP" artisan optimize:clear`, lalu `git checkout --detach "$PREVIOUS_REV"` (SHA tercatat sebelum deploy). Pasang lockfile lama dengan Composer `install --no-dev --prefer-dist --optimize-autoloader --no-interaction`, `npm ci && npm run build`, bangun ulang cache config/route/view, reload FPM, periksa migrate:status tanpa menjalankan migrasi lama, lalu buka maintenance dan ulangi smoke test terhadap SHA lama. Jangan mengandalkan aset/vendor baru untuk kode lama. Pada instalasi pertama, kembalikan halaman 503 dan perbaiki; belum ada revisi aktif sebelumnya.
- Jika migrasi merusak data/schema atau kode lama tidak kompatibel: tetap maintenance; pilih forward-fix yang direview atau restore backup **dengan izin pemilik**. Nilai potensi hilangnya transaksi sejak backup (backup harian dapat kehilangan hingga sekitar 24 jam; backup pra-migrasi lebih baru), simpan snapshot kondisi gagal, dan sepakati rekonsiliasi. Jangan memakai `migrate:rollback` sebagai jaminan pemulihan data.

Prosedur restore nyata:

1. Verifikasi otorisasi, target host/socket **dan DB**, checksum arsip terhadap sumber luar VPS, asal serta usia backup, APP_KEY versi yang cocok. Ambil arsip melalui kanal aman; hanya dump tepercaya dari skrip ini. SQL arbitrer bisa menyebut schema lain; argumen DB bukan sandbox. Hak akun restore harus terbatas pada database target. Jangan gunakan root/global grants. `--binary-mode` menonaktifkan perintah client berbahaya saat batch, tetapi tidak menyaring SQL; lihat [MySQL client](https://dev.mysql.com/doc/refman/8.0/en/mysql-command-options.html).
2. Pulihkan dulu ke DB terisolasi yang **sudah dibuat/disetujui pemilik**, tanpa koneksi aplikasi aktif. File `/etc/repair-app/restore-target.cnf` hanya berisi koneksi ke host itu dan akun khusus target, mode `600`. Cek tabel target yang sudah ada: dump mengganti tabel yang dicakup, **tidak menghapus tabel tambahan** dari schema yang lebih baru. Untuk pemulihan schema lama yang tepat, gunakan database kosong yang dibuat pemilik dan arahkan `.env` ke sana setelah validasi; penghapusan/recreate database lama adalah tindakan terpisah yang perlu izin, tidak dilakukan skrip.
3. Jalankan secara eksplisit (ganti nama target; tidak ada default):

```sh
bash /srv/repair-app/ops/restore.sh /etc/repair-app/restore-target.cnf repair_app_recovery /private/verified-backup.sql.gz RESTORE:repair_app_recovery
```

4. Skrip mendekompresi seluruh arsip ke temporary directory mode privat sebelum koneksi DB; gzip rusak/kosong tidak diimpor. Restore MySQL **tidak atomik**, DDL dapat commit dan import gagal bisa meninggalkan target parsial. Tetap maintenance jika gagal, simpan diagnosis aman, jangan otomatis mengulang. Skrip tidak membuat/drop DB, user, atau mengubah `.env`.
5. Operator memeriksa tabel, jumlah baris dan nilai penanda, foreign key, status migrasi dan rumus bisnis terhadap backup; pasangkan kode SHA yang kompatibel (langkah rollback di atas), key/config dan aset yang cocok, perbarui cache dan reload FPM. Bila berpindah DB, ubah `.env` secara privat lalu `config:cache`. Setelah validasi lengkap, `artisan up` dan ulangi smoke test. Catat hasil restore, SHA dan kehilangan data yang disepakati; pemulihan baru diterima sesudah validasi owner.

## Verifikasi lokal dan batas bukti

Tidak perlu build/suite produk untuk perubahan shell/runbook ini. Jalankan `bash -n deploy/{backup,restore,deploy}.sh`, `python3 deploy/check-scripts.py`, dan ShellCheck bila tersedia. Self-check memakai stub, tidak membuktikan MySQL/Nginx/FPM/cron/SSL produksi.

Prosedur berikut adalah riwayat rencana verifikasi T020 yang kini sudah dijalankan dan PASS sesuai assessment di atas; **jangan mengulang restore ke fixture yang ada**. Untuk verifikasi baru yang diberi izin, coordinator lebih dulu memastikan pemilik mengisi kredensial secara aman, mengonfirmasi DB `repair_app`/`repair_app_test` khusus proyek, dan mereservasi `db:mysql-test` eksklusif. Pemilik menyediakan dua akun terbatas pada target/dua file `.cnf` privat; jangan kirim nilai lewat chat. Gunakan `repair_app_test` yang sudah disetujui dan kosong; bila ada data, STOP, jangan overwrite. Catat SHA kandidat yang diperiksa.

Setelah izin tulis fixture jelas: dengan MySQL client dan `.cnf` privat, jalankan `CREATE TABLE t020_backup_probe (id INT PRIMARY KEY, marker VARCHAR(64)) ENGINE=InnoDB`, isi `(1,'T020-synthetic-7')`, query jumlah/nilai dan simpan hasil sintetis. Jalankan `backup.sh ... repair_app_test <private-temp-dir> - -`: **expected exit nonzero Q-04, arsip lokal tetap ada**, bukan backup harian PASS. Jalankan `gzip -t` pada arsip yang baru tercipta; simpan SHA-256 arsip. Setelah persetujuan modifikasi fixture, ubah hanya marker probe menjadi `T020-mutated`, lalu `restore.sh ... repair_app_test <exact-archive> RESTORE:repair_app_test`. Query harus kembali tepat satu baris marker `T020-synthetic-7`, periksa exit/status; catat dump/import benar-benar MySQL nyata. Tidak perlu drop data untuk membuktikannya; pembersihan tabel sintetis memerlukan kewenangan pemilik. Arsip/fixture tetap privat.

Kasus salin luar VPS nyata, round-trip unduh/checksum, jadwal cron, `nginx -t`, FPM, HTTPS/renewal dan smoke target tetap menunggu Q-03/Q-04 dan akses/izin. DB-only restore di atas memenuhi bagian round-trip lokal bila berhasil, tetapi tidak mengubah gerbang salinan luar VPS/go-live. Pertahankan hasil gagal lama dan ulangi hanya pemeriksaan yang sebelumnya terblokir pada SHA yang sama; bila sumber/config berubah, tentukan dampak QA.
