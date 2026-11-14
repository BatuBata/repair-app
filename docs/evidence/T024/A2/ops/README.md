# T024 A2 — Certbot 2.1 compatibility correction

Ponytail full tetap aktif. Input source T022 `d047f25ea31d703660212e4205b937053c92adcb`, QA T023 `c07b6c6a4245b86a594ba15e44a3a44bca31cdf0`; effective model receipt masih unknown. A1 immutable dipertahankan FAIL: host PHP8.3/FPM/Nginx/validLE berhasil tetapi Certbot2.1 menolak opsi `--run-deploy-hooks`. APT404 indeksSury adalah failure environment yang terpisah; metadata repo existing telah dipulihkan coordinator. A2 hanya mengoreksi pemeriksaan renewal, tanpa perubahan source aplikasi.

**Jangan menjalankan ulang bootstrap pada host yang sudah terpasang.** Script bootstrap tetap template first install dengan guard target-existing. Untuk deployment aktual T024, parent menjalankan hanya recovery/check sempit berikut dalam terminal root yang sudah diizinkan, setelah memverifikasi digest hook A1 yang tetap sama:

```sh
certbot renew --cert-name demo-repair-app.scannara.asia --dry-run
env RENEWED_LINEAGE=/etc/letsencrypt/live/demo-repair-app.scannara.asia RENEWED_DOMAINS=demo-repair-app.scannara.asia /etc/letsencrypt/renewal-hooks/deploy/demo-repair-app
```

Kedua environment variable diperlukan: hook existing memfilter `RENEWED_DOMAINS`; lineage-only adalah no-op yang tidak membuktikan reload. Dry-run hanya sertifikat demo. Explicit hook memvalidasi Nginx sebelum reload, tidak menyunting situs/sertifikat lain. Pemanggilan hook terpisah ini tidak diklaim sebagai Certbot dry-run menjalankan deploy-hook otomatis; keduanya dibuktikan terpisah. Event renewal nyata berikutnya tetap memerlukan pemantauan operator. Timer Certbot existing telah aktif.

Delta host-code terhadap A1 hanya penggantian satu baris `certbot renew ... --run-deploy-hooks` menjadi supported dry-run + explicit root-owned demo hook. Konfigurasi Nginx, FPM, logrotate, renewal-hook, adapter backup dan template environment byte-identik A1. Tidak ada DB/account/password/cron/migrasi/seed/unmaintenance dalam koreksi ini. Auto→manual php/phar/phar.phar8.2 tetap keputusan yang disepakati; marker demo tetap503.

`check-bootstrap-paths.py` memakai stub CLI Certbot2.1 yang menolak opsi unsupported, menjalankan hook aktual yang diadaptasi hanya path dummy, dan membuktikan reload hook terjadi setelah dry-run; recovery HTTP/TLS dan default8.2 tetap diperiksa. `check-ops.py` menjaga41guard/backupcases. `checks-first-failure.txt` adalah riwayat harness A1 yang disalin untuk provenance, bukan kegagalan A2 baru. Pemeriksaan lokal tidak menggantikan hasil host.

Hasil source-install target dan permissiongate berada di parent folder ops-next, terpisah dari bundle operator ini. Source terpasang tetap503; Owner sudah mengisi dan menutup staging environment; worker melakukan sinkronisasi privat atomic0640 dan PDOread-only membuktikan target demo_repair_app ada dengan0tabel. Jangan menyalin template blank lagi. Parent melaporkan APP_KEY dibuat sekali tanpa nilai dicetak, filemaintenance aktif, storage-link target exact dan Git bersih; pertahankan key pada semua sinkronisasi environment berikutnya. Migrasi/seed belum dijalankan. Backupoffsite/restore/cron dan secret owner tetap gate; tidak ada penerimaan rilis dari bundle ini.

## Bounded root finalization, setelah review independen

Persistent root shell telah ditolak automatic approval review dan tidak dijalankan. Gunakan hanya `finalize-host.sh` yang sudah ditinjau: target/path/revision/marker/hash manifest dan root-owned hook dipastikan sebelum perubahan; hanya grup/SGID demo, probe ACL sintetis eksklusif dan explicit TLS hook. Tidak ada shell interaktif, exec shell, DB/account/secret/migrasi/key/seed/up/cron atau permission global. Jika gagal, tetap503 dan jangan retry buta. Probe yang selesai dibuat/ditulis didaftarkan untuk cleanup EXIT dan hanya dihapus bila path/owner sesuai; tidak menyentuh data user. Jika exclusive fopen berhasil tetapi penulisan awal gagal sebelum pendaftaran, probe sintetis dapat tertinggal untuk diagnosis; jangan membersihkan dengan wildcard.

Parent mengunggah file hasil review ke `/home/muzib/deploy-demo-repair-app/finalize-host.sh`, mode700. Satu invocation sudo di bawah menyalin ke path root tetap, memverifikasi SHA256 hasil review **setelah salin**, kemudian menjalankan script terbatas; tidak membuka shell interaktif:

```sh
sudo /bin/bash -c '
set -euo pipefail
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
test ! -e /root/repair-app-t024-finalize.sh
test ! -L /root/repair-app-t024-finalize.sh
install -o root -g root -m 0700 /home/muzib/deploy-demo-repair-app/finalize-host.sh /root/repair-app-t024-finalize.sh
printf "fb4e90944bc85627e57939ab9c455759509d3c160961b3f16b81962c9b716275  /root/repair-app-t024-finalize.sh\n" | sha256sum -c -
/bin/bash /root/repair-app-t024-finalize.sh FINALIZE:demo-repair-app
'
```

Password sudo diisi owner langsung pada terminal bila diminta. Script berakhir sendiri, tanpa membuka/menahan shell root. Jangan menjalankan ulang bootstrap first-install. `ROOT-FINALIZE.md` sebelumnya adalah rencana yang kini digantikan script ini. Hasil root aktual belum dijalankan oleh worker; perintah shell root yang ditolak tidak dihitung telah mengeksekusi perubahan.
