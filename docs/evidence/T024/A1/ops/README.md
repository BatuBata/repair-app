# T024 A1 — persiapan host demo, belum go-live

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery dan QA/release dibaca. Sumber aplikasi dipin `d047f25ea31d703660212e4205b937053c92adcb` (T022), QA T023 `c07b6c6a4245b86a594ba15e44a3a44bca31cdf0`. Model diminta `gpt-6.1-sol`; tidak mengklaim model efektif tanpa receipt runtime.

Target D-20/D-21: Debian 12 `muzib@202.10.40.165`, `/var/www/demo-repair-app/current`, domain `demo-repair-app.scannara.asia`, MariaDB 10.11. Paket ini **hanya persiapan PHP/FPM/Nginx/TLS**. Tidak membuat akun/DB, mengganti password `new_gym`, menyalin aplikasi, migrasi, seed, cron, atau membuka aplikasi. Tidak mengubah situs lama, timezone host, repo APT, PHP global 8.2, atau konfigurasi PHP 7.4/8.2.

## Perubahan host yang benar-benar akan dijalankan operator

- Instal PHP 8.3 berdampingan dari repo Sury yang sudah dikonfigurasi; simulasi ulang wajib menunjukkan nol upgrade/removal dan hanya paket `php8.3-*` baru. Tidak ada `apt update`/upgrade global. Paket FPM dapat mengaktifkan layanan baru php8.3-fpm beserta pool bawaan versi 8.3; pool demo memakai socket khusus. Pool 7.4/8.2 tidak disentuh.
- **Perubahan mode alternatives diterima coordinator:** `php`, `phar`, `phar.phar` berpindah **auto → manual**, tetap menunjuk executable 8.2 yang sama. Pemasangan 8.3 tidak mengubah runtime default. Pilihan 8.3 tetap terdaftar; jangan menjalankan `--auto` kemudian tanpa meninjau perubahan default yang akan terjadi.
- Buat hanya direktori demo baru; kode milik `muzib:www-data`, FPM `www-data`, socket `/run/php/demo-repair-app.sock`, log/rotasi khusus demo. Hanya folder runtime aplikasi yang nanti diberi hak tulis FPM oleh tahap instalasi terpisah.
- HTTP awal hanya ACME + 503; sertifikat Let's Encrypt hanya domain demo. Pilihan akun/email Certbot, bila ditanyakan, diisi pemilik di terminalnya. Hook root hanya reload Nginx untuk lineage demo. Dry-run renewal dipersempit `--cert-name demo-repair-app.scannara.asia`; timer Certbot existing harus sudah aktif.
- HTTPS selalu 503 selama `/var/www/demo-repair-app/.deploy-pending` ada. Marker dimiliki `muzib`; coordinator dapat membukanya setelah semua gerbang lulus tanpa langkah root baru. HTTP ACME tetap berfungsi. Root web hanya `current/public`; logo raster dengan nama `[A-Za-z0-9_-]+` diizinkan, PHP/storage lainnya diblokir. Upload dibatasi Nginx 4m, pool PHP upload 2M/post 4M.

## Jalankan setelah bundle final sudah ditinjau

Parent mengunggah bundle ke `/home/muzib/deploy-demo-repair-app/ops`. Baca enam file host sebelum menyalin. Cocokkan digest manifest `HOST-SHA256SUMS` dengan handoff yang ditinjau melalui kanal tepercaya; pengecekan manifest saja tidak membuktikan asal bundle. Jalankan di terminal SSH pemilik, tanpa `set -x`, tanpa password di argumen:

```sh
set -e
cd /home/muzib/deploy-demo-repair-app/ops
sha256sum -c HOST-SHA256SUMS
sudo test ! -e /root/repair-app-t024-bootstrap
sudo install -d -o root -g root -m 0700 /root/repair-app-t024-bootstrap
sudo install -o root -g root -m 0600 bootstrap.sh php-fpm.conf nginx-http.conf nginx-https.conf logrotate.conf renewal-hook.sh HOST-SHA256SUMS /root/repair-app-t024-bootstrap/
sudo bash -c 'cd /root/repair-app-t024-bootstrap && sha256sum -c HOST-SHA256SUMS'
sudo bash /root/repair-app-t024-bootstrap/bootstrap.sh PREPARE:demo-repair-app
```

Bootstrap menolak target/config/sertifikat yang sudah ada, symlink bundle, bundle bukan milik root atau dapat ditulis grup/orang lain, OS berbeda, domain yang sudah ada pada konfigurasi aktif, serta rencana APT yang tidak aman. Root menjalankan salinan tetap milik root di bawah `/root`, tanpa `source`/`eval` berkas user atau `.env`. Bila gagal: berhenti dan laporkan tahapnya; **jangan mengulang bootstrap**. Periksa efek parsial sebelum pemulihan. Kegagalan validasi HTTP melepas hanya tautan enabled demo yang baru dibuat; kegagalan validasi TLS mengembalikan HTTP503 tervalidasi. Kegagalan APT tetap mempertahankan nilai runtime default 8.2 yang sudah dipin; paket dapat terpasang sebagian, jangan menebak rollback. Situs lama tidak dihapus/ditimpa.

Jangan menghapus `.deploy-pending` dalam tahap ini. Bila satu gerbang aplikasi/backup belum lulus, pertahankan 503.

## Input privat dan langkah lanjutan terpisah

Parent menyiapkan template `.env` di `/home/muzib/deploy-demo-repair-app/environment`, mode 600, **hanya bila belum ada**. Owner mengisi DB_PASSWORD dan ADMIN_INITIAL_PASSWORD lewat editor privat; APP_KEY dibuat sekali saat instalasi pertama. Jangan kirim nilainya lewat chat, jangan `source .env`, jangan cetak file atau isi cache config. Parent nanti menyalin file privat ke `current/.env`, mode 640, pemilik `muzib:www-data`, setelah direktori aplikasi tersedia. Template memakai `DB_CONNECTION=mariadb` yang tersedia di config rilis, timezone aplikasi Asia/Makassar, HTTPS/cookie aman, cache/session database, log daily warning. Password contoh sengaja kosong.

DB `demo_repair_app`/hak `new_gym` harus diperiksa coordinator secara privat sebelum provision; akun mungkin dipakai aplikasi lain. Tidak ada perintah SQL dalam bootstrap. Kredensial backup/restore terpisah dan hak hanya DB demo disiapkan sesudah pemeriksaan dan keputusan owner.

`backup-mariadb.sh` identik dengan `deploy/backup.sh` sumber kecuali nama client `mariadb-dump` dan penghapusan `--set-gtid-purged=OFF`/`--column-statistics=0`, yang tidak didukung MariaDB. Validasi file .cnf/izin/path, lock, error tertutup, arsip lokal, salin SSH, dan retensi tetap ada. `- -` tetap menghasilkan **exit nonzero** sesudah menyimpan arsip lokal; ini bukan backup harian sukses. Jangan memasang `backup.cron.pending` sebelum Q-04 dan restore nyata/retrieval/checksum lulus. Host tetap Asia/Jakarta: contoh 02.00 WIB = 03.00 WITA, bukan 03.00 WIB. Backup hanya aplikasi demo (DB + upload/config privat sesuai rencana owner), bukan seluruh host atau aplikasi lain. Skrip ini mencakup DB; salinan upload/config privat dan tujuan offsite masih perlu rencana/implementasi setelah pilihan owner. Backup tidak dianggap lengkap hanya dengan dump DB.

## Bukti yang benar-benar dijalankan

`checks.json`: 41 pemeriksaan guard/backup berbasis stub. `bootstrap-path-checks.json`: empat jalur bootstrap penuh berbasis stub (sukses tertahan marker, gagal validasi HTTP, gagal validasi TLS, gagal instal APT); ketiga alternatives tetap pada nilai awal 8.2 dalam mode manual yang disepakati. `syntax-checks.txt`: tiga `bash -n`. Dua kegagalan harness awal akibat path temp/symlink macOS dipertahankan di `checks-first-failure.txt`; bukan kegagalan host/app dan bukan percobaan deploy.

Belum diuji: paket Debian nyata, konfigurasi Nginx/FPM nyata, logrotate nyata, ACME/renewal nyata, upload 2 MiB, MariaDB compatibility/restore nyata, offsite/cron, source install, migrasi, seed, alur web/perangkat, atau go-live. Tidak ada aksi remote oleh worker persiapan ini.
