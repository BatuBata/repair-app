# T055–T056 — Aksi dan riwayat pelanggan

Base `075871e14c23414fe26f7087efd5b4f9039bf7ba` + perubahan lokal. Menu shared row-actions: Lihat/Edit/Hapus, ikon sprite existing. Delete hanya baris Customer tenant aktif, recheck admin aktif, konfirmasi JS sebelum action. TTS/nota tetap independen. Halaman Show admin-only memakai ID locked dan eager loading category; history berdasarkan customer_phone dalam scope tenant aktif, 20 per halaman. Batas nomor HP dijelaskan di UI: nomor berubah mengubah hasil riwayat. Tidak ada migrasi atau backfill.

Pemeriksaan developer: Customers 7 tes/64 assertion PASS; full suite 237 PASS, 1 MySQL existing SKIP, 2527 assertion. Pint, PHPStan --debug (0 error), npm build dan diff check PASS. Tiga kegagalan awal hanya fixture: model belum direfresh untuk snapshot DB; middleware membersihkan tenant context sesudah HTTP sehingga test perlu menyetel kembali context. Source tidak diubah untuk mengatasi fixture.

QA independen `/root/customer_history_qa` membaca Ponytail full dan QA project-delivery, read-only: Customers+TTS 25 PASS/223 assertion; 2 edge/7 assertion memastikan pagination, ID locked dan akun nonaktif ditolak. Hash source sama sebelum/sesudah QA. Harness `/private/tmp/CustomerHistoryIndependentQaTest.php`. Tidak ada temuan backend penghambat.

Browser coordinator pada SQLite sintetis `/private/tmp/repair-customers-XXXXXX.sqlite`, server8302: menu shared menunjukkan Lihat/Edit/Hapus; Lihat membuka history1TTS yang benar dan tautan ke TTS; Edit mengisi data yang benar; halaman history390px scrollWidth=innerWidth390 dan screenshot dibaca. Klik Hapus memicu timeout kontrol CDP/dialog; hasil konfirmasi/batal browser tidak diklaim PASS. AX berikutnya kembali daftar dan kedua pelanggan masih terlihat. Unit/feature test delete dan preservation seluruh atribut TTS PASS. Tidak menghapus pelanggan produksi. Server uji dihentikan.

Belum: MariaDB, HP/printer fisik, konfirmasi hapus end-to-end browser, push/deploy. Tidak ada skema/data produksi disentuh.
