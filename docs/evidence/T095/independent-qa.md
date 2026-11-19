# T095 — QA independen rilis pengambilan TTS Batal

## Reuse preflight — PASS

Source target `029003eb7075fc33b8b3789510511fe5402ebad4` dibandingkan melalui `git show` dengan seluruh enam hash source-manifest T094: identik. QA perilaku T094 tetap berlaku; tidak ada alasan menjalankan ulang suite lokal yang sama. Ponytail **full** dan prosedur QA project-delivery tetap aktif dari sumber yang telah dibaca pada sesi ini.

## Pascadeploy — PASS

Pemeriksaan independen dilakukan sesudah coordinator mengonfirmasi [CI/deploy44559835155](https://github.com/BatuBata/repair-app/actions/runs/44559835155) sukses. Target nyata: `muzib@202.10.40.165:/var/www/demo-repair-app/current`, revision `029003eb7075fc33b8b3789510511fe5402ebad4`. Hasil aktual disimpan dalam `postdeploy-independent.json`.

- SSH BatchMode + PHP8.3 hanya-baca: `.release-revision` dan `last-success.json` tepat SHA target; receipt pass `2026-11-19T15:56:03Z`; kedua marker maintenance tidak ada.
- Bootstrap aplikasi dan `SELECT 1` berhasil. Nama15 migrasi di filesystem dibandingkan dengan ledger DB; pending0. Tidak menjalankan migrasi atau menulis schema/data.
- Empat berkas runtime server (`ManageServiceOrder`, `admin-actions`, detailTTS, tracking) di-hash dan dibandingkan terhadap bytes `git show` SHA target: semuanya identik, mismatch0.
- Source server diverifikasi memiliki urutan guard/tenant-lock → Batal → picked_up_on masihnull → todayAsia/Makassar → return sebelum transisiDiambil/pembayaran. UI admin memiliki tombol hanyaBatal/belumdiambil; detail/public memiliki informasi pengambilan dan tanggal existing.
- `/usr/bin/curl` sistem dengan verifikasi TLS aktif: `/up`, `/login`, `/icons/repair-app-192.png` HTTP200 dan `ssl_verify_result=0` semuanya. Tidak menggunakan pengabaian sertifikat.

Batas QA: tidak login, menandai TTS pelanggan, membuka tracking token pelanggan, membuat transaksi, mengirimWA, membaca environment/kredensial/isi baris pelanggan atau menguji perangkat fisik. Verifikasi perilaku menggunakan QA T094 pada source yang sama; produksi diperiksa read-only. Tidak mengulang suite/hash211berkas atau tes keamanan rilis sebelumnya karena delta hanyaempatberkas tersebut. Tidak ada cacat pascadeploy yang ditemukan dalam scope ini.
