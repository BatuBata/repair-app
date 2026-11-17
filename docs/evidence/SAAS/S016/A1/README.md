# S016/A1 — QA independen panel owner SaaS

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Kontrak QA `project-delivery` dan aturan app screen `design-taste-frontend` juga diterapkan.

## Putusan

**PASS** untuk revisi produk tepat `6d3a0d3cb0458be71f9aff5e0e5226d341f5aa16` (`codex/saas-multitenant`). Tidak ada drift source terhadap revisi itu; perubahan working tree di luar scope adalah ledger/evidence coordinator. Model diminta `gpt-6.1-sol`; receipt model efektif tidak tersedia sehingga tidak diklaim.

## Pemeriksaan independen

| Area | Ekspektasi | Hasil aktual |
|---|---|---|
| Login/config | Tanpa konfigurasi gagal tertutup; salah dibatasi; benar membuat session platform terpisah | PASS. 503 saat hash kosong, lima kegagalan diterima dan percobaan keenam 429; username dinormalisasi; kode meregenerasi session, menyimpan fingerprint konfigurasi, dan menolak session setelah hash berubah. |
| Logout dan pemisahan auth | Logout invalidasi session; user tenant tidak dapat masuk panel | PASS melalui focused test dan browser lokal; logout kembali ke `/platform/login`. Route platform terproteksi middleware platform, sementara route login tetap `web`. |
| CSRF dan route binding | Mutasi memakai `web`/CSRF dan tenant berasal dari binding | PASS. `route:list -v` menunjukkan `web` pada seluruh route dan `EnsurePlatformOwner` pada dashboard/mutasi. Toggle memakai `Tenant $tenant`, bukan ID request bebas. |
| Daftar tenant | Hanya identitas tenant, owner pertama, status, tanggal; tanpa data bisnis | PASS. Test menanam produk rahasia dan memastikan tidak tampil. Query controller hanya membaca tenant serta kolom owner `tenant_id`, `name`, `username`. Browser menampilkan nama, owner/username, tanggal, status. |
| Provisioning | Sukses atomik, trim/lowercase, konfirmasi dan minimum 9 karakter, duplikat/invalid tanpa write, rollback/context pulih; command tetap bekerja | PASS. Focused suite mencakup action dan command. Browser lokal membuat `Sumber QA`; konfirmasi berbeda ditolak, jumlah tetap dua, dan field password tidak diisi ulang. |
| Status tenant | Nonaktif tidak menghapus row; login internal diblokir; tracking lama tetap publik | PASS. Browser mengubah ringkasan `2 aktif dari 2` menjadi `1 aktif dari 2`, row tetap ada, lalu dipulihkan. Focused tracking test terpisah PASS 1 test/43 assertion pada tenant nonaktif. |
| Secret/log | Password tidak tampil ulang atau masuk output/log | PASS. Error browser mengosongkan dua input password; log server hanya path/timing. Git hanya memuat nama env kosong dan secret sintetis di test, tidak ada credential server. |
| Scope minimum | Tidak ada schema/dependency/hapus tenant/billing/trial/impersonasi/analitik/deploy | PASS. Diff commit tidak mengubah migration, Composer/npm lock, atau dependency; pencarian fitur di source terkait kosong. Tidak ada produksi, deploy, merge, atau credential nyata yang disentuh. |

## Perintah dan hasil

- `php artisan test tests/Feature/Platform/PlatformOwnerTest.php tests/Feature/Tenancy/IdentityTest.php` — **PASS**, 18 test, 150 assertion.
- `php artisan test tests/Feature/Tenancy/ServiceIsolationTest.php --filter=test_documents_whatsapp_and_public_tracking_use_the_owner_store` — **PASS**, 1 test, 43 assertion.
- `php artisan route:list --path=platform -v` — enam route terdaftar; middleware sesuai tabel di atas.
- `git diff --check <parent> <revision>` — **PASS**.
- Audit nama file commit — tidak ada migration, dependency/lockfile, atau konfigurasi produksi.
- Full suite implementer pada source identik dipakai sebagai bukti pendukung S014: **PASS**, 228 test, 2.434 assertion, 1 skip existing. QA ini tidak mengulang suite penuh karena subset berisiko dan tracking telah dijalankan independen pada revision yang sama.

Semua perintah PHP memakai `/opt/homebrew/opt/php@8.3/bin/php`.

## UI responsif

Browser memakai SQLite sementara dan credential sintetis, bukan produksi. Login dan dashboard diamati independen; screenshot login exact-revision yang dapat diaudit tetap berada di `.taste-skill/s014-login/`.

| Viewport | Hasil |
|---|---|
| 1440×900 | Tidak ada overflow horizontal. Sidebar, daftar dan form membentuk framed sheet yang jelas; error konfirmasi terlihat di bawah field. |
| 767×1024 (observasi mendekati target 768×1024) | Tidak ada overflow. Sidebar melipat menjadi topbar; daftar menjadi row ringkas; input 48px dan aksi utama 44px. Artifact S014 pada exact revision melengkapi pemeriksaan 768×1024. |
| 390×844 | Tidak ada overflow. Tombol aksi row dan logout 44px, input 48px, form satu kolom, status serta identitas tetap terbaca. |

Label input terlihat, fokus login terlihat jelas, status aktif/nonaktif berbeda secara teks (bukan warna saja), owner kosong ditampilkan sebagai dash dan keterangan, serta error validasi dekat dengan field. Konsol browser: 0 error/warning. Arah visual tetap `framed-sheet`; layar langsung menjawab dua tugas utama: melihat toko aktif dan menambah client. Tidak ada chart, legenda, atau ornamen baru yang tidak dibutuhkan.

## Batas dan risiko

- **Dilewati:** visual empty-state tanpa tenant tidak dibuat di browser karena migrasi fixture selalu membuat tenant default; cabang `@empty` ditinjau statis. State owner kosong, error, aktif, dan nonaktif diperiksa langsung.
- **Dilewati:** MariaDB concurrency, Pint, PHPStan, build, dan full suite tidak diulang; source sama persis dengan S014 dan perubahan ini tidak menyentuh schema, numbering, dependency, atau asset build sesudah bukti tersebut.
- **Risiko tersisa:** konfigurasi credential production dan perilaku di reverse proxy belum diuji karena deployment/produksi di luar izin S016. Risiko produk dari pemeriksaan lokal dinilai rendah.
