# S003/A1 — Identitas dan onboarding tenant

Implementasi dan self-check PASS; penerimaan software tetap menunggu QA independen S007. Tidak ada commit/push/deploy oleh worker.

- Task/role: S003, developer, attempt A1.
- Root/branch: `/Users/muzib/Projects/web/repair-app`, `codex/saas-multitenant`.
- Base: `4454e0c`; input S002: `sha256:b2f96a6201d13e31997abab578edc0917f899f2b2c54657617c607907637626c`.
- Model diminta: `gpt-6.1-sol`, effort `high`; model efektif: `null` (receipt backend tidak tersedia).
- Ponytail full — dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery dan kontrak worker pada `references/coordination.md` juga dibaca.
- Scope tambahan disetujui coordinator pada assignment yang sama: `Category.php`, `EnsureActiveAccount.php`, serta empat view shell/login/users. Worker tidak menulis ledger pusat.
- Pin source candidate: lihat `source-manifest.json` dan `candidate.sha256`; ledger coordinator tidak dimasukkan ke hash worker.

Perubahan minimum:

- `User`, `Category`, dan `StoreSetting` memakai trait S002 `BelongsToTenant`: query fail-closed dan create mengambil tenant dari context, mengabaikan `tenant_id` kiriman. Username tetap unik global; kategori unik per tenant.
- Fortify mencari satu username global, memeriksa password, status user dan tenant, lalu menetapkan context dari tenant user terverifikasi. Provider session/remember dan middleware akun melewati scope hanya untuk pemulihan identitas auth; fitur pengelolaan user tetap scoped, termasuk perlindungan admin terakhir.
- StoreSetting tidak lagi singleton `id=1`; layar memakai satu baris tenant aktif. Logo baru berada di `logos/{tenant_id}/`. Validasi raster, ukuran, path terkunci, dan pembersihan upload ketika transaksi gagal dipertahankan.
- `tenant:create` ditemukan otomatis oleh Artisan (tidak perlu perubahan bootstrap/provider registration). Command hanya interaktif; tidak menerima argumen password. Password memakai `secret(..., false)` agar tidak fallback ke input terlihat. Validasi minimal 9 karakter tetap. Tenant/store kosong/owner admin dibuat dalam satu transaksi, error tidak mencetak isi exception, dan context sebelumnya dipulihkan.
- Command tidak menjalankan seeder/preset; owner mengisi kategori dan teknisi di layar existing. Seeder legacy hanya dapat berjalan bila pemanggil menetapkan context tenant secara eksplisit. Factory user menyediakan context fixture default tenant 1 bila kosong, atau tenant yang ditentukan eksplisit oleh fixture.
- Login generik sebelum tenant diketahui. Shell/title/users membaca nama StoreSetting tenant aktif; jika masih kosong, memakai nama Tenant. Tidak ada perubahan tata letak atau CSS.

## Pemeriksaan aktual

Semua PHP memakai `/opt/homebrew/opt/php@8.3/bin/php` (PHP 8.3), bukan PHP global 7.4.

| Perintah aktual | Expected | Actual/bukti |
|---|---|---|
| `php artisan test tests/Feature/Tenancy/IdentityTest.php tests/Feature/Tenancy/FoundationTest.php tests/Feature/Auth tests/Feature/Users tests/Feature/Settings --compact` | Auth dua tenant, session/remember, tenant nonaktif, username global, isolasi user/settings/category, onboarding/rollback/password, dan regresi lulus | PASS, 66 tes/614 assertion, 3504 ms; `tests.json` |
| `php vendor/bin/phpstan analyse app/Providers/FortifyServiceProvider.php app/Http/Middleware/EnsureActiveAccount.php app/Models/User.php app/Models/StoreSetting.php app/Models/Category.php app/Livewire/Users app/Livewire/Settings app/Console/Commands/CreateTenant.php database/factories database/seeders --memory-limit=512M --debug` | Tidak ada error analisis tipe pada scope PHP | PASS, 0 error; `phpstan.json` |
| `php vendor/bin/pint --test app/Providers/FortifyServiceProvider.php app/Http/Middleware/EnsureActiveAccount.php app/Models/User.php app/Models/StoreSetting.php app/Models/Category.php app/Livewire/Users app/Livewire/Settings app/Console/Commands/CreateTenant.php database/factories database/seeders tests/Feature/Auth tests/Feature/Users tests/Feature/Settings tests/Feature/Tenancy/IdentityTest.php` | Format kode sesuai proyek | PASS; `pint.json` |
| `python3 /tmp/s003-terminal-check.py` | Terminal PTY nyata menyembunyikan password dan provisioning berhasil | PASS, exit 0; password acak tidak ada dalam transcript; owner/store benar dan kategori kosong. Script identik dipertahankan di `terminal-check.py`, hasil `terminal-check.txt` |
| `git diff --check` | Tidak ada masalah whitespace | PASS |

`php` pada tabel adalah singkatan path PHP 8.3 di atas. Tes memakai SQLite in-memory; tes konkurensi admin menggunakan SQLite sementara miliknya. Uji terminal memigrasikan SQLite baru di direktori sementara dan membersihkannya setelah pemeriksaan. Tidak mengakses MySQL, `repair_app`, `repair_app_test`, server, atau database produksi.

Cakupan bukti spesifik: login global dua tenant dari context kosong, guard/session baru, cookie remember asli, tenant dinonaktifkan pada session berjalan dan login ulang, foreign user ID pada edit/reset/toggle menghasilkan 404, foreign admin tidak memenuhi batas admin lokal terakhir, username tenant lain ditolak, create model dengan forged tenant_id tetap tenant aktif, query tanpa context kosong, nama kategori sama boleh lintas tenant tetapi ditolak dalam tenant sama, kategori foreign tidak terlihat/diubah, store/logo tenant berbeda, branding tenant kedua tidak memuat Anugerah, provisioning sukses serta exception setelah store dibuat mengembalikan seluruh transaksi, context sebelumnya dipulihkan, username duplicate/password 8 karakter/no-interaction ditolak, dan password tidak dicetak pada output sukses/gagal maupun terminal nyata.

Riwayat self-check A1 dipertahankan:

1. Regresi awal: 52 tes, 44 lulus; 3 gagal karena fixture perubahan akun sesudah HTTP memakai query scoped saat context telah dibersihkan, 4 error fixture konkurensi belum mengisi tenant_id, 1 error Settings membuat historical order melalui DocumentNumber milik S004. Fixture disesuaikan dalam scope S003: perubahan akun auth-ID eksplisit, konkurensi tenant 1, historical order dibuat langsung untuk menguji relasi kategori tanpa menjalankan alur penomoran S004.
2. Tes identitas baru pertama: 61 tes, 60 lulus; login dengan context kosong menemukan 403 pada LoginResponse. Fortify kini menetapkan tenant terverifikasi sebelum respons login.
3. Pemeriksaan berikutnya: 65 tes, 64 lulus; negative assertion `Toko A` cocok dengan teks statis `Toko Anugerah`, bukan kebocoran data. Assertion diperjelas memakai username. PHPStan menemukan mixed-return factory lookup; menggunakan properti tenant_id bertipe integer yang sudah didefinisikan memperbaiki penyebab.
4. Kandidat sebelum perluasan branding: 65/65 tes, 607 assertion, PHPStan/Pint PASS. Setelah scope empat view disetujui dan diubah, verifikasi relevan dijalankan lagi: 66/66 dan 614 assertion PASS. Bukan reset attempt atau klaim QA independen.

Tidak diuji: full suite lintas modul S004/S005, browser/visual QA, MySQL, deploy, perangkat fisik. Seeder standalone kini sengaja menolak context kosong; pemanggil seed/command legacy di luar S003 perlu diberi tenant eksplisit saat integrasi. PWA manifest/install metadata existing masih bernama Anugerah dan di luar empat view yang diizinkan. Risiko software gabungan tetap dinilai pada S006/S007.
