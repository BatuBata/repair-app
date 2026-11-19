# T099 — satu hitungan transaksi

D-62 main lokal tanpa push/deploy. Sharedindex-search total/unit menjadi opsional; count hanya dirender jika total tersedia, termasuknilai0. Pemanggil laporan tidak lagi mengirim total/unit sehingga hitungan hanya ada di sebelah judul Transaksi. Sepuluh pemanggil index lainnya tetapmengirimcount/unit dan tidakberubah.

Actualchecks:Reports+IndexPagination14PASS233assert2844ms; focusedindependent1PASS9assert336ms (header2→0/searchcountabsent/shared0barang/omittedcount). Pintdirty/diffPASS. CUAfixtureSQLite T098 desktop1280/mobile375:header50/4/0selalu tepat1label,searchCounts0,documentwidth=viewport. desktop.jpg/mobile.jpg/browser-checks.json;sourcepin3files cf070fe54349b2d6d577c56ee675fc7dd572da6ae52e46e422b5c372fb43cd4a; independent-qa.md.

Tidakmengubahbackend/data/operatorDB/filters/pagination/aset atau dependencies. Build/suitepenuh/produksi tidakdiulang untuk perubahanrenderini. Tanpa push/deploy. Serverfixture/tabagentditutup,viewportdipulihkan.
