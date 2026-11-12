# T008 — QA independen M1, A1

**Verdict produk: FAIL.** Verifikasi sudah selesai pada **f6b23c974b319520b80991d67200c05ddd5562f4**, input T007 FINAL yang sama. Ada dua cacat peluncur/manifest dan satu celah pencabutan izin unggahan sementara. Tidak ada perbaikan sumber, perubahan ledger kanonik, penerimaan F001, merge `main`, atau deploy. Koordinator menentukan assignment perbaikan; hasil lulus di bawah tetap dapat dipakai untuk bagian yang tidak berubah.

Saya membaca Ponytail **full** dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`, Project Delivery beserta `references/qa-and-release.md`, skill design-taste-frontend beserta seluruh aturan app-screen, serta panduan Orca orchestration/browser terpasang. Ekspektasi berasal dari AGENTS/T008 kanonik, PRD AC04/19/20/24/26, §10/13/M1, DESIGN, PRODUCT dan mockup yang benar-benar dibuka; bukan kesimpulan implementer. Sesi ini terpisah dari implementer/integrator, tanpa worker tambahan. Sol high diminta, tidak ada fallback; identitas model backend efektif tidak ditampilkan oleh `worker-show`.

## Temuan yang perlu diperbaiki

### F01 — peluncur teknisi menuju 403 (utama, AC26)

1. Login sebagai teknisi aktif (login itu sendiri mengarah ke `/tts/baru`).
2. Buka `/profil`, baca manifest yang ditautkan, lalu lakukan navigasi nyata ke `start_url` manifest: `/`.
3. **Expected:** peluncur mengirim pengguna yang sudah login ke tujuan perannya, yaitu `/tts/baru`; form TTS itu sendiri memang masih T009/404.
4. **Actual:** URL tetap `/`, respons dokumen **403**, layar “Akses ditolak”. Ini kegagalan entry point M1, terpisah dari belum tersedianya form T009. Root/dashboard harus tetap menolak teknisi; jangan melonggarkan guard dashboard untuk menyamarkan cacat peluncur.

Pemilik: kontrak T003 manifest dan routing T004; `public/manifest.webmanifest:1`, rute admin root pada `routes/modules/scaffold.php`, `User::homePath()`. Bukti: `browser-results.json` → manifest teknisi (final Android emulation), `screenshots/manifest-launch-teknisi-360.png`. Admin start `/` menghasilkan 200; guest start `/` menghasilkan login.

### F02 — Chrome tidak menemukan manifest pada login (AC26)

Buka `/login` sebagai tamu. **Expected:** manifest ditautkan sehingga Chrome dapat menemukan aplikasi dari pintu masuk ini. **Actual:** tidak ada `link[rel=manifest]`; `Page.getAppManifest` mengembalikan URL/data kosong dan `Page.getInstallabilityErrors` memberikan **`no-manifest`**. File manifest yang di-fetch langsung tetap 200 dan JSON valid; itu tidak memperbaiki tautan yang hilang pada halaman.

Pemilik: layout login T004/shared manifest T003, `resources/views/layouts/auth/simple.blade.php:4`–head. Bukti: `browser-results.json` → manifest guest. Layout autentikasi app mempunyai link manifest; layout login hanya mempunyai `theme-color`.

### F03 — grant unggah lama tetap menerima file setelah demosi (batas unggah)

1. Admin aktif memanggil **aksi nyata `Store::_startUpload`**; URL bertanda tangan disimpan privat.
2. Controller sintetis lain menurunkan admin itu menjadi teknisi melalui **`Users::edit/save`**.
3. Dengan sesi aktor yang sama, POST PNG ke URL unggah yang masih berlaku.
4. **Expected dalam kontrak QA:** aktor yang kehilangan izin tidak dapat menulis byte unggahan (403). **Actual:** **200**, satu file sementara ditulis. Signature yang diubah ditolak **401** dan GET pengaturan toko tetap **403**.

Dampak terbatas yang terbukti: penerimaan file **sementara** memakai capability URL yang belum kedaluwarsa; bukan penyimpanan logo publik/pengaturan, bukan kemampuan membuat grant baru. Semua aksi komponen sesudah demosi/deaktivasi tetap ditolak. Ini dicatat sebagai kegagalan pencabutan batas unggah yang diminta assignment; signature URL tidak dicantumkan di bukti. Pemilik: T004/T005 shared upload authorization, `bootstrap/app.php:20`, `Store`/konfigurasi middleware unggah; endpoint framework `FileUploadController` memeriksa signature tetapi tidak role admin. Bukti: `upload-boundary.json`, `upload-grant.log`; helper privat `upload-boundary.py` dipertahankan untuk koordinator.

## Pemeriksaan yang benar-benar dijalankan

`cases.json` adalah matriks expected/actual/command/pin/bukti. Whitespace akhir pada log framework dinormalisasi setelah Git whitespace check menolaknya; hasil dan angka tes tidak diubah. Semua command PHP memakai `/opt/homebrew/opt/php@8.3/bin/php` **8.3.30**; PHP global tidak diubah. Tidak memasang dependency, menjalankan composer setup, atau membangun ulang aset.

| Lingkup | Hasil independen | Bukti |
| --- | --- | --- |
| Auth/users/settings/profile/dashboard terarah | **36 tes PASS, 296 assertion** | `targeted.log`, `targeted.xml`, `targeted-execution.json` |
| Tambahan adversarial: semua rute admin guest/teknisi; aksi setelah nonaktif; protokol unggah setelah demosi; kategori/TTS/creator historis; gabungan role/status admin terakhir | **5 tes PASS, 64 assertion** | `IndependentTest.php`, `independent.log/xml` |
| Tambahan celah coverage: category mode locked, `_uploadErrored` setelah demosi/nonaktif | **2 tes PASS, 3 assertion**; hanya dua kasus baru ini dijalankan | `independent-extra.log/xml` |
| Sesi/cookie HTTP nyata | **10 observasi PASS**: recaller dipulihkan setelah session cookie dihapus; demosi, deaktivasi, reset password langsung setelah login; sesi/remember lama mengikuti status/role/password terbaru | `http-sessions.json` |
| Balapan admin MySQL aktual | **4 pasangan PASS**; satu operasi sukses, yang lain constraint benar atau 403; selalu tersisa satu admin aktif | `mysql-baseline.json`, `mysql-races.json`, `mysql-inventory.json` |
| AC24 layar M1 | **12 layar/state dasar × 3 lebar PASS**, ditambah error, menu, panjang, preview, gulir bawah dan penyimpanan sentuh | `browser-results.json`, `supplement-results.json`, `visual-summary.json`, screenshots/contact |
| Manifest admin | **PASS diagnostik desktop Chrome dengan Android emulation**: parse tanpa error, ikon nyata192/512, theme/background benar, standalone, start admin200 | `browser-results.json` |
| Manifest guest/teknisi | **FAIL**, F01/F02 di atas | JSON dan screenshot peluncuran |
| Unggah lama setelah demosi | **FAIL**, F03 di atas; publik/logo tidak disimpan | `upload-boundary.json` |

43 tes independen menghasilkan **363 assertion PASS**. Itu tidak mengubah tiga observasi gagal menjadi PASS. Guest HTML redirect dan JSON401 serta teknisi403 diperiksa pada **semua lima** rute yang tersedia: `/`, `/dashboard`, `/pengaturan/pengguna`, `/pengaturan/toko`, `/pengaturan/kategori`. Rute profil tetap tersedia untuk kedua peran. Forged ID negatif/string/array/hilang, state/id/path locked, last-admin deactivate/demote, validasi password/current password/confirmation, username, raster/SVG/spoof/oversize, serta rollback logo gagal tulis tercakup pada subset yang dijalankan.

Kategori nonaktif benar-benar ditolak oleh `CreateServiceOrder` untuk TTS baru; relasi/nama kategori dan pembuat TTS lama tetap ada setelah akun/kategori dinonaktifkan. **Form `/tts/baru` sendiri belum ada**: tidak diklaim lulus sebagai layar/fitur T009.

## MySQL/InnoDB dan provenance fixture

Hanya **`repair_app_qa_m1`** digunakan. Pencarian `information_schema` difilter tepat ke nama schema ini: **belum ada**, kemudian dibuat, **0 tabel sebelum migrasi**. Migrasi candidate committed selesai, menghasilkan20tabel; `users` dan seluruh tabel memakai **InnoDB**, versi server **8.0.31**. Kredensial root lokal dari `.env` kanonik dibaca hanya ke memori; koneksi loopback. Password, key, DSN, process-environment dump, SQL mentah, hash/password rows, dan metadata schema lain tidak dicatat.

Empat pasangan mengikuti pola T004: deactivate/deactivate, demote/demote, deactivate/demote, demote/deactivate. Kedua proses memakai aktor admin pertama, target pertama diri sendiri dan target kedua peer. Dua proses PHP independen boot dan menunggu barrier `ready-0/1`, lalu satu file `start` melepas keduanya (poll1ms). Aksi memakai **metode Users aktual**, bukan update SQL untuk operasi yang diuji. Durasi/start skew/overlap/query time/jumlah transaction attempts ada di JSON; tidak ada error lingkungan atau retry deadlock yang tersamarkan. Retry maksimum5 adalah perilaku source, observed attempts pada transaksi yang dimulai masing-masing1; penolakan awal403 dapat terjadi sebelum transaksi.

Antarkasus hanya baris sintetis dengan prefix task ini di schema QA sendiri dinonaktifkan; tidak truncate/drop/reset schema. Final fixture dipertahankan: **8 pengguna task-owned,1 admin aktif**. Private provenance: `mysql-ownership.json` di direktori privat pada `session.json`; inventory publik tidak berisi credential/row rahasia. `repair_app` dan `repair_app_test` tidak disentuh. Root untuk pengujian lokal ini **bukan** penerimaan least-privilege produksi. Helper worker tersimpan di bukti; salinan runner adalah arsip script yang dijalankan dari direktori privat (jangan mengeksekusi ulang terhadap schema nonkosong tanpa meninjau provenance).

## Tampilan, input dan perbandingan desain

Chrome terpasang **157.0.8103.52** digerakkan lewat CDP client existing milik skill, tanpa dependency baru/profile pengguna. Setiap capture merekam **innerWidth aktual360/768/1280**, document width sama, font loaded, target/control rect, actionbar dan focus/error. Body **16px** pada360/768 dan **14px** pada1280; seluruh kontrol mobile/tablet terukur minimal **44×44** (checkbox dinilai bersama label target). Input border yang diukur `#8a9296` pada putih **3,17:1**; primary text pada putih **18,43:1**. Ini sampel token shared, bukan sertifikasi kontras seluruh pixel.

Saya membuka seluruh contact sheet utama dan tambahan, serta screenshot native. Login dibandingkan langsung dengan **mockup/login.html pada tiap lebar**. Layar lain dibandingkan dengan pola list/form mockup `tts.html`/`tts-baru.html` dan aturan DESIGN yang disetujui: bingkai L, Plex Sans, aksi kiri, dropdown, status pucat, satu aksi hitam, daftar mobile/nav bawah, tabel/sidebar desktop, form satu kolom dan save menempel. Nama/kategori panjang membungkus; preview192PNG termuat. Home M1 masih shell fondasi, bukan dashboard domain T018.

Mouse native Orca setelah viewport dan snapshot valid berhasil mengganti tipe password; **anomali klik pertama T007 tidak terulang**. Matrix utama mencatat event **isTrusted:true** dari touch CDP di360/768 dan mouse di1280. Enam menu final users/kategori berhasil ArrowDown/Up, Escape + pengembalian fokus, klik luar dan scroll. Save bawah benar-benar mengirim error dan kemudian valid user/category/store di360/768; perubahan password teknisi360 juga berhasil. Login dapat dilanjutkan dari error sampai berhasil.

Capture full-page dari halaman yang sudah tergulir dapat menempatkan fixed bar di tengah gambar gabungan; itu **bukan bukti overlap produk**. Capture viewport/bawah tambahan memperlihatkan field bawah dapat digulir dan Simpan/nav tetap terjangkau. Seluruh kegagalan helper/input/environment awal dipertahankan pada `environment-observations.json` dan JSON first-run; hanya subset yang belum terbukti diulang. Termasuk `APP_ENV=testing` fake-upload disk dan input yang tertutup sticky header; helper kemudian memusatkan target dan memeriksa hit-test. Ada reset tidak sengaja **hanya fixture SQLite privat task** oleh RefreshDatabase saat dua tes tambahan: browser run sudah selesai; baseline ulang dibuat pada **file baru** `browser-repeat.sqlite`, dan runner masa depan memakai SQLite in-memory. Fixture MySQL tetap utuh.

Final manifest diagnostics memakai UA/client hints Android13/Pixel7, touch1 dan coarse pointer; lebar awal about:blank980 sebelum meta viewport bukan ukuran halaman uji, seluruh halaman launch terukur360. Chrome diagnostic admin/teknisi tanpa installability error **tidak membuktikan pemasangan Android fisik**; navigasi peluncur teknisi tetap gagal403.

## Reuse, sumber tetap dan publikasi

`source-proof.json`: **164file** sama, aggregate **11cbc71ccb63f910845ac4bd779244a090905de4723829554dfafe3c9740525f**. `assets-proof.json`:14file build sama. Semua pin T002–T005 ancestor FINAL. Kode sama dengan `ede491e`; perbedaan menuju FINAL hanya bukti T007. **Suite integrator T00771PASS1MySQLSKIP698assertions dipakai ulang**, tidak dipresentasikan sebagai tes independen yang baru. PHPStan/Pint/build existing tetap bukti producer asal. Numbering MySQL16saves T002 tetap bukti developer pada layanan/skema unchanged; tidak dijalankan ulang dan tidak menghapus fixture `repair_app_test`. Detail producer/pin/impact pada `reused-evidence.json`.

Branch awal `candidate/m1` bersih, kemudian `qa/m1` dibuat tepat dari FINAL. Hanya `docs/evidence/T008/**` ditulis di Git. Input requirement/mockup/font/asset hashes dicatat; pemeriksaan akhir membuktikan byte source/build tidak berubah dan candidate/main/taskpins utama tetap. Server8088 dihentikan, port bebas, tab native milik task ditutup, seluruh Chrome temporary profile ditutup. Private repeat SQLite dan fixture MySQL ditinggalkan untuk koordinator. `resource-release.json`, `publication.json` dan `timings.json` menyimpan receipt/durasi aktual.

Untuk menghindari self-reference hash, `result.json` menunjuk commit bukti inti immutable; commit publikasi berikutnya hanya menambahkan result/receipt. SHA final yang benar-benar di-push dan diverifikasi diberikan pada handoff runtime. **Checked revision selalu FINALf6b23c9**, bukan commit evidence dan bukan pin lama T007dfcd5c3.

Belum diuji: Android/keyboard/printer fisik, HTTPS/VPS/produksi/deploy, suite remoteCI, dan layar modul lanjutan; semua tetap NOT_RUN/T025 atau task terkait. Risiko yang harus dibawa: peluncur teknisi403, manifest login hilang, dan grant unggah sementara lama belum dicabut setelah role berubah; F001 belum boleh diterima dari laporan ini.
