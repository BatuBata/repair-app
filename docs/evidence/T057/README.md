# T057 — UX pencarian pelanggan

User18Nov2026: kolom berisi Pelanggan baru mengharuskan hapus teks sebelum pencarian. Perubahan: opsi sentinel dihapus dari search-select TTS; placeholder nama/HP kosong; form baru bisa diisi langsung; sesudah memilih pelanggan, tombol Pelanggan baru mengosongkan ID/nama/HP/alamat melalui action yang recheck active account dan hanya untuk TTS baru. Wrapper diberi wire:key per pilihan agar state Alpine tidak tertinggal saat reset. Shared search-select mengosongkan query saat membuka dan close mengembalikan selectedLabel tanpa mengubah selected value.

Developer: 27 tes Customers/SearchSelect/TTS PASS/241 assertion; full238 tests237PASS1existingMySQLSKIP2530assertion; PHPStan0 dan Pint PASS. Build dari T055 dipakai ulang (tidak ada kelas CSS baru). Browser localhost8302 SQLite sintetis: awal combo kosong, ketik langsung Ratna menampilkan empty state (fixture itu tidak ditemukan), ketik Uji memilih pelanggan uji existing, Enter mengisi data; klik kembali lalu ketik7891 menghasilkan query7891 bukan append nama, hasil cocok; Escape tidak mengubah pilihan; tombol Pelanggan baru mengembalikan combo/identity kosong dan editable.390px scrollWidth390 sama innerWidth390, screenshot dilihat. Server dihentikan. Data produksi tidak disentuh.

QA independen disampaikan melalui follow-up native `/root/customer_history_qa`; hasil akhir tercatat di ledger. Perubahan aksi/history T055 yang belum dipush tetap dipertahankan.

Belum: MariaDB dan perangkat fisik; tidak push/deploy. Komponen shared kini siap pencarian baru setiap fokus, pilihan tetap dipertahankan bila pencarian dibatalkan.
