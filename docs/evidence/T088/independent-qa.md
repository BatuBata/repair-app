# T088 — QA independen

**Verdict: PASS untuk kandidat lokal A2 di bawah.** Tidak ada temuan produk yang belum diselesaikan dalam scope T088. A1 FAIL1024 dan jejak perbaikan tetap dicatat.

Ponytail **full**, dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; peran tester mengikuti `project-delivery/references/qa-and-release.md`. Tester tidak mengubah kode produk, ledger, Git, database operator, atau deployment. `DESIGN.md` dan D-51 menjadi acuan tampilan; panduan app-screen dan UI/UX dipakai untuk review.

## Pin sumber

- Base: `eeacbfb2788d73f9490c3101d75cb9203e166ee6`.
- Candidate akhir A2: `sha256:e198c3a07a89f50f3975c8d9db8eda7850730ff9ab35f6a8f701c012c39fc813`, sesuai `source-manifest.json`.
- SHA256 kedua Blade dihitung ulang dan cocok dengan manifest akhir: index `51191273f4b0d3f8de776c6c4ad2f0aeada30f67044d83c910f2b0c03d4e27d9`, styles `92cf1f7ce19890c59807fde00dde44dcc6bf455ed762ff9e374f13355b00ac2d`. Dokumen/bukti bukan perubahan produk.
- Pin A1 sebelumnya: `sha256:f8d07682cb2494ac8b8c8cd54964cd6f2b290225caa52a287585e9ebe80e15e5`. Index/test target identik pada A2; CSS A2 diperiksa melalui diff dan bukti browser yang terdampak.

## Pemeriksaan tester yang dijalankan

1. Baca kontrak T088, D-51, token/aturan laporan/detail dalam DESIGN, kedua Blade, controller Reports Index, serta existing ReportsTest. Diff produk hanya dua Blade; backend, schema, izin, action dan dependency tidak berubah.
2. Bandingkan atribut Livewire (`click`, `model.live`, `key`, `loading`, `loading.class`) dan seluruh event Alpine secara otomatis terhadap base. Multiset identik. Semua binding `$displayMoney` identik setelah mengurangi satu duplikasi net/revenue pada header lama. Rumus/backend tidak diubah. Date picker, period, type/method/bank, chart pager/tooltip, detail close/focus dan detail links tetap terhubung seperti baseline.
3. Jalankan `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --configuration phpunit.xml docs/evidence/T088/ReportUiQaTest.php --colors=never`. **PASS: 1 test, 21 assertion, 485ms.** SQLite in-memory melalui phpunit.xml; tidak mengakses database operator. Uji cabang baru: net0 tetap netral, pengeluaran1 menghasilkan net−1 dan kelas negatif; satu angka utama berlabel Laba bersih, laporan tetap baca-saja, label periode/filter dan markup loading tersedia.
4. Inspeksi visual screenshot parent `before-desktop.jpg`, `report-desktop.jpg`, `report-tablet.jpg`, `report-mobile.jpg`. Net Rp880.000 menjadi angka utama, rincian tetap lengkap, pembayaran/transfer dan transaksi terbuka, aksi baris tetap paling kiri. Urutan di HP/tablet bertumpuk, tanggal dua kolom muat, angka dan uraian tidak saling bertumpuk pada gambar. Nilai revenue Rp2.925.000, modal Rp2.030.000, gross Rp895.000, pengeluaran Rp15.000, net Rp880.000 dan bank Rp2.150.000 sama sebelum/sesudah. Jumlah daftar Rp2.910.000 mencakup pengeluaran minus, sehingga tidak sama dengan omzet; laba daftar tetap Rp880.000.

## Bukti parent

Build, 21 existing tests/344 assertion, lint source dan interaksi CUA adalah pemeriksaan parent, bukan klaim eksekusi tester. Tester telah membaca receipt final `README.md` dan `browser.md`: filter jenis/metode/bank hanya membatasi daftar; ringkasan tetap880000; dialog diskon/close/Escape/focus, tanggal cepat/global, empty/invalid, pager dan tooltip dilaporkan PASS. Tester mengaudit ikatan terkait dan meninjau gambar yang dirujuk di bawah secara independen. Parent tidak mengklaim pengamatan request lambat; markup/class/loading status tetap dan diverifikasi melalui audit dan uji tester.

## Temuan 1024px dan perbaikan

**A1 FAIL** pada lebar sela desktop1024px: tester menemukan jumlah lebar kolom tetap719px melebihi ruang panel baru. Parent mengonfirmasi lewat DOM: tabel719px di ruang680px, kolom Keterangan0px dan tinggi baris membesar. Ini tidak terlihat pada tiga lebar utama375/768/1440.

Parent memperbaiki A2 hanya pada CSS responsif: mode daftar transaksi existing diperluas sampai1199px, sehingga tanggal/jenis tetap di metadata keterangan dan nilai modal/laba tetap di dialog sebagaimana mode daftar existing. Mode tabel penuh mulai1200px. Breakpoint periode, pembayaran dan angka lain tetap. Tidak menambah variasi markup/kolom. Percobaan awal band kolom sebagian ditinggalkan sebelum pin akhir karena membuat footer tidak selaras; tidak menjadi kandidat akhir.

Parent mengonfirmasi retest normal1024/1100: tabel680/756px, lebar keterangan628/704px, footer menampilkan label dan laba bersih. Tester membaca `report-1024.jpg` dan mengonfirmasi metadata tanggal/jenis serta total terlihat. Tester juga meminta `height:auto` pada td daftar1024–1199 agar perilaku baris panjang sama dengan daftar HP existing; A2 memasukkannya.

Tester membaca `chart-mobile.jpg`: tooltip tanggal19Nov berisi omzetRp1.625.000, servisRp175.000, penjualanRp1.450.000; counter jendela2/2 sempat pecah tiga baris. Tester membaca ulang `chart-mobile-final.jpg`: A2 menjaga counter2/2 satu baris (receipt26.72×18px) dan tombol tetap tinggi minimal44px. `invalid-mobile.jpg` menampilkan tanggal20Nov–19Nov dengan pesan error di tanggal akhir dan warning, tanpa angka laporan yang menyesatkan.

**A2 retest PASS:** tester membaca `large-1024.jpg` dan `large-negative-mobile.jpg`. Net−Rp1.179.869.179.999.120.000 tampil lengkap dan terbungkus perkelompok angka pada375px, warna token negatif disertai tanda minus. Omzet/modal melampaui PHP_INT_MAX tetap berupa angka tepat. Baris angka besar dan footer1024px muat, metadata tetap jelas. Receipt parent mengukur doc375/1024/1440 tanpa overflow halaman; tabel1024 tetap680px, seluruh7baris memiliki scrollHeight≤clientHeight. Td panjang auto-height38/56px dan row85/127px sesuai kontennya. Tidak mengakses atau mengubah database operator.

## Batas

Tidak mengulang seluruh suite/build pada index identik dan perbaikan CSS runtime; audit hash/atribut/binding akhir diulang dan PASS. Uji browser langsung tetap di sesi CUA parent agar tidak berebut perangkat. Capture full-page mobile/tablet menempatkan sticky header/nav di tengah gambar; screenshot viewport `mobile-top.jpg` menunjukkan header di atas dan nav di bawah secara benar, jadi ini artefak capture, bukan temuan produk. Belum menguji HP/printer fisik, pembaca layar, request dengan latensi paksa atau produksi; scope hanya laporan lokal, tanpa push/deploy.
