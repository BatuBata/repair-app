# T097 — QA independen rilis keterangan pengambilan daftar Servis

## Reuse preflight — PASS

Target `87bd477da3fe88378b086bb84601b81fa5a8a8e3` dibandingkan memakai `git show` dengan ketiga hash source-manifest T096: identik. QA perilaku T096 tetap berlaku; suite lokal yang sama tidak diulang. Ponytail **full** dari sumber skill5.3.3 yang telah dibaca dan panduan QA project-delivery tetap berlaku.

## Pascadeploy — PASS

Target nyata `muzib@202.10.40.165:/var/www/demo-repair-app/current`, revision `87bd477da3fe88378b086bb84601b81fa5a8a8e3`. Pemeriksaan dimulai setelah coordinator mengonfirmasi [CI/deploy44562204381](https://github.com/BatuBata/repair-app/actions/runs/44562204381) sukses. Hasil aktual di `postdeploy-independent.json`.

- SSH BatchMode + skrip PHP8.3 hanya-baca: `.release-revision` dan `last-success.json` tepat SHA target, receipt pass `2026-11-19T16:08:32Z`; tidak ada marker maintenance aplikasi/deployment.
- Bootstrap aplikasi dan `SELECT 1` berhasil.
- Hash dua berkas runtime (`app/Livewire/Tts/Index.php`, `resources/views/livewire/tts/index.blade.php`) identik dengan bytes `git show` SHA target, mismatch0.
- Source server memilih picked_up_on dan menampilkan dua teks pengambilan berdasarkan tanggal hanya untuk enum Batal. Tidak memanggil aksi pengambilan atau membaca baris pelanggan.
- `/usr/bin/curl` dengan verifikasi TLS aktif: `/up`, `/login`, `/icons/repair-app-192.png` HTTP200 dan `ssl_verify_result=0` semuanya.

Tidak mengulang suite, pemeriksaan schema, hash luas atau tes keamanan yang telah dibuktikan karena delta hanya dua berkas tersebut dan tidak ada migrasi baru. Tidak login, mengambil data pelanggan/token tracking/environment, mengirimWA, menandai barang diambil, membuat transaksi atau menguji perangkat fisik. Tidak ditemukan cacat pascadeploy dalam scope read-only ini.
