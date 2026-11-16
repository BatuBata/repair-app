# Rancangan SaaS Multi-Toko Repair App

Status: **Draft untuk direview**  
Branch: `codex/saas-multitenant`  
Tanggal: 16 November 2026  
Dasar: aplikasi produksi Anugerah pada commit `a03f6f1`

## 1. Tujuan

Mengubah aplikasi satu toko menjadi layanan SaaS yang dapat dipakai banyak usaha servis tanpa membuat repo, deployment, atau database baru untuk setiap client.

Target awal:

- toko servis komputer/laptop;
- toko servis HP/tablet;
- toko servis elektronik umum;
- usaha jasa servis campuran.

Setiap toko memiliki pengguna, kategori, nomor dokumen, transaksi, laporan, logo, pengaturan, dan data customer sendiri. Data satu toko tidak boleh terlihat atau dapat diubah oleh toko lain.

## 2. Keputusan rancangan yang direkomendasikan

| Topik | Rekomendasi awal | Alasan |
|---|---|---|
| Model aplikasi | Satu codebase dan satu deployment | Perbaikan dan fitur cukup dirilis sekali untuk semua client |
| Model data | Shared database dengan `tenant_id` pada seluruh data milik toko | Paling sederhana dan hemat untuk tahap awal; tetap dapat dipisah nanti bila skala menuntut |
| Istilah tenant | Tenant = satu toko/usaha | Mudah dipahami dan cocok dengan struktur aplikasi sekarang |
| URL toko | Subdomain `{slug}.domain-saas` | Tenant diketahui sebelum login; username seperti `admin` boleh dipakai di toko berbeda |
| Client pertama | Data Anugerah dimigrasikan menjadi tenant pertama | Tidak membuat ulang data dan tidak mengubah alur kerja client aktif |
| Jenis usaha | Preset onboarding, bukan cabang kode | Toko HP dan komputer memakai alur servis yang sama; yang berbeda terutama kategori dan identitas |
| Registrasi | Dibuat manual oleh pengelola platform pada tahap awal | Menghindari pembayaran, spam, dan provisioning otomatis sebelum operasional stabil |
| Pembayaran SaaS | Pencatatan paket/status manual dahulu | Payment gateway bukan syarat untuk membuktikan multi-tenant aman |
| Pengguna | Satu akun hanya menjadi anggota satu tenant pada v1 SaaS | Menghindari kompleksitas pindah toko dan membership yang belum dibutuhkan |
| Admin platform | Guard dan tabel akun terpisah dari pengguna toko | Mengurangi risiko akun platform ikut terbawa query tenant |

## 3. Yang tetap sama

Fitur inti yang sudah dipakai Anugerah dipertahankan:

- alur TTS Diterima → Dikerjakan → Selesai → Diambil;
- teknisi dan admin toko;
- dokumen struk 58mm dan PDF A5;
- tracking customer melalui token;
- stok, penjualan, pengeluaran, dashboard, dan laporan;
- WhatsApp manual melalui `wa.me`;
- tampilan mobile-first.

SaaS tidak membuat versi kode khusus “toko HP” dan “toko komputer”. Saat onboarding, jenis usaha hanya mengisi kategori awal yang berbeda.

Contoh preset:

| Jenis usaha | Kategori awal |
|---|---|
| Servis HP | HP Android, iPhone, Tablet, Smartwatch, Aksesori |
| Servis komputer | Laptop, PC, Monitor, Printer, UPS |
| Elektronik umum | TV, Mesin cuci, AC, Kulkas, Elektronik kecil |
| Campuran | Tidak mengisi preset; owner memilih sendiri |

Kategori tetap dapat ditambah, diubah, atau dinonaktifkan oleh admin toko.

## 4. Batas v1 SaaS

Masuk v1 SaaS:

- isolasi data tenant;
- subdomain dan login per toko;
- onboarding tenant oleh admin platform;
- jenis usaha dan preset kategori;
- branding/pengaturan per toko;
- status tenant dan paket manual;
- migrasi Anugerah tanpa kehilangan data;
- backup, restore, audit keamanan tenant, dan observabilitas dasar.

Belum masuk:

- registrasi publik dan trial otomatis;
- payment gateway dan invoice langganan otomatis;
- satu akun mengelola banyak toko;
- custom domain client;
- custom field atau workflow berbeda per toko;
- plugin per jenis usaha;
- aplikasi mobile native/offline;
- database terpisah per tenant;
- white-label penuh selain nama, logo, alamat, telepon, dan syarat garansi.

## 5. Arsitektur target

| Lapisan | Rancangan |
|---|---|
| Browser | Membuka `{slug}.domain-saas`; tracking customer tetap memakai link bertoken |
| Resolver tenant | Middleware membaca subdomain, memastikan tenant aktif, lalu memasang `TenantContext` untuk request tersebut |
| Autentikasi toko | Login mencari username hanya di tenant aktif; unik per tenant, bukan global |
| Aplikasi | Laravel/Livewire yang sama; seluruh model milik toko memakai scope tenant |
| Admin platform | Area `/platform` dengan guard dan tabel akun terpisah; tidak memakai scope tenant toko |
| Database | Satu MariaDB/MySQL; semua data toko memiliki `tenant_id` dan indeks yang sesuai |
| File | Logo/dokumen upload disimpan di folder `tenants/{tenant_uuid}/...` |
| Deployment | Satu pipeline CI/CD dan satu release untuk seluruh tenant |
| Backup | Backup database dan file lengkap, terenkripsi, disalin ke lokasi di luar VPS, serta diuji restore |

### 5.1 Resolusi tenant

Alur request internal:

1. Host `anugerah.domain-saas` menghasilkan slug `anugerah`.
2. Middleware mencari tenant aktif berdasarkan slug.
3. `TenantContext` menyimpan tenant selama request.
4. Login dan semua query bisnis otomatis dibatasi oleh `tenant_id` tersebut.
5. ID milik tenant lain menghasilkan 404, bukan 403, agar keberadaan datanya tidak bocor.

Domain produksi Anugerah yang sekarang dapat tetap menjadi alias tenant Anugerah agar shortcut dan link lama tidak langsung rusak.

### 5.2 Tracking customer

`tracking_token` tetap unik global dan sulit ditebak. Controller publik menemukan TTS dari token, lalu mengambil identitas toko melalui tenant milik TTS tersebut. Halaman tidak membuka nomor HP, alamat, modal, atau data tenant lain.

## 6. Perubahan model data

### 6.1 Tabel baru

#### `tenants`

Kolom minimum:

- `id`;
- `uuid` publik untuk path file dan integrasi;
- `name`;
- `slug` unik global;
- `business_type`: `phone`, `computer`, `electronics`, atau `general`;
- `timezone`, default `Asia/Makassar` untuk Anugerah;
- `currency`, default `IDR`;
- `status`: `trial`, `active`, `suspended`, `cancelled`;
- `plan_code`;
- `trial_ends_at` dan `subscription_ends_at` opsional;
- timestamps.

#### `platform_users`

Akun pengelola SaaS, terpisah dari admin dan teknisi toko. Password di-hash dan area platform memakai rate limit serta autentikasi terpisah.

### 6.2 Tabel yang menjadi tenant-aware

| Tabel | Perubahan utama |
|---|---|
| `users` | Tambah `tenant_id`; username unik menjadi `(tenant_id, username)` |
| `categories` | Tambah `tenant_id`; nama unik menjadi `(tenant_id, name)` |
| `number_sequences` | Tambah `tenant_id`; primary key menjadi `(tenant_id, prefix, period)` |
| `service_orders` | Tambah `tenant_id`; nomor unik menjadi `(tenant_id, number)`; token tetap unik global |
| `service_order_items` | Tambah `tenant_id` untuk pertahanan berlapis dan audit isolasi |
| `products` | Tambah `tenant_id`; stok hanya milik tenant tersebut |
| `stock_entries` | Tambah `tenant_id` |
| `sales` | Tambah `tenant_id`; nomor unik menjadi `(tenant_id, number)` |
| `sale_items` | Tambah `tenant_id` |
| `expenses` | Tambah `tenant_id` |
| `store_settings` | Ganti singleton `id=1` menjadi satu baris per `tenant_id` |

`sessions`, passkeys, dan token reset tetap terhubung ke pengguna; pemisahan tenant berasal dari user yang sudah memiliki `tenant_id`.

### 6.3 Aturan relasi

- Setiap relasi antardata bisnis harus memiliki tenant yang sama.
- Request tidak boleh menerima `tenant_id` dari form/browser.
- `tenant_id` selalu diambil dari `TenantContext` atau parent record yang sudah lolos scope.
- Foreign key biasa tetap dipakai; service dan test wajib menolak hubungan silang tenant.
- Model top-level dan child memakai trait tenant yang sama agar raw ID tenant lain tidak dapat dibuka.

## 7. Perubahan kode yang diperlukan

### Fondasi tenant

- model `Tenant` dan `TenantContext` request-scoped;
- middleware resolver host/subdomain;
- trait `BelongsToTenant` untuk global scope dan pengisian `tenant_id` saat create;
- policy/guard yang menghasilkan 404 untuk record tenant lain;
- helper URL yang selalu memakai domain tenant yang benar.

### Query yang wajib diperbaiki

Saat ini banyak query mengasumsikan satu toko, antara lain:

- dashboard, laporan, garansi, dan pencarian TTS;
- pemilihan teknisi, kategori, produk, dan penjualan;
- nomor dokumen di `DocumentNumber`;
- `StoreSetting::find(1)` dan seluruh header dokumen;
- validasi unik username dan kategori;
- PDF, struk, tracking, WhatsApp, dan upload logo;
- seeder admin awal.

Perubahan harus dilakukan di fondasi bersama, lalu setiap caller diaudit. Menambahkan `tenant_id` tanpa memperbaiki query dan validasi unik tidak dianggap selesai.

## 8. Onboarding client baru

Alur v1 SaaS yang direkomendasikan:

1. Admin platform memilih **Tambah toko**.
2. Isi nama toko, slug, jenis usaha, zona waktu, paket, dan masa aktif.
3. Isi nama owner, username, dan password awal.
4. Sistem membuat tenant, store setting, owner admin, number sequence kosong, serta kategori preset dalam satu transaksi.
5. Sistem menampilkan URL login tenant dan checklist pengaturan awal.
6. Owner mengganti password, melengkapi logo/alamat/telepon, memeriksa kategori, lalu membuat teknisi.

Tidak ada repo, database, `.env`, atau deployment baru untuk client tersebut.

## 9. Paket dan status langganan

Untuk tahap awal, paket cukup berupa konfigurasi internal:

| Paket contoh | Batas awal |
|---|---|
| Starter | 1 admin, maksimal 3 teknisi, fitur inti |
| Growth | Lebih banyak pengguna dan laporan lengkap |

Batas final dan harga belum diputuskan. Enforcement yang aman:

- tenant `active` dapat menggunakan aplikasi;
- tenant `suspended` tidak dapat membuat/mengubah data;
- data tidak langsung dihapus;
- platform admin dapat mengaktifkan kembali;
- tracking customer lama tetap tersedia untuk periode yang akan diputuskan.

Payment gateway ditambahkan setelah dua atau tiga tenant nyata membuktikan alur produk dan paketnya.

## 10. Keamanan dan isolasi tenant

Kriteria wajib sebelum client kedua masuk:

- ID record tenant lain selalu 404 pada seluruh rute dan Livewire action;
- pencarian, dashboard, laporan, selectbox, export, PDF, dan struk tidak pernah mencampur tenant;
- username dan nomor dokumen boleh sama di tenant berbeda, tetapi tidak dalam tenant yang sama;
- teknisi hanya dapat dipilih dari tenant yang sama;
- kategori/produk milik tenant lain ditolak walaupun ID dikirim manual;
- upload logo tidak dapat menimpa atau dibaca melalui path tenant lain;
- log aplikasi memuat `tenant_id` dan request ID, tetapi tidak memuat password/token;
- command, scheduler, queue/job, dan seeder wajib menerima tenant secara eksplisit;
- cache key dan rate-limit key memasukkan tenant/host bila menyimpan data tenant;
- akun platform tidak otomatis mendapat akses isi transaksi toko; akses dukungan harus eksplisit dan tercatat bila kelak dibutuhkan.

## 11. Migrasi Anugerah tanpa kehilangan data

Karena aplikasi sudah dipakai client, migrasi tidak boleh memakai `migrate:fresh`, reset database, atau backfill tanpa backup.

Urutan aman:

1. Buat backup database dan folder upload produksi, salin keluar VPS, lalu uji restore pada database terpisah.
2. Catat jumlah dan checksum baris setiap tabel penting.
3. Buat tenant Anugerah dan kolom `tenant_id` yang masih nullable.
4. Backfill seluruh data lama ke tenant Anugerah dalam transaksi/batch terkontrol.
5. Verifikasi jumlah, relasi, total laporan, nomor dokumen, user, dan file sebelum/sesudah identik.
6. Tambah indeks/unique composite dan ubah `tenant_id` menjadi wajib.
7. Deploy kode tenant-aware dengan alias domain lama ke tenant Anugerah.
8. Smoke test login, buat TTS, laporan, PDF/struk, tracking, stok, dan penjualan.
9. Siapkan rollback kode dan database yang sudah diuji sebelum maintenance ditutup.

Migrasi produksi harus memiliki maintenance window dan persetujuan terpisah. Pengecualian “tanpa backup” saat demo tidak aman untuk data client yang sudah dipakai.

## 12. Strategi pengujian

### Tes otomatis wajib

- dua tenant dengan ID data yang tumpang tindih secara logis;
- seluruh index/show/edit/action hanya mengembalikan tenant aktif;
- percobaan mengirim ID kategori, teknisi, produk, TTS, penjualan, dan pengeluaran milik tenant lain ditolak;
- penomoran TTS/JL berjalan independen dan aman terhadap konkurensi per tenant;
- unique username/kategori/nomor berlaku per tenant;
- dashboard dan laporan dihitung hanya dari tenant aktif;
- dokumen dan tracking memakai identitas tenant pemilik;
- suspend/activate tenant sesuai aturan;
- provisioning tenant bersifat atomik: gagal sebagian tidak meninggalkan tenant rusak;
- migrasi fixture Anugerah mempertahankan semua baris dan total.

### QA integrasi

- login dua tenant pada browser/sesi berbeda;
- uji URL tenant salah dan tenant nonaktif;
- uji seluruh layar 360/768/1280;
- uji tracking dan PDF dari kedua tenant;
- security review khusus IDOR, Livewire payload, upload, cache, dan command CLI;
- backup/restore nyata sebelum pilot tenant kedua.

## 13. Tahapan implementasi

| Tahap | Hasil | Gate keluar |
|---|---|---|
| S0 — Keputusan produk | Brand/domain, model URL, onboarding, paket, suspend, zona waktu disetujui | Draft ini direview |
| S1 — Fondasi schema | Tenant, context, scope, unique per tenant, migrasi kompatibel | Tes isolasi model dan migrasi lulus |
| S2 — Tenant-aware app | Semua query, auth, nomor, dokumen, tracking, file, laporan terisolasi | Regresi fitur + security tenant PASS |
| S3 — Platform onboarding | Platform admin membuat/aktifkan/suspend tenant dan owner | Provisioning atomik dan audit PASS |
| S4 — Migrasi Anugerah | Data produksi menjadi tenant pertama dengan domain lama tetap berfungsi | Backup/restore, checksum, smoke PASS |
| S5 — Pilot client kedua | Toko HP nyata memakai preset dan alur yang sama | Tidak ada kebocoran data; feedback operasional diterima |
| S6 — Komersialisasi | Paket final, billing, landing/onboarding publik bila dibutuhkan | Diputuskan dari data pilot |

Implementasi tidak dimulai sekaligus. S1–S4 harus selesai dan lulus QA independen sebelum client kedua diberi akses.

## 14. Kriteria selesai v1 SaaS

- Client baru dapat dibuat tanpa repo, database, atau deployment baru.
- Anugerah tetap dapat memakai data dan URL lamanya setelah migrasi.
- Toko HP dan komputer dapat memakai alur yang sama melalui kategori preset/pengaturan toko.
- Seluruh data internal dan dokumen terisolasi per tenant.
- Tes kebocoran lintas tenant lulus untuk semua modul dan Livewire action.
- Penomoran, laporan, stok, pengguna, dan pengaturan independen per tenant.
- Backup offsite dan restore telah diuji.
- Pipeline satu codebase dapat merilis tanpa menghapus data tenant.
- Pilot tenant kedua selesai sebelum registrasi publik atau billing otomatis dibuat.

## 15. Keputusan yang perlu dikonfirmasi

Rancangan memakai default rekomendasi berikut sampai user memilih berbeda:

1. **Domain SaaS:** subdomain per toko, misalnya `anugerah.nama-saas.com`.
2. **Onboarding:** client dibuat manual oleh admin platform, bukan daftar sendiri.
3. **Billing:** status dan paket diatur manual; belum ada payment gateway.
4. **Keanggotaan:** satu user hanya berada di satu toko.
5. **Jenis usaha:** hanya preset kategori dan contoh, bukan fitur/kode terpisah.
6. **Suspend:** aplikasi toko menjadi read-only/blok perubahan; data tidak dihapus dan aturan masa aktif tracking perlu ditentukan.
7. **Zona waktu:** disimpan per tenant; mata uang v1 tetap Rupiah.

Setelah keputusan ini disetujui, dokumen dapat dinaikkan menjadi PRD SaaS dan dibuatkan `SAAS-TODO.md` terpisah. `PRD.md` dan `TODO.md` lama tetap menjadi catatan produk produksi satu toko sampai migrasi SaaS benar-benar diterima.
