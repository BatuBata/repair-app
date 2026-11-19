# T085 — form tambah penjualan

Form tambah mengikuti detail terbuka; pelanggan/barang utama, total/tanggal/garansi/pembayaran/simpan di kanan. Di HP satu kolom+actionbar existing. Tidak ada backend/binding/action baru. Scope source dua Blade dipin source-manifest.json.

Developer: SalesTest + CustomerRelationsTest PASS25tests/235assert1373ms pada candidate sebelum perbaikan wrapper ikon/separator HP; final render ditinjau CUA. npm build PASS sebelum final perbaikan Blade; integrated check akhir akan dilaporkan terpisah, tidak menyamakan snapshot.

CUA coordinator memakai fixtureSQLite /private/tmp/repair-t064.sqlite pada8193, bukanDBoperator. Desktop1440 dan1280: dua kolom, tidakoverflow; HP375 docwidth375 satu kolom, satu visible save. Nested label save menjaga Menyimpan tersembunyi saat idle. Cari barangBaterai→price650000/stock22, add+removeitem2 PASS. PilihcustomerSintetis→identitydisabled; Pelangganbaru→clear+unlocked, isi namaPembeliUjiForm. Qty2→total1300000. Submitkosong→inlineproduct/priceerrors +focusproduct. Datepicker18Nov selected; QRIS/transferBCA pilih/searchbank utuh dan tidakterpotong375(bank-mobile.jpg). HP submit→/penjualan/2 JL2611002: qty2 total1300000 date18Nov BCA, custombuyername tersimpan; stock22→20. Hanya menambah satu transaksi fixture sintetis; tidak menyentuhoperatorDB.

Final screenshotdesktop1440(form-desktop.jpg),1280(form-1280.jpg),full375(form-mobile.jpg) danviewportitems375(items-mobile.jpg),popoverbank375(bank-mobile.jpg). FullpageHP memuat overlaynav/actionbar pada posisi viewport capture; viewportitems menunjukkan controls tidaktertutup saatdigulir.

Tidak HP/printerfisik, screenreader, WA, productionDB atau push/deploy. QA independen dicatat di independent-qa.md.

Integrated suite:275cases:273PASS/1SKIP(MySQLguard)/1FAIL, failure onlyDatePickerTest expecting old literal foreach sold_ondate mapping. Updated existingassert to explicit x-ui.fieldname sold_on type=date as currenttemplate (samepatternrestock); failingfile rerun2PASS21assert115ms. All application cases passed; no productdatepickerchange. OriginalFAIL log retained /private/tmp/repair-t085-t086-suite.log and targeted /private/tmp/repair-t085-datepicker-recheck.log. FinalbuildPASS /private/tmp/repair-t085-t086-final-build.log. Manifestincludesupdatedtest now; independentreview extendedtoit.
