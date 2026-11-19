# T098 — filter pembayaran Transaksi laporan

D-61: sesudahT097rilis selesai, perbaiki spacing/layout lalu push/deploy. Perubahan runtime hanya4barisCSS pada reports/styles.blade.php: marginbawah toolbar24; dua kolom filter stabil412px dengan grid minmax0 danfieldmin-width0; HP<=639satu kolom. Tidakmengubahbackend/perhitungan/form/componentselect/schema/dependency/filter.

Reproduksi nyata fixtureSQLite terpisah `/private/tmp/repair-t098.sqlite`,25sales+25expenses dengan4transfer(3BCA,1namabankpanjang). Sebelum1280: toolbarsemuametode68px→transfer148px karena containerflex411px tidakmuat2field200+gap12; bankturun80px dantabtertarikke bawah. Jarak toolbar/table16px. before-all.jpg/before-transfer.jpg dan geometry.json.

## Verifikasi sebelum rilis

- Sesudah1280/1440: all/transfer sama68px;1024sama125;768sama141;375satu kolom141→233sesuaipenambahanfield. Seluruh375/768/1024/1280/1440 gapketabel24px dan documentwidth sama viewport.
- CUA keyboardbankBCA3rows/namabankpanjang1row,QRISempty,resetsemuametode50rows, page2→transferresetpage1/4rows. RingkasannetRp350000tetap di seluruhfilter. Dropdownbank200pxdesktop/305pxHP tidakterpotong; teksbankpanjanglengkap/terbungkus(menuHP74px),keyboardArrowDown/Enter/Escape bekerja.
- PHP8.3 `artisan test --compact tests/Feature/Reports tests/Feature/IndexPaginationTest.php`:14PASS233assert2263ms; PHP8.3Pintdirty+gitdiffcheckPASS.
- Manifest1fileCSS source-manifest.json sha256:305f0b019501343b5ded3c5449b600543280187d2c5071e4679da19a2f2e79f0. QAindependen source+before/after/geometry tercatat independent-qa.md.
- Bukti after-all.jpg/after-transfer.jpg/after-mobile.jpg/dropdown-desktop.jpg/dropdown-mobile.jpg. Serverfixture/tabagent ditutup,viewportdipulihkan. Build/suitepenuh lokal tidakdiulang karenaCSS inline tidakmenambahutility/aset danlogic tidakberubah; CIremote menjalankanbuild/fullchecks.

## Rilis

User telah mengizinkan push/deploy pada permintaan yang sama. Workflow/sourceSHA/receipt/QAserverreadonly akan dicatat setelah rilis aktual; dokumenpersiapan ini tidakmengklaimdeploymentPASS. Tidaklogin/menulisDBoperator,memilihbankpadatransaksipelanggan,mengirimWA,atau uji printer/perangkatfisik. BackupD22tetapditunda.
