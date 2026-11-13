# T008 A2 — QA independen M1

**PASS pada source `db5de005a345de644a7aa9056ea93f5a81117a3d`**, input kanonik T007 yang sama. Source aplikasi saat assignment HEAD `255d805` dan sesudahnya identik terhadap pin tersebut; commit lain hanya metadata/bukti. F01/F02/F03 dari A1 telah diuji ulang dan lulus. Riwayat FAIL A1 tetap berlaku sebagai catatan historis, tidak ditimpa. Hanya coordinator menerima F001 dan memperbarui TODO; laporan ini tidak melakukan merge main, push atau deploy.

Tester ini terpisah dari implementer/integrator, tanpa worker tambahan. Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery + `references/qa-and-release.md`, instruksi proyek, T008/T007, PRD §10/13/M1 AC04/19/20/24/26, serta design-taste-frontend + aturan app-screen dibaca. DESIGN/mockup yang disetujui tetap acuan; tidak ada arah desain baru. Sol high diminta; identitas backend efektif tidak diekspos receipt dan tidak diklaim.

## Hasil baru

| Pemeriksaan | Expected dan actual | Bukti |
| --- | --- | --- |
| F01 peluncur peran | `/mulai` guest → login; admin → `/`200; teknisi → `/tts/baru`404 di tiga lebar. Root/dashboard teknisi tetap403. Role yang baru diubah melalui Users diterapkan pada sesi HTTP lama | `browser-results.json`, `http-boundaries.json`, screenshot launcher/guard |
| F02 manifest login | Chrome menemukan dan mem-parse manifest dari login tamu, admin dan profil teknisi; start `/mulai`, standalone, nama toko, ikon nyata192/512; **9diagnostik**, tidak ada installability error | `browser-results.json`; Android13/Pixel7 UA dan mobile emulation di Chrome nyata157.0.8103.52 |
| F03 batas unggah nyata | Grant didapat dari aksi aktual `Store::_startUpload`; admin lain memanggil `Users::edit/save` untuk demosi dan deaktivasi. POST multipart PNG memakai sesi/CSRF nyata yang sama mendapat **403**, delta filesystem **0** pada kedua kasus | `http-boundaries.py/json`, `fixture.php`, log fixture grant/transition |
| Kontrol batas unggah | Admin aktif200/1path/2file (byte+metadata); signature diubah401/no file; tamu401/no file; teknisi403/no file; sesi nonaktif telah logout sehingga request berikutnya401. Semua lima rute admin menolak aktor demosi403 | **13observasi HTTP PASS** dalam `http-boundaries.json`; grant/key/password tetap privat |
| Regresi terarah | `AuthenticationTest`, `RoleLauncherTest`, `TemporaryUploadAuthorizationTest`, `SettingsTest`: **30PASS,284assertion**, tidak ada skip/error | `targeted.log/xml`, `targeted-execution.json` |
| Browser tiga lebar | **27observasi PASS**,11screenshot baru; innerWidth/documentWidth sama360/768/1280, font IBM Plex Sans loaded pada layar app, body16/16/14px. Login, home, profil dan tujuan peran diperiksa | `browser-qa.mjs`, `browser-results.json`; semua11screenshot dibuka |

`/tts/baru`404 memang diharapkan sebelum T009. PASS di sini membuktikan **tujuan peluncur** sesuai peran; tidak mengklaim form TTS sudah tersedia. AC26 pada HP Android nyata tetap T025.

## Sumber, penggunaan ulang dan review

`provenance-before.json`, `source-final.json` dan `reused-evidence.json` memuat pin/hash dan alasan dampak. Dari manifest164file source historis A1, **161identik**; tiga file existing berubah adalah `public/manifest.webmanifest`, `resources/views/layouts/auth/simple.blade.php`, `routes/modules/auth.php`. Tiga file baru adalah `config/livewire.php` dan dua tes Auth. Seluruh source saat ini identik dengan pin T007. **14aset build** identik dengan hash historis dan restorasi T007 A2; **27file PRD/DESIGN/PRODUCT/mockup** identik. Composer installed metadata dan source framework/Livewire batas unggah juga sama. PHP8.3.30/Chrome157 sama dengan QA A1. Tidak build ulang atau mengubah dependency.

Bukti independen A1 pada checked revision `f6b23c9`, evidence `b5919d1`, dipakai ulang secara eksplisit untuk **23case IDs** yang tetap berlaku: matriks guest/teknisi; pencabutan sesi/remember/reset password; aksi komponen/ID palsu/state terkunci; kategori nonaktif dan histori pembuat/TTS; validasi dan gagal tulis logo; admin terakhir serial; **empat pasangan race MySQL/InnoDB**; **12layar/state M1 ×3lebar** beserta menu, fokus, error, teks panjang, preview dan penyimpanan melalui sentuh. Daftar ID, SHA256 laporan asal, producer serta kondisi ada di `reused-evidence.json`. Source `User`, middleware akun/admin, Users, Store, Categories, Profile, service dan migrasi tidak berubah. MySQL existing tidak diakses atau di-reset sekarang. Ini **reuse hasil independen**, bukan tes MySQL baru.

Suite lengkap integrator T007 A2 **80PASS817assertion1MySQLSKIP** pada source pin sama ditinjau dan dipakai ulang sebagai bukti integrator; tidak disamakan dengan tes baru tester dan tidak diulang. FAIL manifest/launcher/upload A1 tidak dipakai sebagai PASS; ketiganya mempunyai bukti baru di atas.

Semua11screenshot baru serta13contact sheet utama A1 (360×5,768×4,1280×4) benar-benar dibuka. Login tetap sama dengan mockup/login pada setiap lebar; daftar dan form mengacu pola mockup/tts dan tts-baru yang terlihat di contact sheet. Pertanyaan layar tetap: masuk, pengelolaan akun, mengganti password, data toko/garansi, kategori yang dapat dipakai. Bingkai L desktop, daftar dua baris/aksi kiri, menu pilihan, navigasi bawah, tombol Simpan di atas nav, form satu kolom dan teks panjang tetap sesuai DESIGN. Tidak ada overlap permanen, pemotongan atau aksi hilang pada bukti yang diperiksa. Capture full-page lama dapat menggandakan fixed bar saat menggulir; penilaian keterjangkauan berasal dari capture viewport/lower dan hasil interaksi A1, bukan artefak gabungan itu. Home M1 adalah shell fondasi; dashboard domain tetap T018. Review A2 tidak membuat skor desain/sertifikasi aksesibilitas baru. Sampel data baru seluruhnya sintetis T008 dan hanya untuk QA.

## Perintah aktual dan lingkungan

Semua dari root repo:

```text
python3 docs/evidence/T008/A2/provenance.py
python3 docs/evidence/T008/A2/runner.py init
python3 docs/evidence/T008/A2/runner.py targeted
python3 docs/evidence/T008/A2/runner.py serve
python3 docs/evidence/T008/A2/runner.py cache
python3 docs/evidence/T008/A2/http-boundaries.py
node docs/evidence/T008/A2/browser-qa.mjs
python3 docs/evidence/T008/A2/final-proof.py
```

Runner memakai `/opt/homebrew/opt/php@8.3/bin/php`, Node22.23.2, SQLite file baru mode0600 pada direktori privat0700 (`session.json`), tanpa root.env berubah. `init` menjalankan migrate **tanpa fresh/drop/reset**, kemudian fixture3user/2kategori/1toko;20tabel dipertahankan. PHPUnit memakai SQLite in-memory terpisah. `cache` menjalankan artisan config:cache ke **path privat**; cache mem-pin disk/session/view/log ke storage privat. Jangan mencetak atau commit isi cache/grant/credentials. Helper `serve` tidak diaktifkan lagi setelah handoff; browser profile dan listener8188 telah ditutup.

`environment-observations.json` mempertahankan kegagalan helper/lingkungan: sandbox listener/network EPERM, satu approval timeout kemudian retry berhasil; helper awal200upload tetapi salah mengasumsikan lokasi filesystem; satu font-wait yang keliru diwajibkan pada halaman error403 bawaan. Tak ada source fix. Setelah config cache privat, kontrol positif membuktikan file nyata masuk ke path yang dihitung sebelum pengujian negatif. Upload awal menulis **satu PNG sintetis + metadata** ke ignored root `storage/app/private/livewire-tmp`; keduanya dipertahankan, tidak menghapus data. Initial browser observations tetap di `browser-environment-failure.json`. Tidak mengulang full suite/build; pengulangan browser hanya untuk melengkapi diagnostik terhalang helper itu.

## Review batas kepercayaan

Rute `/mulai` memakai `auth`, lalu `User::activeAccount()` membaca akun sekarang sebelum `homePath()`; tidak menerima tujuan redirect dari input user. Root/dashboard tetap group `auth,admin`. Manifest file statis tidak memuat rahasia, link ditemukan pada layout login dan app. Framework upload controller menggabungkan middleware `web` dengan config `auth,admin,throttle:60,1`; auth/admin terjadi sebelum validasi signature dan penulisan file. `EnsureActiveAccount` memperbarui aktor/logout akun nonaktif dan `EnsureAdmin` membaca akun terbaru. Grant lama tidak menggantikan guard ini. Signature/expiry, throttle60, batas temp12MB dan preview MIME bawaan masih tercakup30tes; `Store` tetap memvalidasi raster≤2MB sebelum publikasi dan memeriksa admin pada boot/save/render. Scope unggah M1 hanya logo admin; uploader untuk peran lain di fitur mendatang perlu meninjau kebijakan global ini.

Belum diuji: instalasi/keyboard/touch Android fisik, printer, HTTPS/VPS/deploy, remoteCI dan fitur sesudah M1. Tidak ada deploy; grant/key/password privat dan fixture sintetis disimpan untuk penelusuran. Risiko tersisa terkait batas itu; tiga temuan produk A1 telah tertutup pada pin ini.
