# Rancangan Lean SaaS Multi-Toko

Status: **Draft untuk direview**  
Branch: `codex/saas-multitenant`  
Tanggal: 16 November 2026  
Dasar: aplikasi produksi Anugerah pada commit `a03f6f1`

## 1. Tujuan

Satu aplikasi dapat dipakai beberapa toko servis tanpa membuat repo, database, atau deployment baru untuk setiap client.

Target pertama hanya:

- mempertahankan semua fitur Anugerah;
- menambahkan toko servis HP/komputer/elektronik lain;
- memastikan data satu toko tidak dapat dibaca atau diubah toko lain;
- membuat client baru dengan proses operasional sederhana.

Jenis usaha **tidak membuat versi aplikasi berbeda**. Toko HP dan toko komputer memakai alur servis yang sama. Perbedaannya cukup melalui nama toko, logo, kategori barang, pengguna, dan pengaturan yang sudah ada.

## 2. Aturan anti-over-engineering

Aturan ini berlaku untuk seluruh implementasi SaaS:

1. Fitur dibuat hanya bila diperlukan untuk isolasi tenant, migrasi Anugerah, atau dipakai client nyata.
2. Jangan membuat konfigurasi generik hanya karena mungkin dibutuhkan nanti.
3. Bila kebutuhan dua jenis toko dapat diselesaikan oleh kategori yang sudah ada, jangan membuat modul atau workflow baru.
4. Gunakan komponen, model, dan pola Laravel yang sudah ada sebelum menambah abstraksi atau dependency.
5. Jangan memasang package multi-tenancy; kebutuhan awal cukup dengan `tenant_id`, scope, middleware, dan test isolasi kecil yang dapat dibaca tim.
6. Jangan membuat API, microservice, queue, event bus, feature flag, atau plugin system untuk pekerjaan yang masih dapat diselesaikan monolith sekarang.
7. Satu perubahan harus menyelesaikan kebutuhan sekarang dan mempunyai acceptance test. Tidak ada kode “untuk nanti”.
8. Backlog bukan izin implementasi. Fitur backlog baru dikerjakan setelah ada kebutuhan client dan keputusan user.

Pertanyaan wajib sebelum menambah fitur:

- Client mana yang membutuhkan ini sekarang?
- Apakah fitur existing atau pengaturan toko sudah cukup?
- Apa yang benar-benar gagal bila fitur ini tidak dibuat?
- Bisakah diselesaikan dengan perubahan yang lebih kecil?

## 3. Scope minimum v1 SaaS

### Wajib dibuat

- tabel tenant/toko;
- `tenant_id` pada data utama milik toko;
- pembatasan otomatis seluruh query berdasarkan tenant pengguna yang login;
- nomor TTS/JL, kategori, pengguna, stok, laporan, dan pengaturan yang independen per tenant;
- command internal untuk membuat tenant dan owner pertama;
- migrasi semua data Anugerah menjadi tenant pertama;
- test kebocoran data antartoko;
- backup dan rollback sebelum migrasi produksi.

### Tetap memakai fitur existing

- login username/password;
- peran Admin dan Teknisi;
- form dan status TTS;
- struk/PDF/tracking;
- stok, penjualan, pengeluaran, dashboard, dan laporan;
- identitas toko, logo, garansi, kategori;
- deployment dan CI/CD yang sudah berjalan.

### Sengaja tidak dibuat pada v1 SaaS

- panel admin platform;
- registrasi client secara publik;
- paket Starter/Growth dan pembatasan fitur;
- pembayaran atau invoice langganan otomatis;
- trial otomatis;
- subdomain atau custom domain per client;
- satu user menjadi anggota banyak toko;
- impersonasi/login sebagai client;
- custom field dan custom workflow;
- plugin per jenis usaha;
- tema/white-label selain pengaturan toko existing;
- aplikasi mobile native/offline;
- database terpisah per tenant;
- analitik lintas semua tenant;
- sistem notifikasi atau queue baru.

Billing dan kontrak client dicatat di luar aplikasi dahulu. Bila jumlah client membuat pekerjaan manual tidak praktis, barulah panel platform atau billing dipertimbangkan.

## 4. Arsitektur minimum

| Bagian | Rancangan lean |
|---|---|
| Codebase | Satu repo Laravel/Livewire yang sekarang |
| Deployment | Satu aplikasi dan satu pipeline CI/CD |
| Database | Satu MariaDB/MySQL bersama |
| Identitas tenant | `tenant_id` milik user yang sudah login |
| Login | Tetap satu halaman login; username tetap unik global untuk menghindari perubahan UX dan DNS |
| Data toko | Query otomatis dibatasi ke `tenant_id` aktif |
| Tracking publik | Tetap memakai token global yang sulit ditebak; tenant diambil dari TTS pemilik token |
| File logo | Path memasukkan ID tenant agar file tidak bertabrakan |
| Onboarding | Command Artisan internal, dijalankan operator |

Konsekuensi username global: akun client baru memakai nama unik seperti `admin.sumberhp`. Ini lebih sederhana daripada menambah kode toko pada login atau wildcard subdomain. Bila nanti user menilai pengalaman ini buruk, login berbasis kode toko/subdomain dapat dirancang sebagai perubahan terpisah.

Login adalah satu-satunya query user yang boleh berjalan sebelum tenant diketahui: Fortify mencari username unik global, lalu middleware mengambil tenant dari user yang berhasil login. Setelah itu, daftar pengguna dan seluruh data bisnis wajib memakai scope tenant.

## 5. Model data minimum

### 5.1 Tabel baru `tenants`

Kolom yang benar-benar diperlukan:

- `id`;
- `name`;
- `is_active`, default aktif;
- timestamps.

Tidak perlu `slug`, jenis usaha, paket, trial, subscription, currency, atau feature flags pada tahap ini. Nama, alamat, telepon, logo, dan pengaturan garansi tetap berada di `store_settings`.

### 5.2 Tabel utama yang mendapat `tenant_id`

| Tabel | Perubahan |
|---|---|
| `users` | Tambah `tenant_id`; username tetap unik global |
| `categories` | Tambah `tenant_id`; nama unik menjadi `(tenant_id, name)` |
| `number_sequences` | Tambah `tenant_id`; kunci menjadi `(tenant_id, prefix, period)` |
| `service_orders` | Tambah `tenant_id`; nomor unik menjadi `(tenant_id, number)`; token tetap unik global |
| `products` | Tambah `tenant_id` |
| `sales` | Tambah `tenant_id`; nomor unik menjadi `(tenant_id, number)` |
| `expenses` | Tambah `tenant_id` |
| `store_settings` | Satu baris per `tenant_id`, bukan singleton `id=1` |

Tabel anak tidak perlu menduplikasi `tenant_id` bila selalu diakses melalui parent yang sudah di-scope:

- `service_order_items` melalui `service_orders`;
- `sale_items` melalui `sales`;
- `stock_entries` melalui `products`.

Jika audit implementasi menemukan query langsung yang membuat pola ini tidak aman atau rumit, lebih baik menambah `tenant_id` pada tabel terkait daripada membuat query abstrak yang sulit dipahami. Keputusan dibuat berdasarkan caller aktual, bukan antisipasi.

## 6. Fondasi kode minimum

Hanya tiga bagian bersama yang diperlukan:

1. **`TenantContext`** — menyimpan tenant user yang login selama request.
2. **`BelongsToTenant`** — scope model dan mengisi `tenant_id` saat record dibuat.
3. **Middleware tenant** — memastikan user aktif, tenant tersedia, dan tenant masih aktif.

Aturan keamanan:

- browser/form tidak pernah mengirim atau menentukan `tenant_id`;
- `tenant_id` berasal dari user yang login;
- autentikasi hanya boleh mencari satu user dari username unik global, bukan menampilkan daftar atau data user lintas tenant;
- query record tenant lain menghasilkan 404;
- relasi teknisi, kategori, produk, dan transaksi harus berasal dari tenant yang sama;
- command CLI yang menyentuh data bisnis wajib menerima tenant secara eksplisit;
- tracking publik hanya membuka data aman yang sudah diizinkan sekarang.

Tidak perlu repository layer, service locator, domain event, atau base controller baru. Model/action existing tetap dipakai dan hanya caller yang benar-benar terkait tenant yang diperbaiki.

## 7. Caller yang wajib diaudit

Perubahan tenant harus menjangkau:

- autentikasi dan validasi username;
- daftar/pembuatan pengguna dan teknisi;
- kategori dan pilihan kategori TTS;
- TTS, status, pencarian, dan tracking;
- `DocumentNumber` untuk TTS/JL;
- dashboard dan pengingat garansi;
- stok, restok, penjualan, dan pengeluaran;
- laporan;
- `StoreSetting::find(1)` dan header dokumen;
- PDF, struk, WhatsApp, dan logo;
- seeder serta command operasional.

Menambahkan kolom `tenant_id` tanpa mengaudit caller di atas tidak dianggap selesai.

## 8. Toko HP tanpa fitur baru

Toko baru dibuat dengan pengaturan toko existing, lalu admin mengisi kategori sesuai kebutuhannya.

Contoh kategori:

| Toko | Kategori |
|---|---|
| Servis HP | Android, iPhone, Tablet, Smartwatch |
| Servis komputer | Laptop, PC, Monitor, Printer |
| Elektronik umum | TV, Mesin cuci, AC, Kulkas |

Tidak perlu kolom `business_type` atau preset otomatis pada v1. Memasukkan beberapa kategori saat onboarding lebih murah dan lebih mudah diubah daripada memelihara sistem preset.

## 9. Onboarding client baru

Gunakan satu command internal, misalnya:

```text
php artisan tenant:create
```

Command meminta secara interaktif:

- nama toko;
- nama owner;
- username unik;
- password awal tanpa menampilkannya di terminal.

Dalam satu transaksi, command membuat:

- tenant;
- store setting kosong/default;
- akun admin owner.

Setelah login, owner sendiri mengisi identitas toko, kategori, dan akun teknisi melalui layar yang sudah ada. Command tidak perlu membuat paket, domain, preset, checklist onboarding, atau email undangan.

## 10. Migrasi Anugerah

Aplikasi sudah dipakai client, sehingga migrasi wajib aman dan dapat dibatalkan.

Urutan minimum:

1. Backup database dan folder upload, salin ke luar VPS, lalu buktikan restore pada database terpisah.
2. Catat jumlah baris dan total penting sebagai baseline.
3. Buat tenant Anugerah.
4. Tambah `tenant_id` nullable pada tabel utama.
5. Backfill seluruh data existing ke tenant Anugerah.
6. Verifikasi jumlah baris, relasi, nomor dokumen, stok, dan laporan tidak berubah.
7. Aktifkan scope tenant, unique/index baru, lalu jadikan `tenant_id` wajib.
8. Smoke test login, TTS, tracking, PDF, stok, penjualan, dan laporan.
9. Bila verifikasi gagal, rollback release dan database menggunakan prosedur yang telah diuji.

Tidak boleh memakai `migrate:fresh`, reset database, atau eksperimen langsung pada produksi. Pengecualian backup saat masih demo tidak berlaku lagi karena aplikasi sudah dipakai client.

## 11. Pengujian wajib

Gunakan dua tenant sintetis. Test minimum:

- list, detail, edit, dan action tidak dapat membaca ID tenant lain;
- payload manual tidak dapat memakai teknisi, kategori, atau produk tenant lain;
- dashboard dan laporan hanya menghitung tenant aktif;
- nomor TTS/JL independen per tenant dan tetap aman saat konkurensi;
- kategori bernama sama boleh ada di tenant berbeda;
- dokumen, WhatsApp, tracking, dan logo memakai identitas tenant pemilik;
- tenant nonaktif tidak dapat login atau mengubah data;
- migrasi fixture Anugerah mempertahankan semua baris dan total;
- seluruh regresi fitur existing tetap lulus.

Tidak perlu membuat framework test tenant sendiri. Gunakan factory/helper kecil bila duplikasi setup test mulai mengganggu pembacaan.

## 12. Tahapan implementasi kecil

| Tahap | Isi | Gate |
|---|---|---|
| L1 — Schema dan scope | `tenants`, `tenant_id`, context, trait, middleware | Test isolasi model PASS |
| L2 — Audit fitur existing | Perbaiki caller di §7 dan test dua tenant | Full regression + QA security PASS |
| L3 — Migrasi Anugerah | Backup/restore, backfill, constraint, smoke test | Data dan total identik |
| L4 — Client kedua | Command onboarding dan pilot satu toko HP | Tidak ada kebocoran; alur harian berhasil |

Jangan mengerjakan L4 sebelum L1–L3 lulus. Setelah pilot client kedua, evaluasi kebutuhan nyata sebelum menambah fitur SaaS lain.

## 13. Kriteria selesai v1 SaaS

- Client kedua dapat dibuat tanpa repo, database, atau deployment baru.
- Anugerah tetap bekerja dengan data lama yang utuh.
- Setiap user hanya melihat data tenant-nya.
- Seluruh modul existing lulus test isolasi dan regresi.
- Nomor dokumen, kategori, pengguna, stok, dan laporan independen per tenant.
- Toko HP berjalan cukup dengan pengaturan dan kategori existing.
- Backup/restore migrasi sudah dibuktikan.
- Tidak ada panel, billing, domain, paket, atau sistem konfigurasi baru yang belum dipakai client.

## 14. Keputusan yang masih diperlukan

Hanya dua keputusan yang menghambat implementasi:

1. Username boleh dibuat unik global seperti `admin.sumberhp` agar login tetap sederhana. Rekomendasi: **ya untuk v1 SaaS**.
2. Tenant nonaktif diblokir login sepenuhnya, sementara tracking customer lama tetap dapat dibuka. Rekomendasi: **ya**, agar customer tidak kehilangan akses status/nota.

Setelah dua keputusan tersebut disetujui, rancangan dapat dinaikkan menjadi PRD SaaS dan dibuatkan `SAAS-TODO.md`. `PRD.md` dan `TODO.md` existing tetap menjadi sumber produk produksi sampai migrasi SaaS diterima.
