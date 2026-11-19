# T100 — kapitalisasi tampilan nama pelanggan

D-63, main lokal tanpa push/deploy. PHP ucwords dipakai hanya pada presentasi nama: masterpelanggan/index/detail/pagetitle/menu, TTSindex/detail/invoiceheader, salesindex/detail, dashboardbacklog/reminderdisplay, selectorpelanggan, Reportdescriptions danDocumentcustomerpayload(print/PDF). Nama item/alamat/username tidak diformat. Hurufkapital existing dipertahankan sesuai ucwords, misalnyaRF samarinda→RF Samarinda.

TidakmemakaiModelaccessor/mutator atau mengubahactions/persistencesnapshot. Inputcustomername/Alpinepreview memakaiCSScapitalize visual; value/wirepayload tetapraw, sehingga edit/save/pilihcustomer tidak memaksa perubahan case. Publictracking/PDFpublik tetap tidak menambahkan nama/HP/alamat pelanggan; bodyWA bukanhalaman dan tidakdiubah.

## Pemeriksaan aktual

- PHP8.3 `artisan test --compact`:299kasus,298PASS3401assert16736ms,1guardedMySQLconcurrencySKIP. Dilakukansebelum3testindependen baru ditambah. Seluruhregresicustomer/TTS/sales/report/document/tenant existingPASStanpaperubahanassertionlama.
- QAindependen CustomerNameDisplayTest:3PASS88assert640ms; mencakup9routes/index/detail/invoice/selector/dashboard/reminder/report/documentHTMLpayload, rawCustomer/TTS/Sales tidakberubah setelahread maupunrealedit/save/selectedcustomercreatesnapshot, RFpreserved, HTMLescaped/nullsale/privatepublic/tenantguards.
- PHP8.3PHPStan0error,PintdirtyPASS,gitdiffcheckPASS.
- CUAfixtureSQLiteT100, nama rawbudi santoso. Daftar/detailTTS,detailpelanggan/pagetitle,detailpenjualan/dashboard menampilkanBudiSantoso;RF Samarinda tetap. SelectorTTS/sales tampilBudiSantoso, DOMinputvalue tetapbudi santoso dancomputedtext-transformcapitalize. Reportsearchbudi memuat2deskripsiBudiSantoso dancount2hanyaheader(revisiT099tetap). Bukti browser-checks.json danservice-list.jpg/customer-selection.jpg/dashboard.jpg/report.jpg; report-mobile.jpg hanya kontekslaporanbukanbuktinama pada barisHP.
- Source-manifest15file runtime/test sha256:a1f051237cfd38380641c998ab36bf0726575c1f5dc2335b1bdb8283f2d396d5; pin danbukti ditinjauindependen dalam independent-qa.md.

Serverfixture/tabagent ditutup,viewportdipulihkan. Tidakmengubahdataoperator/produksi,schema/dependency atau melakukanpush/deploy. Buildaset tidakdiulang karena perubahan ucwords/inlineCSS tanpa kelasutilitybaru; font/perangkatfisik/WA eksternal tidakdiuji. Suite lokal MySQL guarded tidakdiaktifkan; tidak ada operasi database baru.
