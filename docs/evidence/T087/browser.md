# T087 — CUA coordinator

Server8193 memakai SQLite fixture /private/tmp/repair-t064.sqlite; migrasi sales.discount 3.19ms PASS. Tidak menggunakan DBoperator/production.

Desktop1440: barang Baterai650000/qty1/stock20. Diskon50000→subtotal650000/total600000, screenshot discount-desktop.jpg. Diskon700000→submit ditolak, inlineerror Diskon tidak boleh melebihi subtotal barang, fokusdiscount aria-invalidtrue, totalPeriksaisian, bukan nilai negatif. HP375 docwidth375, error/field/fokusutuh (discount-error-mobile.jpg).

Preview diskon650000→total0. Ubahqty2→subtotal1300000/total650000 (discount sekali), lalu qty1 dan diskon50000→600000. PilihQRIS dan simpanfixture→JL2611003 /penjualan/3. Detail: hargaunit650000, subtotal650000, discount−50000, total600000, modal450000, laba150000; discount-detail-mobile.jpg. StrukHTML: subtotal650000 diskon−50000 total600000 (discount-receipt.jpg); tidakmenekan tombolcetak/printer. Listpenjualan003=600000; legacy001=850000/002=1300000 tetap. Hanya1fixturebaru dibuat, stock20→19; tidak ada transaksioperator.

Tab ditutup/viewportreset/serverfixture dihentikan. Bukan ujiHP/printerfisik atau pembaca layar. Bukti UI coordinator, bukan sesi browser independen.
