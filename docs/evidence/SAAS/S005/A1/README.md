# S005 A1 — isolasi stok, transaksi, dashboard, dan laporan

Candidate lokal siap diserahkan ke QA independen. Worker tidak mengubah ledger, membuat commit, push, atau deploy.

- Workspace `/Users/muzib/Projects/web/repair-app`, branch `codex/saas-multitenant`.
- Base `9805be8b0c8f444ab17a26f7eaa4b9ba4324b66b`.
- Input S004 `sha256:c721514661e503ee70abe9fbdd508896f18835b3e0070cb1d6c20664c4a66674`; seluruh hash file input dicocokkan kembali dan tetap identik.
- Candidate `sha256:9ab1f13ae929cb26cdac0c12aa61137e48d518e2617b886cf354f44b9340802f`; manifest/algoritma pada [candidate.json](candidate.json). Evidence dan ledger dikecualikan.
- Model diminta `gpt-6.1-sol`, effort `high`; model efektif/receipt tidak tersedia dari runtime dan dicatat `null`.
- Ponytail **full** dibaca lengkap dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, kontrak worker, adapter Codex, AGENTS, S005 dan rancangan SaaS terkait juga dibaca.

## Perubahan dan audit caller

`Product`, `Sale`, dan `Expense` memakai trait `BelongsToTenant` existing: query tanpa context mengembalikan nol baris dan create tanpa context gagal. Save model yang sudah ada memeriksa tenant asli dan tenant saat ini, sehingga instance asing tidak dapat dipindahkan ke tenant aktif. Creator penjualan/pengeluaran harus berasal dari tenant aktif.

`StockEntry` dan `SaleItem` tetap tabel anak tanpa tenant duplikat. Validasi saving memeriksa produk, parent penjualan, dan creator restok dalam tenant aktif. Update child existing memeriksa parent asli sebelum payload baru sehingga child tenant asing tidak dapat dipindahkan. Tidak ada query child umum baru; caller yang perlu membaca child selalu memakai parent atau `whereHas('product')` pada total restok laporan.

Audit mencakup semua pemanggil Product/StockEntry/Sale/SaleItem/Expense pada `app`, `database`, dan tes; action CreateSale/DeleteSale; seluruh Livewire Stock/Sales/Expenses/Dashboard/Reports; Report; Document/controller dokumen; serta relasi parent/child. Daftar, detail, edit, restok, pilihan produk, penjualan, penghapusan, dan garansi existing sudah memuat ID melalui root query. Scope root menutup seluruh jalur tersebut tanpa perubahan action duplikat. CreateSale tetap memakai urutan row lock, validasi, transaksi dan retry existing; payload campuran asing membatalkan stok, parent, child dan counter sekaligus.

Dua query SQL langsung pada dashboard sekarang memfilter `orders.tenant_id` dan `sales.tenant_id` eksplisit. Query model pada dashboard/laporan/pengeluaran dibatasi trait root. Total restok laporan membatasi produk pemilik melalui `whereHas`. Fallback nama WhatsApp pengingat memakai nama Tenant aktif ketika nama pengaturan kosong. Dokumen penjualan S004 tidak memerlukan perubahan: Sale scoped dan StoreSetting aktif existing sudah memberi identitas tenant.

Fixture existing Stock/Sales/Reports yang membuat data atau Livewire setelah HTTP request kini menetapkan context tenant fixture secara eksplisit; middleware memang membersihkan context setelah request. DocumentTest tidak diubah dan kedua error commerce yang dicatat S004 telah hilang.

## Bukti aktual

Perintah rinci, expected/actual, exit code dan batas ada pada [checks.json](checks.json). Semua PHP memakai `/opt/homebrew/opt/php@8.3/bin/php`.

| Pemeriksaan | Hasil |
|---|---|
| CommerceIsolation + regresi Stock/Sales/Expenses/Dashboard/Reports/Documents | **66/66 PASS, 893 assertions**, 3860 ms; [tests.txt](tests.txt) |
| Isolasi setelah perapian akhir | [isolation-final.txt](isolation-final.txt) |
| Perlombaan stok MySQL dua tenant | **PASS**, masing-masing dua admin berbeda berebut satu stok: satu sukses, satu shortage, stok akhir 0, satu sale/item, counter +1, tidak ada orphan; kedua tenant `JL-2611-001`; [mysql-concurrency.json](mysql-concurrency.json) |
| PHPStan terarah `--debug` | **PASS, 0 errors**; [phpstan.txt](phpstan.txt) |
| Pint seluruh PHP berubah + runner evidence | **PASS**; [pint.txt](pint.txt) |
| `git diff --check` | **PASS**, output kosong; [diff-check.txt](diff-check.txt) |

Enam tes isolasi mencakup list serta print/PDF sendiri, 404 route dan action asing, foreign product selection, edit/restok/delete/expense/sale yang sudah ter-mount lalu berpindah tenant, rollback payload campuran, creator/produk/parent silang, instance root/child asing, nomor JL independen, fail-closed tanpa context, nilai dashboard/laporan per tenant, pengingat garansi asing, dan fallback identitas toko.

Runner stok existing `tests/Feature/Sales/mysql-concurrency.php` diadaptasi ke schema tenant dan **mewajibkan** database lokal bernama `saas_s005_*`, runtime privat, serta context tenant eksplisit. Harness [mysql-concurrency.php](mysql-concurrency.php) menciptakan satu database baru setelah memeriksa nama belum ada, menjalankan migrasi biasa, lalu menjalankan existing stock race dua kali untuk tenant berbeda. Database **`saas_s005_20261116_165831_3331`** dan fixture dipertahankan. Inventaris sebelum setiap race dan verifikasi byte-identik baseline disimpan privat di runtime yang tercatat pada JSON; race tenant kedua juga membuktikan fixture tenant pertama tidak berubah. Tidak memakai migrate:fresh/reset/drop; `repair_app`, `repair_app_test`, produksi dan database existing lain tidak disentuh. Kredensial hanya dibaca privat dari `.env` dan tidak dicetak.

## Riwayat pemeriksaan gagal A1

- Pengembangan awal sempat memakai method `StoreSetting::current()` yang tidak ada. Dikoreksi ke query existing sebelum candidate final.
- [regression.txt](regression.txt): 60 tes, 57 PASS, 3 ERROR. Dua fixture Stock belum memiliki context; satu Livewire Reports dibuat setelah HTTP membersihkan context. Fixture diperbaiki eksplisit.
- [isolation-initial.txt](isolation-initial.txt): lima ERROR karena fixture StoreSetting belum mengisi kolom wajib address/warranty_terms. Diperbaiki fixture.
- [isolation-second.txt](isolation-second.txt): empat PASS, satu FAIL karena fixture jenis jasa otomatis menolkan modal tetapi expected memasukkan modal 10. Fixture diganti jenis part untuk benar-benar menguji modal laporan.
- [phpstan-initial.txt](phpstan-initial.txt): tiga property tenant_id belum dideklarasikan pada PHPDoc root model. Ditambahkan deklarasi properti seperti pola ServiceOrder existing.
- MySQL pertama gagal PDOException pada sandbox, [mysql-initial.txt](mysql-initial.txt). Perintah sama kemudian disetujui require_escalated dan PASS; tidak ada penolakan auto-review. Receipt retry error kosong, [mysql-retry-error.txt](mysql-retry-error.txt).

Seluruhnya bagian pengembangan A1, bukan QA independen. Sesudah suite gabungan PASS, perubahan akhir hanya menghapus filter tenant SQL identik yang terduplikasi, merapikan imports/format, dan mendeklarasikan PHPDoc tenant. Tes isolasi terarah dijalankan kembali; suite penuh tidak diulang tanpa alasan.

Tidak dijalankan: seluruh suite proyek, browser visual, perangkat/printer fisik, produksi/deploy, atau QA independen. Risiko/batas: full regression dan QA integrasi tetap gate coordinator; database evidence dan dua runtime privat dipertahankan untuk konsumen QA, tanpa pembersihan data.
