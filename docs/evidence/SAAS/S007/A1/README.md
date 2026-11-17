# S007/A1 — QA independen SaaS

Verdict: **PASS** untuk kandidat S006 yang dipin di bawah. Tidak ditemukan kebocoran tenant atau regresi pada kasus wajib yang dijalankan. Satu tes historis dilewati secara eksplisit; tes tersebut **bukan PASS**. Bukti MySQL penggantinya dijalankan pada database baru milik S007.

- Target: `sha256:066bb9e82bd3ac5142a1ef516fc46c862c7e3b1b2cbc28ee4eb0a8ab402996f6`.
- Source diperiksa: `dec7804`; workspace `009f174` hanya berbeda pada status ledger `SAAS-TODO.md`. SHA lengkap ada pada `pin.json`.
- Manifest S006 berisi 246 file. Seluruh hash file, hash gabungan, dan enumerasi scope dari Git diperiksa sendiri sebelum/sesudah QA; cocok. Tidak ada source/test/config/ledger yang diubah tester.
- Input S003–S005 mengikuti manifest S006; kesimpulan implementer tidak dijadikan oracle pengujian.
- Peran: tester independen. Model diminta `gpt-6.1-sol`, effort `high`; model efektif **belum terverifikasi** karena receipt tidak tersedia.
- Ponytail **full** dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery, QA/release, kontrak worker, AGENTS, kontrak S007, dan rancangan SaaS juga dibaca.

## Pemeriksaan otomatis

PHP selalu `/opt/homebrew/opt/php@8.3/bin/php`. Perintah dijalankan dari `/Users/muzib/Projects/web/repair-app`.

| Perintah aktual | Expected | Actual / bukti |
|---|---|---|
| `php artisan test --compact` | Regresi lulus; baseline MySQL tidak disentuh | 217 tes: **216 lulus**, 2.368 assertion, **1 skipped**; `sqlite.log` |
| `php vendor/bin/pint --test` | Format kandidat valid | Lulus; `pint.log` |
| `php vendor/bin/phpstan analyse --memory-limit=512M` | Tidak ada error analisis | 0 error; `phpstan.log` |
| `npm run build` | Asset dapat dibangun | Lulus; `build.log` |

Skip bernama `Tests\Feature\Domain\MySqlConcurrencyTest::test_mysql_fresh_seed_and_parallel_document_creation`. Test ini hardcode `repair_app_test`, mensyaratkan kosong dan memanggil `migrate:fresh`; tidak diaktifkan agar fixture baseline existing aman. SQLite tidak diklaim membuktikan konkurensi MySQL.

## MySQL nyata, baru, dan terisolasi

Semua command di bawah memakai `QA_RUNTIME=/private/tmp/saas-s007-runtime-20261117` dan `php docs/evidence/SAAS/S007/A1/mysql.php <mode>`. Credential dibaca privat dari `.env`, tidak dicetak. Koneksi hanya host loopback dan database **`saas_s007_20261117_01`**, dibuat setelah memastikan nama belum ada. Tidak terhubung ke `repair_app` atau `repair_app_test`.

| Mode aktual | Expected / actual | Bukti |
|---|---|---|
| `setup` | Migrasi schema lama, isi fixture 11 tabel, terapkan SaaS; setiap field lama identik setelah backfill. Kolom tenant wajib, unique TTS/JL/kategori/setting dan primary counter sesuai kontrak. Rollback single tenant lalu apply ulang mempertahankan data. **Lulus** | `mysql-setup.log` |
| `proof` | 4 proses per tenant/prefix, tenant A/B bersamaan: TTS/JL masing-masing 001–004. Dua penjualan atas stok satu unit: 1 success/1 shortage per tenant. Payload sale campuran produk A/B ditolak tanpa perubahan stok/sale/counter. Rollback multi-tenant ditolak sebelum menghapus schema. **Lulus** | `mysql-proof.log` |
| `number-proof` | Uji row counter existing, waktu masuk dicatat sesudah barrier; overlap nyata semua pemanggilan. Masing-masing tenant/prefix memperoleh 005–008 unik. **Lulus** | `mysql-existing-counter.log` |
| `details` | Catat ID expense privat dan snapshot fixture tenant legacy untuk pemeriksaan akhir | `mysql-baseline.log` |
| `deactivate` | Nonaktifkan hanya tenant B sintetis untuk uji sesi/browser | Dieksekusi oleh `security-browser.mjs`; `security-browser.json` |
| `verify` | Fixture legacy 11 tabel identik; nama/akun/kategori/expense tetap, payload palsu tidak menghasilkan data. Stok produk utama A/B tepat 20, masing-masing 3 sale dan 2 TTS. Omzet akhir A 251.000, B 2.501.000. **Lulus** | `mysql-final.log` |

Matriks MySQL memakai action/model aplikasi asli, bukan simulasi SQL stok/nomor. Suite SQLite penuh di atas mencakup tes isolasi model, child relation, dokumen, WhatsApp, onboarding atomik, logo, login/remember, dan regresi existing.

## Browser dua tenant dan serangan payload

Command aktual: `node docs/evidence/SAAS/S007/A1/browser.mjs inspect`, kemudian `node docs/evidence/SAAS/S007/A1/browser.mjs`; lanjutan memakai hasil kasus yang sudah terbukti pada kandidat sama. Tambahan keamanan: `node docs/evidence/SAAS/S007/A1/security-browser.mjs`.

Playwright yang sudah terpasang dari tools mobile-mcp dan Chrome lokal digunakan tanpa dependency baru. Server PHP privat hanya pada `http://127.0.0.1:8877`, database S007, akun sintetis, sesi browser A/B terpisah. Total **34 kasus alur + 11 kasus keamanan lulus** pada `browser.json` dan `security-browser.json`.

- Login A/B, list TTS/stok/sale/expense/user/category, pilihan kategori/produk/teknisi, identitas setting, create/detail TTS, create product, restock, create/detail sale terpisah. Pemeriksaan akhir MySQL memastikan stok dan jumlah transaksi hasil browser benar.
- Dashboard/report sebelum sale browser: omzet A **151.000**, B **1.501.000**; report filter hari/jenis dan pilihan detail tidak membuka transaksi tenant asing. Angka berasal dari fixture berbeda faktor 10, ditambah sale race 1.000 tiap tenant.
- **14 URL foreign per tenant** (TTS/detail/edit/sukses/invoice/struk/PDF/WhatsApp, stock edit/restock, sale/detail/struk/PDF) semuanya **404**.
- Foreign action ID pengguna/kategori/stok dan expense edit/delete ditolak **404**. Foreign kategori/teknisi/produk pada payload Livewire menghasilkan error validasi; tidak ada mutasi asing.
- Mengubah ID/field terkunci TTS, product, invoice, sale, expense, user/category/mode, dan logo path ditolak **419**, sesuai `CannotUpdateLockedPropertyException` Livewire installed ketika `APP_DEBUG=false`.
- Replay snapshot **asli bertanda tangan** dari form B menggunakan cookie/CSRF sesi A: product edit, TTS edit, invoice save, dan sale delete semuanya **404**. Mengandalkan locked property saja tidak cukup; caller nyata memang membaca ulang record melalui scope tenant.
- Struk HTML menampilkan identitas owner; PDF own/private dan public valid `application/pdf` dengan header `%PDF`. Tracking guest maupun user tenant asing memakai identitas pemilik token, tanpa nama/alamat customer privat atau field modal. Token invalid **404**. Data public PDF juga dibuktikan aman oleh test `ServiceIsolationTest::test_documents_whatsapp_and_public_tracking_use_the_owner_store` dalam suite penuh: customer/phone null, item tanpa cost, identitas owner benar.
- Setelah B dinonaktifkan: mutasi Livewire **403**, sesi/remember existing **403**, login baru ditolak. Tracking/PDF historis B tetap **200** sesuai keputusan produk; tenant A tetap bekerja.

Screenshot: `dashboard-A/B.png`, `report-A/B.png`, `tts-A/B.png`, `tracking-owner-A/B.png`, `inactive-tenant.png`. Report B dan tracking A diperiksa visual: state nyata dan identitas sesuai tenant.

## Audit source keamanan dan batas scope

Pencarian aktual `rg` untuk seluruh bypass scope, query raw, direct child queries, locked IDs dan caller disimpan pada `query-audit.txt`. Seluruh route aplikasi serta model/action/komponen terkait dibaca.

| Permukaan | Kesimpulan audit yang dibuktikan |
|---|---|
| `BelongsToTenant`, context, middleware | Tanpa context query kosong dan create gagal; create mengambil tenant dari context. Middleware mengambil tenant dari user hasil autentikasi, menolak tenant nonaktif, dan membersihkan context pada akhir request. |
| Fortify provider + `EnsureActiveAccount` bypass | Hanya lookup username global unik atau user ID dari sesi/remember yang terautentikasi. Tidak ada endpoint list user global. Sesi user aktif diambil ulang; tenant ditetapkan sesudahnya. |
| Tracking/Document bypass | Lookup dibatasi token global 64 karakter; category/technician/store dibatasi eksplisit ke tenant order pemilik. Public query tidak mengambil customer privat; item public tidak memilih cost. |
| `DocumentNumber` raw | Insert/lock/update selalu memakai key tenant+prefix+period dalam transaksi. MySQL paralel membuktikan row baru dan existing. |
| Dashboard raw joins | Dua query ke child item mengikat `orders.tenant_id` / `sales.tenant_id`. Count Eloquent memakai scope. Fixture faktor 10 membuktikan total tidak bercampur. |
| Users raw lock | `User::min('id')` memakai scope tenant; raw update mengunci ID tersebut saja, lalu mutasi user dicari ulang melalui scope. Tidak mengubah data tenant lain. |
| Child items/restock/report | TTS/sale item diakses melalui parent ter-scope, invoice membatasi field baris dan tidak menerima child ID bebas. Restock parent di-lock dan divalidasi tenant; direct stock-entry report memakai `whereHas('product')`. Model sale-item/stock-entry memvalidasi parent/product/creator tenant. |
| Livewire/foreign IDs | ID utama locked; mount, render, save dan action tetap query scoped/active-account. Pengujian raw HTTP dan replay snapshot mencakup risiko stale ID. |
| Command/seeder | `tenant:create` internal interaktif dan atomik; password secret; seeder meminta context eksplisit. Tidak ada API/panel/registrasi/billing/domain/paket/queue baru. |

`scope-check.json`: sejak baseline `a03f6f1`, manifest/lock Composer dan npm **tidak berubah**; hanya **satu migrasi SaaS** baru. Schema tenant berisi id/name/is_active/timestamps dan delapan tenant_id sesuai kontrak. Tidak ditemukan perluasan spekulatif.

## Kendala harness dan keadaan akhir

Kendala awal bersifat environment/harness, bukan defect source: TCP localhost sandbox perlu escalation, router PHP perlu cwd `public/` dan proses tetap hidup, CSRF Livewire berada pada `data-csrf`, label uang mencakup prefix Rp, satu variabel harness melewati scope, serta pembaca reactive state diganti menunggu DOM nyata. Ekspektasi locked-property awal 500 dikoreksi menjadi 419 setelah membaca exception installed. Riwayat gagal harness dipertahankan pada `browser-run.log`, `browser-error.txt`, `harness-errors.txt`; lanjutan hanya melengkapi kasus belum terbukti. Tidak ada suite penuh/build ulang setelah source yang sama lulus.

Server dihentikan, port 8877 tertutup, semua context Chrome ditutup, direktori runtime privat beserta key/password/token sintetis dihapus. Database **`saas_s007_20261117_01` dipertahankan** untuk inspeksi, hanya data sintetis QA; tenant B tetap nonaktif. `cleanup.json` merekam tindakan aktual dan pemeriksaan bahwa text evidence tidak memuat password DB, APP_KEY, password sintetis, atau token tracking. Tidak ada commit, push, atau deploy oleh tester.

Tidak diuji: produksi, restore backup produksi, maintenance window, perangkat HP/printer fisik, dan render isi teks PDF secara terpisah dari test data/sumber; semua berada di gate berikutnya atau di luar scope S007. Hasil ini menerima keamanan/regresi kandidat software lokal, bukan izin migrasi/deployment produksi.
