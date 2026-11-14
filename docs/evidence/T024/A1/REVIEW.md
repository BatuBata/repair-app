# T024 A1 — review persiapan demo

Reviewer independen `/root/t024_review`, model diminta `gpt-6.1-sol`; receipt native tidak mengungkap identitas model efektif. Ponytail full dan project-delivery dibaca dari sumber yang dicatat dalam README operator. Hasil **PASS khusus paket persiapan**, bukan deployment atau penerimaan T024.

Sumber aplikasi `d047f25ea31d703660212e4205b937053c92adcb` telah lulus T023. Coordinator menyiapkan checkout bersih pada `/home/muzib/deploy-demo-repair-app/release-source`; 230 sumber, 14 aset build dan 9 font cocok dengan manifest QA, diperiksa ulang di server. Paket operator dan template environment kosong berada di staging privat, bukan web root. Semua checksum paket operator cocok setelah transfer.

Reviewer membaca konfigurasi, skrip dan instruksi operator, lalu menjalankan salinan sementara secara independen: 41 kasus guard/backup, empat jalur bootstrap simulasi dan tiga pemeriksaan `bash -n` PASS. Bukti simulasi implementer ada dalam `ops/checks.json` dan `ops/bootstrap-path-checks.json`; pengulangan independen dilaporkan melalui handoff native reviewer. Dua kegagalan awal harness implementer tetap disimpan dalam `ops/checks-first-failure.txt`.

Temuan yang ditangani: aturan Nginx mengizinkan hanya logo raster berformat nama yang sesuai; koneksi memakai `mariadb`; opsi dump MySQL yang tidak didukung MariaDB dihapus dari adapter khusus target. Jika validasi Nginx gagal, konfigurasi demo dipulihkan tanpa mengganti situs lain. Alternatives `php`, `phar`, `phar.phar` dipin manual ke executable 8.2 yang sama sebelum instalasi 8.3; perubahan mode ini diterima coordinator dan dijelaskan dalam README. Batas unggahan mempertahankan logo 2 MiB yang diizinkan aplikasi.

Manifest host: `4b0484e966f9cb18ec20b7e4d91e2e0945f638242f45abcf9b41a1a7eb8404ac`.
Manifest seluruh paket: `b9289e35459b7c61d16d8f6a5343f6a4e011baa429c399c5b8a7bf8e8bdcd1f1`.

T024 menunggu owner menjalankan perintah sudo yang telah disiapkan, mengisi password DB/admin secara privat, serta menentukan backup. User telah mengizinkan MariaDB existing, tetapi baru meminta penjelasan lingkup backup; belum menyetujui pengecualian backup lokal. Backup yang direncanakan hanya milik aplikasi demo, bukan seluruh server atau aplikasi lain.

Belum dijalankan: instalasi PHP/FPM/Nginx nyata, SSL/renewal, koneksi dan kompatibilitas MariaDB, migrasi/seed, dump/restore nyata, cron/offsite, smoke web, Android/printer. ShellCheck tidak tersedia. Tidak ada data aplikasi existing yang diubah atau dihapus; marker pemeliharaan harus dipertahankan sampai gerbang aplikasi dan backup selesai.
