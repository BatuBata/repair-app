# S004 A1 — isolasi servis, dokumen, tracking, dan nomor

Implementasi lokal siap untuk integrasi S005 dan QA independen. Seluruh case servis lulus; suite gabungan masih mempunyai dua error pada fixture commerce yang belum diisolasi oleh S005. Tidak ada commit, push, atau deploy oleh worker ini.

- Workspace: `/Users/muzib/Projects/web/repair-app`, branch `codex/saas-multitenant`.
- Base: `6421faa496bda183ffe7473d94fcfc04860137e3`.
- Input S003: `sha256:d5a8bb86214580207d73ca1e8039b84f20e34dc912cad3c924be5398320a3160`.
- Candidate source: `sha256:c721514661e503ee70abe9fbdd508896f18835b3e0070cb1d6c20664c4a66674`; daftar berkas dan algoritma ada di [candidate.json](candidate.json). Evidence dan ledger tidak masuk hash.
- Model diminta `gpt-6.1-sol`, effort `high`; model efektif dan receipt `null` karena runtime tidak memberi receipt model.
- Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery dan kontrak worker juga dibaca. Ledger tidak ditulis worker.

## Perubahan dan audit caller

`ServiceOrder` memakai trait tenant existing: query tanpa context tidak menghasilkan data dan create tanpa context gagal. Save memeriksa pemilik record existing serta kategori, creator, dan teknisi dalam tenant aktif. Validasi kategori pada `CreateServiceOrder` dan identitas creator juga dibatasi tenant.

Caller yang diperiksa: semua query TTS/Invoice (list, search, detail, edit, status, penugasan, invoice), `CreateServiceOrder`, `ManageServiceOrder`, `InvoiceLines::revision`, controller tracking/WhatsApp/dokumen, semua pemakai `DocumentNumber`, relasi `ServiceOrderItem`, dashboard dan laporan yang membaca TTS. Query child pada alur servis selalu memakai parent. Query SQL langsung pada dashboard berada dalam S005 dan dilaporkan ke coordinator; tidak diubah S004.

Nomor menggunakan kunci `(tenant_id, prefix, period)` dengan transaksi, insert-or-ignore, dan row lock existing. TTS dan JL tetap memakai pola nomor yang sama, dengan counter independen per tenant.

Tracking publik dan nota publik melepas hanya scope tenant pada lookup token 64 karakter yang eksplisit, lalu tetap membandingkan token secara persis dengan `hash_equals`. Kategori, teknisi, dan identitas toko dibaca dengan tenant pemilik order. Nama customer, telepon, alamat, modal, serta laba tidak ditambahkan pada output publik. Header cache/noindex existing tetap dipertahankan. Data toko dibawa bersama data dokumen sehingga render publik tidak bergantung pada tenant sesi browser.

Perluasan scope dari coordinator mencakup `routes/modules/tracking.php` dan `routes/modules/documents.php`: hanya `tracking.show`/`tracking.pdf` dikecualikan dari middleware akun/tenant aktif, sehingga sesi tenant atau akun nonaktif tetap dapat membuka link publik lama. WhatsApp serta semua route internal tetap terlindungi. Jika `StoreSetting.name` kosong, fallback memakai nama `Tenant` pemilik, selaras shell S003.

## Pemeriksaan aktual

Semua perintah memakai PHP 8.3 proyek. Daftar perintah, expected/actual dan exit code ada di [checks.json](checks.json).

| Pemeriksaan | Hasil aktual | Bukti |
|---|---|---|
| TTS + Invoice + Tracking + Documents + 6 tes isolasi dua tenant | 51 tes, 49 PASS, 2 ERROR commerce S005; 466 assertions, 2215 ms | [regression-final.json](regression-final.json) |
| Nomor MySQL bersamaan | PASS: 32 proses, dua putaran, counter baru dan existing, transaksi tiap grup overlap; TTS/JL tenant 1 dan 2 masing-masing 001–008 | [mysql-concurrency.json](mysql-concurrency.json) |
| PHPStan terarah dengan `--debug` | PASS, 0 errors | [phpstan.txt](phpstan.txt) |
| Pint pada seluruh PHP berubah dan runner evidence | PASS | [pint.txt](pint.txt) |
| `git diff --check` | PASS, output kosong | [diff-check.txt](diff-check.txt) |

Enam tes isolasi mencakup list/search, create/edit sendiri, 404 pada route dan Livewire ID asing, action asing tanpa perubahan parent/child, relasi kategori/teknisi/creator silang, percobaan memindahkan instance asing, nomor/token/context kosong, PDF/thermal/WhatsApp/tracking pemilik, sesi tenant/akun nonaktif pada publik, serta nama tenant kedua saat pengaturan kosong.

Dua error yang masih terbuka: `DocumentTest::test_sale_receipt_keeps_large_integer_amounts_and_admin_access` membuat Product tanpa `tenant_id`; `DocumentTest::test_long_thermal_text_preserves_words_newlines_and_unbroken_text` membuat Sale tanpa `tenant_id`. Root Product/Sale merupakan scope S005. Tidak ada perubahan fixture untuk menyamarkan error tersebut. S005/integrasi harus menjalankan ulang kedua case setelah model commerce diperbaiki.

MySQL pertama terhalang sandbox (EPERM), tersimpan di [mysql-sandbox-blocked.txt](mysql-sandbox-blocked.txt). Eksekusi berizin berikutnya membuat database baru **`saas_s004_20261116_164324_153`**, memeriksa nama baru dan host lokal, menjalankan migrasi biasa tanpa reset, lalu mempertahankannya. `repair_app`, `repair_app_test`, database produksi, dan data existing tidak disentuh. Runner yang dapat diperiksa ada di [mysql-concurrency.php](mysql-concurrency.php); menjalankannya lagi membuat database baru lain. Kredensial hanya dibaca privat dari `.env`, tidak dicetak atau dimasukkan artifact.

PHPStan parallel sempat terhalang socket EPERM; mode `--debug` menghindari socket. Hasil awal tujuh error tipe disimpan di [phpstan-initial.txt](phpstan-initial.txt), diperbaiki dengan deklarasi properti tenant yang benar dan menghapus pengecekan null yang sudah dijamin guard. Bukti FAIL fixture awal dipertahankan di [regression-initial.json](regression-initial.json) dan [isolation-initial.json](isolation-initial.json); snapshot isolasi sebelum tambahan fallback nama juga dipertahankan di [isolation-before-owner-fallback.json](isolation-before-owner-fallback.json). Hasil awal isolasi gagal karena nama route dan perbandingan fixture sebelum refresh; keduanya diperbaiki pada tes A1. Semua ini bagian pengembangan A1, bukan QA independen.

Tidak dijalankan: seluruh suite proyek, browser visual, printer fisik, produksi/deploy, maupun QA independen. Risiko tersisa: commerce S005 dan query langsung dashboard harus diintegrasikan serta diuji sebelum candidate dapat dianggap lulus seluruh regresi; database evidence khusus tetap tersimpan untuk QA.
