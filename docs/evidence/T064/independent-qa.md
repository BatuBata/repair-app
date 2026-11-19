# T065 — QA independen pengecekan opsional

Verdict: **PASS**, lingkup perangkat lunak lokal D-38; tidak ada temuan cacat produk. Tidak push/deploy, tidak mengubah kode aplikasi atau ledger, tidak menyentuh database operator.

Tester terpisah dari implementer, runtime model diwarisi tanpa pergantian/klaim receipt model efektif. Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery beserta `references/qa-and-release.md` dibaca. Kontrak T064/T065, PRD D-38, AGENTS, caller/domain/route/tenant scope/migrasi dan view terkait diperiksa.

## Pin dan environment

Base `1fc6fb14c70d6dde84c0043a96ab5f15b5cb51e7`, snapshot `sha256:9f1cd601a0ed6ad3e0433dabaad63a0c2503a6e36c6a8f3ad2a986378e5b7bb0`, input T064 `source-manifest.json`. Pemeriksaan Python `hashlib.sha256(Path(file).read_bytes()).hexdigest()` terhadap semua 17 entry manifest dan `git rev-parse HEAD` PASS; pemeriksaan akhir juga PASS 17/17. Semua tes di bawah memakai PHP8.3 proyek dan konfigurasi `phpunit.xml` SQLite `:memory:`, bukan MySQL lokal/operator.

## Perintah aktual dan hasil

```sh
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit tests/Feature/Invoice/InspectionTest.php tests/Feature/Invoice/InvoiceTest.php tests/Feature/Domain/DomainTest.php --colors=never
```

PASS **45 tes, 513 assertions, 1790ms**. Ini mencakup kedua jalur penetapan, nota diblokir saat cek, transisi legal, simpan/kirim, pembatalan, XSS, role/tenant, stale status, dan guard rollback. Fullsuite/build/Pint/PHPStan tidak diulang karena source backend/config/dependency yang diuji pengembang identik; subset dipilih untuk risiko status/otorisasi/migrasi.

```sh
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --bootstrap vendor/autoload.php /private/tmp/T065IndependentTest.php --colors=never > /private/tmp/T065-independent-result.json
```

Tes tambahan ditulis tester di `/private/tmp`, tanpa mengubah test produk. PASS **5 tes, 67 assertions, 650ms**. Sumber dan output tersimpan pada path di atas; SHA256 tes tambahan `86dce6072a44db28db44c08bb936b745a8a5478e2f4329c9ba1291623b4ed426`. Kasus:

| Kasus | Ekspektasi dan observasi aktual |
|---|---|
| Checking reassign | Reassign ke teknisi lain dengan argumen `dikerjakan` tetap Pengecekan, waktu cek tetap, waktu kerja null; penetapan teknisi berganti waktunya. Reassign teknisi sama tetap mempertahankan waktu. Mulai kerja boleh tanpa catatan/persetujuan digital. PASS |
| Batas/atomic validation | 4000 karakter Unicode hasil + 4000 karakter rencana diterima. Whitespace-only hasil, rencana4001, dan array ditolak tanpa merusak kedua nilai tersimpan. PASS |
| WA terbaru dan privasi | `sendInspection` menyimpan teks terbaru dengan `& + ?` dan newline sebelum redirect; query WA terdekode memuat keduanya. WA tanpa query inspection tetap tanda terima dan tidak memuat catatan. Tracking tidak memuat catatan/nama, header cache private/no-store. Tidak mengirim pesan eksternal. PASS |
| Role/tenant | Form dimount admin kemudian akun didemosi: saveInspection/sendInspection/startWork masing-masing403, catatan/tahap tetap. Panggilan langsung simpan/mulai dengan tenant asing ditolak model-not-found. PASS |
| Stale dan legacy | Form lama simpan/kirim sesudah mulai kerja ditolak status dan tidak menulis catatan. Caller assign dua argumen tetap langsung Dikerjakan. Semua5status lama melalui down/up SQLite mempertahankan seluruh atribut lama identik;4kolom baru null. PASS |

Riwayat verifikasi: eksekusi pertama tes tambahan 4 tes/44 assertions menghasilkan3PASS1FAIL karena assertion mengharapkan string header `private, no-store`, sedangkan framework menormalkan menjadi `no-store, private`. Ini kegagalan assertion bukti, bukan produk. Assertion diganti memeriksa kedua directive; satu kasus seluruh5status legacy ditambahkan, kemudian hasil akhir di atas PASS. Tidak mengubah kode aplikasi.

## Review risiko dan bukti pendukung

`ManageServiceOrder::locked()` memeriksa admin aktif sebelum lookup tenant-scoped dan transaksi mengunci baris terkini; metode baru memakai gerbang yang sama. Invoice tetap mensyaratkan Dikerjakan. `startWork` hanya Pengecekan dengan teknisi; reassign tidak menulis ulang tahap/timestamp kerja/cek. WA tetap route auth/admin dan lookup tenant-scoped. Tracking memilih kolom secara eksplisit tanpa kedua catatan dan template menampilkan status/timestamp baru dengan fallback legacy. Escaping Blade tetap dipakai untuk catatan.

Migrasi aditif memperluas enum dan menambah4kolom nullable; guard down dijalankan sebelum schema berubah saat ada Pengecekan. Bukti implementer `/private/tmp/repair-t064-mysql.php` dan `.log` direview: isolated disposable MySQL8.0.31, legacy attributes/items preserved, new enum accepted, unsafe rollback refused, safe down/up preserved. Ini **bukti pengembang yang direview**, bukan eksekusi MySQL independen. Browser coordinator390/1280 dua jalur dan finaltextarea/jarak tombol mengacu README T064; tidak diklaim sebagai sesi browser tester.

Tidak ada temuan yang menahan penerimaan perangkat lunak lokal. Batas: tidak mengulang fullsuite/build, tidak menguji MariaDB10.11, MySQL nyata independen, browser independen, HP/printer fisik, pengiriman WA nyata, atau deploy. Down migrasi secara desain menghapus kolom catatan/timestamp baru ketika tidak ada status Pengecekan; rollback nyata tetap perlu rencana perlindungan catatan, tidak dilakukan di QA ini.
