# T096 — QA independen informasi pengambilan pada daftar Servis

**PASS** pada snapshot `sha256:15d10ebd586288e8d4a5a35a82840ea728ab696550ae3333083d944b7f3c9aed`; tiga hash `source-manifest.json` diverifikasi identik. Main lokal tanpa push/deploy. Reviewer terpisah, Ponytail **full** dari skill5.3.3 yang telah dibaca dan panduan QA project-delivery tetap berlaku.

Perintah reviewer (PHP8.3, SQLite in-memory `RefreshDatabase`):

- `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint tests/Feature/Tts/CancelledPickupListTest.php`: PASS.
- `/opt/homebrew/opt/php@8.3/bin/php artisan test --compact tests/Feature/Tts/CancelledPickupListTest.php`: **2 PASS,34 assertions,319ms**.

Tes DOM memeriksa asosiasi per baris: TTS Batal yang belum diambil menampilkan Barang belum diambil, Batal yang sudah diambil menampilkan Barang sudah diambil; kedua teks tidak tertukar. TTS normal berstatus Diambil yang juga memiliki tanggal pengambilan tidak memperoleh keterangan khusus Batal. Admin dan teknisi melihat hasil yang sama. Filter Batal dan pencarian nama mempertahankan teks yang benar; data Batal toko lain tidak masuk hasil/hitungan/pencarian.

Review diff: query existing hanya menambahkan `picked_up_on` ke select, tidak menambah query, filter/status/schema atau mengubah mutasi. View menampilkan teks hanya pada enum Batal, memakai tanggal existing sebagai penentu, ditempatkan di bawah nama pelanggan pada kolom yang memiliki ruang di desktop/HP. Aksi dan pill status tetap.

Bukti coordinator yang digunakan tanpa pengulangan suite: TtsTest+IndexPaginationTest **25 PASS,291 assertions,2473ms**, Pint dirty/diff PASS. Ini mempertahankan pembuktian pagination/search existing.

`desktop.jpg`, `mobile.jpg`, `mobile-batal.jpg` dibuka dan diperiksa independen; `browser-checks.json` diperiksa. Campuran3baris menunjukkan dua keterangan berbeda pada Batal dan tanpa keterangan pada Diterima. Tab Batal menyisakan2baris dengan keterangan yang benar. Desktop1280/HP375 terbaca, nama/keterangan tidak menabrak status dan scrollWidth sama viewport. Interaksi browser dijalankan coordinator, bukan reviewer.

Tidak menguji perangkat fisik atau produksi/operatorDB; tidak menjalankan build ulang karena tidak ada sumber aset/CSS/utility baru. Tidak ada cacat tersisa pada scope ini; tanpa dependency, push atau deployment.
