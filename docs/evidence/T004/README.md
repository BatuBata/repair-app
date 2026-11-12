# T004 — developer A1

Hasil: **developer checks PASS**, menunggu QA independen M1 T008 dan integrasi. Input T002 `4a0667c302c979ab40641459bf9e7afac0655e09`, T003 `a08d1d785b72199e1000840ea8e2afe1d7f38fbd`, base assembly `78e5a5f04864dac3aab917731562f4254ba75c28`. Ini candidate task, bukan main/T007 acceptance.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; worker rules Project Delivery dan panduan design app-screens dibaca. Model terminal Orca teramati `GPT-6.1-Sol high`; tidak ada fallback, worker lain, atau perubahan ledger. Coordinator menyetujui scope sempit assertion guest root pada `tests/Feature/ExampleTest.php` melalui `msg_aebdbe33315c`.

## Perubahan dan kontrak untuk T005

- Fortify dipakai hanya untuk login/logout melalui controller resmi dan pipeline sesi framework. Username/password + ingat saya; tidak ada syarat email, registrasi publik, recovery mandiri, verifikasi email, 2FA, passkey atau hapus akun. Endpoint starter tersebut tidak didaftarkan; SFC pengaturan lama berhenti dengan 404 dan tidak menyimpan aksi lama.
- Admin login ke `/`, teknisi ke `/tts/baru`; intended URL tidak mengubah tujuan peran. `/` dan alias `/dashboard` memerlukan `['auth', 'admin']`. Root hanya shell dan akses akun/profil tanpa angka dashboard rekaan.
- `/pengaturan/pengguna` bernama `users.index`, komponen `App\Livewire\Users\Index`. Daftar, tambah, ubah nama/username/peran/status, aktif/nonaktif, dan reset password memakai satu komponen dengan mode dan ID terkunci. Tidak ada operasi delete.
- `/profil` tetap bernama `profile.edit`, komponen `App\Livewire\Profile\ChangePassword`. Semua akun aktif dapat mengganti password sendiri dengan password saat ini + validasi framework. Alias `/settings`, `/settings/profile`, `/settings/security` mengarah ke `/profil`; appearance dan endpoint keamanan lain tidak tersedia.
- Middleware web `EnsureActiveAccount` menyegarkan akun dari DB sebelum `AuthenticateSession`, sehingga sesi lama memakai role/status/password terkini. Nonaktif membatalkan sesi dan memberi 403. Login menyimpan hash password sesi; reset/password change mengganti remember token. Password change merotasi ID sesi; logout menghapus sesi dan merotasi token CSRF.
- **Pola modul T005 dan berikutnya:** rute admin memakai `middleware(['auth', 'admin'])`; setiap aksi Livewire publik dan render yang membaca data terlindungi memanggil `User::activeAccount(admin: true)`. Untuk modul semua peran gunakan `User::activeAccount()`. Jangan mengandalkan menu atau mount saja. Custom middleware juga didaftarkan sebagai persistent middleware Livewire.
- Semua mutasi pengguna lewat transaksi dengan lima retry. Update no-op pada ID baris pengguna tertua menjadi kunci bersama yang stabil karena produk tidak menghapus akun; locking read memeriksa ulang aktor, target, dan admin lain setelah kunci didapat. Ini mencegah dua mutasi menghilangkan admin aktif terakhir, termasuk demosi. Skema T002 tidak berubah.
- `/tts/baru` belum diimplementasikan (T009): redirect dan keberadaan sesi terbukti, downstream 404 dicatat terpisah. T004 tidak membuat TTS palsu. Destinasi domain lain pada shell T003 tetap menjadi pekerjaan task berikutnya.

## Pemeriksaan aktual

PHP proyek `/opt/homebrew/opt/php@8.3/bin/php` **8.3.30**, Node **22.23.2**, npm **10.9.8**. Dependency terkunci dipasang lokal dengan `php $(command -v composer) install --no-interaction --prefer-dist --no-scripts`, `npm ci --ignore-scripts`, lalu `php artisan package:discover --ansi`; tidak menjalankan composer setup, tidak mengubah dependency/lockfile.

`/tmp/t004-run.py` adalah harness privat: random APP_KEY dalam environment, `DB_CONNECTION=sqlite`, tes `:memory:`, browser SQLite file baru, `RUN_MYSQL_DOMAIN=0`. Harness dan kredensial tidak masuk Git. Perintah di bawah memakai PHP proyek; semua expected dan actual diringkas dari log yang disimpan.

| Perintah aktual | Expected | Actual / bukti |
| --- | --- | --- |
| `python3 /tmp/t004-run.py test` | Seluruh tes lokal lulus; MySQL tidak disentuh | **62 kasus: 61 PASS, 1 SKIP, 612 assertions**, 3,088 detik pada run final; `suite-final-source.log` |
| `python3 /tmp/t004-run.py test tests/Feature/Users/ConcurrentAdminTest.php` | Empat pasangan mutasi bersama menyisakan satu admin aktif | **4 PASS, 24 assertions**, 1,552 detik pada run awal; juga tercakup suite final |
| `php vendor/bin/phpstan analyse --memory-limit=512M --no-progress` | Tidak ada error tipe/statik | **PASS, 0 errors**, `static-input-final.log` |
| `php vendor/bin/pint --test <seluruh file PHP T004 yang diubah>` | Format sesuai repo | **PASS**, `format-final-source.log` |
| `npm run build` | Asset terbangun | **PASS**, bundling 334ms; `build-final.log` |
| `curl --request POST http://127.0.0.1:8084/login --data 'username=csrf-check'` (tanpa token/header origin) | Pemalsuan request ditolak | **419**, `csrf.log` |
| `node …/lint.mjs --app <empat salinan sementara Blade berakhiran .html>` | Tidak ada pola terlarang | **0 FAIL, 0 WARN**, `design-lint-final.log` |
| `node …/inspect.mjs --app http://127.0.0.1:8084/login --out docs/evidence/T004/inspector-login` | Font/tata letak acuan terukur | **0 FAIL, 1 WARN**: ruang kosong disengaja untuk login terpusat 380px; `design-inspector.log` |
| Orca browser pada port **8084**, private SQLite | Seluruh alur akun dan ukuran layar | Lihat `browser-checks.json`, `browser-final-flows.json`, screenshot dan `*-measurements.json` |

Cakupan suite termasuk login username/invalid input/invalid credentials/throttle/remember/role redirect, email nullable, endpoint terlarang, sesi lama setelah role/nonaktif/reset, 403 server dan setiap aksi Livewire setelah demosi, ID invalid/terkunci, pembuatan/ubah/aktif/reset pengguna, password profil, admin terakhir dan empat race antar proses, serta scaffold/layout/domain/seeder yang terdampak User/auth. MySQL concurrency suite existing dilewati oleh guard opt-in; bukan dianggap lulus. Assertion cookie recaller dan pergantian ID sesi memakai fasilitas framework.

Build memberi warning existing untuk fontaine opsional dan URL font publik yang diselesaikan runtime; font IBM Plex Sans 400/500/600 benar-benar loaded di seluruh capture. Tidak ada dependency baru. Static/Pint/PHP tests final memeriksa source yang di-pin oleh `source-manifest.json`; perubahan HTML terakhir hanya separator menu memakai `<hr>` shared dan ikut suite final.

## Riwayat A1 yang dipertahankan

- `tests-first.log`: environment belum memiliki APP_KEY; dipulihkan dengan random key privat, tanpa membaca kredensial atau mengubah konfigurasi global. `tests-second.log` kemudian **35 PASS / 246 assertions**.
- `static-first.log`: return/parameter type dan resolver translator belum lengkap; diperbaiki tanpa ignore/baseline. `format.log`/`format-correction.log` menyimpan syntax error dari edit lokal yang langsung diperbaiki.
- `tests-final.log`: **56 PASS, 1 FAIL**; layout preview memakai User sintetis yang belum disimpan. Middleware kini hanya mencari DB untuk identity dengan ID; akun nyata tetap diperiksa. `auth-layout-correction.log` lulus kasus auth/layout tersebut.
- `auth-session-final.log` dan `auth-session-correction.log`: satu kasus reset segera setelah login gagal. Prioritas framework memakai interface `AuthenticatesSessions`, bukan nama class concrete; urutan diperbaiki dan `auth-session-order-final.log` **11 PASS / 76 assertions**. Input username/password berbentuk array kemudian diperiksa agar rate limiter tidak TypeError; `auth-input-final.log` **11 PASS / 79 assertions**.
- Browser awal menemukan menu shared yang dipindah ke body kehilangan konteks `wire:click`. Hanya markup pengguna diberi panggilan ke ID komponen Livewire; CSS/JS/component T003 tidak diubah. Pada tab background setelah resize beberapa klik Orca melaporkan unchanged; keyboard bekerja dan uji sentuh akhir di tab aktif membuktikan menu terbuka (`dropdown-final.json`).
- Lint awal tidak mengenali ekstensi `.blade.php`; pemeriksaan final memakai salinan sementara `.html`, tanpa mengubah source. Seluruh iterasi ini tetap **A1**, bukan reset anggaran attempt.

## Visual dan batas pembuktian

Arah tetap `framed-sheet`, seed **1526667476**, dari DESIGN/mockup yang disetujui; tidak ada arah baru. Login dibandingkan langsung dengan `mockup/login.html`; daftar dengan komponen `mockup/tts.html`, form/profil dengan `mockup/tts-baru.html`. Screenshot asli disimpan untuk 360/768/1280, plus inspector 390/768/1440. Semua image dibuka dan ditinjau; `visual-review.md` menjelaskan hasil dan pengecualian. Semua nilai password dikosongkan sebelum screenshot; screenshot form memperlihatkan state kosong, error non-password, atau sukses.

Tidak ada luapan horizontal pada layar T004; body/input **16px** pada 360/768 dan **14px** pada 1280. Menu mobile berada di body, lebar208px, item44px; panah dan Escape bekerja; semua isian dapat digulir ke atas actionbar yang tetap terlihat. Perbandingan komponen tidak mengklaim layout seluruh layar pengguna identik dengan layar TTS yang memang berbeda isi.

QA M1 independen, MySQL/InnoDB concurrency, integrasi main, deploy/HTTPS lingkungan produksi, printer, dan pengisian password admin nyata **belum dilakukan**. Private fixture tetap terpisah dari database lokal nyata; PRD/DESIGN/PRODUCT/mockup/design notes/dependency/lockfile/ledger tidak berubah. Hash commit aktual, push receipt dan checked revision dikembalikan melalui handoff runtime agar tidak memerlukan commit administratif kedua.
