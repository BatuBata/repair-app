# S013 — Kontrak panel owner SaaS minimum

Ponytail full diterapkan. Scope yang disetujui hanya:

- login owner platform terpisah dan gagal tertutup bila credential server belum dikonfigurasi;
- daftar tenant tanpa data bisnis;
- membuat tenant beserta owner pertama melalui transaksi dan validasi yang sama dengan command existing;
- aktif/nonaktifkan tenant.

Tidak ada migrasi schema, dependency, hapus tenant, billing, paket, trial, impersonasi, analitik lintas tenant, custom domain, registrasi publik, API, atau role platform bertingkat. Deployment produksi tidak termasuk run ini.

Arah visual memakai `framed-sheet` seed `68281819`, mempertahankan palette dan tipografi existing. Pertanyaan layar: “Toko mana yang aktif dan bagaimana menambah client baru?” Aksi utama: “Tambah toko”.
