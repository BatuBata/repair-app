# T007 — kandidat integrasi M1, A1

**PASS untuk perakitan dan pemeriksaan integrasi ini.** Kandidat `candidate/m1` dibuat langsung dari `ede491e76cbed8000aaee6e86392dc58d2741210`; kode produk, tes, skema, konfigurasi, dan dependency tidak diubah. Penerimaan F001 serta merge aplikasi ke `main` tetap menunggu QA independen T008. Ledger kanonik tidak ditulis.

Ponytail **full** benar-benar dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, `references/qa-and-release.md`, aturan worker/Git pada `references/coordination.md`, serta panduan Orca orchestration dan browser terpasang dibaca. Sesi integrator baru ini diminta memakai `gpt-6.1-sol` high, tanpa fallback; identitas backend efektif **belum terverifikasi**. Tidak ada worker tambahan.

## Input dan kesetaraan sumber

| Input | Pin aktual | Hasil |
| --- | --- | --- |
| T002 | `4a0667c302c979ab40641459bf9e7afac0655e09` | ancestor kandidat |
| T003 | `a08d1d785b72199e1000840ea8e2afe1d7f38fbd` | ancestor kandidat |
| T004 | `ebeb0b62f1a39720091026c1c3a0e8ec535c818e` | ancestor kandidat |
| T005 | `ede491e76cbed8000aaee6e86392dc58d2741210` | basis kandidat dan ancestor |

`git merge-base --is-ancestor <pin> <basis>` berhasil untuk keempat input. `git ls-files -u` kosong. Semua input sudah tergabung lewat snapshot dependensi sebelumnya, sehingga tidak ada merge tambahan atau resolusi konflik. Branch lokal/remote `candidate/m1` belum ada saat diperiksa, lalu `git switch -c candidate/m1 <basis>` berhasil; branch T005 dipertahankan pada pin asalnya.

`source-manifest.json` memuat SHA-256 setiap 164 file sumber aplikasi, tes, migrasi, public assets, konfigurasi, CI dan lockfile, beserta hash agregat dengan algoritme yang dinyatakan. Setiap byte dibandingkan dengan `git show <basis>:<path>` dan cocok. Dokumen user, mockup, catatan desain, bukti lama dan file rahasia tidak dimasukkan ke manifest produk. Setelah commit bukti, pemeriksaan diff membatasi semua perubahan pada `docs/evidence/T007/`.

Untuk menghindari referensi hash commit ke dirinya sendiri, `result.json` mem-pin commit bukti pertama yang sudah immutable; commit publikasi berikutnya hanya menambah hasil dan receipt. SHA HEAD final diberikan pada handoff runtime, dengan byte produk sama dan manifest yang sama. QA T008 harus mem-pin SHA final aktual dari handoff/remote, bukan hanya nama branch.

## Kontrak antarmodul

- Migrasi T002 dan seed menghasilkan admin sintetis, sembilan kategori aktif, serta pengaturan awal kosong dengan garansi 30 hari. Tes domain, relasi, seed dengan cache konfigurasi, dan empat kasus konkurensi admin SQLite ikut suite penuh.
- Fortify T004 memakai username/password dan tujuan sesuai peran. Shell T003 menampilkan identitas admin/teknisi nyata dari akun sintetis yang tersimpan. Root `/` dan `/dashboard` memerlukan admin; root berisi shell tanpa angka dashboard rekaan. Dashboard domain T018 dan TTS T009 belum dibuat.
- Nama/rute `users.index` → `/pengaturan/pengguna`, `settings.store` → `/pengaturan/toko`, `settings.categories` → `/pengaturan/kategori` cocok dengan caller dan middleware `auth` + `admin`; `profile.edit` → `/profil` memakai `auth`. Alias `/settings`, `/settings/profile`, `/settings/security` menuju profil. `route-contract.json` menyimpan assertion terhadap keluaran `route:list` aktual.
- Middleware akun aktif diperiksa sebelum sesi autentikasi; kedua guard terdaftar sebagai persistent middleware Livewire. Komponen pengguna/toko/kategori memeriksa admin aktif pada boot/render/aksi. Suite mencakup penolakan aksi langsung dan akun yang berubah setelah komponen dibuka, bukan hanya menu tersembunyi.

## Pemeriksaan aktual

Runtime: PHP proyek `/opt/homebrew/opt/php@8.3/bin/php` **8.3.30**, Node **22.23.2**, npm **10.9.8**. PHP global tidak diubah; dependency existing dipakai tanpa instalasi/setup. Tidak ada `.env` lokal di worktree ini, dan `.env` kanonik tidak dibaca/disalin. APP_KEY dan kedua password sintetis dibuat acak dalam area privat mode 700, file mode 600, lalu diberikan melalui environment proses. Password tidak menjadi argumen command, isi log atau screenshot.

Harness privat yang benar-benar dipakai: `/var/folders/z3/lllr0gjd35z5pplbyrcr_wf00000gn/T/repair-t007-mhikya2r/runner.py`; mode `test`, `init`, `routes`, `serve`, `bridge-start`, dan `stop`. Seluruh command PHP aktual, target dan durasi proses ada pada `*-execution.json`; harness tidak masuk Git karena mengakses fixture/kunci privat. `browser-events.jsonl` menyimpan perintah Orca, expected URL, respons dan durasi aktual tanpa credential.

| Perintah / langkah | Expected | Actual dan bukti |
| --- | --- | --- |
| `python3 <harness>/runner.py test` → PHP proyek `artisan test --no-ansi --log-junit=<T007>/suite-junit.xml` | Seluruh suite lokal lulus; MySQL opt-in dilewati | **71 PASS, 1 SKIP, 72 kasus, 698 assertions, 0 error/failure**; `suite.log`, `suite-junit.xml`, `suite-summary.json`. PHPUnit XML 3,351002 detik, stdout runner 3362 ms, proses 4,272 detik. |
| Mode tes: `DB_CONNECTION=sqlite DB_DATABASE=:memory: RUN_MYSQL_DOMAIN=0` | Tidak menulis MySQL | MySQL concurrency berstatus **SKIP**, bukan PASS. Race SQLite existing membuat fixture file sendiri yang terisolasi. |
| `python3 <harness>/runner.py init` → `artisan migrate:fresh --seed --force --no-interaction --no-ansi` | Hanya file SQLite baru milik T007, kosong sebelum command | **PASS**, proses 0,339 detik; file baru ukuran 0 byte, tabel kosong sebelum migrasi, enam migrasi selesai, seed 1 admin/9 kategori/1 pengaturan; `fixture-proof.json`, `migrate-seed.log`. |
| PHP bootstrap privat menambah `tech_t007` ke fixture yang sama | Akun teknisi sintetis untuk smoke | **PASS**, proses 0,255 detik; `fixture-technician.log`. Tidak menetapkan password admin aplikasi nyata. |
| `artisan route:list --json` melalui mode `routes` | Nama dan guard rute cocok | **PASS**, proses 0,274 detik; `routes.log`, `route-contract.json`. |
| Preview `artisan serve --host=127.0.0.1 --port=8087 --no-reload --no-ansi`, SQLite privat dan sesi file | Login dan shell dua peran benar | **PASS** dalam batas smoke; langkah/status rinci pada bagian berikut. |
| Periksa manifest aset, nama content hash pada log build T004, diff input CSS/JS/build dan source T005 | Pakai aset existing bila masih sama | **PASS/reuse**, 14 file di-hash pada `assets-manifest.json`; seluruh 12 nama asset content hash cocok dengan `T004/build-final.log`. CSS/JS, Vite dan package/lockfile tidak berubah antara T004–T005; styling kategori tambahan berada di Blade secara inline. Request CSS/JS runtime seluruhnya 200; Plex Sans dimuat. **Tidak rebuild.** |
| PHPStan/Pint | Gunakan bukti sah pada sumber identik | **Reuse**, bukan run baru: T005 PHPStan seluruh union source 0 error + Pint lingkup Settings; Pint T004 untuk auth/profil/users yang tidak berubah. Pin/scope/rujukan pada `reused-evidence.json`. |
| Perbandingan layanan nomor/skema/harness MySQL dengan pin T002 | Bukti numbering lama tetap dapat dipakai untuk sumber terkait yang sama | Diff kosong; developer T002 membuktikan 16 penyimpanan MySQL/4 putaran, tetap bukti lama, **tidak diuji ulang dan bukan QA independen**. InnoDB admin-race tetap T008. |

Pemeriksaan whitespace staged Git pertama menolak spasi akhir keluaran framework pada `migrate-seed.log` dan `server.log`; kedua log dinormalisasi hanya pada whitespace tanpa mengubah hasil, kemudian diperiksa ulang. Full suite dijalankan sekali karena union M1 belum diperiksa penuh pada T005; tidak diulang setelah perubahan dokumen saja. Tidak ada akses MySQL, reset fixture existing, migrasi produksi, perubahan global, source patch, dependency baru atau deploy.

## Smoke browser expected/actual

Browser Orca milik worktree ini, satu tab task `7ebe8701-7ad6-4b77-97fc-a75798205093`. `browser-results.json` berisi ringkasan terstruktur; `browser-events.jsonl` berisi bukti mentah.

| Kasus | Expected | Actual |
| --- | --- | --- |
| Admin login | Ke `/`, shell Admin | Ke `/`, Beranda T003 dan identitas Admin tampil. |
| Admin navigasi pengguna/pengaturan/kategori/profil | Halaman dan data seed tersedia | Link pengguna, Pengaturan, Kategori, dan avatar profil mencapai rute yang benar; kelima request root/users/toko/kategori/profil **200**. |
| Admin simpan toko | Model/Livewire/DB bekerja bersama | Nama sintetis `Toko Uji T007` disimpan; pesan `Pengaturan toko berhasil disimpan.` muncul. |
| Logout admin | Ke login; guest tidak dapat membaca rute privat | `/login`; lima request protected dengan `Accept: application/json` semuanya **401**. |
| Teknisi login, Ingat saya dicentang | Autentikasi sukses, tujuan `/tts/baru` | Redirect nyata `/tts/baru`, **404 karena T009 belum ada**. Ini tidak dianggap form TTS sukses. |
| Teknisi profil | Sesi autentikasi tetap valid, profil dan menu teknisi | `/profil` **200**, identitas `Teknisi Uji T007 · tech_t007`, menu Buat TTS/Daftar TTS/Profil; screenshot tersedia. |
| Teknisi rute admin | 403 | `/`, `/dashboard`, pengguna, toko dan kategori semuanya **403**, halaman penolakan toko benar-benar dibuka. |
| Logout teknisi | Ke login | `/login`, lalu tab task ditutup. |

Screenshot `admin-home`, `admin-users`, `admin-store`, `admin-categories`, `admin-profile`, `technician-pending-tts`, `technician-profile`, dan `technician-forbidden` dibuka untuk inspeksi. Capture toko berada pada posisi gulir setelah Simpan agar field dan pesan sukses terlihat. Aksi baris users/kategori berada di kolom paling kiri dengan menu `···`; layar memakai shell yang sama.

Satu anomali bukti dipertahankan: klik pertama `Kelola pengguna` sesudah perintah viewport melaporkan unchanged dan `wait --url` timeout **25000 ms**. Focus + Enter kemudian mencapai pengguna; klik mouse berikutnya pada navigasi serta Simpan berhasil. Tidak ada perubahan kode untuk menyamarkan kejadian ini; pemeriksaan pointer independen tetap T008. Tidak ada error pada pengamatan console terakhir.

Ini smoke desktop: viewport diminta 1280×900 dan capture pertama berukuran itu; jendela native berikutnya terukur **1231×838** dengan document width sama, sehingga tidak diklaim sebagai validasi seluruh lebar responsif 360/768/1280. Plex Sans loaded; Plex Mono belum dipakai/dimuat pada halaman teks yang diukur. QA manifest dan semua ukuran/state responsif T008 tetap diperlukan.

## Handoff, izin dan sisa

Fixture privat dipertahankan pada path di `fixture-proof.json`/`resource-release.json`; tidak ada credential di bukti. Admin/teknisi logout, tab sendiri ditutup, kelompok proses server 55095 dan bridge sementara 55096 dihentikan, dan port 8087 terbukti bebas. Tidak ada tab atau terminal worker lama yang digerakkan.

Sebelum push, remote diverifikasi `https://github.com/BatuBata/repair-app.git`; tidak ada hook Git aktif. Workflow CI hanya dipicu push `main` atau pull request, tidak ada deployment hook; push normal `candidate/m1` sudah diizinkan user. Receipt push, hash remote dan penjagaan `main`/T005 dicatat pada hasil publikasi/handoff. Tidak ada force push, penghapusan branch, merge main, penerimaan F001 atau perubahan status ledger.

Belum diuji: QA independen T008, InnoDB admin-race, responsive/negative/manifest lengkap, perangkat Android/printer, CI remote, HTTPS/produksi dan deploy. Risiko tersisa: T009 `/tts/baru` serta layar domain/dashboard T018 masih belum tersedia; hasil ini kandidat fondasi M1, bukan penerimaan fitur atau rilis produksi.
