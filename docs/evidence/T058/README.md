# T058–T059 — Relasi pelanggan transaksi

Base `e8cd716d5c75f23920227a2e8ebdfca44c619ca6` + perubahan lokal. User18Nov2026 mengizinkan customer_id TTS dan penjualan, data lama tetap, tanpa overengineering, jangan push.

Satu migrasi aditif nullable FK di service_orders dan sales, indexed, nullOnDelete. Tidak ada backfill. Model belongsTo/customer dan hasMany balik, tenant validation pada model dan layanan. TTS form membuat/memilih Customer dalam transaksi lalu mengirim ID. CreateServiceOrder memvalidasi ID tenant dan membaca ulang pelanggan terkunci untuk snapshot. Penjualan mendapat search-select existing, reset pilihan, auto-register jika nama+HP lengkap; identitas sebagian/anonymous tetap valid dan ID null. Customer registration/stock/item/counter dalam transaksi penjualan yang sama. Snapshot nama/HP/alamat TTS dan nama/HP penjualan tetap independen.

History: dua query scoped tenant, grouping `(customer_id=id OR (customer_id IS NULL AND customer_phone=currentPhone))`; relasi eksplisit pelanggan lain tidak tercampur oleh fallback HP. Riwayat penjualan dengan snapshot item dan pagination salesPage terpisah dari TTS. Query lama tanpa ID tetap phone-based, tidak ada perubahan baris lama. Penghapusan pelanggan men-null-kan FK saja, semua field transaksi lainnya dan item tetap.

Developer: tes Customers/Sales/TTS44PASS415assert; CustomerRelations6PASS46assert; full243PASS1existingMySQLSKIP2576assert. Pint dan build PASS. PHPStan awal8error inferensi findOrFail mixed bisa Collection; diatasi whereKey()->firstOrFail(), hasil final0error. Tidak suppression atau dependency baru.

QA independen `/root/customer_relation_qa` read-only dengan Ponytail full/project-delivery: 53tes484assert PASS (Customers/Relations/TTS/Sales/TenancyMigration), edge2tes14assert PASS. Edge memastikan linked transaction tidak masuk pelanggan lain walaupun snapshot HP sama, partial identity sale tidak membuat pelanggan. Hash sumber tetap. Harness `/private/tmp/CustomerRelationIndependentQaTest.php`. Tidak ada blocker backend.

Browser coordinator pada SQLite sintetis terisolasi `/private/tmp/repair-customers-XXXXXX.sqlite`, server8302: migrasi aditif berhasil; buat linkedTTS fixture; UI pilih pelanggan QA Relasi di penjualan, field identitas otomatis/disabled; pilih barang, harga otomatis, simpanJL2611001; edit HP pelanggan8997→8996 via UI; halaman pelanggan tetap menunjukkan linkedTTS dan penjualan sedangkan detail penjualan tetap memakai HP8997. Riwayat dan form penjualan390px scrollWidth=innerWidth390. Screenshot dilihat; server dihentikan. Tidak ada data produksi disentuh.

Belum: MariaDB nyata, perangkat fisik, push/deploy; database operator existing tidak dimigrasikan. CI/deployment berikutnya harus menjalankan migrasi sebelum menggunakan kode ini. Data lama tanpa ID tetap mengikuti pencocokan HP, bukan histori ID stabil.
