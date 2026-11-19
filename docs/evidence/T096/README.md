# T096 — pengambilan TTS Batal di daftar Servis

D-59, main lokal tanpa push/deploy. Query daftar mengambil picked_up_on existing. View menambahkan satu keterangan ringkas di bawah nama pelanggan hanya untuk status Batal: Barang sudah diambil bila tanggal ada, Barang belum diambil bila null. Badge Batal, aksi, pencarian, filter dan pagination tetap; tidak menambah field/status/filter/dependency/CSS/aset baru.

Bukti aktual:

- PHP8.3 `artisan test --compact tests/Feature/Tts/TtsTest.php tests/Feature/IndexPaginationTest.php`:25PASS291assert2473ms.
- QA independen `CancelledPickupListTest`:2PASS34assert319ms; DOM perbaris membuktikan picked/unpicked/normal tepat pada admin/teknisi, pencarian/filter dan isolasi tenant. TestfilePintPASS.
- PHP8.3 `vendor/bin/pint --dirty --test` PASS; `git diff --check`PASS.
- CUA fixtureSQLite `/private/tmp/repair-t096.sqlite` dengan3bariscampur. Desktop1280/HP375 menunjukkan keduaketerangan hanya padaBatal; normalDiterima tanpa labeltambahan. SearchQA dantabBatal tepat2baris; documentwidth sama viewport. Bukti desktop.jpg/mobile.jpg/mobile-batal.jpg/browser-checks.json. Nama pelanggan dan label tetap terbaca, kolomstatus tidak dilebarkan.
- Manifest3file runtime/test source-manifest.json sha256:15d10ebd586288e8d4a5a35a82840ea728ab696550ae3333083d944b7f3c9aed; pin/screenshot ditinjau independen dalam independent-qa.md.

Serverfixture/tabagent ditutup danviewportdipulihkan. TidakmengubahDBoperator, menandai TTS pelanggan atau melakukanpush/deploy. Build/PHPStan/suitepenuh/MySQL tidakdiulang untuk penambahan satu kolomSELECT dan viewtext tanpa perubahan types/schema/aset; perilaku dipastikan pengujian target/browser. Produksi tetap source029003e.
