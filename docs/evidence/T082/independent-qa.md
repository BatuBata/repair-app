# T083 — QA independen banner bersama

Verdict: **PASS** untuk cakupan D-46 pada snapshot T082. Tidak ditemukan cacat produk dalam pemeriksaan ini.

- Input/base: `c24f47c4dba63f72e259d791b7d4c7e4d5d9f620`.
- Checked revision: `sha256:a604346b08f12782ae46f4f51d44c10b4e30049dfc90fb95cadc4e880ef3f17f` dari `source-manifest.json`. Pemeriksaan SHA-256 independen membuktikan seluruh 25 file masih identik dengan manifest saat QA.
- Peran: tester independen. Ponytail **full**, dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; workflow QA dari `/Users/muzib/.agents/skills/project-delivery/references/qa-and-release.md`. Model diwariskan oleh runtime; tidak mengklaim model_hint sebagai model efektif.
- Lingkungan: PHP 8.3.30, PHPUnit 12.5.43, SQLite `:memory:`, cache/session fixture array. Tidak memakai MySQL/database operator. Hanya berkas laporan ini ditulis di repository; kode produk dan ledger tidak diubah.

## Inventaris seluruh caller

Penelusuran `resources/views` menemukan **35** pemanggil `<x-ui.notice>`, semuanya memiliki tipe eksplisit: **14 success, 15 error, 5 warning, 1 info**.

| View di resources/views | Success | Error | Warning | Info |
| --- | ---: | ---: | ---: | ---: |
| livewire/customers/index.blade.php | 1 | 0 | 0 | 0 |
| livewire/dashboard/index.blade.php | 1 | 1 | 0 | 0 |
| livewire/expenses/index.blade.php | 1 | 1 | 0 | 0 |
| livewire/invoice/form.blade.php | 0 | 1 | 0 | 0 |
| livewire/invoice/published.blade.php | 0 | 0 | 1 | 0 |
| livewire/profile/change-password.blade.php | 1 | 1 | 0 | 0 |
| livewire/reports/index.blade.php | 0 | 1 | 1 | 0 |
| livewire/sales/form.blade.php | 0 | 1 | 1 | 0 |
| livewire/sales/index.blade.php | 1 | 0 | 0 | 0 |
| livewire/sales/show.blade.php | 1 | 1 | 0 | 0 |
| livewire/settings/banks.blade.php | 1 | 0 | 0 | 0 |
| livewire/settings/categories.blade.php | 1 | 1 | 0 | 0 |
| livewire/settings/store.blade.php | 1 | 1 | 0 | 0 |
| livewire/stock/form.blade.php | 0 | 1 | 0 | 1 |
| livewire/stock/index.blade.php | 1 | 0 | 0 | 0 |
| livewire/stock/restock.blade.php | 1 | 1 | 1 | 0 |
| livewire/tts/detail.blade.php | 1 | 1 | 0 | 0 |
| livewire/tts/form.blade.php | 0 | 1 | 1 | 0 |
| livewire/users/index.blade.php | 1 | 1 | 0 | 0 |
| platform/index.blade.php | 1 | 1 | 0 | 0 |

Review pemicu di caller dan penghasil status di Livewire/controller: semua success berasal dari aksi tersimpan/diubah/dihapus/ditandai yang selesai; laporan invalid bukan success. Lima warning adalah laporan invalid, perhitungan nota melampaui batas, barang nonaktif, kategori aktif kosong, dan barang jualan aktif kosong. Petunjuk restok tetap informasi dengan tautan. Banner validasi/error mempertahankan teks, `x-init`, serta pemetaan fokus yang sudah ada. Pesan input `error-text`, `aria-invalid`, dan `aria-describedby` tetap dekat input. Banner garansi tracking publik dan state kosong/loading bukan hasil aksi dan tidak disalahklasifikasikan sebagai success.

## Pemeriksaan perilaku independen

Perintah aktual: `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit -c phpunit.xml /private/tmp/NoticeIndependentTest.php --testdox`.

Delapan kasus berikut telah PASS; enam lulus pada run pertama dan dua lulus setelah koreksi fixture QA, tanpa mengubah produk:

1. Render komponen success/error/warning/info, tipe `unknown`, `null`, array, serta tipe bawaan. Kelas, label, ikon tunggal, `aria-hidden` ikon, role dan `aria-atomic` benar. Error `alert`; lainnya `status`. `id`, class tambahan, `data-qa`, `x-init` dan tautan dipertahankan. Payload `<script>` ter-escape.
2. Aksi tambah barang yang valid menghasilkan redirect dan flash success pada GET `/stok`; aksi ubah status barang juga merender success.
3. Penyimpanan barang invalid menghasilkan satu banner error/alert, field error, `aria-invalid`, hubungan pesan field dan atribut fokus tetap; tidak menyimpan barang.
4. Restok barang nonaktif mula-mula warning/status. Percobaan simpan yang ditolak menghasilkan warning dan error/alert, tanpa success; stok dan riwayat tidak berubah.
5. Form TTS tanpa kategori aktif dan form penjualan tanpa produk aktif menghasilkan warning/status. Form ubah barang menghasilkan info dan tautan restok yang benar.
6. Laporan dengan tanggal, tipe, metode pembayaran atau bank invalid tidak menghasilkan success; report null, warning tersedia dan kesalahan filter terkait memakai error/alert. Laporan valid tanpa transaksi tidak menghasilkan banner keberhasilan palsu.
7. Simpan kategori valid menghasilkan success; simpan invalid menghasilkan error, dengan pesan input tetap tersedia.
8. GET `/platform` dengan flash success dan error tenant menghasilkan kedua tipe/role benar; payload dinamis keduanya ter-escape.

Riwayat pertama: `6 PASS, 1 error fixture, 1 failure fixture`, 180 assertions, 807ms (`/private/tmp/repair-t083-independent.log`). Fixture pertama sempat mencari model setelah middleware menghapus konteks tenant; fixture kedua memasukkan ViewErrorBag tanpa melewati serializer session JSON. Diagnosis disimpan pada `/private/tmp/repair-t083-platform-diagnose*.log`. Setelah memasang kembali konteks tenant dan menyimpan fixture session sebelum request, hanya dua kasus terdampak diulang: **2 PASS, 12 assertions, 468ms**, `/private/tmp/repair-t083-corrected-fixtures.log`. Tidak ada kegagalan produk yang dihapus atau suite penuh diulang.

## Review visual dan aksesibilitas

Tester membaca langsung enam screenshot final dari sesi CUA coordinator: `success-1440.jpg`, `error-1440.jpg`, `warning-1440.jpg`, `success-375.jpg`, `error-375.jpg`, `warning-375.jpg` di direktori ini. Ketiga jenis pesan punya warna berbeda yang tegas, label teks dan ikon; isi terbungkus dengan baik pada 375px. Ada ruang jelas dari kartu berikutnya pada desktop dan HP. Screenshot desktop error memperlihatkan fokus pada input jumlah masuk serta pesan field; screenshot HP warning memperlihatkan seluruh banner dan awal kartu berikutnya tanpa tumpang tindih. Screenshot HP error memperlihatkan keseluruhan banner dan batas kartu, sedangkan field berada di bawah viewport.

CSS bersama menetapkan margin bawah 20px, border kiri 4px dan wrapping; override lama invoice yang memaksa display block sudah dihapus. Receipt pengukuran CUA coordinator pada README mencatat gap 20px, lebar dokumen 375px, fokus qty, role dan warna computed sesuai token. Angka computed tersebut adalah bukti coordinator yang direview, bukan pengukuran browser yang dijalankan ulang oleh tester.

## Batas pemeriksaan

Tidak mengulang full suite/build T082 pada snapshot yang sama. Tidak menjalankan seluruh 35 cabang melalui browser; seluruh caller direview secara statis dan kasus berisiko diuji melalui render/Livewire, ditambah review enam screenshot. Tidak menjalankan pembaca layar, HP/printer nyata, MySQL, produksi, push atau deployment. Penerimaan perangkat/produksi di luar D-46 tetap mengikuti gate proyek yang sudah ada.
