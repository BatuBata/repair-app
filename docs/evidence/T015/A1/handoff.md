# T015 — integrasi M2/M4, A1

**PASS integrator**, branch `codex/m2-m4-candidate`. Base `2e12ec9631bc6e9370f78048eeebeb24f37869fe`; merge pinned T014 `aef8ffc75b11344b10ddaabe9ccae4a7e1776e45` membawa T012/T013. Inputs wajib T011 `0736515bb5f673febfe293604166bad46308954a`, T013 `3c35dd1118121d02cca7abb0ae99b78baeb30634`, T014 `aef8ffc75b11344b10ddaabe9ccae4a7e1776e45`. Source/artefak final dipin pada manifest, dan commit handoff mengikat snapshot yang sama. Tidak merge main; T016 independen tetap gate.

Ponytail **full** tetap dimuat dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery QA/coordination dibaca untuk assignment integrator ini; desain memakai DESIGN existing. Sol high diminta, backend efektif tidak diekspos receipt. Sesi ini pernah mengimplementasi T009/T010 sehingga bukan tester independen T016. Tidak ada worker tambahan, push/deploy, atau edit ledger oleh integrator.

## Integrasi dan pemeriksaan

`git merge --no-ff --no-commit aef8ffc75b11344b10ddaabe9ccae4a7e1776e45` selesai otomatis tanpa konflik. Tidak ada patch business logic atau koneksi semantik tambahan. T009–T011 dipertahankan, sumber Stock/Sales/Expenses cocok byte/hash input masing-masing; `input-source-proof.json`. Tidak ada migrasi/model/dependency baru. Helper T008 hasil formatting b5ae99a tetap identik. TODO pada index merge mempertahankan versi ours; perubahan working TODO untuk grant/status berasal coordinator dan tidak distage integrator. AGENTS/.env tetap sama, main tetap b5ae99a; `control-proof.json`/`merge-proof.json`.

| Perintah aktual pada sumber gabungan | Hasil |
| --- | --- |
| `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint --test` | **PASS global**, termasuk helper PHP docs existing; `pint-global.log`. Helper T015 yang ditambahkan kemudian dicek tersendiri PASS (`fixture-pint.log`). |
| `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpstan analyse --memory-limit=512M --no-progress --debug` | **PASS0error**, `phpstan.log`. |
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' RUN_MYSQL_DOMAIN=0 /opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --colors=never --log-junit docs/evidence/T015/A1/suite.xml` | **157PASS,1585assertion,1guardedMySQL SKIP** dari158tes; `suite.log/xml`. Dijalankan sekali, tidak diulang setelah PASS. |
| `npm run build` (Node22.23.2/npm10.9.8) | **PASS**, satu production build pada Blade gabungan; `build.log`. CSS baru `assets/app-CxA26Lmt.css`;14file pada `assets-manifest.json`, sumber input build dan runtime font turut dipin. |
| `node docs/evidence/T015/A1/browser-smoke.mjs`, lalu subset guard | **14observasi PASS**, `browser-results.json` dan `browser-guards-final.log`. |

Build memperingatkan optional fontaine dan5URL font IBM yang diselesaikan saat runtime. Font tersebut tracked/ada dan Chrome memuat IBM Plex dengan sukses. Tidak memasang package atau mengubah konfigurasi untuk warning. Jangan memakai bundle M1 untuk T016: gunakan tepat14file public/build saat ini dengan SHA256 dari `assets-manifest.json`; artifact sama boleh disalin ke fixture QA, jangan rebuild tanpa alasan kondisi baru.

## Smoke pada bundle baru

Satu rangkaian nyata desktop1280 dan capture terkait360 membuktikan: teknisi login→buat TTS; admin login→tetapkan teknisi→terbitkan nota total1.000.000/modal700.000; tamu membuka tracking publik yang menampilkan harga jual tanpa HP customer/modal; admin membuat barang stok3unit→menjual2unit pada125.000 dengan snapshot modal70.000→stok1→hapus penjualan lewat dialog task→stok kembali3; admin tambah pengeluaran150.000 lalu ubah175.000 lewat menu yang dipindah ke body. Semua melalui UI/komponen aplikasi. `invoice-proof.json`, `stock-proof.json`, `sale-proof.json`, `sale-deleted-proof.json`, `expense-proof.json` memuat expected/actual database sintetis. Dropdown menggunakan pola selectOption DOM existing, sedangkan tombol/ketikan memakai input CDP nyata. Tidak mengirim pesan WhatsApp ke pihak lain.

Tracking, nota dan pengeluaran tampak dengan IBM Plex, lebar dokumen sama viewport1280/360. Screenshot utama dibuka untuk memverifikasi bundle baru. Teknisi tidak menerima angka modal di detail nota dan mendapat HTTP403 pada pengeluaran/penjualan; body403 tidak memuat angka keuangan. Guard final merekam actual font sistem/error framework dan shellfalse, bukan memaksa font aplikasi. Runtime JS exception0 pada subset akhir.

Runner awal salah menunggu Plex pada halaman403 framework setelah seluruh smoke utama/tracking selesai; `browser-failure.json`/`browser.log` dipertahankan sebagai kegagalan bukti, bukan bug asset/aplikasi. Hanya dua guard tersisa diperiksa ulang dengan kondisi DOM/HTTP yang tepat; assertion noamounts tambahan sesuai review coordinator dicatat pada run subset final. Tidak mengulang alur, full suite atau build.

## Isolasi dan batas

Preflight sebelum server/browser: direktori task0700, credential/config0600, SQLite baru kosong, path cache/session/views/storage privat, TASTE_SKILL_TMP subtree baru; Chrome headless dengan process/profile unik dan CDP port0, PHP8.3 loopback8189. Migrasi existing dijalankan hanya ke file SQLite baru (bukan migrate:fresh/DB existing). `browser-preflight.json`, `fixture-seed.log`. Credential/key/tracking token disimpan privat dan tidak dicetak/committed; URL tracking pada log laporan disamarkan lewat label kasus, tanpa token. Tidak GUI/CUA/Orca atau menghapus worktree/evidence lama. Chrome/server sudah ditutup; listener8189 tidak ada, source/bundle tetap sesuai hash (`resource-release.json`).

Ini smoke integrasi, bukan pengulangan matriks individual atau penerimaan F002/F003. Real MySQL concurrency independen, perangkat Android/printer, visual nominal ekstrem dan kasus risiko lengkap tetap T016/T023. Dashboard/laporan/PDF downstream belum menjadi bukti T015. Riwayat developer MySQL T013 dan batas T010 dipertahankan tanpa dinaikkan menjadi QA independen. Main tidak boleh digabung sebelum T016 PASS dan review relevan.
